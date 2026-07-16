import type { SupportMessageRole } from '@/types/support';

interface Props {
    role: SupportMessageRole;
    content: string;
}

export default function MessageRow({ role, content }: Props) {
    const isUser = role === 'user';

    return (
        <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
            <div
                className={`max-w-[80%] rounded-2xl px-3.5 py-2 text-sm leading-relaxed ${
                    isUser
                        ? 'bg-brand-green text-white rounded-br-md'
                        : 'bg-gray-100 text-gray-800 rounded-bl-md'
                }`}
            >
                {content}
            </div>
        </div>
    );
}
