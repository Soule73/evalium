import { useState, useRef, useEffect, useCallback, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

export type TooltipPosition = 'top' | 'right' | 'bottom' | 'left';

export interface TooltipProps {
  content: ReactNode;
  children: ReactNode;
  position?: TooltipPosition;
  delay?: number;
  disabled?: boolean;
  className?: string;
}

/**
 * Reusable tooltip component rendered via Portal with fixed positioning.
 * Immune to parent overflow clipping. Supports four positions with auto-flip.
 */
const Tooltip = ({
  content,
  children,
  position = 'right',
  delay = 200,
  disabled = false,
  className,
}: TooltipProps) => {
  const [isVisible, setIsVisible] = useState(false);
  const [coords, setCoords] = useState({ top: 0, left: 0 });
  const [resolvedPosition, setResolvedPosition] = useState(position);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const triggerRef = useRef<HTMLDivElement>(null);
  const tooltipRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  const calculatePosition = useCallback(() => {
    if (!triggerRef.current || !tooltipRef.current) return;

    const triggerRect = triggerRef.current.getBoundingClientRect();
    const tooltipRect = tooltipRef.current.getBoundingClientRect();
    let resolved = position;

    if (position === 'right' && triggerRect.right + tooltipRect.width + 8 > window.innerWidth) {
      resolved = 'left';
    } else if (position === 'left' && triggerRect.left - tooltipRect.width - 8 < 0) {
      resolved = 'right';
    } else if (position === 'top' && triggerRect.top - tooltipRect.height - 8 < 0) {
      resolved = 'bottom';
    } else if (position === 'bottom' && triggerRect.bottom + tooltipRect.height + 8 > window.innerHeight) {
      resolved = 'top';
    }

    setResolvedPosition(resolved);

    const positionMap: Record<TooltipPosition, { top: number; left: number }> = {
      top: {
        top: triggerRect.top - tooltipRect.height - 8,
        left: triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2,
      },
      bottom: {
        top: triggerRect.bottom + 8,
        left: triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2,
      },
      right: {
        top: triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2,
        left: triggerRect.right + 8,
      },
      left: {
        top: triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2,
        left: triggerRect.left - tooltipRect.width - 8,
      },
    };

    setCoords(positionMap[resolved]);
  }, [position]);

  const handleMouseEnter = useCallback(() => {
    if (disabled) return;
    timeoutRef.current = setTimeout(() => {
      setIsVisible(true);
    }, delay);
  }, [disabled, delay]);

  const handleMouseLeave = useCallback(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }
    setIsVisible(false);
  }, []);

  useEffect(() => {
    if (isVisible) {
      requestAnimationFrame(calculatePosition);
    }
  }, [isVisible, calculatePosition]);

  const arrowStyles: Record<TooltipPosition, string> = {
    top: 'top-full left-1/2 -translate-x-1/2 border-t-gray-900 border-x-transparent border-b-transparent',
    right: 'right-full top-1/2 -translate-y-1/2 border-r-gray-900 border-y-transparent border-l-transparent',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-gray-900 border-x-transparent border-t-transparent',
    left: 'left-full top-1/2 -translate-y-1/2 border-l-gray-900 border-y-transparent border-r-transparent',
  };

  if (disabled) {
    return <>{children}</>;
  }

  return (
    <div
      ref={triggerRef}
      className={`inline-flex ${className ?? ''}`}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
      onFocus={handleMouseEnter}
      onBlur={handleMouseLeave}
    >
      {children}
      {isVisible && content && createPortal(
        <div
          ref={tooltipRef}
          role="tooltip"
          className="fixed z-[9999] whitespace-nowrap px-2.5 py-1.5 text-xs font-medium text-white bg-gray-900 rounded-md shadow-lg pointer-events-none"
          style={{ top: coords.top, left: coords.left }}
        >
          {content}
          <span
            className={`absolute w-0 h-0 border-4 ${arrowStyles[resolvedPosition]}`}
            aria-hidden="true"
          />
        </div>,
        document.body,
      )}
    </div>
  );
};

export default Tooltip;
