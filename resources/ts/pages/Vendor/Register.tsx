import { useRef, useState, useCallback, useEffect, FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import { Category } from '@/types/product';
import { resizeImage } from '@/utils/imageResize';

interface Props {
    categories: Pick<Category, 'id' | 'name' | 'slug'>[];
}

interface AddressSuggestion {
    place_id: string;
    description: string;
}

interface PlaceDetails {
    formatted: string;
    street: string;
    city: string;
    state: string;
    lat: number;
    lng: number;
}

interface SelectedAddress {
    formatted: string;
    street: string;
    city: string;
    state: string;
    lat: number;
    lon: number;
}

export default function VendorRegister({ categories }: Props) {
    const [businessName, setBusinessName] = useState('');
    const [bio, setBio] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [avatar, setAvatar] = useState<File | null>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const [whatsappNumber, setWhatsappNumber] = useState('');
    const [addressQuery, setAddressQuery] = useState('');
    const [suggestions, setSuggestions] = useState<AddressSuggestion[]>([]);
    const [selectedAddress, setSelectedAddress] = useState<SelectedAddress | null>(null);
    const [addressLoading, setAddressLoading] = useState(false);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);
    // One Places session token bundles the typing + final lookup into one billed session.
    const sessionTokenRef = useRef<string>(crypto.randomUUID());

    useEffect(() => {
        return () => {
            if (avatarPreview) URL.revokeObjectURL(avatarPreview);
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
                const res = await fetch(`/api/geo/autocomplete?q=${encodeURIComponent(query)}&session=${sessionTokenRef.current}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                const results: AddressSuggestion[] = data.suggestions ?? [];
                setSuggestions(results);
                setShowSuggestions(results.length > 0);
            } catch {
                setSuggestions([]);
            } finally {
                setAddressLoading(false);
            }
        }, 350);
    }, []);

    const handleAddressInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        const val = e.target.value;
        setAddressQuery(val);
        setSelectedAddress(null);
        setErrors((prev) => ({ ...prev, address: '' }));
        searchAddress(val);
    };

    const selectSuggestion = async (s: AddressSuggestion) => {
        setAddressQuery(s.description);
        setShowSuggestions(false);
        setAddressLoading(true);
        try {
            const res = await fetch(`/api/geo/place?place_id=${encodeURIComponent(s.place_id)}&session=${sessionTokenRef.current}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) throw new Error('place lookup failed');
            const p: PlaceDetails = await res.json();
            setAddressQuery(p.formatted || s.description);
            setSelectedAddress({ formatted: p.formatted, street: p.street, city: p.city, state: p.state, lat: p.lat, lon: p.lng });
            setErrors((prev) => ({ ...prev, address: '', city: '', state: '', latitude: '', longitude: '' }));
        } catch {
            setAddressQuery('');
            setErrors((prev) => ({ ...prev, address: 'Could not load that address. Please pick another.' }));
        } finally {
            // Selection closes the billing session — start a fresh token for the next search.
            sessionTokenRef.current = crypto.randomUUID();
            setAddressLoading(false);
        }
    };

    const handleAvatarChange = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const resized = await resizeImage(file, 1280);
            setAvatar(resized);
            setAvatarPreview((prev) => {
                if (prev) URL.revokeObjectURL(prev);
                return URL.createObjectURL(resized);
            });
        }
    }, []);

    const removeAvatar = useCallback(() => {
        setAvatar(null);
        if (avatarPreview) URL.revokeObjectURL(avatarPreview);
        setAvatarPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }, [avatarPreview]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!selectedAddress) {
            setErrors((prev) => ({ ...prev, address: 'Please select an address from the suggestions.' }));
            return;
        }

        const formData = new FormData();
        formData.append('business_name', businessName);
        formData.append('bio', bio);
        formData.append('category_id', categoryId);
        formData.append('whatsapp_number', whatsappNumber);
        formData.append('address', selectedAddress.street || selectedAddress.formatted);
        formData.append('city', selectedAddress.city);
        formData.append('state', selectedAddress.state);
        formData.append('latitude', String(selectedAddress.lat));
        formData.append('longitude', String(selectedAddress.lon));
        if (avatar) formData.append('avatar', avatar);

        router.post('/vendor/register', formData, {
            forceFormData: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (errs) => setErrors(errs),
        });
    };

    const inputClass = (field: string) =>
        `w-full rounded-lg border px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/50 ${
            errors[field] ? 'border-red-300' : 'border-gray-300'
        }`;

    return (
        <>
            <Head title="Become a Vendor - jiidaa" />

            <div className="min-h-screen bg-gray-50">
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="container mx-auto px-4 py-3">
                        {/* <a href="/" className="flex items-center space-x-3">
                            <img src="/logo/jiidaa.jpeg" alt="jiidaa" className="h-10 w-auto" />
                            <h1 className="text-xl font-bold text-gray-900">jiidaa</h1>
                        </a> */}
                    </div>
                </nav>

                <div className="max-w-2xl mx-auto px-4 py-12">
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-serif font-bold text-primary-dark">Become a Vendor</h1>
                        <p className="mt-2 text-gray-500">Set up your vendor profile and start connecting with customers</p>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 space-y-6">

                        {/* Avatar Upload */}
                        <div className="flex flex-col items-center">
                            <label className="block text-sm font-medium text-gray-900 mb-3">Profile Picture</label>
                            <div className="relative">
                                <div
                                    onClick={() => fileInputRef.current?.click()}
                                    className="w-28 h-28 rounded-full border-2 border-dashed border-gray-300 hover:border-primary-olive flex items-center justify-center cursor-pointer overflow-hidden transition-colors"
                                >
                                    {avatarPreview ? (
                                        <img src={avatarPreview} alt="Avatar preview" className="w-full h-full object-cover" />
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
                                    <button type="button" onClick={removeAvatar} className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                        &times;
                                    </button>
                                )}
                            </div>
                            <input ref={fileInputRef} type="file" accept=".jpg,.jpeg,.png,.webp" onChange={handleAvatarChange} className="sr-only" />
                            <p className="text-xs text-gray-400 mt-2">JPG, PNG or WebP. Large images are automatically resized.</p>
                            {errors.avatar && <p className="text-sm text-red-600 mt-1">{errors.avatar}</p>}
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
                                className={inputClass('business_name')}
                            />
                            {errors.business_name && <p className="text-sm text-red-600 mt-1">{errors.business_name}</p>}
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
                                className={`${inputClass('category_id')} bg-white`}
                            >
                                <option value="">Select a category</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                                ))}
                            </select>
                            {errors.category_id && <p className="text-sm text-red-600 mt-1">{errors.category_id}</p>}
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
                                className={`${inputClass('bio')} resize-none`}
                            />
                            <div className="flex justify-between mt-1">
                                {errors.bio ? <p className="text-sm text-red-600">{errors.bio}</p> : <span />}
                                <span className="text-xs text-gray-400">{bio.length}/1000</span>
                            </div>
                        </div>

                        {/* WhatsApp Number */}
                        <div>
                            <label htmlFor="whatsapp_number" className="block text-sm font-medium text-gray-900 mb-1">
                                WhatsApp Number <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">+234</span>
                                <input
                                    id="whatsapp_number"
                                    type="tel"
                                    value={whatsappNumber}
                                    onChange={(e) => setWhatsappNumber(e.target.value)}
                                    placeholder="8012345678"
                                    className={`${inputClass('whatsapp_number')} pl-14`}
                                />
                            </div>
                            <p className="text-xs text-gray-400 mt-1">Customers will contact you via WhatsApp on this number.</p>
                            {errors.whatsapp_number && <p className="text-sm text-red-600 mt-1">{errors.whatsapp_number}</p>}
                        </div>

                        {/* Business Address */}
                        <div ref={suggestionsRef} className="relative">
                            <label htmlFor="address" className="block text-sm font-medium text-gray-900 mb-1">
                                Business Address <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <input
                                    id="address"
                                    type="text"
                                    value={addressQuery}
                                    onChange={handleAddressInput}
                                    onFocus={() => suggestions.length > 0 && setShowSuggestions(true)}
                                    placeholder="Start typing your street, area or business name..."
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

                            {/* Suggestions dropdown */}
                            {showSuggestions && suggestions.length > 0 && (
                                <ul className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    {suggestions.map((s, i) => (
                                        <li
                                            key={i}
                                            onMouseDown={(e) => e.preventDefault()}
                                            onClick={() => selectSuggestion(s)}
                                            className="px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                                        >
                                            <div className="flex items-start gap-2">
                                                <svg className="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span className="line-clamp-2">{s.description}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}

                            <p className="text-xs text-gray-400 mt-1">
                                Type your address and select from the suggestions.
                            </p>
                            {errors.address && <p className="text-sm text-red-600 mt-1">{errors.address}</p>}
                            {(errors.latitude || errors.longitude) && (
                                <p className="text-sm text-red-600 mt-1">Please select an address from the suggestions.</p>
                            )}
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
