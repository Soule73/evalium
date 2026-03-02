/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    // Add other VITE_ environment variables here as needed
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
