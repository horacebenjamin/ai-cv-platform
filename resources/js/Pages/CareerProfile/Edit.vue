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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Separator } from '@/Components/ui/separator';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CareerProfileProps } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Circle,
    CircleUserRound,
    Contact,
    ExternalLink,
    FileText,
    Lightbulb,
    MapPin,
} from 'lucide-vue-next';

interface CareerProfileForm {
    first_name: string;
    last_name: string;
    headline: string;
    phone: string;
    location: string;
    website: string;
    linkedin_url: string;
    github_url: string;
    portfolio_url: string;
    bio: string;
}

const props = defineProps<CareerProfileProps>();

const form = useForm<CareerProfileForm>({
    first_name: props.profile.firstName ?? '',
    last_name: props.profile.lastName ?? '',
    headline: props.profile.headline ?? '',
    phone: props.profile.phone ?? '',
    location: props.profile.location ?? '',
    website: props.profile.website ?? '',
    linkedin_url: props.profile.linkedinUrl ?? '',
    github_url: props.profile.githubUrl ?? '',
    portfolio_url: props.profile.portfolioUrl ?? '',
    bio: props.profile.bio ?? '',
});

const submit = (): void => {
    form.patch(route('career-profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Career Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium text-primary">Your factual foundation</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-tight sm:text-2xl">
                    Career Profile
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Keep your core career facts accurate here so they can be reused consistently across future application workflows.
                </p>
            </div>
        </template>

        <template #actions>
            <Button variant="outline" as-child>
                <Link :href="route('profile.edit')">Account settings</Link>
            </Button>
        </template>

        <div class="flex max-w-6xl flex-col gap-6">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
                <Card class="overflow-hidden border-primary/15 shadow-sm">
                    <CardHeader class="gap-1 bg-primary/[0.035] p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="max-w-xl">
                                <CardTitle class="text-lg">Profile completeness</CardTitle>
                                <CardDescription class="mt-1 leading-6">
                                    This percentage reflects recorded factual details only. It is not an ATS or employability score.
                                </CardDescription>
                            </div>
                            <div class="shrink-0 sm:text-end">
                                <p class="text-4xl font-semibold tracking-tight text-primary">
                                    {{ completeness.percentage }}%
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ completeness.completedFields }}/{{ completeness.totalFields }} core details
                                </p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-5 sm:p-6">
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            aria-label="Career profile completeness"
                            :aria-valuenow="completeness.percentage"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-[width]"
                                :style="{ width: `${completeness.percentage}%` }"
                            />
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="flex items-center gap-2 text-sm font-medium">
                                    <CheckCircle2 class="size-4 text-emerald-600" aria-hidden="true" />
                                    Completed
                                </p>
                                <div v-if="completeness.completedAreas.length" class="mt-2.5 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="area in completeness.completedAreas"
                                        :key="area.key"
                                        variant="secondary"
                                    >
                                        {{ area.label }}
                                    </Badge>
                                </div>
                                <p v-else class="mt-2.5 text-sm text-muted-foreground">
                                    Save your first core details to get started.
                                </p>
                            </div>

                            <div>
                                <p class="flex items-center gap-2 text-sm font-medium">
                                    <Lightbulb class="size-4 text-amber-600" aria-hidden="true" />
                                    Still to add
                                </p>
                                <div v-if="completeness.missingAreas.length" class="mt-2.5 flex flex-wrap gap-2">
                                    <Badge
                                        v-for="area in completeness.missingAreas"
                                        :key="area.key"
                                        variant="outline"
                                    >
                                        {{ area.label }}
                                    </Badge>
                                </div>
                                <p v-else class="mt-2.5 text-sm text-muted-foreground">
                                    All core profile details are recorded.
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="flex flex-col shadow-sm">
                    <CardHeader class="gap-1 p-5 pb-4 sm:p-6 sm:pb-4">
                        <CardTitle class="text-base">Profile section completeness</CardTitle>
                        <CardDescription>
                            See which parts of your Career Profile still need attention.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col gap-4 p-5 pt-0 sm:p-6 sm:pt-0">
                        <p class="text-sm font-semibold text-foreground">
                            {{ completeness.sectionCompleteness.summary }}
                        </p>

                        <div class="flex flex-1 flex-col justify-evenly gap-3">
                            <div
                                v-for="area in completeness.sectionCompleteness.areas"
                                :key="area.key"
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <CheckCircle2
                                        v-if="area.status === 'complete'"
                                        class="size-4 shrink-0 text-emerald-600"
                                        aria-hidden="true"
                                    />
                                    <Circle
                                        v-else
                                        class="size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <span class="text-sm font-medium">{{ area.label }}</span>
                                </div>
                                <Badge
                                    class="shrink-0 whitespace-nowrap"
                                    :variant="area.status === 'complete' ? 'secondary' : 'outline'"
                                >
                                    {{ area.status === 'complete' ? 'Complete' : 'Needs work' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <Card class="shadow-sm">
                    <CardHeader class="gap-2 sm:p-7">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <CircleUserRound class="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle class="text-lg">Professional identity</CardTitle>
                                <CardDescription class="mt-1">
                                    The name, headline, and location used to identify you professionally.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-5 p-6 pt-0 sm:grid-cols-2 sm:p-7 sm:pt-0">
                        <div class="flex flex-col gap-2">
                            <Label for="first_name">First name</Label>
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                class="scroll-mt-24"
                                autocomplete="given-name"
                                required
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div id="last_name" class="flex scroll-mt-24 flex-col gap-2">
                            <Label for="last_name_input">Last name</Label>
                            <Input
                                id="last_name_input"
                                v-model="form.last_name"
                                autocomplete="family-name"
                                required
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div id="headline" class="flex scroll-mt-24 flex-col gap-2 sm:col-span-2">
                            <Label for="headline_input">Professional headline</Label>
                            <Input
                                id="headline_input"
                                v-model="form.headline"
                                placeholder="For example, Senior Product Designer"
                                autocomplete="organization-title"
                            />
                            <p class="text-xs text-muted-foreground">
                                A concise, factual description of your current professional focus.
                            </p>
                            <InputError :message="form.errors.headline" />
                        </div>
                        <div id="location" class="flex scroll-mt-24 flex-col gap-2 sm:col-span-2">
                            <Label for="location_input">Location</Label>
                            <div class="relative">
                                <MapPin class="pointer-events-none absolute start-3 top-2.5 size-4 text-muted-foreground" aria-hidden="true" />
                                <Input
                                    id="location_input"
                                    v-model="form.location"
                                    class="ps-9"
                                    placeholder="City, region, or remote"
                                    autocomplete="address-level2"
                                />
                            </div>
                            <InputError :message="form.errors.location" />
                        </div>
                    </CardContent>
                </Card>

                <Card id="bio" class="scroll-mt-24 shadow-sm">
                    <CardHeader class="gap-2 sm:p-7">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <FileText class="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle class="text-lg">Professional summary</CardTitle>
                                <CardDescription class="mt-1 leading-6">
                                    Record an accurate overview of your experience, strengths, and professional direction. Do not add credentials or claims you cannot support.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6 pt-0 sm:p-7 sm:pt-0">
                        <Label for="bio_input" class="sr-only">Professional summary</Label>
                        <Textarea
                            id="bio_input"
                            v-model="form.bio"
                            class="min-h-40 resize-y leading-6"
                            placeholder="Summarise your factual professional background and focus."
                        />
                        <div class="mt-2 flex items-start justify-between gap-4">
                            <InputError :message="form.errors.bio" />
                            <p class="ms-auto text-xs text-muted-foreground">
                                {{ form.bio.length }}/5000
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="gap-2 sm:p-7">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Contact class="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle class="text-lg">Professional contact info</CardTitle>
                                <CardDescription class="mt-1">
                                    Add only the contact details and public profiles you want available to future career documents.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-5 p-6 pt-0 sm:grid-cols-2 sm:p-7 sm:pt-0">
                        <div id="phone" class="flex scroll-mt-24 flex-col gap-2">
                            <Label for="phone_input">Phone number</Label>
                            <Input id="phone_input" v-model="form.phone" type="tel" autocomplete="tel" />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="website">Website</Label>
                            <Input id="website" v-model="form.website" type="url" placeholder="https://example.com" autocomplete="url" />
                            <InputError :message="form.errors.website" />
                        </div>
                        <div id="linkedin_url" class="flex scroll-mt-24 flex-col gap-2">
                            <Label for="linkedin_url_input">LinkedIn</Label>
                            <Input id="linkedin_url_input" v-model="form.linkedin_url" type="url" placeholder="https://linkedin.com/in/…" />
                            <InputError :message="form.errors.linkedin_url" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="github_url">GitHub</Label>
                            <Input id="github_url" v-model="form.github_url" type="url" placeholder="https://github.com/…" />
                            <InputError :message="form.errors.github_url" />
                        </div>
                        <div class="flex flex-col gap-2 sm:col-span-2">
                            <Label for="portfolio_url">Portfolio</Label>
                            <Input id="portfolio_url" v-model="form.portfolio_url" type="url" placeholder="https://portfolio.example.com" />
                            <InputError :message="form.errors.portfolio_url" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-primary/15 shadow-sm">
                    <CardContent class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div>
                            <p class="text-sm font-medium">Keep your facts current</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Saving here immediately updates dashboard completeness.
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <Transition
                                enter-active-class="transition-opacity"
                                enter-from-class="opacity-0"
                                leave-active-class="transition-opacity"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="flex items-center gap-2 text-sm text-emerald-700">
                                    <CheckCircle2 class="size-4" aria-hidden="true" />
                                    Career profile saved.
                                </p>
                            </Transition>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving…' : 'Save career profile' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                    <Separator class="flex-1" />
                    <ExternalLink class="size-3.5" aria-hidden="true" />
                    <span>Account email and security remain in Settings.</span>
                    <Separator class="flex-1" />
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
