import { Head, router } from '@inertiajs/react';
import { Conversation as ConversationType, ChatMessage, ChatPagination } from '@/types/chat';
import VendorSidebar from '@/components/VendorSidebar';
import ConversationList from '@/components/Chat/ConversationList';
import MessagePanel from '@/components/Chat/MessagePanel';

interface Props {
    conversation: ConversationType;
    messages: ChatMessage[];
    pagination: ChatPagination;
    conversations: ConversationType[];
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
}

export default function ChatConversation({ conversation, messages, conversations, auth }: Props) {
    const isVendor = auth.user.role === 'vendor';

    const handleConversationSelect = (id: number) => {
        router.visit(`/chat/${id}`);
    };

    return (
        <>
            <Head title={`Chat - ${conversation.vendor?.business_name || conversation.vendor?.name || 'Messages'}`} />

            <div className="min-h-screen bg-gray-50">
                {isVendor && <VendorSidebar />}

                <div className={`flex h-screen ${isVendor ? 'md:ml-[260px]' : ''}`}>
                    {/* Conversation List - desktop only */}
                    <div className="hidden lg:block w-[350px] flex-shrink-0 border-r border-gray-200">
                        <ConversationList
                            conversations={conversations}
                            activeConversationId={conversation.id}
                            authUserId={auth.user.id}
                            onConversationSelect={handleConversationSelect}
                        />
                    </div>

                    {/* Message Panel */}
                    <div className="flex-1 flex flex-col min-w-0">
                        <MessagePanel
                            conversation={conversation}
                            initialMessages={messages}
                            authUserId={auth.user.id}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
