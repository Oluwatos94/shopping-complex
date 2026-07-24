import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import Layout from "./layouts";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./pages/**/*.tsx", { eager: true });
        const path = `./pages/${name}.tsx`;

        // Tolerate casing differences between the server-side page name and the
        // on-disk folder/file (e.g. render('auth/Login') vs pages/Auth/Login.tsx).
        const key =
            path in pages
                ? path
                : Object.keys(pages).find((k) => k.toLowerCase() === path.toLowerCase());
        const page: any = key ? pages[key] : undefined;

        if (!page) {
            const available = Object.keys(pages)
                .map((k) => k.replace("./pages/", "").replace(".tsx", ""))
                .join(", ");
            throw new Error(`[Inertia] Page not found: "${name}" (looked for ${path}). Available pages: ${available}`);
        }

        page.default.layout =
            page.default.layout || ((page: any) => <Layout children={page} />);

        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});