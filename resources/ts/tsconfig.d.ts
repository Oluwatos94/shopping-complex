/// <reference types="vite/client" />

interface ImportMeta {
    glob: ImportGlobFunction;
}

export interface ImportGlobFunction {
    (pattern: string, options?: { eager?: boolean }): Record<string, any>;
}
