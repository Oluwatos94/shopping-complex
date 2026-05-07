import { useRef, useState, useCallback, FormEvent, useEffect } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { Product } from '@/types/product';

interface Props {
    productLimit: number | null;
    activeProductsCount: number;
    editProduct?: Product | null;
    onEditClose?: () => void;
}

const MAX_IMAGES = 5;
const MAX_IMAGE_BYTES = 20 * 1024 * 1024;  // 20 MB
const MAX_VIDEO_BYTES = 100 * 1024 * 1024; // 100 MB

export default function UploadProductFab({ productLimit, activeProductsCount, editProduct, onEditClose }: Props) {
    const isEditMode = !!editProduct;
    const atLimit = !isEditMode && productLimit !== null && activeProductsCount >= productLimit;
    const [open, setOpen] = useState(isEditMode);

    const [imagePreviews, setImagePreviews] = useState<string[]>([]);
    const [videoPreview, setVideoPreview] = useState<string | null>(null);
    const [fileSizeError, setFileSizeError] = useState<string | null>(null);

    const [deleteConfirm, setDeleteConfirm] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const imageInputRef = useRef<HTMLInputElement>(null);
    const videoInputRef = useRef<HTMLInputElement>(null);

    const form = useForm({
        name: editProduct?.name ?? '',
        description: editProduct?.description ?? '',
        price: editProduct?.price ? String(editProduct.price) : '',
        pay_on_delivery: editProduct?.pay_on_delivery ?? false as boolean,
        is_returnable: editProduct?.is_returnable ?? false as boolean,
        images: [] as File[],
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
                images: [],
                video: null,
            });
            setImagePreviews([]);
            setVideoPreview(null);
            setFileSizeError(null);
        }
    }, [editProduct?.id]);

    const handleImageChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);
        if (!files.length) return;

        setFileSizeError(null);

        const oversized = files.filter(f => f.size > MAX_IMAGE_BYTES);
        if (oversized.length) {
            setFileSizeError(`File too large — max 20MB per image. Please compress and try again.`);
            if (imageInputRef.current) imageInputRef.current.value = '';
            return;
        }

        const currentCount = form.data.images.length;
        const available = MAX_IMAGES - currentCount;
        if (available <= 0) {
            setFileSizeError(`Maximum ${MAX_IMAGES} images per product.`);
            if (imageInputRef.current) imageInputRef.current.value = '';
            return;
        }

        const toAdd = files.slice(0, available);
        if (toAdd.length < files.length) {
            setFileSizeError(`Only ${available} slot(s) remaining — ${files.length - toAdd.length} file(s) skipped.`);
        }

        // clear video if switching to images
        if (videoPreview) {
            URL.revokeObjectURL(videoPreview);
            setVideoPreview(null);
            form.setData('video', null);
        }

        const newPreviews = toAdd.map(f => URL.createObjectURL(f));
        setImagePreviews(prev => [...prev, ...newPreviews]);
        form.setData('images', [...form.data.images, ...toAdd]);

        if (imageInputRef.current) imageInputRef.current.value = '';
    }, [videoPreview, form.data.images]);

    const handleVideoChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setFileSizeError(null);

        if (file.size > MAX_VIDEO_BYTES) {
            setFileSizeError('Video is too large — maximum size is 100MB.');
            if (videoInputRef.current) videoInputRef.current.value = '';
            return;
        }

        // clear images if switching to video
        imagePreviews.forEach(URL.revokeObjectURL);
        setImagePreviews([]);
        form.setData('images', []);

        if (videoPreview) URL.revokeObjectURL(videoPreview);
        setVideoPreview(URL.createObjectURL(file));
        form.setData('video', file);

        if (videoInputRef.current) videoInputRef.current.value = '';
    }, [imagePreviews, videoPreview]);

    const removeImage = useCallback((index: number) => {
        setImagePreviews(prev => {
            const url = prev[index];
            if (url) URL.revokeObjectURL(url);
            return prev.filter((_, i) => i !== index);
        });
        form.setData('images', form.data.images.filter((_, i) => i !== index));
        setFileSizeError(null);
    }, [form.data.images]);

    const removeVideo = useCallback(() => {
        if (videoPreview) URL.revokeObjectURL(videoPreview);
        setVideoPreview(null);
        form.setData('video', null);
        if (videoInputRef.current) videoInputRef.current.value = '';
    }, [videoPreview]);

    const resetForm = useCallback(() => {
        form.reset();
        imagePreviews.forEach(URL.revokeObjectURL);
        setImagePreviews([]);
        if (videoPreview) URL.revokeObjectURL(videoPreview);
        setVideoPreview(null);
        setFileSizeError(null);
        setDeleteConfirm(false);
        if (imageInputRef.current) imageInputRef.current.value = '';
        if (videoInputRef.current) videoInputRef.current.value = '';
    }, [imagePreviews, videoPreview]);

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

    const existingImages = editProduct?.images?.filter(img => img.type !== 'product_video') ?? [];
    const existingVideoUrl = editProduct?.images?.find(img => img.type === 'product_video')?.url ?? null;

    const showExistingImages = isEditMode && imagePreviews.length === 0 && !videoPreview && existingImages.length > 0;
    const showExistingVideo = isEditMode && !videoPreview && imagePreviews.length === 0 && !!existingVideoUrl;

    return (
        <>
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

                                {/* Media section */}
                                <div>
                                    {/* Existing images (edit mode, no new selection yet) */}
                                    {showExistingImages && (
                                        <div className="mb-2">
                                            <div className="grid grid-cols-3 gap-2">
                                                {existingImages.map(img => (
                                                    <div key={img.id} className="relative aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                        <img src={img.url} alt="" className="w-full h-full object-cover" />
                                                    </div>
                                                ))}
                                            </div>
                                            <p className="text-xs text-gray-400 mt-1.5">Selecting new images will replace all existing ones</p>
                                        </div>
                                    )}

                                    {/* Existing video (edit mode, no new selection yet) */}
                                    {showExistingVideo && (
                                        <div className="mb-2">
                                            <video src={existingVideoUrl!} className="w-full max-h-48 object-contain rounded-lg" controls />
                                            <p className="text-xs text-gray-400 mt-1.5">Selecting new media will replace the existing video</p>
                                        </div>
                                    )}

                                    {/* New image previews grid */}
                                    {imagePreviews.length > 0 && (
                                        <div className="grid grid-cols-3 gap-2 mb-2">
                                            {imagePreviews.map((src, i) => (
                                                <div key={i} className="relative aspect-square rounded-lg overflow-hidden bg-gray-100">
                                                    <img src={src} alt="" className="w-full h-full object-cover" />
                                                    <button
                                                        type="button"
                                                        onClick={() => removeImage(i)}
                                                        className="absolute top-1 right-1 w-5 h-5 bg-black/60 hover:bg-black text-white rounded-full flex items-center justify-center text-xs leading-none"
                                                    >
                                                        ×
                                                    </button>
                                                </div>
                                            ))}
                                            {imagePreviews.length < MAX_IMAGES && (
                                                <button
                                                    type="button"
                                                    onClick={() => imageInputRef.current?.click()}
                                                    className="aspect-square rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-olive flex items-center justify-center text-gray-400 hover:text-primary-olive transition-colors"
                                                >
                                                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                                    </svg>
                                                </button>
                                            )}
                                        </div>
                                    )}

                                    {/* New video preview */}
                                    {videoPreview && (
                                        <div className="relative mb-2">
                                            <video src={videoPreview} className="w-full max-h-48 object-contain rounded-lg" controls />
                                            <button
                                                type="button"
                                                onClick={removeVideo}
                                                className="absolute top-2 right-2 w-6 h-6 bg-black/60 hover:bg-black text-white rounded-full flex items-center justify-center text-sm"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    )}

                                    {/* Upload area — shown when no new media selected */}
                                    {imagePreviews.length === 0 && !videoPreview && (
                                        <div
                                            onClick={() => imageInputRef.current?.click()}
                                            className="w-full rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-olive cursor-pointer transition-colors flex items-center justify-center"
                                            style={{ minHeight: '10rem' }}
                                        >
                                            <div className="text-center py-8 px-4">
                                                <svg className="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p className="text-sm text-gray-500">Click to upload images (up to {MAX_IMAGES})</p>
                                                <p className="text-xs text-gray-400 mt-1">JPG, PNG, WebP · max 20MB each</p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Video upload link */}
                                    {!videoPreview && (
                                        <button
                                            type="button"
                                            onClick={() => videoInputRef.current?.click()}
                                            className="mt-2 text-xs text-gray-400 hover:text-primary-olive transition-colors underline underline-offset-2"
                                        >
                                            Upload a video instead (MP4, MOV · max 100MB)
                                        </button>
                                    )}

                                    {/* Hidden inputs */}
                                    <input
                                        ref={imageInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        multiple
                                        onChange={handleImageChange}
                                        className="sr-only"
                                    />
                                    <input
                                        ref={videoInputRef}
                                        type="file"
                                        accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-matroska"
                                        onChange={handleVideoChange}
                                        className="sr-only"
                                    />

                                    {/* File size / slot error */}
                                    {fileSizeError && (
                                        <p className="text-sm text-red-600 mt-1.5 flex items-start gap-1.5">
                                            <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                            </svg>
                                            {fileSizeError}
                                        </p>
                                    )}
                                    {(form.errors.images || form.errors.video) && (
                                        <p className="text-sm text-red-600 mt-1">{form.errors.images || form.errors.video}</p>
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
