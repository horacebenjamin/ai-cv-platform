/// <reference types="vite/client" />

import type { AxiosInstance } from 'axios';
import type { Auth } from '@/types';
import { route as routeFunction } from 'ziggy-js';
import '@inertiajs/core';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            auth: Auth;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFunction;
    }
}

declare global {
    const route: typeof routeFunction;

    interface Window {
        axios: AxiosInstance;
    }
}

export {};
