
import { useId } from 'react';

interface LogoProps {
    showIcon?: boolean;
    showName?: boolean;
    showSlogan?: boolean;
    variant?: 'horizontal' | 'vertical';
    width?: number;
    height?: number;
    className?: string;
}

/**
 * Unified brand logo component supporting icon, name, and slogan display
 * in horizontal or vertical layout.
 */
export const Logo = ({
    showIcon = true,
    showName = false,
    showSlogan = false,
    variant = 'horizontal',
    width = 48,
    height = 48,
    className,
}: LogoProps) => {
    const id = useId();
    const topId = `${id}-gem-top`;
    const leftId = `${id}-gem-left`;
    const rightId = `${id}-gem-right`;

    const isVertical = variant === 'vertical';

    return (
        <div className={`flex ${isVertical ? 'flex-col items-center' : 'items-center'} ${className ?? ''}`}>
            {showIcon && (
                <svg
                    width={width}
                    height={height}
                    viewBox="0 0 48 48"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >
                    <defs>
                        <linearGradient id={topId} x1="10" y1="6" x2="38" y2="24" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#818cf8" />
                            <stop offset="1" stopColor="#6366f1" />
                        </linearGradient>
                        <linearGradient id={leftId} x1="10" y1="18" x2="24" y2="44" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#4f46e5" />
                            <stop offset="1" stopColor="#3730a3" />
                        </linearGradient>
                        <linearGradient id={rightId} x1="24" y1="24" x2="38" y2="44" gradientUnits="userSpaceOnUse">
                            <stop stopColor="#6366f1" />
                            <stop offset="1" stopColor="#4f46e5" />
                        </linearGradient>
                    </defs>
                    <path d="M10 18 L24 6 L38 18 L24 24 Z" fill={`url(#${topId})`} />
                    <path d="M10 18 L24 24 L24 44 Z" fill={`url(#${leftId})`} />
                    <path d="M38 18 L24 24 L24 44 Z" fill={`url(#${rightId})`} />
                    <path d="M10 18 L24 6 L24 24 Z" fill="white" opacity="0.15" />
                    <path d="M24 6 L38 18 L24 24 Z" fill="white" opacity="0.06" />
                    <circle cx="17" cy="14" r="1.8" fill="white" opacity="0.5" />
                </svg>
            )}

            {(showName || showSlogan) && (
                <div className={showIcon ? (isVertical ? 'mt-2 text-center' : 'ml-2') : ''}>
                    {showName && (
                        <span className="text-xl font-bold text-indigo-600">Evalium</span>
                    )}
                    {showSlogan && (
                        <p className={`text-xs italic text-indigo-500 ${isVertical ? '' : 'mt-0.5'}`}>
                            Where every grade tells a story
                        </p>
                    )}
                </div>
            )}

            <span className="sr-only">Evalium</span>
        </div>
    );
};