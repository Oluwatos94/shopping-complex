<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use ModulesShoppingComplex\Billing\Data\CoinPack;
use ModulesShoppingComplex\Billing\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Billing\Services\CoinPackRegistry;
use ModulesShoppingComplex\Billing\Services\CoinPurchaseService;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CoinPurchaseController extends Controller
{
    public function __construct(
        private readonly CoinPackRegistry $packs,
        private readonly CoinPurchaseService $purchases,
        private readonly CoinWalletService $wallet,
    ) {}

    /**
     * The purchasable packs and the vendor's current balance — the data source
     * for the coin purchase UI.
     */
    public function packs(): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        return response()->json([
            'balance' => $this->wallet->balance(Auth::user()),
            'packs' => array_map($this->presentPack(...), array_values($this->packs->all())),
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
            return redirect()->route('vendor.dashboard')->with('error', 'Invalid payment reference.');
        }

        try {
            $purchase = $this->purchases->fulfill($reference, Auth::user());
        } catch (\RuntimeException $e) {
            return redirect()->route('vendor.dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.dashboard')->with(
            'success',
            sprintf('%s coins added to your wallet.', number_format($purchase->totalCoins())),
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
