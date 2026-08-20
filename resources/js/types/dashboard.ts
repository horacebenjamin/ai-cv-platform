export interface DashboardOverview {
    totalCvs: number;
    activeApplications: number;
    savedJobs: number;
    coverLetters: number;
    interviewProcesses: number;
}

import type { ProfileCompleteness } from './career-profile';

export interface DashboardCredits {
    available: number | null;
    plan: string | null;
    renewalDate: string | null;
    used: number;
}

export interface RecentCv {
    id: number;
    title: string;
    status: string;
    targetJobTitle: string | null;
    historyCount: number;
    updatedAt: string | null;
}

export interface RecentApplication {
    id: number;
    company: string;
    role: string | null;
    status: string;
    appliedAt: string | null;
    updatedAt: string | null;
}

export interface RecentAiRequest {
    id: number;
    feature: string;
    status: string;
    tokensUsed: number;
    createdAt: string | null;
}

export interface NextFocus {
    key: string;
    title: string;
    description: string;
}

export interface DashboardProps {
    overview: DashboardOverview;
    profile: ProfileCompleteness;
    credits: DashboardCredits;
    recentCvs: RecentCv[];
    recentApplications: RecentApplication[];
    recentAiRequests: RecentAiRequest[];
    nextFocus: NextFocus;
}
