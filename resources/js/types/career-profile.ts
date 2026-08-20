export interface ProfileArea {
    key: string;
    label: string;
}

export interface ProfileSectionArea extends ProfileArea {
    status: 'complete' | 'incomplete';
}

export interface ProfileSectionCompleteness {
    attentionCount: number;
    summary: string;
    areas: ProfileSectionArea[];
}

export interface ProfileCompleteness {
    exists: boolean;
    percentage: number;
    completedFields: number;
    totalFields: number;
    completedAreas: ProfileArea[];
    missingAreas: ProfileArea[];
    missingFields: string[];
    sectionCompleteness: ProfileSectionCompleteness;
}

export interface CareerProfileData {
    firstName: string | null;
    lastName: string | null;
    headline: string | null;
    phone: string | null;
    location: string | null;
    website: string | null;
    linkedinUrl: string | null;
    githubUrl: string | null;
    portfolioUrl: string | null;
    bio: string | null;
}

export interface CareerProfileProps {
    profile: CareerProfileData;
    completeness: ProfileCompleteness;
}
