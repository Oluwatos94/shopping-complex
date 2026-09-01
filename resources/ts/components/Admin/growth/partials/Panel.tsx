import { ReactNode } from 'react';

interface Props {
    title: string;
    subtitle?: string;
    children: ReactNode;
}

export default function Panel({ title, subtitle, children }: Props) {
    return (
        <div className="bg-white p-6 rounded-xl border border-gray-100">
            <div className="mb-5">
                <h3 className="font-bold text-gray-900">{title}</h3>
                {subtitle && <p className="text-xs text-gray-500 mt-0.5">{subtitle}</p>}
            </div>
            {children}
        </div>
    );
}
