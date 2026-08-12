<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;

class ReferralSeeder extends Seeder
{
    /**
     * Referrals handed to the first vendors, busiest first. The pending column
     * seeds unverified signups, which are attributed but deliberately excluded
     * from the vendor's count.
     */
    private const REFERRALS_PER_VENDOR = [
        ['verified' => 25, 'pending' => 4],
        ['verified' => 12, 'pending' => 2],
        ['verified' => 8, 'pending' => 0],
        ['verified' => 3, 'pending' => 1],
        ['verified' => 1, 'pending' => 0],
    ];

    public function run(): void
    {
        $referralService = app(ReferralService::class);

        $vendors = User::query()
            ->where('role', 'vendor')
            ->orderBy('id')
            ->take(count(self::REFERRALS_PER_VENDOR))
            ->get();

        if ($vendors->isEmpty()) {
            $this->command?->warn('No vendors found — seed vendors first.');

            return;
        }

        $index = 0;

        foreach ($vendors as $position => $vendor) {
            $code = $referralService->codeFor($vendor);
            $plan = self::REFERRALS_PER_VENDOR[$position];
            $emails = [];

            foreach (['verified', 'pending'] as $state) {
                for ($i = 0; $i < $plan[$state]; $i++) {
                    $index++;
                    $email = "referral-demo-{$index}@jiidaa.test";
                    $emails[] = $email;

                    $user = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => fake()->name(),
                            'password' => Hash::make('password'),
                            'role' => 'customer',
                            'email_verified_at' => $state === 'verified' ? now() : null,
                        ]
                    );

                    // Staggered so the newest-first breakdown has something to order.
                    if ($user->wasRecentlyCreated) {
                        $joinedAt = now()->subDays(random_int(0, 45))->subHours(random_int(0, 23));
                        $user->forceFill(['created_at' => $joinedAt, 'updated_at' => $joinedAt])->save();
                    }
                }
            }

            User::query()
                ->whereIn('email', $emails)
                ->whereNull('referred_by')
                ->update(['referred_by' => $vendor->id]);

            $this->command?->info(sprintf(
                '%s (%s) → %d counted, %d awaiting verification',
                $vendor->business_name ?? $vendor->name,
                $code,
                $plan['verified'],
                $plan['pending']
            ));
        }
    }
}
