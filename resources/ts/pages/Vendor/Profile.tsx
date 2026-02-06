import { useRef, useState, useCallback, FormEvent } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Product } from '@/types/product';

interface VendorProfile {
    id: number;
    name: string;
    email: string;
    business_name: string;
    business_description?: string;
    business_logo?: string;
    is_verified: boolean;
    created_at: string;
}

interface VendorStats {
    products_count: number;
    reviews_count: number;
    average_rating: number;
    followers_count: number;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    vendor: VendorProfile;
    products: PaginatedProducts;
    stats: VendorStats;
    isOwner: boolean;
    isFollowing: boolean;
}

interface SidebarItem {
    label: string;
    href: string;
    icon: React.ReactNode;
}

function Sidebar({ vendor }: { vendor: VendorProfile }) {
    const items: SidebarItem[] = [
        {
            label: 'Home',
            href: '/',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            ),
        },
        {
            label: 'Catalogue',
            href: '/products',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            ),
        },
        {
            label: 'Chat',
            href: '/chat',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            ),
        },
        {
            label: 'Analytics',
            href: '#',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        },
        {
            label: 'Settings',
            href: '#',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            ),
        },
        {
            label: 'Subscription',
            href: '#',
            icon: (
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
            ),
        },
    ];

    return (
        <aside className="fixed left-0 top-0 bottom-0 w-[100px] bg-gray-100 border-r border-gray-200 flex flex-col items-center py-6 z-40">
            {/* Logo */}
            <Link href="/" className="mb-8">
                <img
                    src="/logo/dark-mode-2.svg"
                    alt="Shopping Complex"
                    className="h-10 w-auto"
                />
            </Link>

            {/* Nav Items */}
            <nav className="flex-1 flex flex-col items-center gap-2">
                {items.map((item) => (
                    <Link
                        key={item.label}
                        href={item.href}
                        className="flex flex-col items-center gap-1 px-3 py-3 rounded-xl text-primary-brown hover:bg-primary-olive/10 hover:text-primary-olive transition-colors w-[80px]"
                    >
                        {item.icon}
                        <span className="text-[10px] font-medium">{item.label}</span>
                    </Link>
                ))}
            </nav>

            {/* Profile Avatar at Bottom */}
            <Link
                href={`/vendors/${vendor.id}`}
                className="mt-auto flex flex-col items-center gap-1 px-3 py-3 rounded-xl hover:bg-primary-olive/10 transition-colors"
            >
                <div className="w-10 h-10 rounded-full overflow-hidden border-2 border-primary-olive">
                    {vendor.business_logo ? (
                        <img
                            src={vendor.business_logo}
                            alt={vendor.business_name}
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                            <span className="text-white text-sm font-bold">
                                {vendor.business_name.charAt(0).toUpperCase()}
                            </span>
                        </div>
                    )}
                </div>
                <span className="text-[10px] font-medium text-primary-brown">Profile</span>
            </Link>
        </aside>
    );
}

