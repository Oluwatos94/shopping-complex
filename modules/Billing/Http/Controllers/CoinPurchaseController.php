<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use ModulesShoppingComplex\Billing\Data\CoinPack;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Billing\Services\CoinPackRegistry;
use ModulesShoppingComplex\Billing\Services\CoinPurchaseService;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\LeadPricingService;
use ModulesShoppingComplex\Catalog\Models\Category;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CoinPurchaseController extends Controller
{
    public function __construct(
        private readonly CoinPackRegistry $packs,
        private readonly CoinPurchaseService $purchases,
        private readonly CoinWalletService $wallet,
        private readonly LeadPricingService $pricing,
    ) {}

    public function wallet(): InertiaResponse|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $vendor = Auth::user();
        $balance = $this->wallet->balance($vendor);
        $rate = $this->pricing->costFor($vendor);

        $category = $vendor->category_id === null ? null : Category::find($vendor->category_id);

        $lowThreshold = (int) config('billing.leads.low_balance_leads', 3) * (int) config('billing.leads.default_cost', 5);

        $ledger = CoinLedgerEntry::where('vendor_id', $vendor->id)
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (CoinLedgerEntry $entry): array => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'amount' => $entry->amount,
                'balance_after' => $entry->balance_after,
                'date' => $entry->created_at->toIso8601String(),
            ]);

        $spent = CoinLedgerEntry::where('vendor_id', $vendor->id)
            ->where('type', CoinLedgerTypeEnum::DEBIT)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (CoinLedgerEntry $entry): string => $entry->created_at->toDateString())
            ->map(fn ($group, string $date): array => [
                'date' => $date,
                'count' => (int) $group->sum(fn (CoinLedgerEntry $entry): int => -$entry->amount),
            ])
            ->values();

        return Inertia::render('Vendor/Wallet', [
            'vendor' => ['business_name' => $vendor->business_name ?? $vendor->name],
            'balance' => $balance,
            'lead_rate' => $rate,
            'leads_affordable' => intdiv($balance, max(1, $rate)),
            'category' => $category === null ? null : ['name' => $category->name, 'cost' => (int) $category->lead_coin_cost],
            'low_balance' => $balance < $lowThreshold,
            'low_balance_threshold' => $lowThreshold,
            'unbilled_out_of_coins' => BillableLead::where('vendor_id', $vendor->id)
                ->where('unbilled_reason', LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE)
                ->count(),
            'spent_series' => $spent,
            'ledger' => $ledger,
            'top_up_link' => route('vendor.coins.packs'),
        ]);
    }

    /**
     * The purchasable packs and the vendor's current balance — the data source
     * for the coin purchase UI.
     */
    public function packs(): InertiaResponse|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $vendor = Auth::user();
        $rate = $this->pricing->costFor($vendor);
        $category = $vendor->category_id === null ? null : Category::find($vendor->category_id);

        return Inertia::render('Vendor/Coins', [
            'balance' => $this->wallet->balance($vendor),
            'lead_rate' => $rate,
            'category_name' => $category?->name,
            'packs' => array_map(
                fn (CoinPack $pack): array => $this->presentPack($pack) + ['leads_at_rate' => intdiv($pack->totalCoins(), max(1, $rate))],
                array_values($this->packs->all()),
            ),
        ]);
    }

    public function checkout(Request $request, string $pack): RedirectResponse|SymfonyResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $coinPack = $this->packs->find($pack);

        if ($coinPack === null) {
            return back()->with('error', 'That coin pack is not available.');
        }

        try {
            $session = $this->purchases->startCheckout(
                Auth::user(),
                $coinPack,
                route('vendor.coins.callback'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($session->type === CheckoutTypeEnum::REDIRECT) {
            return Inertia::location($session->url);
        }

        return back()->with('error', 'Unable to start the payment.');
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()->route('vendor.coins.packs')->with('error', 'Invalid payment reference.');
        }

        try {
            $purchase = $this->purchases->fulfill($reference, Auth::user());
        } catch (\RuntimeException $e) {
            return redirect()->route('vendor.coins.packs')->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.coins.packs')->with(
            'success',
            sprintf('%s coins added to your wallet. Your balance is up to date below.', number_format($purchase->totalCoins())),
        );
    }

    /**
     * @return array{key: string, name: string, price: int, coins: int, bonus_coins: int, total_coins: int}
     */
    private function presentPack(CoinPack $pack): array
    {
        return [
            'key' => $pack->key,
            'name' => $pack->name,
            'price' => $pack->price,
            'coins' => $pack->coins,
            'bonus_coins' => $pack->bonusCoins,
            'total_coins' => $pack->totalCoins(),
        ];
    }

    private function denyNonVendor(): ?RedirectResponse
    {
        if (Auth::user()->role !== 'vendor') {
            return redirect()->route('home')->with('error', 'Only vendors can buy coins.');
        }

        return null;
    }
}
