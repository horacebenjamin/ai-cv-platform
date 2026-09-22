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

export interface ProposedProfileField {
    key: string;
    label: string;
    value: string;
    currentValue: string | null;
    isBlank: boolean;
}

export interface ProposedExperience {
    job_title: string;
    company: string;
    location: string | null;
    employment_type: string | null;
    start_date: string | null;
    end_date: string | null;
    currently_employed: boolean;
    summary: string | null;
    achievements: string[];
    technologies: string[];
}

export interface ProposedSkill {
    name: string;
    category: string | null;
    proficiency: string | null;
}

export interface ProposedProject {
    name: string;
    role: string | null;
    description: string | null;
    outcomes: string | null;
    technologies: string[];
    url: string | null;
    repository_url: string | null;
    start_date: string | null;
    end_date: string | null;
}

export interface ProposedEducation {
    institution: string;
    qualification: string;
    subject: string | null;
    grade: string | null;
    start_date: string | null;
    end_date: string | null;
}

export interface ProposedCertification {
    name: string;
    organisation: string | null;
    issue_date: string | null;
    expiry_date: string | null;
    credential_id: string | null;
    credential_url: string | null;
}

export interface ProfileImportProps {
    import: {
        id: number;
        status: string;
        sourceType: string;
        createdAt: string | null;
        appliedAt: string | null;
        skippedCount: number;
    };
    proposed: {
        professional: ProposedProfileField[];
        experiences: ProposedExperience[];
        skills: ProposedSkill[];
        projects: ProposedProject[];
        education: ProposedEducation[];
        certifications: ProposedCertification[];
    };
}
