<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\LeadPricingService;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;

class CoinWalletSeeder extends Seeder
{
    private const VENDOR_CATEGORIES = [
        'fashion@example.com' => 'fashion-clothing',
        'tech@example.com' => 'electronics-repairs',
        'homedecor@example.com' => 'furniture-appliances',
        'beauty@example.com' => 'health-beauty',
        'sports@example.com' => 'outdoors-entertainment',
    ];

    private const SEARCHES = ['phone screen repair', 'laptop charger', 'bluetooth speaker', 'ankara gown', 'office chair', 'birthday cake'];

    private const AREAS = ['Yaba, Lagos', 'Ikeja, Lagos', 'Wuse, Abuja', 'Surulere, Lagos', 'Port Harcourt', 'Lekki, Lagos'];

    public function run(): void
    {
        $this->backfillCategories();

        $this->seedActiveWallet('tech@example.com');
        $this->seedDrainedWallet('fashion@example.com');
    }

    private function backfillCategories(): void
    {
        foreach (self::VENDOR_CATEGORIES as $email => $slug) {
            $vendor = $this->vendor($email);
            $categoryId = Category::where('slug', $slug)->value('id');

            if ($vendor !== null && $vendor->category_id === null && $categoryId !== null) {
                $vendor->update(['category_id' => $categoryId]);
            }
        }
    }

    private function seedActiveWallet(string $email): void
    {
        $vendor = $this->vendor($email);

        if ($vendor === null || CoinLedgerEntry::where('vendor_id', $vendor->id)->exists()) {
            return;
        }

        $wallet = app(CoinWalletService::class);
        $rate = app(LeadPricingService::class)->costFor($vendor);
        $today = Carbon::now();
        $seq = 0;

        Carbon::setTestNow($today->copy()->subDays(26));
        $wallet->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 400);
        $wallet->credit($vendor, CoinLedgerTypeEnum::BONUS, 40);

        for ($daysAgo = 20; $daysAgo >= 0; $daysAgo -= 2) {
            $this->chargeLead($wallet, $vendor, $rate, $today->copy()->subDays($daysAgo), $seq++);
        }

        Carbon::setTestNow();

        $refundable = BillableLead::where('vendor_id', $vendor->id)
            ->where('state', BillableLeadStateEnum::CHARGED)
            ->latest('id')
            ->first();

        if ($refundable !== null) {
            $wallet->refund($vendor, $refundable);
            $refundable->update(['credit_reason' => LeadCreditReasonEnum::DUPLICATE]);
        }
    }

    private function seedDrainedWallet(string $email): void
    {
        $vendor = $this->vendor($email);

        if ($vendor === null || CoinLedgerEntry::where('vendor_id', $vendor->id)->exists()) {
            return;
        }

        $wallet = app(CoinWalletService::class);
        $rate = app(LeadPricingService::class)->costFor($vendor);
        $today = Carbon::now();
        $seq = 0;

        Carbon::setTestNow($today->copy()->subDays(12));
        $wallet->credit($vendor, CoinLedgerTypeEnum::PURCHASE, $rate * 4);

        foreach ([9, 7, 5, 3] as $daysAgo) {
            $this->chargeLead($wallet, $vendor, $rate, $today->copy()->subDays($daysAgo), $seq++);
        }

        foreach ([1, 0] as $daysAgo) {
            Carbon::setTestNow($today->copy()->subDays($daysAgo));
            BillableLead::create([
                'vendor_id' => $vendor->id,
                'buyer_identity' => 'seed_'.$vendor->id.'_miss_'.$seq++,
                'channel' => ViewSourceEnum::WHATSAPP,
                'coins_charged' => 0,
                'state' => BillableLeadStateEnum::UNBILLED,
                'unbilled_reason' => LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE,
                'buyer_search' => self::SEARCHES[$seq % count(self::SEARCHES)],
                'buyer_area' => self::AREAS[$seq % count(self::AREAS)],
                'window_start' => Carbon::now(),
            ]);
        }

        Carbon::setTestNow();
    }

    private function chargeLead(CoinWalletService $wallet, User $vendor, int $rate, Carbon $day, int $seq): void
    {
        Carbon::setTestNow($day);

        $lead = BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => 'seed_'.$vendor->id.'_'.$seq,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => $rate,
            'state' => BillableLeadStateEnum::CHARGED,
            'delivered_number' => '2348030000000',
            'buyer_search' => self::SEARCHES[$seq % count(self::SEARCHES)],
            'buyer_area' => self::AREAS[$seq % count(self::AREAS)],
            'window_start' => $day,
        ]);

        $wallet->debit($vendor, $rate, $lead);
    }

    private function vendor(string $email): ?User
    {
        return User::where('email', $email)->where('role', 'vendor')->first();
    }
}
