import { useEffect, useRef, useState, useCallback } from 'react';

type RevealAnimation =
    | 'fade-up'
    | 'fade-down'
    | 'fade-left'
    | 'fade-right'
    | 'scale-up'
    | 'fade-in';

interface ScrollRevealOptions {
    threshold?: number;
    rootMargin?: string;
    once?: boolean;
    animation?: RevealAnimation;
    delay?: number;
}

interface ScrollRevealReturn {
    ref: React.RefObject<HTMLDivElement | null>;
    isVisible: boolean;
    className: string;
}

/**
 * Hook that triggers CSS animations when an element enters the viewport via IntersectionObserver.
 *
 * @param options - Configuration for threshold, animation type, delay and one-time trigger.
 * @returns Ref to attach, visibility state, and CSS class string for the element.
 */
export function useScrollReveal(options: ScrollRevealOptions = {}): ScrollRevealReturn {
    const {
        threshold = 0.15,
        rootMargin = '0px 0px -60px 0px',
        once = true,
        animation = 'fade-up',
        delay = 0,
    } = options;

    const ref = useRef<HTMLDivElement | null>(null);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const element = ref.current;
        if (!element) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsVisible(true);
                    if (once) {
                        observer.unobserve(element);
                    }
                } else if (!once) {
                    setIsVisible(false);
                }
            },
            { threshold, rootMargin },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, [threshold, rootMargin, once]);

    const delayStyle = delay > 0 ? `scroll-reveal-delay-${delay}` : '';
    const baseClass = isVisible
        ? `scroll-reveal-visible scroll-reveal-${animation}`
        : 'scroll-reveal-hidden';
    const className = [baseClass, delayStyle].filter(Boolean).join(' ');

    return { ref, isVisible, className };
}

interface StaggeredRevealOptions {
    baseDelay?: number;
    staggerDelay?: number;
    threshold?: number;
    rootMargin?: string;
    animation?: RevealAnimation;
}

interface StaggeredItemReturn {
    className: string;
    style: React.CSSProperties;
}

interface StaggeredRevealReturn {
    containerRef: React.RefObject<HTMLDivElement | null>;
    isVisible: boolean;
    getItemProps: (index: number) => StaggeredItemReturn;
}

/**
 * Hook for staggered scroll animations on a group of child elements.
 *
 * @param itemCount - Number of items to stagger.
 * @param options - Configuration for delays, threshold, and animation type.
 * @returns Container ref, visibility state, and a function to get props for each item.
 */
export function useStaggeredReveal(
    _itemCount: number,
    options: StaggeredRevealOptions = {},
): StaggeredRevealReturn {
    const {
        baseDelay = 0,
        staggerDelay = 120,
        threshold = 0.1,
        rootMargin = '0px 0px -40px 0px',
        animation = 'fade-up',
    } = options;

    const containerRef = useRef<HTMLDivElement | null>(null);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const element = containerRef.current;
        if (!element) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsVisible(true);
                    observer.unobserve(element);
                }
            },
            { threshold, rootMargin },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, [threshold, rootMargin]);

    const getItemProps = useCallback(
        (index: number): StaggeredItemReturn => ({
            className: isVisible
                ? `scroll-reveal-visible scroll-reveal-${animation}`
                : 'scroll-reveal-hidden',
            style: {
                transitionDelay: isVisible ? `${baseDelay + index * staggerDelay}ms` : '0ms',
            },
        }),
        [isVisible, animation, baseDelay, staggerDelay],
    );

    return { containerRef, isVisible, getItemProps };
}

interface ScrollSlideshowReturn {
    wrapperRef: React.RefObject<HTMLDivElement | null>;
    activeIndex: number;
    progress: number;
}

/**
 * Hook that drives a sticky slideshow based on scroll position within a tall wrapper.
 *
 * The wrapper should be `(slideCount + 1) * 100vh` tall. A sticky child fills the viewport.
 * As the user scrolls through the wrapper, `activeIndex` cycles through slides
 * and `progress` tracks 0-1 within each slide.
 *
 * @param slideCount - Total number of slides.
 * @param navHeight - Height in px of any fixed/sticky nav to offset from the top.
 * @returns Wrapper ref, current active slide index, and per-slide scroll progress.
 */
export function useScrollSlideshow(slideCount: number, navHeight = 64): ScrollSlideshowReturn {
    const wrapperRef = useRef<HTMLDivElement | null>(null);
    const [activeIndex, setActiveIndex] = useState(0);
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        const wrapper = wrapperRef.current;
        if (!wrapper) return;

        let rafId: number;
        let ticking = false;

        const update = () => {
            const rect = wrapper.getBoundingClientRect();
            const wrapperHeight = wrapper.offsetHeight;
            const viewportHeight = window.innerHeight;
            const scrollableDistance = wrapperHeight - viewportHeight;

            if (scrollableDistance <= 0) {
                ticking = false;
                return;
            }

            const scrolled = -(rect.top - navHeight);
            const clampedScroll = Math.max(0, Math.min(scrolled, scrollableDistance));
            const overallProgress = clampedScroll / scrollableDistance;

            const rawIndex = overallProgress * slideCount;
            const newIndex = Math.min(Math.floor(rawIndex), slideCount - 1);
            const slideProgress = rawIndex - newIndex;

            setActiveIndex(newIndex);
            setProgress(Math.min(slideProgress, 1));
            ticking = false;
        };

        const handleScroll = () => {
            if (!ticking) {
                ticking = true;
                rafId = requestAnimationFrame(update);
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        update();

        return () => {
            window.removeEventListener('scroll', handleScroll);
            cancelAnimationFrame(rafId);
        };
    }, [slideCount, navHeight]);

    return { wrapperRef, activeIndex, progress };
}
