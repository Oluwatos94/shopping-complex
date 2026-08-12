<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;

class ReferralSeeder extends Seeder
{
    /** Referral counts handed to the first vendors, highest first. */
    private const REFERRALS_PER_VENDOR = [25, 12, 8, 3, 1];

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
            $emails = [];

            foreach (range(1, self::REFERRALS_PER_VENDOR[$position]) as $ignored) {
                $index++;
                $email = "referral-demo-{$index}@jiidaa.test";
                $emails[] = $email;

                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "Referred User {$index}",
                        'password' => Hash::make('password'),
                        'role' => 'customer',
                        'email_verified_at' => now(),
                    ]
                );
            }

            User::query()
                ->whereIn('email', $emails)
                ->whereNull('referred_by')
                ->update(['referred_by' => $vendor->id]);

            $this->command?->info(sprintf(
                '%s (%s) → %d referrals',
                $vendor->business_name ?? $vendor->name,
                $code,
                count($emails)
            ));
        }
    }
}
