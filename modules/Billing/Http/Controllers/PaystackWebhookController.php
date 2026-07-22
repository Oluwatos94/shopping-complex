<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Repositories\UserRepository;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            Log::warning('Paystack webhook rejected: invalid signature', [
                'ip' => $request->ip(),
            ]);

            return response('Invalid signature.', 401);
        }

        if ($request->json('event') !== 'charge.success') {
            return response('Event ignored.', 200);
        }

        $reference = (string) $request->json('data.reference', '');
        $vendorId = (int) $request->json('data.metadata.vendor_id', 0);

        if ($reference === '' || $vendorId === 0) {
            Log::warning('Paystack webhook missing reference or vendor metadata', [
                'reference' => $reference,
                'vendor_id' => $vendorId,
            ]);

            return response('Missing payment metadata.', 200);
        }

        try {
            $vendor = $this->userRepository->find($vendorId);
        } catch (ModelNotFoundException) {
            Log::warning('Paystack webhook vendor not found', ['vendor_id' => $vendorId]);

            return response('Vendor not found.', 200);
        }

        try {

            $this->subscriptionService->handleCallback(PaymentMethodEnum::PAYSTACK, $reference, $vendor);
        } catch (\RuntimeException $e) {
            Log::error('Paystack webhook processing failed', [
                'reference' => $reference,
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);

            return response('Not processed.', 200);
        }

        return response('OK', 200);
    }

    private function hasValidSignature(Request $request): bool
    {
        $signature = (string) $request->header('x-paystack-signature', '');
        if ($signature === '') {
            return false;
        }

        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature);
    }
}
