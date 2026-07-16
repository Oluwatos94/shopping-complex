import { formatTime } from '@/utils/date';
import { ChatMessage } from '@/types/chat';

interface Props {
    message: ChatMessage;
    isOwn: boolean;
    showTimestamp: boolean;
}

function ReadReceipt({ message }: { message: ChatMessage }) {
    const isRead = !!message.read_at;
    const isDelivered = !!message.delivered_at;
    const color = isRead ? 'text-green-500' : 'text-gray-400';

    if (!isDelivered && !isRead) return null;

    return (
        <svg className={`w-4 h-4 ${color} inline-block ml-1`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
            {(isRead || isDelivered) && (
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 13l4 4L23 7" transform="translate(-4, 0)" />
            )}
        </svg>
    );
}

export default function MessageBubble({ message, isOwn, showTimestamp }: Props) {
    const hasAttachment = !!message.attachment_path;
    const isImage = message.attachment_type === 'image';

    return (
        <div className={`flex ${isOwn ? 'justify-end' : 'justify-start'} mb-1`}>
            <div className={`max-w-[70%] ${isOwn ? 'items-end' : 'items-start'}`}>
                {/* Attachment */}
                {hasAttachment && isImage && (
                    <div className={`mb-1 rounded-xl overflow-hidden ${isOwn ? 'rounded-br-md' : 'rounded-bl-md'}`}>
                        <img
                            src={`/storage/${message.attachment_path}`}
                            alt={message.attachment_name || 'Image'}
                            className="max-w-full max-h-64 object-cover"
                        />
                    </div>
                )}

                {hasAttachment && !isImage && (
                    <a
                        href={`/storage/${message.attachment_path}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`flex items-center gap-2 mb-1 px-3 py-2 rounded-lg text-xs ${
                            isOwn ? 'bg-primary-olive/80 text-white' : 'bg-[#f5f0e8] text-gray-700'
                        }`}
                    >
                        <svg className="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span className="truncate">{message.attachment_name || 'Attachment'}</span>
                    </a>
                )}

                {/* Message content */}
                {message.content && (
                    <div
                        className={`px-4 py-2.5 text-sm leading-relaxed ${
                            isOwn
                                ? 'bg-primary-olive text-white rounded-2xl rounded-br-md'
                                : 'bg-[#f5f0e8] text-gray-800 rounded-2xl rounded-bl-md'
                        }`}
                    >
                        {message.content}
                    </div>
                )}

                {/* Timestamp & Read receipt */}
                {showTimestamp && (
                    <div className={`flex items-center gap-0.5 mt-1 ${isOwn ? 'justify-end' : 'justify-start'}`}>
                        <span className={`text-[11px] ${isOwn ? 'text-gray-400' : 'text-gray-400'}`}>
                            {formatTime(message.created_at)}
                        </span>
                        {isOwn && <ReadReceipt message={message} />}
                    </div>
                )}
            </div>
        </div>
    );
}
