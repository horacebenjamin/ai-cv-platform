<script setup lang="ts">
import { Badge } from '@/Components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { DashboardProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import {
    Bot,
    Bookmark,
    BriefcaseBusiness,
    CircleUserRound,
    FilePenLine,
    FileText,
    Files,
    MessagesSquare,
    Sparkles,
    Target,
    WalletCards,
} from 'lucide-vue-next';

defineProps<DashboardProps>();

const user = usePage().props.auth.user!;
const firstName = user.name.trim().split(/\s+/)[0] || user.name;

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return 'Not recorded';
    }

    return dateFormatter.format(new Date(value));
};

const humanize = (value: string): string =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const featureLabels: Record<string, string> = {
    cover_letter: 'Cover letter',
    cv_generation: 'CV generation',
    cv_rewrite: 'CV rewrite',
    job_match_analysis: 'Job match analysis',
    professional_summary: 'Professional summary',
    skills_optimisation: 'Skills optimisation',
};

const applicationStatusClass = (status: string): string => {
    if (['interview', 'technical_test', 'final_interview'].includes(status)) {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    if (status === 'offer') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (status === 'applied') {
        return 'border-blue-200 bg-blue-50 text-blue-800';
    }

    return 'border-border bg-muted text-muted-foreground';
};

const aiStatusClass = (status: string): string => {
    if (status === 'completed') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (status === 'failed') {
        return 'border-red-200 bg-red-50 text-red-800';
    }

    if (status === 'processing') {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    return 'border-border bg-muted text-muted-foreground';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium text-primary">Welcome back</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-tight sm:text-2xl">
                    {{ firstName }}’s workspace
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Focus on the next useful step in your job search.
                </p>
            </div>
        </template>

        <div class="flex flex-col gap-6">
            <Card class="overflow-hidden border-primary/15 bg-primary text-primary-foreground shadow-sm">
                <CardContent class="relative p-6 sm:p-7">
                    <Sparkles
                        class="absolute -right-8 -top-10 size-40 text-primary-foreground/5"
                        :stroke-width="1"
                        aria-hidden="true"
                    />
                    <div class="relative flex max-w-3xl items-start gap-4">
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary-foreground/10"
                            aria-hidden="true"
                        >
                            <Target class="size-5" />
                        </span>
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.14em] text-primary-foreground/70"
                            >
                                Next focus
                            </p>
                            <h2 class="mt-2 text-lg font-semibold sm:text-xl">
                                {{ nextFocus.title }}
                            </h2>
                            <p class="mt-1.5 text-sm leading-6 text-primary-foreground/75">
                                {{ nextFocus.description }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <section aria-labelledby="workspace-overview-heading">
                <h2 id="workspace-overview-heading" class="sr-only">
                    Workspace overview
                </h2>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                    <Card class="shadow-sm">
                        <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                <Files class="size-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-2xl font-semibold tracking-tight">
                                    {{ overview.totalCvs }}
                                </p>
                                <p class="text-xs text-muted-foreground">CVs</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                                <BriefcaseBusiness class="size-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-2xl font-semibold tracking-tight">
                                    {{ overview.activeApplications }}
                                </p>
                                <p class="text-xs text-muted-foreground">Active applications</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                <Bookmark class="size-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-2xl font-semibold tracking-tight">
                                    {{ overview.savedJobs }}
                                </p>
                                <p class="text-xs text-muted-foreground">Saved roles</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                <MessagesSquare class="size-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-2xl font-semibold tracking-tight">
                                    {{ overview.interviewProcesses }}
                                </p>
                                <p class="text-xs text-muted-foreground">Interview stages</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="col-span-2 shadow-sm md:col-span-1">
                        <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <FilePenLine class="size-[18px]" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-2xl font-semibold tracking-tight">
                                    {{ overview.coverLetters }}
                                </p>
                                <p class="text-xs text-muted-foreground">Cover letters</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-3">
                <Card class="xl:col-span-2 shadow-sm">
                    <CardHeader class="gap-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle class="text-base">Active applications</CardTitle>
                                <CardDescription class="mt-1">
                                    Roles still moving through your application workflow.
                                </CardDescription>
                            </div>
                            <BriefcaseBusiness class="size-5 text-muted-foreground" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentApplications.length" class="flex flex-col">
                            <template
                                v-for="(application, index) in recentApplications"
                                :key="application.id"
                            >
                                <Separator v-if="index > 0" />
                                <article class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-medium">
                                            {{ application.role ?? 'Role not recorded' }}
                                        </h3>
                                        <p class="mt-1 truncate text-sm text-muted-foreground">
                                            {{ application.company }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center justify-between gap-4 sm:justify-end">
                                        <div class="text-end text-xs text-muted-foreground">
                                            <p>Applied</p>
                                            <p class="mt-0.5 font-medium text-foreground">
                                                {{ formatDate(application.appliedAt) }}
                                            </p>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            :class="applicationStatusClass(application.status)"
                                        >
                                            {{ humanize(application.status) }}
                                        </Badge>
                                    </div>
                                </article>
                            </template>
                        </div>
                        <div v-else class="rounded-lg border border-dashed p-6 text-center">
                            <BriefcaseBusiness class="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
                            <p class="mt-3 text-sm font-medium">No active applications</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Applications will appear here once they enter your workflow.
                            </p>
                        </div>
                        <p class="mt-4 text-xs text-muted-foreground">
                            Interview stages reflect recorded statuses. Interview dates are not stored yet.
                        </p>
                    </CardContent>
                </Card>

                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-1">
                    <Card class="shadow-sm">
                        <CardHeader class="gap-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <CardTitle class="text-base">Career profile</CardTitle>
                                    <CardDescription class="mt-1">
                                        Core factual details completed.
                                    </CardDescription>
                                </div>
                                <CircleUserRound class="size-5 text-muted-foreground" aria-hidden="true" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-end justify-between gap-4">
                                <p class="text-3xl font-semibold tracking-tight">
                                    {{ profile.percentage }}%
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ profile.completedFields }}/{{ profile.totalFields }} details
                                </p>
                            </div>
                            <div
                                class="mt-4 h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-label="Career profile completeness"
                                :aria-valuenow="profile.percentage"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-[width]"
                                    :style="{ width: `${profile.percentage}%` }"
                                />
                            </div>
                            <p v-if="profile.missingFields.length" class="mt-4 text-xs leading-5 text-muted-foreground">
                                Still missing: {{ profile.missingFields.slice(0, 3).join(', ') }}<template v-if="profile.missingFields.length > 3"> and {{ profile.missingFields.length - 3 }} more</template>.
                            </p>
                            <p v-else class="mt-4 text-xs leading-5 text-muted-foreground">
                                All core profile details are recorded.
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardHeader class="gap-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <CardTitle class="text-base">AI credits</CardTitle>
                                    <CardDescription class="mt-1">
                                        Capacity from your active subscription.
                                    </CardDescription>
                                </div>
                                <WalletCards class="size-5 text-muted-foreground" aria-hidden="true" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-3xl font-semibold tracking-tight">
                                        {{ credits.available ?? '—' }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ credits.available === null ? 'No active plan' : 'Credits available' }}
                                    </p>
                                </div>
                                <Badge v-if="credits.plan" variant="secondary">
                                    {{ humanize(credits.plan) }}
                                </Badge>
                            </div>
                            <Separator class="my-4" />
                            <div class="flex justify-between gap-4 text-xs">
                                <span class="text-muted-foreground">Credits used</span>
                                <span class="font-medium">{{ credits.used }}</span>
                            </div>
                            <div v-if="credits.renewalDate" class="mt-2 flex justify-between gap-4 text-xs">
                                <span class="text-muted-foreground">Renews</span>
                                <span class="font-medium">{{ formatDate(credits.renewalDate) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <Card class="xl:col-span-2 shadow-sm">
                    <CardHeader class="gap-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle class="text-base">Recently worked on</CardTitle>
                                <CardDescription class="mt-1">
                                    Your latest CV activity and saved history.
                                </CardDescription>
                            </div>
                            <Files class="size-5 text-muted-foreground" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentCvs.length" class="grid gap-3 md:grid-cols-3">
                            <article
                                v-for="cv in recentCvs"
                                :key="cv.id"
                                class="rounded-lg border bg-muted/20 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-background text-primary ring-1 ring-border">
                                        <FileText class="size-4" aria-hidden="true" />
                                    </span>
                                    <Badge variant="outline" class="bg-background">
                                        {{ humanize(cv.status) }}
                                    </Badge>
                                </div>
                                <h3 class="mt-4 line-clamp-2 text-sm font-medium">
                                    {{ cv.title }}
                                </h3>
                                <p class="mt-1 line-clamp-1 text-xs text-muted-foreground">
                                    {{ cv.targetJobTitle ?? 'General CV' }}
                                </p>
                                <div class="mt-4 flex items-center justify-between gap-2 text-xs text-muted-foreground">
                                    <span>{{ cv.historyCount }} {{ cv.historyCount === 1 ? 'snapshot' : 'snapshots' }}</span>
                                    <span>{{ formatDate(cv.updatedAt) }}</span>
                                </div>
                            </article>
                        </div>
                        <div v-else class="rounded-lg border border-dashed p-6 text-center">
                            <Files class="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
                            <p class="mt-3 text-sm font-medium">No CVs yet</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Your most recently updated CVs will appear here.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="gap-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle class="text-base">Recent AI activity</CardTitle>
                                <CardDescription class="mt-1">
                                    Request status and recorded token usage.
                                </CardDescription>
                            </div>
                            <Bot class="size-5 text-muted-foreground" aria-hidden="true" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentAiRequests.length" class="flex flex-col">
                            <template
                                v-for="(request, index) in recentAiRequests"
                                :key="request.id"
                            >
                                <Separator v-if="index > 0" />
                                <article class="py-3 first:pt-0 last:pb-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-sm font-medium">
                                                {{ featureLabels[request.feature] ?? humanize(request.feature) }}
                                            </h3>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                {{ request.tokensUsed.toLocaleString() }} tokens · {{ formatDate(request.createdAt) }}
                                            </p>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            :class="aiStatusClass(request.status)"
                                        >
                                            {{ humanize(request.status) }}
                                        </Badge>
                                    </div>
                                </article>
                            </template>
                        </div>
                        <div v-else class="rounded-lg border border-dashed p-6 text-center">
                            <Bot class="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
                            <p class="mt-3 text-sm font-medium">No AI activity yet</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Completed and in-progress requests will appear here.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
