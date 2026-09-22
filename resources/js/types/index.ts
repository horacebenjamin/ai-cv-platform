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
    CareerProfileData,
    CareerProfileOptions,
    CareerProfileProps,
    CareerProfileSections,
    CareerProfileTab,
    ProfileArea,
    ProfileCertificationItem,
    ProfileCompleteness,
    ProfileEducationItem,
    ProfileExperienceItem,
    ProfileProjectItem,
    ProfileSectionArea,
    ProfileSectionCompleteness,
    ProfileSkillItem,
} from './career-profile';

export type {
    DashboardCredits,
    DashboardOverview,
    DashboardProps,
    NextFocus,
    RecentAiRequest,
    RecentApplication,
    RecentCv,
} from './dashboard';
