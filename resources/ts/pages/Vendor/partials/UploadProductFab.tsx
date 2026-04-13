import { useRef, useState, useCallback, FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';

interface Props {
    productLimit: number | null;
    activeProductsCount: number;
}

export default function UploadProductFab({ productLimit, activeProductsCount }: Props) {
    const atLimit = productLimit !== null && activeProductsCount >= productLimit;
    const [open, setOpen] = useState(false);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm({
        name: '',
        description: '',
        price: '',
        pay_on_delivery: false as boolean,
        is_returnable: false as boolean,
        image: null as File | null,
    });

    const handleImageChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('image', file);
            setImagePreview(URL.createObjectURL(file));
        }
    }, []);

    const resetForm = useCallback(() => {
        form.reset();
        if (imagePreview) URL.revokeObjectURL(imagePreview);
        setImagePreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }, [imagePreview]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/vendor/products/upload', {
            forceFormData: true,
            onSuccess: () => {
                resetForm();
                setOpen(false);
            },
        });
    };

    return (
        <>
            {/* FAB */}
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

            {/* Modal */}
            {open && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center">
                    <div className="absolute inset-0 bg-black/50" onClick={() => { if (!form.processing) { setOpen(false); resetForm(); } }} />
                    <div className="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between mb-5">
                            <h2 className="text-lg font-serif font-bold text-primary-dark">Add Product</h2>
                            <button
                                onClick={() => { setOpen(false); resetForm(); }}
                                className="text-gray-400 hover:text-gray-600"
                                disabled={form.processing}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {/* Upgrade prompt — shown when the vendor has hit their plan's product limit */}
                        {atLimit ? (
                            <div className="text-center py-6">
                                <div className="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-4">
                                    <svg className="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-base font-semibold text-gray-900 mb-1">Product limit reached</h3>
                                <p className="text-sm text-gray-500 mb-5">
                                    You've used all {productLimit} product slots on your current plan.
                                    Upgrade to add more.
                                </p>
                                <Link
                                    href="/vendor/subscription"
                                    className="inline-block bg-primary-olive text-white text-sm font-semibold px-6 py-2.5 rounded-lg hover:bg-primary-dark transition-colors"
                                >
                                    View Plans
                                </Link>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-4">
                                {/* Image Upload */}
                                <div>
                                    <div
                                        onClick={() => fileInputRef.current?.click()}
                                        className={`w-full aspect-square max-h-48 rounded-lg border-2 border-dashed flex items-center justify-center cursor-pointer overflow-hidden transition-colors ${
                                            form.errors.image ? 'border-red-300' : 'border-gray-300 hover:border-primary-olive'
                                        }`}
                                    >
                                        {imagePreview ? (
                                            <img src={imagePreview} alt="Preview" className="w-full h-full object-cover" />
                                        ) : (
                                            <div className="text-center py-8">
                                                <svg className="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p className="text-sm text-gray-500">Click to upload image</p>
                                                <p className="text-xs text-gray-400 mt-1">JPG, PNG or WebP. Max 5MB</p>
                                            </div>
                                        )}
                                    </div>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        onChange={handleImageChange}
                                        className="sr-only"
                                    />
                                    {form.errors.image && <p className="text-sm text-red-600 mt-1">{form.errors.image}</p>}
                                </div>

                                {/* Title */}
                                <div>
                                    <input
                                        type="text"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="Product title"
                                        className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
                                            form.errors.name ? 'border-red-300' : 'border-gray-300'
                                        }`}
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
                                        className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 resize-none ${
                                            form.errors.description ? 'border-red-300' : 'border-gray-300'
                                        }`}
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
                                            className={`w-full rounded-lg border pl-8 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
                                                form.errors.price ? 'border-red-300' : 'border-gray-300'
                                            }`}
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
                                    {form.processing ? 'Uploading...' : 'Upload Product'}
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
