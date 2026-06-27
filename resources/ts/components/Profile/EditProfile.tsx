import { useState, useRef, useCallback, FormEvent } from 'react';
import { router } from '@inertiajs/react';

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    bio: string | null;
    role: string;
    business_name: string | null;
    avatar: string | null;
}

interface Props {
    user: UserData;
    errors?: Record<string, string>;
}

export default function EditProfile({ user, errors: serverErrors }: Props) {
    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);
    const [phone, setPhone] = useState(user.phone || '');
    const [bio, setBio] = useState(user.bio || '');
    const [avatar, setAvatar] = useState<string | null>(user.avatar);
    const [uploading, setUploading] = useState(false);
    const [processing, setProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleAvatarChange = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);
        const formData = new FormData();
        formData.append('avatar', file);

        const xsrfToken = document.cookie
            .split('; ')
            .find(c => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        try {
            const res = await fetch('/profile/avatar', {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (res.ok) {
                const data = await res.json();
                setAvatar(data.avatar_url);
            }
        } catch {
            // Silent fail
        } finally {
            setUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    }, []);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.put('/profile', { name, email, phone: phone || null, bio: bio || null }, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 className="text-lg font-serif font-bold text-brand-ink mb-6">Profile Information</h2>

            {/* Avatar */}
            <div className="flex items-center gap-4 mb-6">
                <button
                    onClick={() => fileInputRef.current?.click()}
                    disabled={uploading}
                    className="relative w-20 h-20 rounded-full overflow-hidden border-2 border-gray-200 hover:border-brand-green transition-colors group"
                >
                    {avatar ? (
                        <img src={avatar} alt={name} className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full bg-brand-green flex items-center justify-center">
                            <span className="text-white text-2xl font-bold">{name.charAt(0).toUpperCase()}</span>
                        </div>
                    )}
                    <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    {uploading && (
                        <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <div className="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        </div>
                    )}
                </button>
                <input
                    ref={fileInputRef}
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp"
                    onChange={handleAvatarChange}
                    className="sr-only"
                />
                <div>
                    <p className="text-sm font-medium text-gray-700">Profile Photo</p>
                    <p className="text-xs text-gray-400">Click to upload. JPG, PNG or WebP. Max 5MB.</p>
                </div>
            </div>

            {/* Form */}
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        type="text"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                    {serverErrors?.name && <p className="text-sm text-red-600 mt-1">{serverErrors.name}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                    {serverErrors?.email && <p className="text-sm text-red-600 mt-1">{serverErrors.email}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input
                        type="tel"
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        placeholder="Optional"
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green"
                    />
                    {serverErrors?.phone && <p className="text-sm text-red-600 mt-1">{serverErrors.phone}</p>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea
                        value={bio}
                        onChange={(e) => setBio(e.target.value)}
                        rows={3}
                        placeholder="Tell us about yourself..."
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green resize-none"
                    />
                    {serverErrors?.bio && <p className="text-sm text-red-600 mt-1">{serverErrors.bio}</p>}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="bg-brand-green text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:bg-brand-ink transition-colors disabled:opacity-50"
                >
                    {processing ? 'Saving...' : 'Save Changes'}
                </button>
            </form>
        </div>
    );
}
