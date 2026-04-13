import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import RoleDropdown from '@/components/Admin/users/partials/RoleDropdown';
import InviteModal from '@/components/Admin/users/partials/InviteModal';
import { AdminUser, UserSummary } from '@/types/user';
import { Paginated } from '@/types/product';
import { formatDate } from '@/utils/date';
import { initials } from '@/utils/string';

interface Props {
    users: Paginated<AdminUser>;
    summary: UserSummary;
}

const ROLE_BADGE: Record<string, string> = {
    admin:    'bg-primary-brown/10 text-primary-brown',
    vendor:   'bg-primary-olive/10 text-primary-olive',
    customer: 'bg-gray-100 text-gray-600',
};


export default function Users({ users, summary }: Props) {
    const [activeRole, setActiveRole] = useState('');
    const [search, setSearch] = useState('');
    const [openDropdown, setOpenDropdown] = useState<number | null>(null);
    const [inviteOpen, setInviteOpen] = useState(false);

    const applyFilters = (overrides: { role?: string; search?: string; page?: number } = {}) => {
        const params: Record<string, string | number> = {};
        const r = overrides.role !== undefined ? overrides.role : activeRole;
        const q = overrides.search !== undefined ? overrides.search : search;
        if (r) params.role = r;
        if (q) params.search = q;
        if (overrides.page) params.page = overrides.page;
        router.get('/admin/users', params, { preserveScroll: true });
    };

    const handleRoleFilter = (role: string) => {
        setActiveRole(role);
        applyFilters({ role, page: 1 });
    };

    const handleSearch = (value: string) => {
        setSearch(value);
        applyFilters({ search: value, page: 1 });
    };

    const handleChangeRole = (userId: number, role: string) => {
        router.patch(`/admin/users/${userId}`, { role }, { preserveScroll: true });
    };

    const goToPage = (page: number) => applyFilters({ page });

    // Build visible page numbers
    const buildPages = () => {
        const total = users.last_page;
        const cur = users.current_page;
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages: (number | '...')[] = [1];
        if (cur > 3) pages.push('...');
        for (let p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) pages.push(p);
        if (cur < total - 2) pages.push('...');
        pages.push(total);
        return pages;
    };

    const roleTabs = [
        { label: 'All Users', value: '' },
        { label: 'Customers', value: 'customer' },
        { label: 'Vendors', value: 'vendor' },
        { label: 'Admins', value: 'admin' },
    ];

    return (
        <>
            <Head title="User Management — Admin" />
            <AdminLayout>
                {/* Page Header */}
                <div className="flex justify-between items-end mb-10">
                    <div>
                        <h2 className="text-4xl font-extrabold tracking-tight text-gray-900 mb-2">
                            User Management
                        </h2>
                        <p className="text-gray-500 text-base">
                            Manage permissions, monitor activity and oversee account lifecycle.
                        </p>
                    </div>
                    <button
                        onClick={() => setInviteOpen(true)}
                        className="bg-primary-olive text-white px-5 py-3 rounded-lg font-bold flex items-center gap-2 active:scale-95 transition-transform hover:brightness-110 shadow-lg shadow-primary-olive/20"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Invite Admin
                    </button>
                </div>

                {/* Stats Bento */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
                    {/* Total — gradient */}
                    <div className="bg-gradient-to-br from-primary-dark to-primary-brown p-6 rounded-xl text-white shadow-lg shadow-primary-dark/20">
                        <p className="text-[10px] uppercase tracking-widest font-bold opacity-70 mb-2">
                            Total Directory
                        </p>
                        <h3 className="text-3xl font-bold">{summary.users.total.toLocaleString()}</h3>
                        <div className="mt-4 flex items-center text-xs bg-white/10 w-fit px-2.5 py-1 rounded-full gap-1">
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 15l7-7 7 7" />
                            </svg>
                            Registered accounts
                        </div>
                    </div>

                    {/* Verified Vendors */}
                    <div className="bg-white p-6 rounded-xl border border-gray-100">
                        <p className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-2">
                            Verified Vendors
                        </p>
                        <h3 className="text-3xl font-bold text-gray-900">
                            {summary.vendors.approved.toLocaleString()}
                        </h3>
                        <p className="text-xs text-primary-olive mt-4 font-medium">
                            {summary.vendors.pending_review} pending approval
                        </p>
                    </div>

                    {/* Active Admins */}
                    <div className="bg-white p-6 rounded-xl border border-gray-100">
                        <p className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-2">
                            Active Admins
                        </p>
                        <h3 className="text-3xl font-bold text-gray-900">
                            {summary.users.admins.toLocaleString()}
                        </h3>
                        <p className="text-xs text-gray-400 mt-4 font-medium">Platform administrators</p>
                    </div>

                    {/* Customers */}
                    <div className="bg-white p-6 rounded-xl border border-gray-100">
                        <p className="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-2">
                            Customers
                        </p>
                        <h3 className="text-3xl font-bold text-gray-900">
                            {summary.users.customers.toLocaleString()}
                        </h3>
                        <p className="text-xs text-gray-400 mt-4 font-medium">Registered buyers</p>
                    </div>
                </div>

                {/* Filters Bar */}
                <div className="flex flex-wrap items-center justify-between gap-4 mb-5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <div className="flex items-center gap-1">
                        {roleTabs.map((tab) => (
                            <button
                                key={tab.value}
                                onClick={() => handleRoleFilter(tab.value)}
                                className={`px-4 py-2 rounded-lg font-medium text-sm transition-colors ${
                                    activeRole === tab.value
                                        ? 'bg-white text-primary-olive border border-primary-olive/20 shadow-sm font-bold'
                                        : 'text-gray-500 hover:text-gray-800'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                    <div className="relative">
                        <svg
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            placeholder="Search accounts, emails..."
                            value={search}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="bg-white border border-gray-200/60 rounded-lg py-2 pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary-olive/20 placeholder:text-gray-300 w-60"
                        />
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl border border-gray-100 overflow-hidden">
                    {users.data.length === 0 ? (
                        <div className="py-20 text-center">
                            <svg className="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <p className="text-gray-400 text-sm">No users found.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="bg-gray-50/60 border-b border-gray-100">
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            User Profile
                                        </th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            Role
                                        </th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            Status
                                        </th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            Date Joined
                                        </th>
                                        <th className="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-50">
                                    {users.data.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="hover:bg-gray-50/40 transition-colors group"
                                        >
                                            {/* Profile */}
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-primary-olive/10 flex items-center justify-center flex-shrink-0">
                                                        <span className="text-primary-olive text-xs font-bold">
                                                            {initials(user.name)}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-bold text-gray-900">{user.name}</p>
                                                        <p className="text-xs text-gray-400">{user.email}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Role */}
                                            <td className="px-6 py-4">
                                                <span
                                                    className={`text-xs font-semibold px-2.5 py-1 rounded-full capitalize ${
                                                        ROLE_BADGE[user.role] ?? 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {user.role}
                                                </span>
                                            </td>

                                            {/* Status */}
                                            <td className="px-6 py-4">
                                                <div className="flex items-center text-xs font-bold text-emerald-600">
                                                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2" />
                                                    Active
                                                </div>
                                            </td>

                                            {/* Date */}
                                            <td className="px-6 py-4 text-xs text-gray-400 font-medium whitespace-nowrap">
                                                {formatDate(user.created_at)}
                                            </td>

                                            {/* Actions */}
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-1 relative">
                                                    {/* View */}
                                                    <button
                                                        title="View profile"
                                                        className="p-2 text-gray-400 hover:text-primary-olive hover:bg-primary-olive/5 rounded-lg transition-all"
                                                    >
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>

                                                    {/* Block / placeholder */}
                                                    <button
                                                        title="Block user"
                                                        className="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                                    >
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                    </button>

                                                    {/* More / role change dropdown */}
                                                    <div className="relative">
                                                        <button
                                                            title="More options"
                                                            onClick={() =>
                                                                setOpenDropdown(
                                                                    openDropdown === user.id ? null : user.id,
                                                                )
                                                            }
                                                            className="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-all"
                                                        >
                                                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M12 5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                                            </svg>
                                                        </button>
                                                        {openDropdown === user.id && (
                                                            <RoleDropdown
                                                                user={user}
                                                                onClose={() => setOpenDropdown(null)}
                                                                onChangeRole={handleChangeRole}
                                                            />
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Table Footer */}
                    <div className="flex items-center justify-between px-6 py-4 bg-gray-50/40 border-t border-gray-100">
                        <p className="text-xs text-gray-400 font-medium">
                            Showing{' '}
                            <span className="font-bold text-gray-700">{users.data.length}</span>{' '}
                            of{' '}
                            <span className="font-bold text-gray-700">{users.total.toLocaleString()}</span>{' '}
                            users
                        </p>
                        {users.last_page > 1 && (
                            <div className="flex items-center gap-1">
                                <button
                                    onClick={() => goToPage(users.current_page - 1)}
                                    disabled={users.current_page === 1}
                                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                {buildPages().map((p, i) =>
                                    p === '...' ? (
                                        <span key={`dots-${i}`} className="px-1 text-gray-400 text-xs">
                                            …
                                        </span>
                                    ) : (
                                        <button
                                            key={p}
                                            onClick={() => goToPage(p as number)}
                                            className={`w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-colors ${
                                                p === users.current_page
                                                    ? 'bg-primary-olive text-white'
                                                    : 'text-gray-500 hover:bg-gray-100'
                                            }`}
                                        >
                                            {p}
                                        </button>
                                    ),
                                )}

                                <button
                                    onClick={() => goToPage(users.current_page + 1)}
                                    disabled={users.current_page === users.last_page}
                                    className="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </AdminLayout>

            <InviteModal open={inviteOpen} onClose={() => setInviteOpen(false)} />
        </>
    );
}
