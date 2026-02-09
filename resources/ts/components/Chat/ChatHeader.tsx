import { Link } from '@inertiajs/react';
import { Conversation } from '@/types/chat';

interface Props {
    conversation: Conversation;
    authUserId: number;
}

function getInitials(name: string): string {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

export default function ChatHeader({ conversation, authUserId }: Props) {
    const isCustomer = authUserId === conversation.customer_id;
    const other = isCustomer ? conversation.vendor : conversation.customer;
    const displayName = other.business_name || other.name;

    return (
        <div className="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200">
            <div className="flex items-center gap-3">
                {/* Back button - mobile only */}
                <Link
                    href="/chat"
                    className="lg:hidden p-1 -ml-1 text-gray-500 hover:text-gray-700"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                {/* Avatar */}
                <div className="w-10 h-10 rounded-full bg-primary-olive/20 flex items-center justify-center overflow-hidden">
                    <span className="text-sm font-bold text-primary-olive">
                        {getInitials(displayName)}
                    </span>
                </div>

                {/* Name & Status */}
                <div>
                    <h3 className="text-sm font-semibold text-gray-900">{displayName}</h3>
                    <p className="text-xs text-green-500 font-medium">Online</p>
                </div>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2">
                <button className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <button className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
