import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import FlashBanner from '@/components/FlashBanner';
import CoinConfirmModal from '@/components/CoinConfirmModal';
import { CoinPack, CoinPacksProps } from '@/types';

interface SharedProps {
    flash: { success?: string; error?: string };
    [key: string]: unknown;
}

const naira = (n: number) => `₦${n.toLocaleString('en-US')}`;
const coins = (n: number) => n.toLocaleString('en-US');

export default function Coins({ vendor, balance, lead_rate, category_name, free_tier, can_purchase, packs }: CoinPacksProps) {
    const { flash } = usePage<SharedProps>().props;
    const [selectedPack, setSelectedPack] = useState<CoinPack | null>(null);
    const [claiming, setClaiming] = useState(false);

    const claimFree = () => {
        setClaiming(true);
        router.post('/vendor/coins/free', {}, { preserveScroll: true, onFinish: () => setClaiming(false) });
    };

    return (
        <>
            <Head title="Buy Coins" />
            <VendorSidebar />

            {selectedPack && (
                <CoinConfirmModal
                    pack={selectedPack}
                    vendor={vendor}
                    onClose={() => setSelectedPack(null)}
                    onDone={() => {
                        setSelectedPack(null);
                        router.reload();
                    }}
                />
            )}

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
                    <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-extrabold tracking-tight text-brand-ink">Buy coins</h1>
                            <p className="mt-1 text-sm text-brand-muted">
                                Coins pay for leads at {category_name ? `the ${category_name} rate` : 'your rate'} of {coins(lead_rate)} per lead.
                            </p>
                        </div>
                        <Link href="/vendor/wallet" className="text-sm font-semibold text-brand-green-dark hover:underline">
                            Back to wallet
                        </Link>
                    </div>

                    {flash?.success && <FlashBanner type="success" message={flash.success} />}
                    {flash?.error && <FlashBanner type="error" message={flash.error} />}

                    <div className="mb-6 rounded-xl border border-brand-line bg-white px-5 py-4">
                        <span className="text-sm text-brand-muted">Current balance</span>{' '}
                        <span className="text-lg font-bold text-brand-ink">{coins(balance)} coins</span>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {free_tier.available && (
                            <div className="flex flex-col rounded-2xl border-2 border-brand-green/40 bg-white p-6">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-base font-bold text-brand-ink">Free tier</h2>
                                    <span className="rounded-full bg-brand-green/10 px-2.5 py-1 text-xs font-bold text-brand-green-dark">
                                        Free
                                    </span>
                                </div>

                                <p className="mt-4 text-3xl font-extrabold text-brand-ink">{coins(free_tier.coins)}</p>
                                <p className="text-sm text-brand-muted">coins</p>

                                <p className="mt-4 text-sm font-medium text-brand-ink">
                                    ≈ {coins(Math.floor(free_tier.coins / Math.max(1, lead_rate)))} leads at your rate
                                </p>

                                <div className="mt-6 flex flex-1 flex-col justify-end">
                                    <p className="mb-3 text-2xl font-extrabold text-brand-ink">₦0</p>
                                    <button
                                        type="button"
                                        onClick={claimFree}
                                        disabled={claiming}
                                        className="flex h-12 items-center justify-center rounded-xl bg-brand-green text-sm font-bold text-white transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {claiming ? 'Claiming…' : 'Claim free coins'}
                                    </button>
                                </div>
                            </div>
                        )}

                        {packs.map((pack) => {
                            const bonusPct = pack.coins > 0 ? Math.round((pack.bonus_coins / pack.coins) * 100) : 0;

                            return (
                                <div key={pack.key} className="flex flex-col rounded-2xl border border-brand-line bg-white p-6">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-base font-bold text-brand-ink">{pack.name}</h2>
                                        {pack.bonus_coins > 0 && (
                                            <span className="rounded-full bg-brand-green/10 px-2.5 py-1 text-xs font-bold text-brand-green-dark">
                                                +{bonusPct}% bonus
                                            </span>
                                        )}
                                    </div>

                                    <p className="mt-4 text-3xl font-extrabold text-brand-ink">{coins(pack.total_coins)}</p>
                                    <p className="text-sm text-brand-muted">coins</p>

                                    {pack.bonus_coins > 0 && (
                                        <p className="mt-1 text-xs text-brand-muted">
                                            {coins(pack.coins)} + {coins(pack.bonus_coins)} bonus
                                        </p>
                                    )}

                                    <p className="mt-4 text-sm font-medium text-brand-ink">
                                        ≈ {coins(pack.leads_at_rate)} leads at your rate
                                    </p>

                                    <div className="mt-6 flex flex-1 flex-col justify-end">
                                        <p className="mb-3 text-2xl font-extrabold text-brand-ink">{naira(pack.price)}</p>
                                        <button
                                            type="button"
                                            onClick={() => setSelectedPack(pack)}
                                            disabled={!can_purchase}
                                            className="flex h-12 items-center justify-center rounded-xl bg-brand-green text-sm font-bold text-white transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            Buy pack
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {!can_purchase && (
                        <p className="mt-6 text-center text-xs font-medium text-brand-ink">
                            You still have enough coins — top up again once your balance runs low.
                        </p>
                    )}

                    <p className="mt-3 text-center text-xs text-brand-muted">
                        Payments settle securely on the Stellar network in Naira. Coins are added the moment your payment is confirmed.
                    </p>
                </div>
            </main>
        </>
    );
}
