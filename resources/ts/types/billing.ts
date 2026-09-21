import { LaravelPaginated } from './common';

export interface LedgerEntry {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    date: string;
}

export interface CoinPack {
    key: string;
    name: string;
    price: number;
    coins: number;
    bonus_coins: number;
    total_coins: number;
    leads_at_rate: number;
}

export interface CoinPacksProps {
    vendor: { business_name: string; email: string };
    balance: number;
    lead_rate: number;
    category_name: string | null;
    free_tier: { coins: number; available: boolean };
    can_purchase: boolean;
    packs: CoinPack[];
}

export interface LeadRow {
    id: number;
    date: string;
    search: string | null;
    area: string | null;
    channel: 'bot' | 'web';
    coins_charged: number;
    state: string;
    unbilled_reason: string | null;
    credit_reason: string | null;
    repeat_count: number;
}

export interface LeadHistoryProps {
    leads: LaravelPaginated<LeadRow>;
    states: string[];
    filters: { state: string; from: string; to: string };
}

export interface VendorWalletProps {
    vendor: { business_name: string };
    balance: number;
    lead_rate: number;
    leads_affordable: number;
    category: { name: string; cost: number } | null;
    low_balance: boolean;
    low_balance_threshold: number;
    unbilled_out_of_coins: number;
    spent_series: { date: string; count: number }[];
    ledger: LaravelPaginated<LedgerEntry>;
    top_up_link: string;
}