function UploadProductFab({}: { vendorId: number }) {
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [price, setPrice] = useState('');
    const [image, setImage] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImageChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setImage(file);
            setImagePreview(URL.createObjectURL(file));
        }
    }, []);

    const reset = useCallback(() => {
        setName('');
        setPrice('');
        setImage(null);
        if (imagePreview) URL.revokeObjectURL(imagePreview);
        setImagePreview(null);
        setErrors({});
        if (fileInputRef.current) fileInputRef.current.value = '';
    }, [imagePreview]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('name', name);
        formData.append('price', price);
        if (image) formData.append('image', image);

        setProcessing(true);
        const xsrfToken = document.cookie
            .split('; ')
            .find(c => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        fetch('/vendor/products/upload', {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) {
                    setErrors(data.errors || { image: data.message || 'Upload failed.' });
                    return;
                }
                reset();
                setOpen(false);
                router.reload({ only: ['products', 'stats'] });
            })
            .catch(() => setErrors({ image: 'Something went wrong.' }))
            .finally(() => setProcessing(false));
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
                    <div className="absolute inset-0 bg-black/50" onClick={() => { if (!processing) { setOpen(false); reset(); } }} />
                    <div className="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                        <div className="flex items-center justify-between mb-5">
                            <h2 className="text-lg font-serif font-bold text-primary-dark">Add Product</h2>
                            <button
                                onClick={() => { setOpen(false); reset(); }}
                                className="text-gray-400 hover:text-gray-600"
                                disabled={processing}
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Image Upload */}
                            <div>
                                <div
                                    onClick={() => fileInputRef.current?.click()}
                                    className={`w-full aspect-square max-h-48 rounded-lg border-2 border-dashed flex items-center justify-center cursor-pointer overflow-hidden transition-colors ${
                                        errors.image ? 'border-red-300' : 'border-gray-300 hover:border-primary-olive'
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
                                {errors.image && <p className="text-sm text-red-600 mt-1">{errors.image}</p>}
                            </div>

                            {/* Title */}
                            <div>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Product title"
                                    className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
                                        errors.name ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                />
                                {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                            </div>

                            {/* Price */}
                            <div>
                                <div className="relative">
                                    <span className="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-gray-400">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={price}
                                        onChange={(e) => setPrice(e.target.value)}
                                        placeholder="0.00"
                                        className={`w-full rounded-lg border pl-8 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
                                            errors.price ? 'border-red-300' : 'border-gray-300'
                                        }`}
                                    />
                                </div>
                                {errors.price && <p className="text-sm text-red-600 mt-1">{errors.price}</p>}
                            </div>

                            {/* Submit */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-primary-olive text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Uploading...' : 'Upload Product'}
                            </button>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

export default function VendorProfilePage({ vendor, products, stats, isOwner, isFollowing: initialIsFollowing }: Props) {
    const [isFollowing, setIsFollowing] = useState(initialIsFollowing);
    const [followersCount, setFollowersCount] = useState(stats.followers_count);
    const [followLoading, setFollowLoading] = useState(false);

    const handleToggleFollow = useCallback(async () => {
        if (followLoading) return;

        setFollowLoading(true);
        const xsrfToken = document.cookie
            .split('; ')
            .find(c => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        try {
            const res = await fetch(`/vendors/${vendor.id}/follow`, {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
                    'Accept': 'application/json',
                },
            });

            if (res.ok) {
                const data = await res.json();
                setIsFollowing(data.following);
                setFollowersCount(data.followers_count);
            }
        } catch {
            // Silently fail
        } finally {
            setFollowLoading(false);
        }
    }, [vendor.id, followLoading]);

    return (
        <>
            <Head title={`${vendor.business_name} - Shopping Complex`} />

            <div className="min-h-screen bg-gray-50">
                {/* Sidebar - only for vendor owner */}
                {isOwner && <Sidebar vendor={vendor} />}

                {/* Main Content */}
                <div className={isOwner ? 'ml-[100px]' : ''}>
                    {/* Cover / Header Area */}
                    <div className="bg-gradient-to-br from-primary-dark via-primary-brown to-primary-dark">
                        {/* Top Nav (only for visitors) */}
                        {!isOwner && (
                            <div className="container mx-auto px-4 py-4">
                                <div className="flex items-center justify-between">
                                    <Link href="/" className="flex items-center space-x-3">
                                        <img
                                            src="/logo/dark-mode-logo.svg"
                                            alt="Shopping Complex"
                                            className="h-8 w-auto"
                                        />
                                        <span className="text-primary-light font-bold text-lg">
                                            Shopping Complex
                                        </span>
                                    </Link>
                                    <Link
                                        href="/vendors"
                                        className="text-sm text-primary-light hover:text-white transition-colors"
                                    >
                                        Browse Vendors
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Profile Section */}
                        <div className="pb-8 pt-6">
                            {/* Avatar */}
                            <div className="flex justify-center">
                                <div className="w-28 h-28 rounded-full border-4 border-white overflow-hidden bg-gray-200 shadow-lg">
                                    {vendor.business_logo ? (
                                        <img
                                            src={vendor.business_logo}
                                            alt={vendor.business_name}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full bg-primary-olive flex items-center justify-center">
                                            <span className="text-white text-3xl font-bold">
                                                {vendor.business_name.charAt(0).toUpperCase()}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Name & Verified Badge */}
                            <div className="text-center mt-4">
                                <div className="flex items-center justify-center gap-2">
                                    <h1 className="text-2xl font-serif font-bold text-white">
                                        {vendor.business_name}
                                    </h1>
                                    {vendor.is_verified && (
                                        <svg className="w-5 h-5 text-primary-peach" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                        </svg>
                                    )}
                                </div>
                                <p className="text-sm text-primary-light mt-1">@{vendor.name}</p>
                            </div>

                            {/* Stats Row */}
                            <div className="flex justify-center gap-10 mt-6">
                                <div className="text-center">
                                    <p className="text-xl font-bold text-white">{stats.products_count}</p>
                                    <p className="text-xs text-primary-light">Products</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-xl font-bold text-white">{stats.reviews_count}</p>
                                    <p className="text-xs text-primary-light">Reviews</p>
                                </div>
                                <div className="text-center">
                                    <div className="flex items-center justify-center gap-1">
                                        <svg className="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        <p className="text-xl font-bold text-white">
                                            {stats.average_rating > 0 ? stats.average_rating.toFixed(1) : '-'}
                                        </p>
                                    </div>
                                    <p className="text-xs text-primary-light">Rating</p>
                                </div>
                            </div>

                            {/* Action Buttons */}
                            <div className="flex justify-center gap-3 mt-6">
                                {isOwner ? (
                                    <div className="px-8 py-2 bg-primary-peach/20 text-primary-light rounded-lg font-semibold text-sm">
                                        {followersCount} {followersCount === 1 ? 'Follower' : 'Followers'}
                                    </div>
                                ) : (
                                    <button
                                        onClick={handleToggleFollow}
                                        disabled={followLoading}
                                        className={`px-8 py-2 rounded-lg font-semibold text-sm transition-colors disabled:opacity-50 ${
                                            isFollowing
                                                ? 'bg-white/10 text-primary-light border border-primary-light hover:bg-white/20'
                                                : 'bg-primary-peach text-primary-dark hover:bg-primary-light'
                                        }`}
                                    >
                                        {followLoading ? '...' : isFollowing ? 'Following' : 'Follow'}
                                    </button>
                                )}
                                <Link
                                    href="/chat"
                                    className="px-8 py-2 border border-primary-light text-primary-light rounded-lg font-semibold text-sm hover:bg-white/10 transition-colors"
                                >
                                    Message
                                </Link>
                            </div>

                            {/* Bio */}
                            {vendor.business_description && (
                                <div className="max-w-lg mx-auto mt-5 px-4">
                                    <p className="text-sm text-primary-light text-center leading-relaxed">
                                        {vendor.business_description}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Products Tab */}
                    <div className="border-b border-gray-200 bg-white">
                        <div className="container mx-auto px-4">
                            <div className="flex justify-center">
                                <button className="px-6 py-3 text-sm font-semibold text-primary-dark border-b-2 border-primary-olive">
                                    <span className="flex items-center gap-2">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        Products
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Products Grid */}
                    <div className="container mx-auto px-4 py-8">
                        {products.data.length > 0 ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                {products.data.map((product) => {
                                    const primaryImage = product.images?.find((img) => img.is_primary)?.url
                                        || product.images?.[0]?.url
                                        || '/images/placeholder.png';
                                    const price = Number(product.price);
                                    const salePrice = product.sale_price ? Number(product.sale_price) : null;
                                    const hasDiscount = salePrice && salePrice < price;

                                    return (
                                        <Link
                                            key={product.id}
                                            href={`/products/${product.id}`}
                                            className="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                                        >
                                            <div className="relative aspect-square overflow-hidden bg-gray-100">
                                                <img
                                                    src={primaryImage}
                                                    alt={product.name}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                />
                                                {hasDiscount && salePrice && (
                                                    <span className="absolute top-2 left-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
                                                        {Math.round(((price - salePrice) / price) * 100)}% OFF
                                                    </span>
                                                )}
                                            </div>
                                            <div className="p-3">
                                                <h3 className="text-sm font-medium text-gray-900 line-clamp-2 group-hover:text-primary-olive transition-colors">
                                                    {product.name}
                                                </h3>
                                                <div className="mt-1">
                                                    {hasDiscount && salePrice ? (
                                                        <div className="flex items-baseline gap-1">
                                                            <span className="text-sm font-bold text-gray-900">
                                                                ${salePrice.toFixed(2)}
                                                            </span>
                                                            <span className="text-xs text-gray-400 line-through">
                                                                ${price.toFixed(2)}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm font-bold text-gray-900">
                                                            ${price.toFixed(2)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="text-center py-16">
                                <svg className="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p className="text-gray-500 text-lg">No products yet</p>
                                <p className="text-gray-400 text-sm mt-1">
                                    {isOwner
                                        ? 'Start adding products to your store.'
                                        : "This vendor hasn't listed any products."}
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="flex justify-center gap-2 mt-8">
                                {Array.from({ length: products.last_page }, (_, i) => i + 1).map((page) => (
                                    <Link
                                        key={page}
                                        href={`/vendors/${vendor.id}?page=${page}`}
                                        className={`w-10 h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors ${
                                            page === products.current_page
                                                ? 'bg-primary-olive text-white'
                                                : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                        }`}
                                    >
                                        {page}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                {/* Upload Product Modal + FAB - only for owner */}
                {isOwner && <UploadProductFab vendorId={vendor.id} />}
            </div>
        </>
    );
}
