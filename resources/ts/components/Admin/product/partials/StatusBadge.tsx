import { ProductStatus } from '@/types/product';

export default function StatusBadge({ status }: { status: ProductStatus }) {
    if (status === 'active') {
        return (
            <span className="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
                Active
            </span>
        );
    }
    if (status === 'flagged') {
        return (
            <span className="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
                Flagged
            </span>
        );
    }
    return (
        <span className="bg-primary-brown/15 text-primary-brown px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest shadow">
            Pending Review
        </span>
    );
}
