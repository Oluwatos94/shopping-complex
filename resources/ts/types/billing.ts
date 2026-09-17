import { LaravelPaginated } from './common';

export interface LedgerEntry {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    date: string;
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
