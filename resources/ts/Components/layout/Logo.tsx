
interface LogoProps {
    className?: string
}

export const Logo = ({ className = ' w-12 h-12 ' }: LogoProps) => {
    return (
        <svg className={className} viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="4" y="8" width="40" height="32" rx="6" fill="#2563eb" />
            <rect x="10" y="14" width="28" height="20" rx="3" fill="#fff" />
            <rect x="14" y="18" width="20" height="3" rx="1.5" fill="#2563eb" />
            <rect x="14" y="24" width="12" height="2.5" rx="1.25" fill="#c7d2fe" />
            <rect x="14" y="29" width="8" height="2.5" rx="1.25" fill="#c7d2fe" />
            <circle cx="38" cy="34" r="3" fill="#2563eb" stroke="#fff" strokeWidth="2" />
        </svg>

    );
};