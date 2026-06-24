import { Product } from '@/types/product';
import ProductCard from '../ProductCard';

interface ProductGridProps {
    products: Product[];
    loading?: boolean;
}

const GRID_CLASS = 'grid gap-[22px] [grid-template-columns:repeat(auto-fill,minmax(180px,1fr))] sm:[grid-template-columns:repeat(auto-fill,minmax(212px,1fr))]';

export default function ProductGrid({ products, loading = false }: ProductGridProps) {
    if (loading) {
        return (
            <div className={GRID_CLASS}>
                {[...Array(10)].map((_, i) => (
                    <div key={i} className="animate-pulse overflow-hidden rounded-[18px] border border-brand-line bg-white">
                        <div className="aspect-square bg-brand-line/60" />
                        <div className="space-y-2 p-4">
                            <div className="h-3 w-1/2 rounded bg-brand-line/60" />
                            <div className="h-4 w-3/4 rounded bg-brand-line/60" />
                            <div className="h-4 w-1/3 rounded bg-brand-line/60" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (products.length === 0) {
        return (
            <div className="rounded-[18px] border border-brand-line bg-white px-5 py-20 text-center font-display">
                <h3 className="text-[19px] font-bold text-brand-ink">No products found</h3>
                <p className="mt-1.5 text-[15px] text-brand-muted">Try a different search or clear your filters.</p>
            </div>
        );
    }

    return (
        <div className={GRID_CLASS}>
            {products.map((product) => (
                <ProductCard key={product.id} product={product} />
            ))}
        </div>
    );
}
