import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { setupZiggy } from '@/utils';
import { ToastContainer, ToastProvider } from './Components';

const appName = import.meta.env.VITE_APP_NAME || 'ExamENA';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx', { eager: false }),
        ),
    setup({ el, App, props }) {
        if (props.initialPage.props.ziggy) {
            setupZiggy(props.initialPage.props.ziggy);
        }

        const root = createRoot(el);
        root.render(
            <ToastProvider defaultPosition="top-right">
                <App {...props} />
                <ToastContainer />
            </ToastProvider>
        );
    },
    progress: {
        color: '#4F46E5',
    },
});