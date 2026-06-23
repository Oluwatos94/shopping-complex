import React from 'react';

interface TrustItem {
    label: string;
    icon: React.ReactNode;
}

const items: TrustItem[] = [
    {
        label: 'Trusted by local businesses',
        icon: (
            <>
                <path d="M12 3 4 6v6c0 4.4 3.4 7.9 8 9 4.6-1.1 8-4.6 8-9V6l-8-3Z" />
                <path d="m9 12 2 2 4-4" />
            </>
        ),
    },
    {
        label: 'Real time discovery',
        icon: <path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" />,
    },
    {
        label: 'GPS-powered matching',
        icon: <path d="M21 3 3 10.5l7.5 2.5L13 21 21 3Z" />,
    },
    {
        label: 'Whatsapp-native chat',
        icon: (
            <>
                <path d="M3.5 20.5 5 16a8 8 0 1 1 3 3l-4.5 1.5Z" />
                <path d="M9 9.5c0 3 2.5 5.5 5.5 5.5" strokeDasharray="0.1 4" />
            </>
        ),
    },
];

const TrustBar: React.FC = () => {
    return (
        <section className="border-y border-brand-line/70 bg-brand-surface font-display text-brand-ink">
            <div className="mx-auto max-w-[1320px] px-6 py-7 lg:px-10">
                <ul className="flex flex-wrap items-center justify-center gap-x-16 gap-y-5 sm:justify-between">
                    {items.map((item) => (
                        <li key={item.label} className="flex items-center gap-2.5">
                            <svg
                                className="h-5 w-5 text-brand-green"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth={1.8}
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                {item.icon}
                            </svg>
                            <span className="text-[15px] font-medium text-brand-muted">{item.label}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
};

export default TrustBar;
