import { route as ziggyRoute, type Config as ZiggyConfig } from 'ziggy-js';

export function setupZiggy(ziggy: Partial<ZiggyConfig>) {
    const routeFn = (name: string, params?: Record<string, unknown>, absolute = false) => {
        return ziggyRoute(name, params, absolute, ziggy as ZiggyConfig);
    };

    (window as unknown as Record<string, typeof routeFn>).route = routeFn;

    return routeFn;
}
