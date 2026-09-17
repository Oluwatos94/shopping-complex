<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Billing\Events\CategoryLeadCostChanged;
use ModulesShoppingComplex\Billing\Http\Requests\UpdateCategoryLeadCostRequest;
use ModulesShoppingComplex\Catalog\Models\Category;

class CoinPricingController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount([
                'vendors as vendors_on_tier_count' => fn ($q) => $q->whereNull('lead_coin_cost_override'),
                'vendors as vendors_with_override_count' => fn ($q) => $q->whereNotNull('lead_coin_cost_override'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'lead_coin_cost'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'lead_coin_cost' => $c->lead_coin_cost,
                'vendors_on_tier' => (int) $c->getAttribute('vendors_on_tier_count'),
                'vendors_with_override' => (int) $c->getAttribute('vendors_with_override_count'),
            ]);

        return Inertia::render('Admin/CoinPricing', [
            'categories' => $categories,
            'defaultCost' => (int) config('billing.leads.default_cost', 5),
        ]);
    }

    public function updateCategory(UpdateCategoryLeadCostRequest $request, Category $category): RedirectResponse
    {
        $previous = (int) $category->lead_coin_cost;
        $new = (int) $request->validated('lead_coin_cost');

        if ($new === $previous) {
            return back()->with('info', "{$category->name} is already at {$new} coins.");
        }

        $category->update(['lead_coin_cost' => $new]);

        CategoryLeadCostChanged::dispatch($category, $previous, $new);

        Log::info('Admin changed category lead cost', [
            'category_id' => $category->id,
            'previous' => $previous,
            'new' => $new,
            'changed_by' => Auth::id(),
        ]);

        return back()->with('success', "{$category->name} lead cost updated to {$new} coins. Affected vendors are being notified.");
    }
}
