<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Stellar;

use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24InteractiveResponse;
use Soneso\StellarSDK\SEP\Interactive\SEP24Transaction;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

final class AnchorClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $sep10SigningKey,
        private readonly string $homeDomain,
        private readonly Network $network,
        private readonly string $ngncAssetCode,
        private readonly string $ngncIssuer,
    ) {}

    /**
     * Authenticate a wallet via SEP-10 and return a JWT. The signer is whichever wallet
     * should own the deposit — the Jiidaa platform wallet for Anchor, a vendor wallet for MPP.
     *
     * @throws \RuntimeException
     */
    public function authenticate(StellarSigner $signer): string
    {
        try {
            $webAuth = new WebAuth(
                rtrim($this->baseUrl, '/').'/auth',
                $this->sep10SigningKey,
                $this->homeDomain,
                $this->network,
            );

            return $webAuth->jwtToken(
                $signer->publicKey,
                [KeyPair::fromSeed($signer->secret)],
            );
        } catch (\Throwable $e) {
            Log::error('SEP-10 authentication failed', ['account' => $signer->publicKey, 'error' => $e->getMessage()]);

            throw new AnchorUnavailableException('Could not authenticate with the deposit provider.');
        }
    }

    /**
     * Start an NGNC interactive deposit that settles into $signer's account, returning the
     * anchor's interactive URL + id. An $amount pre-fills the deposit form with the plan price.
     *
     * @throws \RuntimeException
     */
    public function startNgncDeposit(StellarSigner $signer, string $jwt, ?float $amount = null): SEP24InteractiveResponse
    {
        $request = new SEP24DepositRequest;
        $request->jwt = $jwt;
        $request->assetCode = $this->ngncAssetCode;
        $request->assetIssuer = $this->ngncIssuer;
        $request->account = $signer->publicKey;
        $request->amount = $amount;

        try {
            return $this->interactive()->deposit($request);
        } catch (\Throwable $e) {
            Log::error('SEP-24 deposit init failed', ['account' => $signer->publicKey, 'error' => $e->getMessage()]);

            throw new AnchorUnavailableException('Could not start the deposit. Please try again.');
        }
    }

    /**
     * Fetch the current state of a SEP-24 transaction by its anchor id.
     *
     * @throws \RuntimeException
     */
    public function getTransaction(string $jwt, string $sep24Id): SEP24Transaction
    {
        $request = new SEP24TransactionRequest;
        $request->jwt = $jwt;
        $request->id = $sep24Id;

        try {
            return $this->interactive()->transaction($request)->transaction;
        } catch (\Throwable $e) {
            Log::error('SEP-24 transaction lookup failed', ['sep24_id' => $sep24Id, 'error' => $e->getMessage()]);

            throw new AnchorUnavailableException('Could not check the deposit status.');
        }
    }

    private function interactive(): InteractiveService
    {
        return new InteractiveService(rtrim($this->baseUrl, '/').'/sep24');
    }
}
