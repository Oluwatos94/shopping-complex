import { Product } from '@/types/product';
import ProductCard from '../ProductCard';
import { SkeletonCard } from '@/components/Loading';

interface ProductGridProps {
    products: Product[];
    loading?: boolean;
}

const GRID_CLASS = 'grid grid-cols-2 gap-4 sm:gap-[22px] sm:[grid-template-columns:repeat(auto-fill,minmax(212px,1fr))]';

export default function ProductGrid({ products, loading = false }: ProductGridProps) {
    if (loading) {
        return (
            <div className={GRID_CLASS}>
                <SkeletonCard count={10} variant="product" />
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
