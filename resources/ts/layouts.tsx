import React from "react";
import SupportWidget from "@/components/Support/SupportWidget";

export default function Layout({ children }: { children: React.ReactNode }) {
    return (
        <>
            {children}
            <SupportWidget />
        </>
    );
}
