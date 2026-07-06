<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Stellar;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\StellarWallet;
use ModulesShoppingComplex\Services\Payments\Stellar\Contracts\RecurringCharger;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;

final class SorobanCharger implements RecurringCharger
{
    private const ASSET_DECIMALS = 7;

    private const MAX_STATUS_POLLS = 30;

    public function __construct(
        private readonly SorobanServer $soroban,
        private readonly Network $network,
        private readonly string $platformWalletPublic,
        private readonly string $ngncSac,
    ) {}

    public function charge(StellarWallet $from, float $amount): string
    {
        try {
            $vendorKeyPair = KeyPair::fromSeed($from->secret);

            $function = new InvokeContractHostFunction($this->ngncSac, 'transfer', [
                Address::fromAccountId($from->public_key)->toXdrSCVal(),
                Address::fromAccountId($this->platformWalletPublic)->toXdrSCVal(),
                XdrSCVal::forI128Parts(0, $this->toStroops($amount)),
            ]);
            $operation = (new InvokeHostFunctionOperationBuilder($function))->build();

            $account = $this->soroban->getAccount($from->public_key);
            if ($account === null) {
                throw new ChargeFailedException('Vendor wallet account was not found on-chain.');
            }

            $transaction = (new TransactionBuilder($account))->addOperation($operation)->build();

            // Simulate to obtain the footprint, resource fee, and Soroban auth entries.
            $simulation = $this->soroban->simulateTransaction(new SimulateTransactionRequest($transaction));
            if ($simulation->getResultError() !== null) {
                throw new ChargeFailedException('Charge simulation failed: '.$simulation->getResultError());
            }

            $transaction->setSorobanTransactionData($simulation->getTransactionData());
            $transaction->addResourceFee($simulation->getMinResourceFee() ?? 0);
            $transaction->setSorobanAuth($simulation->getSorobanAuth());

            $transaction->sign($vendorKeyPair, $this->network);

            $send = $this->soroban->sendTransaction($transaction);
            if ($send->hash === null || $send->status === 'ERROR') {
                throw new ChargeFailedException('The network rejected the charge.');
            }

            $this->awaitSettlement($send->hash);

            return $send->hash;
        } catch (ChargeFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('MPP charge failed', ['from' => $from->public_key, 'error' => $e->getMessage()]);

            throw new ChargeFailedException('Could not settle the subscription charge.');
        }
    }

    /**
     * Poll the transaction until it settles, translating a failed/timed-out result into a
     * {@see ChargeFailedException} so the renewal job lapses the vendor rather than extending.
     */
    private function awaitSettlement(string $hash): void
    {
        for ($poll = 0; $poll < self::MAX_STATUS_POLLS; $poll++) {
            $response = $this->soroban->getTransaction($hash);

            if ($response->status === GetTransactionResponse::STATUS_SUCCESS) {
                return;
            }

            if ($response->status === GetTransactionResponse::STATUS_FAILED) {
                throw new ChargeFailedException('The charge failed on-chain.');
            }

            usleep(1_000_000);
        }

        throw new ChargeFailedException('Timed out waiting for the charge to settle.');
    }

    /** Convert a Naira/NGNC amount to the SAC's integer stroop representation (7 decimals). */
    private function toStroops(float $amount): int
    {
        return (int) round($amount * (10 ** self::ASSET_DECIMALS));
    }
}
