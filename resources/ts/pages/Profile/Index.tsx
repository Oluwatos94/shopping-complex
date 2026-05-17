import { Head, Link, usePage } from '@inertiajs/react';
import VendorSidebar from '@/components/VendorSidebar';
import EditProfile from '@/components/Profile/EditProfile';
import ChangePassword from '@/components/Profile/ChangePassword';
import { Conversation } from '@/types/chat';

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    bio: string | null;
    role: string;
    business_name: string | null;
    avatar: string | null;
    created_at: string;
}

interface PageProps {
    [key: string]: unknown;
    user: UserData;
    conversations: Conversation[];
    errors?: Record<string, string>;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    auth?: {
        user?: {
            id: number;
            role: string;
        } | null;
    };
}

function formatTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return date.toLocaleDateString([], { weekday: 'short' });
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function getInitials(name: string): string {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

export default function ProfileIndex() {
    const { user, conversations, errors, flash, auth } = usePage<PageProps>().props;
    const isVendor = auth?.user?.role === 'vendor';

    return (
        <>
            <Head title="Profile - jiidaa" />

            <div className="min-h-screen bg-gray-50">
                {isVendor && <VendorSidebar />}

                <div className={isVendor ? 'md:ml-[260px]' : ''}>
                    {/* Header */}
                    <div className="bg-primary-dark py-8">
                        <div className="max-w-3xl mx-auto px-4">
                            <h1 className="text-2xl font-serif font-bold text-white">Account Settings</h1>
                            <p className="text-sm text-primary-light mt-1">Manage your profile and preferences</p>
                        </div>
                    </div>

                    <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <div className="px-4 py-3 bg-green-50 border border-green-200 rounded-lg">
                                <p className="text-sm text-green-700">{flash.success}</p>
                            </div>
                        )}
                        {flash?.error && (
                            <div className="px-4 py-3 bg-red-50 border border-red-200 rounded-lg">
                                <p className="text-sm text-red-700">{flash.error}</p>
                            </div>
                        )}

                        {/* Edit Profile */}
                        <EditProfile user={user} errors={errors} />

                        {/* Change Password */}
                        <ChangePassword errors={errors} />

                        {/* Recent Conversations */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-lg font-serif font-bold text-primary-dark">Recent Messages</h2>
                                <Link
                                    href="/chat"
                                    className="text-sm text-primary-olive hover:text-primary-dark font-medium transition-colors"
                                >
                                    View All
                                </Link>
                            </div>

                            {conversations.length > 0 ? (
                                <div className="divide-y divide-gray-100">
                                    {conversations.map((convo) => {
                                        const isCustomer = user.id === convo.customer_id;
                                        const other = isCustomer ? convo.vendor : convo.customer;
                                        const displayName = other.business_name || other.name;
                                        const lastMessage = convo.messages?.[0];

                                        return (
                                            <Link
                                                key={convo.id}
                                                href={`/chat/${convo.id}`}
                                                className="flex items-center gap-3 py-3 hover:bg-gray-50 -mx-2 px-2 rounded-lg transition-colors"
                                            >
                                                <div className="w-10 h-10 rounded-full flex-shrink-0 bg-primary-olive/20 flex items-center justify-center">
                                                    <span className="text-xs font-bold text-primary-olive">
                                                        {getInitials(displayName)}
                                                    </span>
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center justify-between">
                                                        <h4 className="text-sm font-medium text-gray-800 truncate">{displayName}</h4>
                                                        {convo.last_message_at && (
                                                            <span className="text-xs text-gray-400 flex-shrink-0 ml-2">
                                                                {formatTime(convo.last_message_at)}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-gray-400 truncate mt-0.5">
                                                        {lastMessage?.content || 'No messages yet'}
                                                    </p>
                                                </div>
                                                {convo.unread_count > 0 && (
                                                    <span className="flex-shrink-0 w-5 h-5 bg-primary-olive text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                                                        {convo.unread_count > 9 ? '9+' : convo.unread_count}
                                                    </span>
                                                )}
                                            </Link>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-400 text-center py-4">No conversations yet.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
