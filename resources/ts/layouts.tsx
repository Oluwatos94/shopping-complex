import React, { useEffect, useState } from "react";
import { router } from "@inertiajs/react";

function PageLoader() {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const startHandler = router.on("start", () => setLoading(true));
        const finishHandler = router.on("finish", () => setLoading(false));
        return () => {
            startHandler();
            finishHandler();
        };
    }, []);

    if (!loading) return null;

    return (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-white/40 backdrop-blur-[1px] pointer-events-none">
            <div className="w-10 h-10 rounded-full border-4 border-gray-200 border-t-primary-olive animate-spin" />
        </div>
    );
}

export default function Layout({ children }: { children: React.ReactNode }) {
    return (
        <>
            <PageLoader />
            {children}
        </>
    );
}
