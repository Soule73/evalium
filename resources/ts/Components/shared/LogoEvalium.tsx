interface LogoEvaliumProps {
    width?: number;
    height?: number;
}

const LogoEvalium = ({ width = 48, height = 48 }: LogoEvaliumProps) => {
    return (
        <>
            <svg width={width} height={height} viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="sidebarGemTop" x1="10" y1="6" x2="38" y2="24" gradientUnits="userSpaceOnUse">
                        <stop stopColor="#818cf8" />
                        <stop offset="1" stopColor="#6366f1" />
                    </linearGradient>
                    <linearGradient id="sidebarGemLeft" x1="10" y1="18" x2="24" y2="44" gradientUnits="userSpaceOnUse">
                        <stop stopColor="#4f46e5" />
                        <stop offset="1" stopColor="#3730a3" />
                    </linearGradient>
                    <linearGradient id="sidebarGemRight" x1="24" y1="24" x2="38" y2="44" gradientUnits="userSpaceOnUse">
                        <stop stopColor="#6366f1" />
                        <stop offset="1" stopColor="#4f46e5" />
                    </linearGradient>
                </defs>
                <path d="M10 18 L24 6 L38 18 L24 24 Z" fill="url(#sidebarGemTop)" />
                <path d="M10 18 L24 24 L24 44 Z" fill="url(#sidebarGemLeft)" />
                <path d="M38 18 L24 24 L24 44 Z" fill="url(#sidebarGemRight)" />
                <path d="M10 18 L24 6 L24 24 Z" fill="white" opacity="0.15" />
                <path d="M24 6 L38 18 L24 24 Z" fill="white" opacity="0.06" />
                <circle cx="17" cy="14" r="1.8" fill="white" opacity="0.5" />
            </svg>
            <span className="sr-only">Evalium</span>
        </>
    );
};

export { LogoEvalium };