import { useState, useRef, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { VendorProfile } from '@/types/vendor';
import { resizeImage } from '@/utils/imageResize';

interface Props {
    vendor: VendorProfile;
    geoapifyKey?: string;
    onClose: () => void;
}

interface AddressSuggestion {
    properties: {
        formatted: string;
        housenumber?: string;
        street?: string;
        city?: string;
        county?: string;
        state?: string;
        lat: number;
        lon: number;
    };
}

interface SelectedAddress {
    formatted: string;
    street: string;
    city: string;
    state: string;
    lat: number;
    lon: number;
}

export default function EditProfileModal({ vendor, geoapifyKey, onClose }: Props) {
    const [businessName, setBusinessName] = useState(vendor.business_name);
    const [bio, setBio] = useState(vendor.business_description ?? '');
    const [whatsapp, setWhatsapp] = useState(vendor.whatsapp_number ?? '');
    const [avatar, setAvatar] = useState<File | null>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const [banner, setBanner] = useState<File | null>(null);
    const [bannerPreview, setBannerPreview] = useState<string | null>(null);
    const [addressQuery, setAddressQuery] = useState(vendor.address ?? '');
    const [suggestions, setSuggestions] = useState<AddressSuggestion[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [addressLoading, setAddressLoading] = useState(false);
    const [selectedAddress, setSelectedAddress] = useState<SelectedAddress | null>(
        vendor.address && vendor.latitude && vendor.longitude
            ? { formatted: vendor.address, street: vendor.address, city: vendor.city ?? '', state: vendor.state ?? '', lat: vendor.latitude, lon: vendor.longitude }
            : null
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const fileRef = useRef<HTMLInputElement>(null);
    const bannerRef = useRef<HTMLInputElement>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (avatarPreview) URL.revokeObjectURL(avatarPreview);
            if (bannerPreview) URL.revokeObjectURL(bannerPreview);
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (suggestionsRef.current && !suggestionsRef.current.contains(e.target as Node)) {
                setShowSuggestions(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const searchAddress = useCallback((query: string) => {
        if (query.length < 3) {
            setSuggestions([]);
            setShowSuggestions(false);
            return;
        }
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(async () => {
            setAddressLoading(true);
            try {
                const params = new URLSearchParams({
                    text: query,
                    'filter[countrycode]': 'ng',
                    format: 'json',
                    limit: '6',
                    apiKey: geoapifyKey ?? '',
                });
                const res = await fetch(`https://api.geoapify.com/v1/geocode/autocomplete?${params}`);
                const data = await res.json();
                const results: AddressSuggestion[] = (data.results ?? []).map(
                    (r: AddressSuggestion['properties']) => ({ properties: r })
                );
                setSuggestions(results);
                setShowSuggestions(results.length > 0);
            } catch {
                setSuggestions([]);
            } finally {
                setAddressLoading(false);
            }
        }, 350);
    }, [geoapifyKey]);

    const handleAddressInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        const val = e.target.value;
        setAddressQuery(val);
        setSelectedAddress(null);
        searchAddress(val);
    };

    const selectSuggestion = (s: AddressSuggestion) => {
        const p = s.properties;
        const street = [p.housenumber, p.street].filter(Boolean).join(' ') || p.formatted.split(',')[0] || p.formatted;
        setAddressQuery(p.formatted);
        setSelectedAddress({ formatted: p.formatted, street, city: p.city || p.county || '', state: p.state || '', lat: p.lat, lon: p.lon });
        setShowSuggestions(false);
    };

    const handleAvatarChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const resized = await resizeImage(file, 1280);
            setAvatar(resized);
            setAvatarPreview((prev) => {
                if (prev) URL.revokeObjectURL(prev);
                return URL.createObjectURL(resized);
            });
        }
    };

    const removeAvatar = () => {
        setAvatar(null);
        if (avatarPreview) URL.revokeObjectURL(avatarPreview);
        setAvatarPreview(null);
        if (fileRef.current) fileRef.current.value = '';
    };

    const handleBannerChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setBanner(file);
            setBannerPreview((prev) => {
                if (prev) URL.revokeObjectURL(prev);
                return URL.createObjectURL(file);
            });
        }
    };

    const removeBanner = () => {
        setBanner(null);
        if (bannerPreview) URL.revokeObjectURL(bannerPreview);
        setBannerPreview(null);
        if (bannerRef.current) bannerRef.current.value = '';
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAddress) {
            setErrors((prev) => ({ ...prev, address: 'Please select an address from the suggestions.' }));
            return;
        }

        const formData = new FormData();
        formData.append('business_name', businessName);
        formData.append('bio', bio);
        formData.append('whatsapp_number', whatsapp);
        formData.append('address', selectedAddress.street || selectedAddress.formatted);
        formData.append('city', selectedAddress.city);
        formData.append('state', selectedAddress.state);
        formData.append('latitude', String(selectedAddress.lat));
        formData.append('longitude', String(selectedAddress.lon));
        if (avatar) formData.append('avatar', avatar);
        if (banner) formData.append('banner', banner);

        router.post('/vendor/profile/update', formData, {
            forceFormData: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (errs) => setErrors(errs),
            onSuccess: () => onClose(),
        });
    };

    const inputClass = (field: string) =>
        `w-full rounded-lg border px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${errors[field] ? 'border-red-300' : 'border-gray-300'}`;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
                <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <h2 className="text-lg font-semibold text-gray-900">Edit Profile</h2>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-5">
                    {/* Banner */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Store Banner</label>
                        <div
                            onClick={() => bannerRef.current?.click()}
                            className="relative w-full h-32 rounded-xl border-2 border-dashed border-gray-300 hover:border-primary-olive overflow-hidden cursor-pointer transition-colors bg-gray-50 flex items-center justify-center"
                        >
                            {bannerPreview ? (
                                <img src={bannerPreview} alt="Banner preview" className="w-full h-full object-cover" />
                            ) : vendor.banner_image ? (
                                <img src={vendor.banner_image} alt="Current banner" className="w-full h-full object-cover" />
                            ) : (
                                <div className="text-center">
                                    <svg className="w-8 h-8 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span className="text-xs text-gray-400">Click to upload a banner image</span>
                                </div>
                            )}
                            {(bannerPreview || vendor.banner_image) && (
                                <div className="absolute inset-0 bg-black/0 hover:bg-black/30 transition-colors flex items-center justify-center">
                                    <span className="opacity-0 hover:opacity-100 text-white text-xs font-medium bg-black/50 px-3 py-1 rounded-full">Change banner</span>
                                </div>
                            )}
                        </div>
                        {bannerPreview && (
                            <button type="button" onClick={removeBanner} className="mt-1 text-xs text-red-500 hover:text-red-700">
                                Remove banner
                            </button>
                        )}
                        <input ref={bannerRef} type="file" accept=".jpg,.jpeg,.png,.webp" onChange={handleBannerChange} className="sr-only" />
                        <p className="text-xs text-gray-400 mt-1">Recommended: 1200×400px. Max 10MB.</p>
                        {errors.banner && <p className="text-xs text-red-600 mt-1">{errors.banner}</p>}
                    </div>

                    {/* Avatar */}
                    <div className="flex flex-col items-center">
                        <div className="relative">
                            <div
                                onClick={() => fileRef.current?.click()}
                                className="w-24 h-24 rounded-full border-2 border-dashed border-gray-300 hover:border-primary-olive flex items-center justify-center cursor-pointer overflow-hidden transition-colors"
                            >
                                {avatarPreview ? (
                                    <img src={avatarPreview} alt="Avatar preview" className="w-full h-full object-cover" />
                                ) : vendor.business_logo ? (
                                    <img src={vendor.business_logo} alt={vendor.business_name} className="w-full h-full object-cover" />
                                ) : (
                                    <div className="text-center px-2">
                                        <svg className="w-7 h-7 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span className="text-xs text-gray-400 mt-1 block">Change</span>
                                    </div>
                                )}
                            </div>
                            {avatarPreview && (
                                <button
                                    type="button"
                                    onClick={removeAvatar}
                                    className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                                >
                                    &times;
                                </button>
                            )}
                        </div>
                        <input ref={fileRef} type="file" accept=".jpg,.jpeg,.png,.webp" onChange={handleAvatarChange} className="sr-only" />
                        <p className="text-xs text-gray-400 mt-2">JPG, PNG or WebP. Max 5MB.</p>
                        {errors.avatar && <p className="text-xs text-red-600 mt-1">{errors.avatar}</p>}
                    </div>

                    {/* Business Name */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Business Name <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            value={businessName}
                            onChange={(e) => setBusinessName(e.target.value)}
                            maxLength={255}
                            className={inputClass('business_name')}
                        />
                        {errors.business_name && <p className="text-xs text-red-600 mt-1">{errors.business_name}</p>}
                    </div>

                    {/* Business Description */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Business Description <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            value={bio}
                            onChange={(e) => setBio(e.target.value)}
                            rows={3}
                            maxLength={1000}
                            className={`${inputClass('bio')} resize-none`}
                        />
                        <div className="flex justify-between mt-1">
                            {errors.bio ? <p className="text-xs text-red-600">{errors.bio}</p> : <span />}
                            <span className="text-xs text-gray-400">{bio.length}/1000</span>
                        </div>
                    </div>

                    {/* WhatsApp Number */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            WhatsApp Number <span className="text-red-500">*</span>
                        </label>
                        <input
                            type="tel"
                            value={whatsapp}
                            onChange={(e) => setWhatsapp(e.target.value)}
                            maxLength={20}
                            className={inputClass('whatsapp_number')}
                        />
                        {errors.whatsapp_number && <p className="text-xs text-red-600 mt-1">{errors.whatsapp_number}</p>}
                    </div>

                    {/* Business Address */}
                    <div ref={suggestionsRef} className="relative">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Business Address <span className="text-red-500">*</span>
                        </label>
                        <div className="relative">
                            <input
                                type="text"
                                value={addressQuery}
                                onChange={handleAddressInput}
                                onFocus={() => suggestions.length > 0 && setShowSuggestions(true)}
                                placeholder="Start typing your address..."
                                autoComplete="off"
                                className={inputClass('address')}
                            />
                            {addressLoading && (
                                <span className="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg className="animate-spin h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                </span>
                            )}
                            {selectedAddress && !addressLoading && (
                                <span className="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg className="h-4 w-4 text-primary-olive" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                </span>
                            )}
                        </div>

                        {showSuggestions && suggestions.length > 0 && (
                            <ul className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                {suggestions.map((s, i) => (
                                    <li
                                        key={i}
                                        onMouseDown={(e) => e.preventDefault()}
                                        onClick={() => selectSuggestion(s)}
                                        className="px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                    >
                                        <div className="flex items-start gap-2">
                                            <svg className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span className="line-clamp-2">{s.properties.formatted}</span>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <p className="text-xs text-gray-400 mt-1">Search and select your new address to update your discovery location.</p>
                        {errors.address && <p className="text-xs text-red-600 mt-1">{errors.address}</p>}
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 py-2.5 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 py-2.5 rounded-lg bg-primary-olive text-white text-sm font-semibold hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
