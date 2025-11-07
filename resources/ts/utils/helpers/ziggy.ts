import { route as ziggyRoute } from 'ziggy-js';

export function setupZiggy(ziggy: any) {
    (window as any).route = (name: string, params?: any, absolute = false) => {
        return ziggyRoute(name, params, absolute, ziggy);
    };

    return (window as any).route;
}