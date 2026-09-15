import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import { Category } from '@/types';

interface CategoryLeadPricing extends Pick<Category, 'id' | 'name' | 'slug'> {
    lead_coin_cost: number;
    vendors_on_tier: number;
    vendors_with_override: number;
}

interface Props {
    categories: CategoryLeadPricing[];
    defaultCost: number;
}

export default function CoinPricing({ categories, defaultCost }: Props) {
    const [draft, setDraft] = useState<Record<number, number>>(
        Object.fromEntries(categories.map((c) => [c.id, c.lead_coin_cost])),
    );
    const [savingId, setSavingId] = useState<number | null>(null);

    const save = (category: CategoryLeadPricing) => {
        const value = draft[category.id];

        if (value === category.lead_coin_cost) return;

        router.patch(
            `/admin/coin-pricing/categories/${category.id}`,
            { lead_coin_cost: value },
            {
                preserveScroll: true,
                onStart: () => setSavingId(category.id),
                onFinish: () => setSavingId(null),
            },
        );
    };

    return (
        <>
            <Head title="Lead Pricing — Admin" />
            <AdminLayout>
                <div className="mb-8">
                    <h2 className="text-3xl font-extrabold tracking-tight text-brand-ink">Lead Pricing</h2>
                    <p className="mt-1 text-sm text-brand-muted">
                        Coins deducted from a vendor each time a new customer contacts them, by category.
                        Changing a rate notifies the affected vendors. Vendors with a negotiated override
                        are not affected. Vendors with no category pay the default of {defaultCost} coins.
                    </p>
                </div>

                <div className="overflow-hidden rounded-2xl border border-brand-line bg-white">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b border-brand-line bg-brand-surface text-xs uppercase tracking-wide text-brand-muted">
                            <tr>
                                <th className="px-5 py-3 font-semibold">Category</th>
                                <th className="px-5 py-3 font-semibold">On this tier</th>
                                <th className="px-5 py-3 font-semibold">On override</th>
                                <th className="px-5 py-3 font-semibold">Cost (coins)</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-brand-line">
                            {categories.map((category) => {
                                const value = draft[category.id] ?? category.lead_coin_cost;
                                const changed = value !== category.lead_coin_cost;

                                return (
                                    <tr key={category.id} className="hover:bg-brand-surface/50">
                                        <td className="px-5 py-3 font-medium text-brand-ink">{category.name}</td>
                                        <td className="px-5 py-3 text-brand-muted">{category.vendors_on_tier}</td>
                                        <td className="px-5 py-3 text-brand-muted">{category.vendors_with_override}</td>
                                        <td className="px-5 py-3">
                                            <input
                                                type="number"
                                                min={1}
                                                max={1000}
                                                value={value}
                                                onChange={(e) =>
                                                    setDraft((d) => ({ ...d, [category.id]: Number(e.target.value) }))
                                                }
                                                className="w-24 rounded-lg border border-brand-line px-3 py-1.5 text-sm outline-none focus:border-brand-green focus:ring-1 focus:ring-brand-green"
                                            />
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <button
                                                onClick={() => save(category)}
                                                disabled={!changed || savingId === category.id || value < 1}
                                                className="rounded-lg bg-brand-green px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-green-dark disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                {savingId === category.id ? 'Saving…' : 'Save'}
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </AdminLayout>
        </>
    );
}
