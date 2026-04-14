import { useRef, useEffect } from 'react';
import { AdminUser } from '@/types/user';

export default function RoleDropdown({
    user,
    onClose,
    onChangeRole,
}: {
    user: AdminUser;
    onClose: () => void;
    onChangeRole: (userId: number, role: string) => void;
}) {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) onClose();
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [onClose]);

    const roles = ['customer', 'vendor', 'admin'] as const;

    return (
        <div
            ref={ref}
            className="absolute right-0 top-full mt-1 w-44 bg-white border border-gray-100 rounded-xl shadow-xl z-20 py-1 animate-dropdown-in"
        >
            <p className="px-4 py-2 text-[10px] uppercase tracking-widest text-gray-400 font-bold border-b border-gray-50">
                Change Role
            </p>
            {roles.map((r) => (
                <button
                    key={r}
                    disabled={r === user.role}
                    onClick={() => { onChangeRole(user.id, r); onClose(); }}
                    className={`w-full text-left px-4 py-2.5 text-sm transition-colors capitalize ${
                        r === user.role
                            ? 'text-gray-300 cursor-default'
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                    }`}
                >
                    {r}
                    {r === user.role && (
                        <span className="ml-2 text-[10px] text-gray-300">(current)</span>
                    )}
                </button>
            ))}
        </div>
    );
}
