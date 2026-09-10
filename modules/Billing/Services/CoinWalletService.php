<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Exceptions\InsufficientCoinsException;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Models\CoinWallet;
use ModulesShoppingComplex\Billing\Repositories\CoinLedgerRepository;
use ModulesShoppingComplex\Identity\Models\User;
use RuntimeException;

final class CoinWalletService
{
    public function __construct(
        private readonly CoinLedgerRepository $ledger,
    ) {}

    public function walletFor(User $vendor): CoinWallet
    {
        return CoinWallet::firstOrCreate(['vendor_id' => $vendor->id], ['balance' => 0]);
    }

    public function balance(User $vendor): int
    {
        return (int) CoinWallet::where('vendor_id', $vendor->id)->value('balance');
    }

    public function credit(
        User $vendor,
        CoinLedgerTypeEnum $type,
        int $coins,
        ?Model $reference = null,
        ?CarbonInterface $expiresAt = null,
    ): CoinLedgerEntry {
        if (! $type->addsCoins()) {
            throw new InvalidArgumentException("{$type->value} does not add coins.");
        }

        $this->assertPositive($coins);

        return DB::transaction(fn (): CoinLedgerEntry => $this->write(
            $this->lockedWallet($vendor),
            $type,
            $coins,
            $reference,
            $expiresAt ?? $this->defaultExpiry(),
        ));
    }

    /**
     * Draws coins oldest lot first, one entry per lot touched.
     *
     * @return Collection<int, CoinLedgerEntry>
     *
     * @throws InsufficientCoinsException
     */
    public function debit(User $vendor, int $coins, ?Model $reference = null): Collection
    {
        $this->assertPositive($coins);

        return DB::transaction(function () use ($vendor, $coins, $reference): Collection {
            $wallet = $this->lockedWallet($vendor);

            $spendable = $this->expireDue($wallet, now());

            if ($wallet->balance < $coins) {
                throw new InsufficientCoinsException($wallet->balance, $coins);
            }

            /** @var Collection<int, CoinLedgerEntry> $entries */
            $entries = new Collection;
            $outstanding = $coins;

            foreach ($spendable as [$lot, $remaining]) {
                if ($outstanding < 1) {
                    break;
                }

                $take = min($remaining, $outstanding);
                $entries->push($this->write($wallet, CoinLedgerTypeEnum::DEBIT, -$take, $reference, null, $lot));
                $outstanding -= $take;
            }

            if ($outstanding > 0) {
                throw new RuntimeException("Coin wallet for vendor {$vendor->id} holds more than its lots.");
            }

            return $entries;
        });
    }

    /**
     * Returns what a reference was charged, on the coins' original deadline —
     * a refund must not silently extend the life of the coins it gives back.
     * Idempotent: a reference is only ever refunded once.
     *
     * @return Collection<int, CoinLedgerEntry>
     */
    public function refund(User $vendor, Model $reference): Collection
    {
        return DB::transaction(function () use ($vendor, $reference): Collection {
            $wallet = $this->lockedWallet($vendor);

            if ($this->ledger->hasCreditFor($vendor->id, $reference)) {
                return new Collection;
            }

            return $this->ledger->debitsFor($vendor->id, $reference)->map(
                fn (CoinLedgerEntry $debit): CoinLedgerEntry => $this->write(
                    $wallet,
                    CoinLedgerTypeEnum::CREDIT,
                    -$debit->amount,
                    $reference,
                    $debit->lot->expires_at ?? $this->defaultExpiry(),
                )
            );
        });
    }

    public function expire(User $vendor, ?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();

        return DB::transaction(function () use ($vendor, $asOf): int {
            $wallet = $this->lockedWallet($vendor);
            $before = $wallet->balance;

            $this->expireDue($wallet, $asOf);

            return $before - $wallet->balance;
        });
    }

    /**
     * Retires every lot past $asOf and hands back the ones still spendable.
     * Caller must already hold the wallet lock.
     *
     * @return list<array{0: CoinLedgerEntry, 1: int}>
     */
    private function expireDue(CoinWallet $wallet, CarbonInterface $asOf): array
    {
        $spendable = [];

        foreach ($this->ledger->openLots($wallet->vendor_id) as [$lot, $remaining]) {
            if ($lot->expires_at !== null && $lot->expires_at->lte($asOf)) {
                $this->write($wallet, CoinLedgerTypeEnum::EXPIRY, -$remaining, null, null, $lot);

                continue;
            }

            $spendable[] = [$lot, $remaining];
        }

        return $spendable;
    }

    private function write(
        CoinWallet $wallet,
        CoinLedgerTypeEnum $type,
        int $amount,
        ?Model $reference,
        ?CarbonInterface $expiresAt = null,
        ?CoinLedgerEntry $lot = null,
    ): CoinLedgerEntry {
        $wallet->balance += $amount;
        $wallet->save();

        return CoinLedgerEntry::create([
            'vendor_id' => $wallet->vendor_id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'lot_id' => $lot?->id,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);
    }

    private function lockedWallet(User $vendor): CoinWallet
    {
        if (($wallet = $this->lockRow($vendor->id)) !== null) {
            return $wallet;
        }

        $this->walletFor($vendor);

        return CoinWallet::where('vendor_id', $vendor->id)->lockForUpdate()->firstOrFail();
    }

    private function lockRow(int $vendorId): ?CoinWallet
    {
        return CoinWallet::where('vendor_id', $vendorId)->lockForUpdate()->first();
    }

    private function defaultExpiry(): CarbonInterface
    {
        return now()->addMonths((int) config('billing.coins.expiry_months'));
    }

    private function assertPositive(int $coins): void
    {
        if ($coins < 1) {
            throw new InvalidArgumentException('Coin amounts must be positive.');
        }
    }
}
