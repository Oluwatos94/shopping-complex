import React from "react";

export default function Layout({ children }: { children: React.ReactNode }) {
    return (
        <main className="bg-black-base text-white">
            <div className="flex items-center justify-center">
                <div className="flex flex-col border w-full max-w-md h-screen">
                    <div className="flex-grow">{children}</div>
                    <div className="h-20 border"></div>
                </div>
            </div>
        </main>
    );
}
