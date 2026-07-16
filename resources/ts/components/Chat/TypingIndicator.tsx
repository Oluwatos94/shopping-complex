interface Props {
    wrapperClassName?: string;
    bubbleClassName?: string;
}

export default function TypingIndicator({
    wrapperClassName = 'flex items-center gap-1 px-4 py-2 ml-2',
    bubbleClassName = 'bg-[#f5f0e8]',
}: Props) {
    return (
        <div className={wrapperClassName}>
            <div className={`rounded-2xl rounded-bl-md px-4 py-3 flex items-center gap-1 ${bubbleClassName}`}>
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:0ms]" />
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:150ms]" />
                <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce [animation-delay:300ms]" />
            </div>
        </div>
    );
}
