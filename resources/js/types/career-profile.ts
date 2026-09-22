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

export type CareerProfileTab =
    | 'overview'
    | 'experience'
    | 'skills'
    | 'projects'
    | 'education'
    | 'certifications';

export interface CareerProfileData {
    exists: boolean;
    firstName: string | null;
    lastName: string | null;
    headline: string | null;
    seniority: string | null;
    preferredRoles: string[];
    phone: string | null;
    location: string | null;
    website: string | null;
    linkedinUrl: string | null;
    githubUrl: string | null;
    portfolioUrl: string | null;
    bio: string | null;
}

export interface ProfileExperienceItem {
    id: number;
    jobTitle: string;
    company: string;
    location: string | null;
    employmentType: string | null;
    startDate: string | null;
    endDate: string | null;
    currentlyEmployed: boolean;
    summary: string | null;
    achievements: string[];
    technologies: string[];
}

export interface ProfileSkillItem {
    id: number;
    name: string;
    category: string | null;
    proficiency: string | null;
}

export interface ProfileProjectItem {
    id: number;
    name: string;
    role: string | null;
    description: string | null;
    context: string | null;
    responsibilities: string | null;
    outcomes: string | null;
    technologies: string[];
    url: string | null;
    repositoryUrl: string | null;
    startDate: string | null;
    endDate: string | null;
}

export interface ProfileEducationItem {
    id: number;
    institution: string;
    qualification: string;
    subject: string | null;
    grade: string | null;
    startDate: string | null;
    endDate: string | null;
    description: string | null;
}

export interface ProfileCertificationItem {
    id: number;
    name: string;
    organisation: string | null;
    issueDate: string | null;
    expiryDate: string | null;
    credentialId: string | null;
    credentialUrl: string | null;
}

export interface CareerProfileSections {
    experiences: ProfileExperienceItem[];
    skills: ProfileSkillItem[];
    projects: ProfileProjectItem[];
    education: ProfileEducationItem[];
    certifications: ProfileCertificationItem[];
}

export interface SeniorityOption {
    value: string;
    label: string;
}

export interface CareerProfileOptions {
    suggestedRoles: string[];
    seniorities: SeniorityOption[];
    skillCategories: string[];
}

export interface CareerProfileProps {
    activeTab: CareerProfileTab;
    profile: CareerProfileData;
    completeness: ProfileCompleteness;
    sections: CareerProfileSections;
    options: CareerProfileOptions;
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
