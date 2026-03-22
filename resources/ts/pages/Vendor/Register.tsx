import { useRef, useState, useCallback, FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    categories: Category[];
}

export default function VendorRegister({ categories }: Props) {
    const [businessName, setBusinessName] = useState('');
    const [bio, setBio] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [avatar, setAvatar] = useState<File | null>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleAvatarChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setAvatar(file);
            const url = URL.createObjectURL(file);
            setAvatarPreview(url);
        }
    }, []);

    const removeAvatar = useCallback(() => {
        setAvatar(null);
        if (avatarPreview) {
            URL.revokeObjectURL(avatarPreview);
        }
        setAvatarPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [avatarPreview]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('business_name', businessName);
        formData.append('bio', bio);
        formData.append('category_id', categoryId);
        if (avatar) {
            formData.append('avatar', avatar);
        }

        router.post('/vendor/register', formData, {
            forceFormData: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (errs) => setErrors(errs),
        });
    };

    return (
        <>
            <Head title="Become a Vendor - Shopping Complex" />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="container mx-auto px-4 py-3">
                        <div className="flex items-center space-x-3">
                            <a href="/" className="flex items-center space-x-3">
                                <img
                                    src="/logo/dark-mode-2.svg"
                                    alt="Shopping Complex"
                                    className="h-10 w-auto"
                                />
                                <h1 className="text-xl font-bold text-gray-900">
                                    Shopping Complex
                                </h1>
                            </a>
                        </div>
                    </div>
                </nav>

                <div className="max-w-2xl mx-auto px-4 py-12">
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-serif font-bold text-primary-dark">
                            Become a Vendor
                        </h1>
                        <p className="mt-2 text-gray-500">
                            Set up your vendor profile and start connecting with customers
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-6">
                        {/* Avatar Upload */}
                        <div className="flex flex-col items-center">
                            <label className="block text-sm font-medium text-gray-900 mb-3">
                                Profile Picture
                            </label>
                            <div className="relative">
                                <div
                                    onClick={() => fileInputRef.current?.click()}
                                    className="w-28 h-28 rounded-full border-2 border-dashed border-gray-300 hover:border-primary-olive flex items-center justify-center cursor-pointer overflow-hidden transition-colors"
                                >
                                    {avatarPreview ? (
                                        <img
                                            src={avatarPreview}
                                            alt="Avatar preview"
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="text-center">
                                            <svg className="w-8 h-8 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            <span className="text-xs text-gray-400 mt-1">Upload</span>
                                        </div>
                                    )}
                                </div>
                                {avatarPreview && (
                                    <button
                                        type="button"
                                        onClick={removeAvatar}
                                        className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600"
                                    >
                                        &times;
                                    </button>
                                )}
                            </div>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                onChange={handleAvatarChange}
                                className="sr-only"
                            />
                            <p className="text-xs text-gray-400 mt-2">JPG, PNG or WebP. Max 5MB.</p>
                            {errors.avatar && (
                                <p className="text-sm text-red-600 mt-1">{errors.avatar}</p>
                            )}
                        </div>

                        {/* Business Name */}
                        <div>
                            <label htmlFor="business_name" className="block text-sm font-medium text-gray-900 mb-1">
                                Business Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="business_name"
                                type="text"
                                value={businessName}
                                onChange={(e) => setBusinessName(e.target.value)}
                                placeholder="Enter your business name"
                                className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
                                    errors.business_name ? 'border-red-300' : 'border-gray-300'
                                }`}
                            />
                            {errors.business_name && (
                                <p className="text-sm text-red-600 mt-1">{errors.business_name}</p>
                            )}
                        </div>

                        {/* Business Category */}
                        <div>
                            <label htmlFor="category_id" className="block text-sm font-medium text-gray-900 mb-1">
                                Business Category <span className="text-red-500">*</span>
                            </label>
                            <select
                                id="category_id"
                                value={categoryId}
                                onChange={(e) => setCategoryId(e.target.value)}
                                className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 bg-white ${
                                    errors.category_id ? 'border-red-300' : 'border-gray-300'
                                }`}
                            >
                                <option value="">Select a category</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                                ))}
                            </select>
                            {errors.category_id && (
                                <p className="text-sm text-red-600 mt-1">{errors.category_id}</p>
                            )}
                        </div>

                        {/* Business Description */}
                        <div>
                            <label htmlFor="bio" className="block text-sm font-medium text-gray-900 mb-1">
                                Business Description <span className="text-red-500">*</span>
                            </label>
                            <textarea
                                id="bio"
                                value={bio}
                                onChange={(e) => setBio(e.target.value)}
                                placeholder="Tell customers about your business..."
                                rows={4}
                                maxLength={1000}
                                className={`w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 resize-none ${
                                    errors.bio ? 'border-red-300' : 'border-gray-300'
                                }`}
                            />
                            <div className="flex justify-between mt-1">
                                {errors.bio ? (
                                    <p className="text-sm text-red-600">{errors.bio}</p>
                                ) : (
                                    <span />
                                )}
                                <span className="text-xs text-gray-400">{bio.length}/1000</span>
                            </div>
                        </div>

                        {/* Submit */}
                        <div className="pt-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-primary-olive text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Creating your profile...' : 'Create Vendor Profile'}
                            </button>
                        </div>

                        <p className="text-xs text-gray-400 text-center">
                            By creating a vendor profile, you agree to our Terms of Service and Vendor Agreement.
                        </p>
                    </form>
                </div>
            </div>
        </>
    );
}
