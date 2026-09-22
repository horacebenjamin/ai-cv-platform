<script setup lang="ts">
import CareerSectionShell from '@/Components/CareerProfile/CareerSectionShell.vue';
import TagListInput from '@/Components/CareerProfile/TagListInput.vue';
import InputError from '@/Components/InputError.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import type { ProfileExperienceItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface ExperienceForm {
    job_title: string;
    company: string;
    location: string;
    employment_type: string;
    start_date: string;
    end_date: string;
    currently_employed: boolean;
    summary: string;
    achievements: string[];
    technologies: string[];
}

const props = defineProps<{
    items: ProfileExperienceItem[];
}>();

const blankExperience = (): ExperienceForm => ({
    job_title: '',
    company: '',
    location: '',
    employment_type: '',
    start_date: '',
    end_date: '',
    currently_employed: false,
    summary: '',
    achievements: [],
    technologies: [],
});

const form = useForm<ExperienceForm>(blankExperience());
const editingId = ref<number | null>(null);
const isFormOpen = ref(false);
const confirmingId = ref<number | null>(null);

const formTitle = computed(() =>
    editingId.value === null ? 'Add work experience' : 'Edit work experience',
);

const monthFormatter = new Intl.DateTimeFormat('en-GB', {
    month: 'short',
    year: 'numeric',
});

const formatMonth = (value: string | null): string =>
    value ? monthFormatter.format(new Date(`${value}T00:00:00`)) : '';

const dateRange = (item: ProfileExperienceItem): string => {
    const start = formatMonth(item.startDate);
    const end = item.currentlyEmployed ? 'Present' : formatMonth(item.endDate);

    return [start, end].filter(Boolean).join(' – ') || 'Dates not recorded';
};

const openAdd = (): void => {
    form.defaults(blankExperience());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    isFormOpen.value = true;
};

const openEdit = (item: ProfileExperienceItem): void => {
    form.defaults({
        job_title: item.jobTitle,
        company: item.company,
        location: item.location ?? '',
        employment_type: item.employmentType ?? '',
        start_date: item.startDate ?? '',
        end_date: item.endDate ?? '',
        currently_employed: item.currentlyEmployed,
        summary: item.summary ?? '',
        achievements: [...item.achievements],
        technologies: [...item.technologies],
    });
    form.reset();
    form.clearErrors();
    editingId.value = item.id;
    isFormOpen.value = true;
};

const close = (): void => {
    isFormOpen.value = false;
    editingId.value = null;
    form.clearErrors();
};

const submit = (): void => {
    if (editingId.value === null) {
        form.post(route('career-profile.experiences.store'), {
            preserveScroll: true,
            onSuccess: close,
        });

        return;
    }

    form.patch(route('career-profile.experiences.update', editingId.value), {
        preserveScroll: true,
        onSuccess: close,
    });
};

const remove = (item: ProfileExperienceItem): void => {
    router.delete(route('career-profile.experiences.destroy', item.id), {
        preserveScroll: true,
        onFinish: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <CareerSectionShell
        title="Work experience"
        description="Record each role factually. Generated CVs may re-order and re-word these facts for a target role, but never add to them."
        add-label="Add experience"
        :form-title="formTitle"
        :is-form-open="isFormOpen"
        :is-empty="props.items.length === 0 && !isFormOpen"
        empty-message="No work experience recorded yet. Add a role, or import an existing CV to propose entries."
        @add="openAdd"
        @cancel="close"
    >
        <template #form>
            <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <Label for="experience_job_title">Job title</Label>
                    <Input id="experience_job_title" v-model="form.job_title" required />
                    <InputError :message="form.errors.job_title" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="experience_company">Company</Label>
                    <Input id="experience_company" v-model="form.company" required />
                    <InputError :message="form.errors.company" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="experience_location">Location</Label>
                    <Input
                        id="experience_location"
                        v-model="form.location"
                        placeholder="City, country, or remote"
                    />
                    <InputError :message="form.errors.location" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="experience_employment_type">Employment type</Label>
                    <Input
                        id="experience_employment_type"
                        v-model="form.employment_type"
                        placeholder="For example, permanent or contract"
                    />
                    <InputError :message="form.errors.employment_type" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="experience_start_date">Start date</Label>
                    <Input id="experience_start_date" v-model="form.start_date" type="date" required />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="experience_end_date">End date</Label>
                    <Input
                        id="experience_end_date"
                        v-model="form.end_date"
                        type="date"
                        :disabled="form.currently_employed"
                    />
                    <label class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                        <input
                            v-model="form.currently_employed"
                            type="checkbox"
                            class="size-4 rounded border-input text-primary focus:ring-ring"
                        />
                        I currently work here
                    </label>
                    <InputError :message="form.errors.end_date" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="experience_summary">Responsibilities</Label>
                    <Textarea
                        id="experience_summary"
                        v-model="form.summary"
                        class="min-h-28"
                        placeholder="What you were responsible for in this role."
                    />
                    <InputError :message="form.errors.summary" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="experience_achievements">Achievements</Label>
                    <TagListInput
                        id="experience_achievements"
                        v-model="form.achievements"
                        placeholder="Add one achievement and press Enter"
                    />
                    <p class="text-xs text-muted-foreground">
                        Record measurable outcomes you can evidence.
                    </p>
                    <InputError :message="form.errors.achievements" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="experience_technologies">Technologies used</Label>
                    <TagListInput
                        id="experience_technologies"
                        v-model="form.technologies"
                        placeholder="Add a technology and press Enter"
                    />
                    <InputError :message="form.errors.technologies" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save experience' }}
                    </Button>
                    <Button type="button" variant="ghost" @click="close">Cancel</Button>
                </div>
            </form>
        </template>

        <article
            v-for="item in props.items"
            :key="item.id"
            class="rounded-lg border p-4"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ item.jobTitle }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ item.company
                        }}<span v-if="item.location"> · {{ item.location }}</span>
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ dateRange(item)
                        }}<span v-if="item.employmentType"> · {{ item.employmentType }}</span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <template v-if="confirmingId === item.id">
                        <Button type="button" variant="destructive" size="sm" @click="remove(item)">
                            Confirm remove
                        </Button>
                        <Button type="button" variant="ghost" size="sm" @click="confirmingId = null">
                            Keep
                        </Button>
                    </template>
                    <template v-else>
                        <Button type="button" variant="outline" size="sm" @click="openEdit(item)">
                            Edit
                        </Button>
                        <Button type="button" variant="ghost" size="sm" @click="confirmingId = item.id">
                            Remove
                        </Button>
                    </template>
                </div>
            </div>

            <p v-if="item.summary" class="mt-3 text-sm leading-6">{{ item.summary }}</p>

            <ul v-if="item.achievements.length" class="mt-3 flex flex-col gap-1.5">
                <li
                    v-for="achievement in item.achievements"
                    :key="achievement"
                    class="text-sm leading-6 text-muted-foreground before:me-2 before:content-['•']"
                >
                    {{ achievement }}
                </li>
            </ul>

            <div v-if="item.technologies.length" class="mt-3 flex flex-wrap gap-1.5">
                <Badge v-for="technology in item.technologies" :key="technology" variant="secondary">
                    {{ technology }}
                </Badge>
            </div>
        </article>
    </CareerSectionShell>
</template>
