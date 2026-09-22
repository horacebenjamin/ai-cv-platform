<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ProfileImportProps } from '@/types/career-profile';
import { Head, Link, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    LoaderCircle,
    ShieldCheck,
    TriangleAlert,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface ImportSelectionForm {
    professional: string[];
    experiences: number[];
    skills: number[];
    projects: number[];
    education: number[];
    certifications: number[];
}

const props = defineProps<ProfileImportProps>();

const indexes = (length: number): number[] => Array.from({ length }, (_, index) => index);

const form = useForm<ImportSelectionForm>({
    professional: props.proposed.professional
        .filter((field) => field.isBlank)
        .map((field) => field.key),
    experiences: indexes(props.proposed.experiences.length),
    skills: indexes(props.proposed.skills.length),
    projects: indexes(props.proposed.projects.length),
    education: indexes(props.proposed.education.length),
    certifications: indexes(props.proposed.certifications.length),
});

const isPending = computed(() => props.import.status === 'pending');
const isReady = computed(() => props.import.status === 'ready');
const isApplied = computed(() => props.import.status === 'applied');
const isFailed = computed(() => props.import.status === 'failed');

const { start, stop } = usePoll(4000, {}, { autoStart: false });

watch(
    isPending,
    (pending) => {
        if (pending) {
            start();
        } else {
            stop();
        }
    },
    { immediate: true },
);

const page = usePage();

const selectionError = computed(() => page.props.errors.selections ?? '');

const totalSelected = computed(
    () =>
        form.professional.length +
        form.experiences.length +
        form.skills.length +
        form.projects.length +
        form.education.length +
        form.certifications.length,
);

const dateRange = (start: string | null, end: string | null): string =>
    [start, end].filter(Boolean).join(' – ') || 'Dates not recorded';

const submit = (): void => {
    form.post(route('career-profile.imports.apply', props.import.id), {
        preserveScroll: true,
    });
};

const discard = (): void => {
    router.delete(route('career-profile.imports.destroy', props.import.id));
};
</script>

<template>
    <Head title="Review imported CV" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium text-primary">Import review</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-tight sm:text-2xl">
                    Review facts from your CV
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    These are proposals read from the CV you pasted. Nothing is saved to your Career
                    Profile until you approve it here.
                </p>
            </div>
        </template>

        <template #actions>
            <Button variant="outline" as-child>
                <Link :href="route('career-profile.edit', { tab: 'import' })">
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Career Profile
                </Link>
            </Button>
        </template>

        <div class="flex max-w-5xl flex-col gap-6">
            <Card v-if="isPending" class="shadow-sm">
                <CardContent class="flex items-center gap-4 p-6">
                    <LoaderCircle class="size-5 animate-spin text-primary" aria-hidden="true" />
                    <div>
                        <p class="font-medium">Reading your CV</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Extraction runs in the background. This page refreshes automatically.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="isFailed" class="border-red-200 shadow-sm">
                <CardContent class="flex items-start gap-4 p-6">
                    <TriangleAlert class="mt-0.5 size-5 text-red-600" aria-hidden="true" />
                    <div>
                        <p class="font-medium">This CV could not be read</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Your Career Profile was not changed. Paste the CV text again, or add the
                            details manually.
                        </p>
                        <Button class="mt-4" variant="outline" as-child>
                            <Link :href="route('career-profile.edit', { tab: 'import' })">
                                Back to import
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="isApplied" class="border-emerald-200 shadow-sm">
                <CardContent class="flex items-start gap-4 p-6">
                    <CheckCircle2 class="mt-0.5 size-5 text-emerald-600" aria-hidden="true" />
                    <div>
                        <p class="font-medium">This import has been applied</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            The facts you approved are now part of your Career Profile.
                        </p>
                        <Button class="mt-4" variant="outline" as-child>
                            <Link :href="route('career-profile.edit')">Open Career Profile</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <template v-else-if="isReady">
                <Card class="border-primary/15 shadow-sm">
                    <CardContent class="flex items-start gap-3 p-5">
                        <ShieldCheck class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                        <div>
                            <p class="text-sm font-medium">Approve only what is accurate</p>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                Approved items are added to your Career Profile. Details you have
                                already saved are never overwritten, and anything you leave unticked
                                is discarded.
                            </p>
                            <p v-if="props.import.skippedCount > 0" class="mt-2 text-sm text-amber-700">
                                {{ props.import.skippedCount }}
                                {{ props.import.skippedCount === 1 ? 'entry' : 'entries' }} could not
                                be read reliably and were left out rather than guessed.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <form class="flex flex-col gap-6" @submit.prevent="submit">
                    <Card v-if="proposed.professional.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Professional information</CardTitle>
                            <CardDescription>
                                Only fields you have left blank can be filled from an import.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <div
                                v-for="field in proposed.professional"
                                :key="field.key"
                                class="rounded-lg border p-3"
                            >
                                <div v-if="field.isBlank" class="flex items-start gap-3">
                                    <input
                                        v-model="form.professional"
                                        type="checkbox"
                                        :value="field.key"
                                        class="mt-1 size-4 rounded border-input text-primary focus:ring-ring"
                                    />
                                    <div class="min-w-0">
                                        <div class="block text-sm font-medium">{{ field.label }}</div>
                                        <div class="mt-0.5 block text-sm leading-6 text-muted-foreground">
                                            {{ field.value }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="flex items-start gap-3">
                                    <div class="min-w-0">
                                        <div class="block text-sm font-medium">{{ field.label }}</div>
                                        <div class="mt-0.5 block text-sm leading-6 text-muted-foreground">
                                            Keeping your saved value: {{ field.currentValue }}
                                        </div>
                                    </div>
                                    <Badge variant="outline" class="ms-auto shrink-0">Already recorded</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="proposed.experiences.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Work experience</CardTitle>
                            <CardDescription>{{ proposed.experiences.length }} proposed</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <div
                                v-for="(experience, index) in proposed.experiences"
                                :key="`experience-${index}`"
                                class="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <input
                                    v-model="form.experiences"
                                    type="checkbox"
                                    :value="index"
                                    class="mt-1 size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                <div class="min-w-0">
                                    <div class="block font-medium">{{ experience.job_title }}</div>
                                    <div class="block text-sm text-muted-foreground">
                                        {{ experience.company
                                        }}<template v-if="experience.location"> · {{ experience.location }}</template>
                                    </div>
                                    <div class="mt-1 block text-xs text-muted-foreground">
                                        {{
                                            dateRange(
                                                experience.start_date,
                                                experience.currently_employed ? 'Present' : experience.end_date,
                                            )
                                        }}
                                    </div>
                                    <div v-if="experience.summary" class="mt-3 block text-sm leading-6">
                                        {{ experience.summary }}
                                    </div>
                                    <ul
                                        v-if="experience.achievements?.length"
                                        class="mt-3 list-disc space-y-1 pl-5 text-sm leading-6 text-muted-foreground"
                                    >
                                        <li
                                            v-for="achievement in experience.achievements"
                                            :key="achievement"
                                        >
                                            {{ achievement }}
                                        </li>
                                    </ul>
                                    <div v-if="experience.technologies.length" class="mt-3 flex flex-wrap gap-1.5">
                                        <Badge
                                            v-for="technology in experience.technologies"
                                            :key="technology"
                                            variant="secondary"
                                        >
                                            {{ technology }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="proposed.skills.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Skills</CardTitle>
                            <CardDescription>
                                Skills already on your profile are skipped automatically.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-wrap gap-2">
                            <div
                                v-for="(skill, index) in proposed.skills"
                                :key="`skill-${index}`"
                                class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm"
                            >
                                <input
                                    v-model="form.skills"
                                    type="checkbox"
                                    :value="index"
                                    class="size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                {{ skill.name }}
                                <span v-if="skill.category" class="text-xs text-muted-foreground">
                                    · {{ skill.category }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="proposed.projects.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Projects</CardTitle>
                            <CardDescription>{{ proposed.projects.length }} proposed</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <div
                                v-for="(project, index) in proposed.projects"
                                :key="`project-${index}`"
                                class="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <input
                                    v-model="form.projects"
                                    type="checkbox"
                                    :value="index"
                                    class="mt-1 size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                <div class="min-w-0">
                                    <div class="block font-medium">{{ project.name }}</div>
                                    <div v-if="project.role" class="block text-sm text-muted-foreground">
                                        {{ project.role }}
                                    </div>
                                    <div v-if="project.description" class="mt-2 block text-sm leading-6">
                                        {{ project.description }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="proposed.education.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Education</CardTitle>
                            <CardDescription>{{ proposed.education.length }} proposed</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <div
                                v-for="(education, index) in proposed.education"
                                :key="`education-${index}`"
                                class="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <input
                                    v-model="form.education"
                                    type="checkbox"
                                    :value="index"
                                    class="mt-1 size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                <div class="min-w-0">
                                    <div class="block font-medium">
                                        {{ education.qualification
                                        }}<template v-if="education.subject"> · {{ education.subject }}</template>
                                    </div>
                                    <div class="block text-sm text-muted-foreground">
                                        {{ education.institution }}
                                    </div>
                                    <div class="mt-1 block text-xs text-muted-foreground">
                                        {{ dateRange(education.start_date, education.end_date) }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="proposed.certifications.length" class="shadow-sm">
                        <CardHeader class="gap-1">
                            <CardTitle class="text-base">Certifications</CardTitle>
                            <CardDescription>{{ proposed.certifications.length }} proposed</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <div
                                v-for="(certification, index) in proposed.certifications"
                                :key="`certification-${index}`"
                                class="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <input
                                    v-model="form.certifications"
                                    type="checkbox"
                                    :value="index"
                                    class="mt-1 size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                <div class="min-w-0">
                                    <div class="block font-medium">{{ certification.name }}</div>
                                    <div v-if="certification.organisation" class="block text-sm text-muted-foreground">
                                        {{ certification.organisation }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-primary/15 shadow-sm">
                        <CardContent class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                            <div>
                                <p class="text-sm font-medium">
                                    {{ totalSelected }} {{ totalSelected === 1 ? 'item' : 'items' }} selected
                                </p>
                                <InputError class="mt-1" :message="selectionError" />
                            </div>
                            <div class="flex items-center gap-3">
                                <Button type="button" variant="ghost" @click="discard">Discard import</Button>
                                <Button type="submit" :disabled="form.processing || totalSelected === 0">
                                    {{ form.processing ? 'Adding…' : 'Add approved facts' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
