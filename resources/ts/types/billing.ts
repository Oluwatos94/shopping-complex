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
    balance: number;
    lead_rate: number;
    category_name: string | null;
    packs: CoinPack[];
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
