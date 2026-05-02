import { useRef, useState, useCallback, FormEvent, useEffect } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { Product } from '@/types/product';

interface Props {
    productLimit: number | null;
    activeProductsCount: number;
    editProduct?: Product | null;
    onEditClose?: () => void;
}

export default function UploadProductFab({ productLimit, activeProductsCount, editProduct, onEditClose }: Props) {
    const isEditMode = !!editProduct;
    const atLimit = !isEditMode && productLimit !== null && activeProductsCount >= productLimit;
    const [open, setOpen] = useState(isEditMode);
    const [mediaPreview, setMediaPreview] = useState<string | null>(null);
    const [isVideo, setIsVideo] = useState(false);
    const [deleteConfirm, setDeleteConfirm] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm({
        name: editProduct?.name ?? '',
        description: editProduct?.description ?? '',
        price: editProduct?.price ? String(editProduct.price) : '',
        pay_on_delivery: editProduct?.pay_on_delivery ?? false as boolean,
        is_returnable: editProduct?.is_returnable ?? false as boolean,
        image: null as File | null,
        video: null as File | null,
    });

    useEffect(() => {
        if (editProduct) {
            setOpen(true);
            form.setData({
                name: editProduct.name,
                description: editProduct.description,
                price: String(editProduct.price),
                pay_on_delivery: editProduct.pay_on_delivery,
                is_returnable: editProduct.is_returnable,
                image: null,
                video: null,
            });
        }
    }, [editProduct?.id]);

    const handleFileChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const fileIsVideo = file.type.startsWith('video/');
        setIsVideo(fileIsVideo);
        setMediaPreview(URL.createObjectURL(file));

        if (fileIsVideo) {
            form.setData('video', file);
            form.setData('image', null);
        } else {
            form.setData('image', file);
            form.setData('video', null);
        }
    }, []);

    const resetForm = useCallback(() => {
        form.reset();
        if (mediaPreview) URL.revokeObjectURL(mediaPreview);
        setMediaPreview(null);
        setIsVideo(false);
        setDeleteConfirm(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }, [mediaPreview]);

    const handleClose = () => {
        if (form.processing) return;
        setOpen(false);
        setDeleteConfirm(false);
        resetForm();
        onEditClose?.();
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        const url = isEditMode
            ? `/vendor/products/${editProduct!.id}/update`
            : '/vendor/products/upload';

        form.post(url, {
            forceFormData: true,
            onSuccess: () => {
                resetForm();
                setOpen(false);
                onEditClose?.();
            },
        });
    };

    const handleDelete = () => {
        if (!editProduct || deleting) return;
        setDeleting(true);
        router.delete(`/vendor/products/${editProduct.id}`, {
            onSuccess: () => {
                setOpen(false);
                onEditClose?.();
            },
            onFinish: () => setDeleting(false),
        });
    };

    const existingMediaUrl = editProduct?.images?.[0]?.url ?? null;
    const existingIsVideo = editProduct?.images?.[0] && (editProduct.images[0] as any).type === 'product_video';

    return (
        <>
            {/* FAB — only shown when not in edit mode */}
            {!isEditMode && (
                <button
                    onClick={() => setOpen(true)}
                    className="fixed bottom-8 right-8 w-14 h-14 bg-primary-olive text-white rounded-full shadow-lg hover:bg-primary-dark hover:shadow-xl transition-all flex items-center justify-center z-50 group"
                >
                    <svg className="w-7 h-7 group-hover:rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    <span className="absolute right-full mr-3 bg-primary-dark text-white text-xs font-medium px-3 py-1.5 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        Add Product
                    </span>
                </button>
            )}

            {/* Modal */}
            {open && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center">
                    <div className="absolute inset-0 bg-black/50" onClick={handleClose} />
                    <div className="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between mb-5">
                            <h2 className="text-lg font-serif font-bold text-primary-dark">
                                {isEditMode ? 'Edit Product' : 'Add Product'}
                            </h2>
                            <button onClick={handleClose} className="text-gray-400 hover:text-gray-600" disabled={form.processing}>
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {atLimit ? (
                            <div className="text-center py-6">
                                <div className="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-4">
                                    <svg className="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-base font-semibold text-gray-900 mb-1">Product limit reached</h3>
                                <p className="text-sm text-gray-500 mb-5">
                                    You've used all {productLimit} product slots. Upgrade to add more.
                                </p>
                                <Link href="/vendor/subscription" className="inline-block bg-primary-olive text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-primary-dark transition-colors">
                                    View Plans
                                </Link>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-4">

                                {/* Media upload area */}
                                <div>
                                    <div
                                        onClick={() => fileInputRef.current?.click()}
                                        className="w-full rounded-lg border-2 border-dashed flex items-center justify-center cursor-pointer overflow-hidden transition-colors border-gray-300 hover:border-primary-olive"
                                        style={{ minHeight: '10rem' }}
                                    >
                                        {mediaPreview ? (
                                            isVideo ? (
                                                <video src={mediaPreview} className="w-full max-h-48 object-contain" controls />
                                            ) : (
                                                <img src={mediaPreview} alt="Preview" className="w-full max-h-48 object-cover" />
                                            )
                                        ) : existingMediaUrl ? (
                                            existingIsVideo ? (
                                                <video src={existingMediaUrl} className="w-full max-h-48 object-contain" controls />
                                            ) : (
                                                <img src={existingMediaUrl} alt="Current" className="w-full max-h-48 object-cover" />
                                            )
                                        ) : (
                                            <div className="text-center py-8 px-4">
                                                <svg className="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p className="text-sm text-gray-500">Click to upload image or video</p>
                                                <p className="text-xs text-gray-400 mt-1">Image: JPG, PNG, WebP (max 10MB) · Video: MP4, MOV, WebM (max 100MB)</p>
                                            </div>
                                        )}
                                    </div>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-matroska"
                                        onChange={handleFileChange}
                                        className="sr-only"
                                    />
                                    {isEditMode && !mediaPreview && (
                                        <p className="text-xs text-gray-400 mt-1">Leave empty to keep current media</p>
                                    )}
                                    {(form.errors.image || form.errors.video) && (
                                        <p className="text-sm text-red-600 mt-1">{form.errors.image || form.errors.video}</p>
                                    )}
                                </div>

                                {/* Title */}
                                <div>
                                    <input
                                        type="text"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="Product title"
                                        className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${form.errors.name ? 'border-red-300' : 'border-gray-300'}`}
                                    />
                                    {form.errors.name && <p className="text-sm text-red-600 mt-1">{form.errors.name}</p>}
                                </div>

                                {/* Description */}
                                <div>
                                    <textarea
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        placeholder="Product description"
                                        rows={3}
                                        className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 resize-none ${form.errors.description ? 'border-red-300' : 'border-gray-300'}`}
                                    />
                                    {form.errors.description && <p className="text-sm text-red-600 mt-1">{form.errors.description}</p>}
                                </div>

                                {/* Price */}
                                <div>
                                    <div className="relative">
                                        <span className="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">₦</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.price}
                                            onChange={(e) => form.setData('price', e.target.value)}
                                            placeholder="0.00"
                                            className={`w-full rounded-lg border pl-8 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${form.errors.price ? 'border-red-300' : 'border-gray-300'}`}
                                        />
                                    </div>
                                    {form.errors.price && <p className="text-sm text-red-600 mt-1">{form.errors.price}</p>}
                                </div>

                                {/* Options */}
                                <div className="flex flex-col gap-3">
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={form.data.pay_on_delivery}
                                            onChange={(e) => form.setData('pay_on_delivery', e.target.checked)}
                                            className="w-4 h-4 rounded border-gray-300 text-primary-olive focus:ring-primary-olive/50"
                                        />
                                        <span className="text-sm text-gray-700">Pay on Delivery available</span>
                                    </label>
                                    <label className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={form.data.is_returnable}
                                            onChange={(e) => form.setData('is_returnable', e.target.checked)}
                                            className="w-4 h-4 rounded border-gray-300 text-primary-olive focus:ring-primary-olive/50"
                                        />
                                        <span className="text-sm text-gray-700">Returns accepted</span>
                                    </label>
                                </div>

                                {/* Submit */}
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="w-full bg-primary-olive text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {form.processing ? (isEditMode ? 'Saving...' : 'Uploading...') : (isEditMode ? 'Save Changes' : 'Upload Product')}
                                </button>

                                {/* Delete — only in edit mode */}
                                {isEditMode && (
                                    <div className="pt-1">
                                        {deleteConfirm ? (
                                            <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-center">
                                                <p className="text-sm text-red-700 mb-3 font-medium">Delete this product? This cannot be undone.</p>
                                                <div className="flex gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleteConfirm(false)}
                                                        className="flex-1 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                                    >
                                                        Cancel
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={handleDelete}
                                                        disabled={deleting}
                                                        className="flex-1 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                                                    >
                                                        {deleting ? 'Deleting...' : 'Yes, delete'}
                                                    </button>
                                                </div>
                                            </div>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => setDeleteConfirm(true)}
                                                className="w-full py-2.5 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
                                            >
                                                Delete product
                                            </button>
                                        )}
                                    </div>
                                )}
                            </form>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
