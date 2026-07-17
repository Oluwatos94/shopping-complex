import { formatTime } from '@/utils/date';
import type { SupportMessageRole } from '@/types/support';

interface Props {
    role: SupportMessageRole;
    content: string;
    timestamp?: string;
}

function Linkified({ content, isUser }: { content: string; isUser: boolean }) {
    const parts = content.split(/(https?:\/\/[^\s]+)/g);

    return (
        <>
            {parts.map((part, i) => {
                if (!/^https?:\/\//.test(part)) return part;

                // Trailing punctuation belongs to the sentence, not the URL.
                const trailing = part.match(/[.,!?;:'")\]]+$/)?.[0] ?? '';
                const url = trailing ? part.slice(0, -trailing.length) : part;

                return (
                    <span key={i}>
                        <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={`break-all underline underline-offset-2 ${isUser ? 'text-white' : 'text-brand-green hover:text-brand-green-dark'}`}
                        >
                            {url}
                        </a>
                        {trailing}
                    </span>
                );
            })}
        </>
    );
}

export default function MessageRow({ role, content, timestamp }: Props) {
    const isUser = role === 'user';
    const isAgent = role === 'agent';

    const bubbleStyle = isUser
        ? 'bg-brand-green text-white rounded-br-md'
        : isAgent
        ? 'bg-brand-ink/5 text-gray-900 border border-brand-ink/10 rounded-bl-md'
        : 'bg-gray-100 text-gray-800 rounded-bl-md';

    return (
        <div className={`flex flex-col ${isUser ? 'items-end' : 'items-start'}`}>
            {isAgent && (
                <span className="mb-1 inline-flex items-center gap-1 rounded-full bg-brand-ink px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                    <svg className="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                    Agent
                </span>
            )}
            <div className={`max-w-[80%] whitespace-pre-wrap break-words rounded-2xl px-3.5 py-2 text-sm leading-relaxed ${bubbleStyle}`}>
                <Linkified content={content} isUser={isUser} />
            </div>
            {timestamp && (
                <span className="mt-1 text-[10px] text-brand-muted">{formatTime(timestamp)}</span>
            )}
        </div>
    );
}
