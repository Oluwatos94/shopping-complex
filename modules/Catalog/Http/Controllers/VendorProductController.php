<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Catalog\Http\Requests\UploadProductRequest;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Services\MediaService;

class VendorProductController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function uploadProduct(UploadProductRequest $request): RedirectResponse
    {
        // TODO: Re-enable after admin panel is built
        // $this->authorize('create', Product::class);

        $user = Auth::user();

        $subscription = $this->subscriptionService->getVendorSubscription($user->id);
        if ($subscription !== null) {
            $activeCount = $user->products()->where('is_active', true)->count();
            if ($activeCount >= $subscription->plan->product_limit) {
                return redirect()->back()->withErrors([
                    'message' => "You have reached the product limit for your {$subscription->plan->name} plan. Upgrade to add more products.",
                ]);
            }
        }

        $product = DB::transaction(function () use ($user, $request) {
            $product = Product::create([
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')).'-'.uniqid(),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'vendor_id' => $user->id,
                'category_id' => $user->category_id,
                'stock' => 0,
                'is_active' => true,
                'pay_on_delivery' => $request->boolean('pay_on_delivery'),
                'is_returnable' => $request->boolean('is_returnable'),
                'tags' => array_values(array_filter(array_map('strtolower', $request->input('tags', [])))),
            ]);

            if ($request->hasFile('video')) {
                $this->mediaService->uploadVideo(
                    file: $request->file('video'),
                    modelType: Product::class,
                    modelId: $product->id,
                    type: 'product_video'
                );
            } elseif ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $this->mediaService->uploadImage(
                        file: $imageFile,
                        modelType: Product::class,
                        modelId: $product->id,
                        type: 'product_image'
                    );
                }
            }

            return $product;
        });

        return redirect()->back()->with('success', 'Product uploaded successfully.');
    }

    public function updateProduct(UploadProductRequest $request, int $productId): RedirectResponse
    {
        $user = Auth::user();
        $product = Product::where('id', $productId)->where('vendor_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($request, $product) {
            $product->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'pay_on_delivery' => $request->boolean('pay_on_delivery'),
                'is_returnable' => $request->boolean('is_returnable'),
                'tags' => array_values(array_filter(array_map('strtolower', $request->input('tags', [])))),
            ]);

            if ($request->hasFile('video')) {
                $this->mediaService->deleteMediaForModel(Product::class, $product->id);
                $this->mediaService->uploadVideo(
                    file: $request->file('video'),
                    modelType: Product::class,
                    modelId: $product->id,
                    type: 'product_video'
                );
            } elseif ($request->hasFile('images')) {
                $this->mediaService->deleteMediaForModel(Product::class, $product->id);
                foreach ($request->file('images') as $imageFile) {
                    $this->mediaService->uploadImage(
                        file: $imageFile,
                        modelType: Product::class,
                        modelId: $product->id,
                        type: 'product_image'
                    );
                }
            }
        });

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function deleteProduct(int $productId): RedirectResponse
    {
        $user = Auth::user();
        $product = Product::where('id', $productId)->where('vendor_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($product) {
            $this->mediaService->deleteMediaForModel(Product::class, $product->id);
            $product->delete();
        });

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
}
