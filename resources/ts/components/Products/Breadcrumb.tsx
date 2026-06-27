import { Link } from '@inertiajs/react';

export interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface BreadcrumbProps {
    items: BreadcrumbItem[];
}

export default function Breadcrumb({ items }: BreadcrumbProps) {
    return (
        <nav aria-label="Breadcrumb" className="py-4">
            <ol className="flex items-center flex-wrap gap-2 text-sm">
                {/* Home Link */}
                <li className="flex items-center">
                    <Link
                        href="/"
                        className="text-gray-500 hover:text-brand-green transition-colors flex items-center gap-1"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span className="sr-only sm:not-sr-only">Home</span>
                    </Link>
                </li>

                {/* Breadcrumb Items */}
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;

                    return (
                        <li key={index} className="flex items-center">
                            {/* Separator */}
                            <svg
                                className="w-4 h-4 text-gray-400 mx-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>

                            {/* Breadcrumb Link or Text */}
                            {isLast || !item.href ? (
                                <span
                                    className={`${
                                        isLast
                                            ? 'text-gray-900 font-medium'
                                            : 'text-gray-500'
                                    } max-w-[200px] truncate`}
                                    aria-current={isLast ? 'page' : undefined}
                                >
                                    {item.label}
                                </span>
                            ) : (
                                <Link
                                    href={item.href}
                                    className="text-gray-500 hover:text-brand-green transition-colors max-w-[200px] truncate"
                                >
                                    {item.label}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
