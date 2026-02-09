interface Props {
    type: 'no-conversations' | 'no-selection';
}

export default function EmptyState({ type }: Props) {
    if (type === 'no-conversations') {
        return (
            <div className="flex-1 flex flex-col items-center justify-center text-center px-6">
                <svg className="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <h3 className="text-lg font-semibold text-gray-500 mb-1">No messages yet</h3>
                <p className="text-sm text-gray-400">Start a conversation from a product page or vendor profile.</p>
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col items-center justify-center text-center px-6">
            <svg className="w-20 h-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <h3 className="text-lg font-semibold text-gray-500 mb-1">Select a conversation</h3>
            <p className="text-sm text-gray-400">Choose a conversation from the list to start messaging.</p>
        </div>
    );
}
