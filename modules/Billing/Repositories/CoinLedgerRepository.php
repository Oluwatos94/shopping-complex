<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;

class CoinLedgerRepository
{
    public function replayBalance(int $vendorId): int
    {
        return (int) CoinLedgerEntry::where('vendor_id', $vendorId)->sum('amount');
    }

    /**
     * Lots that still hold coins, oldest first, paired with what is left in each.
     *
     * @return list<array{0: CoinLedgerEntry, 1: int}>
     */
    public function openLots(int $vendorId): array
    {
        $drawnDown = CoinLedgerEntry::query()
            ->selectRaw('lot_id, -SUM(amount) as drawn')
            ->where('vendor_id', $vendorId)
            ->whereNotNull('lot_id')
            ->groupBy('lot_id')
            ->pluck('drawn', 'lot_id');

        return CoinLedgerEntry::query()
            ->where('vendor_id', $vendorId)
            ->whereNull('lot_id')
            ->where('amount', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (CoinLedgerEntry $lot): array => [$lot, $lot->amount - (int) ($drawnDown[$lot->id] ?? 0)])
            ->filter(fn (array $lot): bool => $lot[1] > 0)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, CoinLedgerEntry>
     */
    public function debitsFor(int $vendorId, Model $reference): Collection
    {
        return $this->forReference($vendorId, $reference, CoinLedgerTypeEnum::DEBIT)
            ->with('lot')
            ->get();
    }

    public function hasCreditFor(int $vendorId, Model $reference): bool
    {
        return $this->forReference($vendorId, $reference, CoinLedgerTypeEnum::CREDIT)->exists();
    }

    /**
     * Streamed rather than materialised: the sweep must not hold every
     * affected vendor in memory.
     *
     * @return LazyCollection<int, int>
     */
    public function vendorsWithExpiredLots(CarbonInterface $asOf): LazyCollection
    {
        return CoinLedgerEntry::query()
            ->select('vendor_id')
            ->where('expires_at', '<=', $asOf)
            ->where('amount', '>', 0)
            ->distinct()
            ->orderBy('vendor_id')
            ->cursor()
            ->map(fn (CoinLedgerEntry $entry): int => $entry->vendor_id);
    }

    /**
     * Sold coins are revenue only once redeemed; until then they are a liability.
     *
     * @return array{coins_sold: int, coins_granted: int, coins_redeemed: int, coins_expired: int, outstanding_liability: int}
     */
    public function report(CarbonInterface $from, CarbonInterface $to): array
    {
        $sumOf = fn (array $types): int => (int) CoinLedgerEntry::whereIn('type', array_map(fn (CoinLedgerTypeEnum $type) => $type->value, $types))
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return [
            'coins_sold' => $sumOf([CoinLedgerTypeEnum::PURCHASE]),
            'coins_granted' => $sumOf([CoinLedgerTypeEnum::BONUS, CoinLedgerTypeEnum::PROMO, CoinLedgerTypeEnum::CREDIT]),
            'coins_redeemed' => -$sumOf([CoinLedgerTypeEnum::DEBIT]),
            'coins_expired' => -$sumOf([CoinLedgerTypeEnum::EXPIRY]),
            'outstanding_liability' => (int) CoinLedgerEntry::where('created_at', '<=', $to)->sum('amount'),
        ];
    }

    /**
     * @return Builder<CoinLedgerEntry>
     */
    private function forReference(int $vendorId, Model $reference, CoinLedgerTypeEnum $type)
    {
        return CoinLedgerEntry::query()
            ->where('vendor_id', $vendorId)
            ->where('type', $type->value)
            ->where('reference_type', $reference->getMorphClass())
            ->where('reference_id', $reference->getKey());
    }
}
