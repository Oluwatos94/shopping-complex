import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/Admin/AdminLayout';
import { VendorApplication } from '@/types/vendor';
import { Paginated } from '@/types/product';
import VendorCard from '@/components/Admin/vendors/partials/VendorCard';
import DetailPanel from '@/components/Admin/vendors/partials/DetailPanel';
import RejectModal from '@/components/Admin/vendors/partials/RejectModal';

interface Props {
    vendors: Paginated<VendorApplication>;
    activeStatus: string;
}

export default function Vendors({ vendors, activeStatus }: Props) {
    const [selectedVendor, setSelectedVendor] = useState<VendorApplication | null>(null);
    const [rejectingVendor, setRejectingVendor] = useState<VendorApplication | null>(null);
    const [processing, setProcessing] = useState<number | null>(null);

    const filterTabs = [
        { label: 'Pending', value: 'pending_review' },
        { label: 'Approved', value: 'approved' },
        { label: 'Rejected', value: 'rejected' },
    ];

    const switchTab = (status: string) => {
        router.get('/admin/vendors/pending', { status }, { preserveScroll: false });
    };

    const handleApprove = (vendor: VendorApplication) => {
        setProcessing(vendor.user_id);
        router.post(
            `/admin/vendors/${vendor.user_id}/approve`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(null);
                    setSelectedVendor(null);
                },
            },
        );
    };

    const handleRejectConfirm = (reason: string) => {
        if (!rejectingVendor) return;
        const vendor = rejectingVendor;
        setRejectingVendor(null);
        setProcessing(vendor.user_id);
        router.post(
            `/admin/vendors/${vendor.user_id}/reject`,
            { rejection_reason: reason },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(null);
                    setSelectedVendor(null);
                },
            },
        );
    };

    const goToPage = (page: number) => {
        router.get('/admin/vendors/pending', { page, status: activeStatus }, { preserveScroll: true });
    };

    const pendingCount = vendors.total;
    const progressPct = Math.min(100, (pendingCount / Math.max(pendingCount, 50)) * 100);

    return (
        <>
            <Head title="Vendor Approval — Admin" />
            <AdminLayout>
                {/* Background decoration */}
                <div className="fixed top-0 left-64 right-0 h-full pointer-events-none -z-10 overflow-hidden">
                    <div className="absolute top-[-10%] right-[-5%] w-[40%] h-[60%] bg-primary-olive/5 rounded-full blur-[120px]" />
                    <div className="absolute bottom-[-5%] left-[20%] w-[30%] h-[50%] bg-primary-peach/5 rounded-full blur-[100px]" />
                </div>

                {/* Page Header */}
                <div className="flex justify-between items-end mb-12">
                    <div className="space-y-1">
                        <p className="text-primary-olive font-bold text-sm tracking-[0.2em] uppercase">
                            Operations
                        </p>
                        <h2 className="text-4xl font-extrabold tracking-tight text-gray-900">
                            Vendor Approval
                        </h2>
                    </div>
                    <div className="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                        {filterTabs.map(tab => (
                            <button
                                key={tab.value}
                                onClick={() => switchTab(tab.value)}
                                className={`px-5 py-2 rounded-lg text-sm font-medium transition-all ${
                                    activeStatus === tab.value
                                        ? 'bg-primary-olive text-white font-bold shadow-md shadow-primary-olive/20'
                                        : 'text-gray-500 hover:bg-white'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Stats Overview */}
                <div className="grid grid-cols-12 gap-5 mb-12">
                    {/* Main stat — earthy gradient */}
                    <div className="col-span-12 lg:col-span-8 bg-gradient-to-br from-primary-dark to-primary-brown p-8 rounded-xl flex items-center justify-between text-white shadow-xl shadow-primary-dark/20">
                        <div>
                            <p className="text-xs uppercase tracking-widest opacity-70 mb-2">
                                Total Applications in Review
                            </p>
                            <p className="text-6xl font-bold tracking-tight">{vendors.total}</p>
                        </div>
                        <div className="flex flex-col items-end gap-4">
                            <span className="bg-white/15 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold">
                                Pending Review
                            </span>
                            <div className="flex -space-x-2">
                                {['A', 'B', 'C'].map((l) => (
                                    <div
                                        key={l}
                                        className="w-9 h-9 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-[11px] font-bold"
                                    >
                                        {l}
                                    </div>
                                ))}
                                <div className="w-9 h-9 rounded-full bg-white/10 border-2 border-white/20 flex items-center justify-center text-[10px] font-bold">
                                    +{Math.max(0, pendingCount - 3)}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Progress card */}
                    <div className="col-span-12 lg:col-span-4 bg-white p-8 rounded-xl border border-gray-100 flex flex-col justify-between">
                        <div>
                            <p className="text-xs uppercase tracking-widest text-gray-400 mb-2">
                                Pending Review
                            </p>
                            <p className="text-4xl font-bold tracking-tight text-gray-900">{pendingCount}</p>
                        </div>
                        <div>
                            <div className="w-full bg-gray-100 rounded-full h-1.5">
                                <div
                                    className="bg-primary-brown h-1.5 rounded-full transition-all"
                                    style={{ width: `${progressPct}%` }}
                                />
                            </div>
                            <p className="text-[10px] text-gray-400 mt-1.5">
                                Showing page {vendors.current_page} of {vendors.last_page}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Application Cards Grid */}
                {vendors.data.length === 0 ? (
                    <div className="py-24 text-center">
                        <svg className="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-gray-400 font-medium">All caught up — no pending applications.</p>
                    </div>
                ) : (
                    <>
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                            {vendors.data.map((vendor) => (
                                <VendorCard
                                    key={vendor.id}
                                    vendor={vendor}
                                    onSelect={setSelectedVendor}
                                    onApprove={handleApprove}
                                    onReject={setRejectingVendor}
                                    processing={processing}
                                />
                            ))}

                            {/* Placeholder card */}
                            <div className="bg-gray-50 border-2 border-dashed border-gray-200 p-6 rounded-xl flex flex-col items-center justify-center text-center min-h-[280px] opacity-60">
                                <svg className="w-8 h-8 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                </svg>
                                <p className="text-sm font-medium text-gray-400">
                                    Select an application to<br />view full details
                                </p>
                            </div>
                        </div>

                        {/* Pagination */}
                        {vendors.last_page > 1 && (
                            <div className="mt-10 flex justify-center gap-3">
                                <button
                                    onClick={() => goToPage(vendors.current_page - 1)}
                                    disabled={vendors.current_page === 1}
                                    className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Previous
                                </button>
                                <button
                                    onClick={() => goToPage(vendors.current_page + 1)}
                                    disabled={vendors.current_page === vendors.last_page}
                                    className="px-6 py-2.5 rounded-full bg-white border border-gray-200 text-gray-600 font-bold text-xs uppercase tracking-widest hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                                >
                                    Load More
                                </button>
                            </div>
                        )}
                    </>
                )}
            </AdminLayout>

            <DetailPanel
                vendor={selectedVendor}
                onClose={() => setSelectedVendor(null)}
                onApprove={handleApprove}
                onReject={setRejectingVendor}
                processing={processing}
            />

            <RejectModal
                vendor={rejectingVendor}
                onClose={() => setRejectingVendor(null)}
                onConfirm={handleRejectConfirm}
            />
        </>
    );
}
