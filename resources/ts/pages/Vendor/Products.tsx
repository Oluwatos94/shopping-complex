import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import { Product } from '@/types/product';
import NotificationModal from '@/components/NotificationModal';
import UploadProductFab from './partials/UploadProductFab';

interface PaginatedVendorProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    products: PaginatedVendorProducts;
    product_limit: number | null;
    active_products_count: number;
}

export default function VendorProducts({ products, product_limit, active_products_count }: Props) {
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
    const [productToDelete, setProductToDelete] = useState<Product | null>(null);
    const [addSignal, setAddSignal] = useState(0);

    const openAdd = () => setAddSignal((s) => s + 1);

    const confirmDelete = () => {
        if (!productToDelete) return;
        setDeletingId(productToDelete.id);
        router.delete(`/vendor/products/${productToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => setProductToDelete(null),
            onFinish: () => setDeletingId(null),
        });
    };

    return (
        <>
            <Head title="My Products" />
            <VendorSidebar />

            <main className="md:ml-[260px] min-h-screen bg-brand-surface pb-20 md:pb-0">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">

                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">My Products</h1>
                            <p className="text-sm text-gray-500 mt-1">{products.total} product{products.total !== 1 ? 's' : ''} in your store</p>
                        </div>
                        <button
                            onClick={openAdd}
                            className="flex items-center gap-2 bg-brand-green text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-brand-ink transition-colors"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                            </svg>
                            Add Product
                        </button>
                    </div>

                    {/* Product grid — two columns on mobile, matching the vendors list */}
                    {products.data.length > 0 ? (
                        <div className="grid grid-cols-2 gap-4 sm:gap-5 sm:[grid-template-columns:repeat(auto-fill,minmax(220px,1fr))]">
                            {products.data.map((product) => {
                                const price = Number(product.price);
                                const primary = product.images?.[0];
                                const isVideo = primary?.type === 'product_video';

                                return (
                                    <div key={product.id} className="group flex flex-col bg-white rounded-2xl border border-brand-line overflow-hidden hover:shadow-sm transition-shadow">
                                        {/* Thumbnail */}
                                        <div className="relative aspect-square bg-gray-100">
                                            {primary ? (
                                                isVideo ? (
                                                    <>
                                                        <video src={primary.url} className="w-full h-full object-cover" muted playsInline preload="metadata" />
                                                        <div className="absolute inset-0 flex items-center justify-center bg-black/20">
                                                            <svg className="w-8 h-8 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M8 5v14l11-7z" />
                                                            </svg>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <img src={primary.url} alt={product.name} className="w-full h-full object-cover" />
                                                )
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <svg className="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            )}
                                            <span className={`absolute top-2 left-2 text-[11px] font-medium px-2 py-0.5 rounded-full ${
                                                product.is_active
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-gray-100 text-gray-500'
                                            }`}>
                                                {product.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </div>

                                        {/* Info */}
                                        <div className="flex flex-col flex-1 p-3">
                                            <p className="text-sm font-semibold text-gray-900 line-clamp-1">{product.name}</p>
                                            <div className="flex items-center justify-between mt-1">
                                                <span className="text-sm font-bold text-gray-900">₦{price.toLocaleString()}</span>
                                                <span className="text-xs text-gray-400">Stock: {product.stock}</span>
                                            </div>

                                            {/* Actions */}
                                            <div className="flex items-center gap-1.5 mt-3">
                                                <button
                                                    onClick={() => setSelectedProduct(product)}
                                                    className="flex-1 h-8 flex items-center justify-center gap-1 rounded-lg border border-gray-200 text-gray-500 hover:border-brand-green hover:text-brand-green transition-colors"
                                                    title="Edit product"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <Link
                                                    href={`/products/${product.slug}`}
                                                    className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:border-brand-green hover:text-brand-green transition-colors"
                                                    title="View product"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.75} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </Link>
                                                <button
                                                    onClick={() => setProductToDelete(product)}
                                                    disabled={deletingId === product.id}
                                                    className="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:border-red-400 hover:text-red-500 transition-colors disabled:opacity-40"
                                                    title="Delete product"
                                                >
                                                    {deletingId === product.id ? (
                                                        <div className="w-3.5 h-3.5 border-2 border-gray-300 border-t-red-500 rounded-full animate-spin" />
                                                    ) : (
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    )}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="bg-white rounded-2xl shadow-sm flex flex-col items-center justify-center py-20 text-center">
                            <svg className="w-16 h-16 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p className="text-gray-700 font-medium mb-1">No products yet</p>
                            <p className="text-gray-400 text-sm mb-6">Add your first product to start selling.</p>
                            <button
                                onClick={openAdd}
                                className="bg-brand-green text-white text-sm font-semibold px-6 py-2.5 rounded-xl hover:bg-brand-ink transition-colors"
                            >
                                Add Product
                            </button>
                        </div>
                    )}

                    {/* Pagination */}
                    {products.last_page > 1 && (
                        <div className="flex justify-center gap-2 mt-6">
                            {Array.from({ length: products.last_page }, (_, i) => i + 1).map((page) => (
                                <Link
                                    key={page}
                                    href={`/vendor/products?page=${page}`}
                                    className={`w-10 h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors ${
                                        page === products.current_page
                                            ? 'bg-brand-green text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                    }`}
                                >
                                    {page}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                <UploadProductFab
                    productLimit={product_limit}
                    activeProductsCount={active_products_count}
                    editProduct={selectedProduct}
                    onEditClose={() => setSelectedProduct(null)}
                    hideFab
                    openAddSignal={addSignal}
                />

                <NotificationModal
                    open={productToDelete !== null}
                    title="Delete product?"
                    message={productToDelete ? `"${productToDelete.name}" will be permanently removed. This cannot be undone.` : ''}
                    confirmLabel="Delete"
                    tone="danger"
                    processing={deletingId === productToDelete?.id}
                    onConfirm={confirmDelete}
                    onClose={() => setProductToDelete(null)}
                />
            </main>
        </>
    );
}
