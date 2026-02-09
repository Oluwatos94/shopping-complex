import { useState, useRef, useCallback, KeyboardEvent, ChangeEvent } from 'react';

interface Props {
    onSend: (content: string, attachment?: File) => void;
    onTyping: () => void;
    isSending: boolean;
    disabled?: boolean;
}

export default function MessageInput({ onSend, onTyping, isSending, disabled }: Props) {
    const [content, setContent] = useState('');
    const [attachment, setAttachment] = useState<File | null>(null);
    const [attachmentPreview, setAttachmentPreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const handleSend = useCallback(() => {
        if ((!content.trim() && !attachment) || isSending) return;
        onSend(content, attachment || undefined);
        setContent('');
        setAttachment(null);
        if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
        setAttachmentPreview(null);
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
        }
    }, [content, attachment, isSending, onSend, attachmentPreview]);

    const handleKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    const handleChange = (e: ChangeEvent<HTMLTextAreaElement>) => {
        setContent(e.target.value);
        onTyping();

        // Auto-grow textarea
        const textarea = e.target;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    };

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setAttachment(file);
        if (file.type.startsWith('image/')) {
            setAttachmentPreview(URL.createObjectURL(file));
        } else {
            setAttachmentPreview(null);
        }
    };

    const removeAttachment = () => {
        setAttachment(null);
        if (attachmentPreview) URL.revokeObjectURL(attachmentPreview);
        setAttachmentPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    return (
        <div className="bg-white border-t border-gray-200">
            {/* Attachment Preview */}
            {attachment && (
                <div className="px-4 pt-3 flex items-center gap-2">
                    {attachmentPreview ? (
                        <img src={attachmentPreview} alt="Preview" className="w-16 h-16 object-cover rounded-lg" />
                    ) : (
                        <div className="flex items-center gap-2 bg-gray-100 rounded-lg px-3 py-2">
                            <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span className="text-xs text-gray-600 truncate max-w-[150px]">{attachment.name}</span>
                        </div>
                    )}
                    <button
                        onClick={removeAttachment}
                        className="p-1 text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            )}

            {/* Input Bar */}
            <div className="flex items-end gap-2 px-4 py-3">
                {/* Attachment Button */}
                <button
                    onClick={() => fileInputRef.current?.click()}
                    disabled={disabled || isSending}
                    className="p-2 text-gray-400 hover:text-primary-olive transition-colors flex-shrink-0"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                </button>
                <input
                    ref={fileInputRef}
                    type="file"
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                    onChange={handleFileChange}
                    className="sr-only"
                />

                {/* Emoji Button (placeholder) */}
                <button className="p-2 text-gray-400 hover:text-primary-olive transition-colors flex-shrink-0">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                {/* Text Input */}
                <div className="flex-1 relative">
                    <textarea
                        ref={textareaRef}
                        value={content}
                        onChange={handleChange}
                        onKeyDown={handleKeyDown}
                        placeholder="Type a message..."
                        rows={1}
                        disabled={disabled || isSending}
                        className="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/30 focus:border-primary-olive disabled:opacity-50 max-h-[120px]"
                    />
                </div>

                {/* Send Button */}
                <button
                    onClick={handleSend}
                    disabled={(!content.trim() && !attachment) || isSending || disabled}
                    className="p-2.5 bg-primary-olive text-white rounded-full hover:bg-primary-dark transition-colors disabled:opacity-30 disabled:cursor-not-allowed flex-shrink-0"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
