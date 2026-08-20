export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
}

export interface Auth {
    user: User | null;
}

export interface PageProps {
    auth: Auth;
}

export type {
    DashboardCredits,
    DashboardOverview,
    DashboardProps,
    NextFocus,
    ProfileCompleteness,
    RecentAiRequest,
    RecentApplication,
    RecentCv,
} from './dashboard';
