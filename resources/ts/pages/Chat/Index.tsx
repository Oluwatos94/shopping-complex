import { Head, router } from '@inertiajs/react';
import { Conversation, ChatPagination } from '@/types/chat';
import VendorSidebar from '@/components/VendorSidebar';
import ConversationList from '@/components/Chat/ConversationList';
import EmptyState from '@/components/Chat/EmptyState';

interface Props {
    conversations: Conversation[];
    pagination: ChatPagination;
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
}

export default function ChatIndex({ conversations, auth }: Props) {
    const isVendor = auth.user.role === 'vendor';

    const handleConversationSelect = (id: number) => {
        router.visit(`/chat/${id}`);
    };

    return (
        <>
            <Head title="Messages - jiidaa" />

            <div className="min-h-screen bg-gray-50">
                {isVendor && <VendorSidebar />}

                <div className={`flex h-screen ${isVendor ? 'md:ml-[100px]' : ''}`}>
                    {/* Conversation List */}
                    <div className="w-full lg:w-[350px] lg:flex-shrink-0 lg:border-r lg:border-gray-200">
                        <ConversationList
                            conversations={conversations}
                            activeConversationId={null}
                            authUserId={auth.user.id}
                            onConversationSelect={handleConversationSelect}
                        />
                    </div>

                    {/* Empty State - desktop only */}
                    <div className="hidden lg:flex flex-1">
                        {conversations.length > 0 ? (
                            <EmptyState type="no-selection" />
                        ) : (
                            <EmptyState type="no-conversations" />
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
