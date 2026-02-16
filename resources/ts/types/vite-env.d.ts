/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;
    // Ajoutez d'autres variables d'environnement VITE_ si nécessaire
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
