<script setup lang="ts">
import CareerSectionShell from '@/Components/CareerProfile/CareerSectionShell.vue';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import type { ProfileEducationItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface EducationForm {
    institution: string;
    qualification: string;
    subject: string;
    grade: string;
    start_date: string;
    end_date: string;
    description: string;
}

const props = defineProps<{
    items: ProfileEducationItem[];
}>();

const blankEducation = (): EducationForm => ({
    institution: '',
    qualification: '',
    subject: '',
    grade: '',
    start_date: '',
    end_date: '',
    description: '',
});

const form = useForm<EducationForm>(blankEducation());
const editingId = ref<number | null>(null);
const isFormOpen = ref(false);
const confirmingId = ref<number | null>(null);

const formTitle = computed(() =>
    editingId.value === null ? 'Add education' : 'Edit education',
);

const yearFormatter = new Intl.DateTimeFormat('en-GB', { year: 'numeric' });

const formatYear = (value: string | null): string =>
    value ? yearFormatter.format(new Date(`${value}T00:00:00`)) : '';

const dateRange = (item: ProfileEducationItem): string =>
    [formatYear(item.startDate), formatYear(item.endDate)].filter(Boolean).join(' – ') ||
    'Dates not recorded';

const openAdd = (): void => {
    form.defaults(blankEducation());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    isFormOpen.value = true;
};

const openEdit = (item: ProfileEducationItem): void => {
    form.defaults({
        institution: item.institution,
        qualification: item.qualification,
        subject: item.subject ?? '',
        grade: item.grade ?? '',
        start_date: item.startDate ?? '',
        end_date: item.endDate ?? '',
        description: item.description ?? '',
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
        form.post(route('career-profile.education.store'), {
            preserveScroll: true,
            onSuccess: close,
        });

        return;
    }

    form.patch(route('career-profile.education.update', editingId.value), {
        preserveScroll: true,
        onSuccess: close,
    });
};

const remove = (item: ProfileEducationItem): void => {
    router.delete(route('career-profile.education.destroy', item.id), {
        preserveScroll: true,
        onFinish: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <CareerSectionShell
        title="Education"
        description="Record qualifications exactly as awarded. Generated CVs never upgrade or infer qualifications."
        add-label="Add education"
        :form-title="formTitle"
        :is-form-open="isFormOpen"
        :is-empty="props.items.length === 0 && !isFormOpen"
        empty-message="No education recorded yet."
        @add="openAdd"
        @cancel="close"
    >
        <template #form>
            <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <Label for="education_institution">Institution</Label>
                    <Input id="education_institution" v-model="form.institution" required />
                    <InputError :message="form.errors.institution" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="education_qualification">Qualification</Label>
                    <Input
                        id="education_qualification"
                        v-model="form.qualification"
                        required
                        placeholder="For example, BSc"
                    />
                    <InputError :message="form.errors.qualification" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="education_subject">Subject</Label>
                    <Input id="education_subject" v-model="form.subject" />
                    <InputError :message="form.errors.subject" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="education_grade">Grade</Label>
                    <Input id="education_grade" v-model="form.grade" placeholder="Optional" />
                    <InputError :message="form.errors.grade" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="education_start_date">Start date</Label>
                    <Input id="education_start_date" v-model="form.start_date" type="date" />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="education_end_date">End date</Label>
                    <Input id="education_end_date" v-model="form.end_date" type="date" />
                    <InputError :message="form.errors.end_date" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="education_description">Notes</Label>
                    <Textarea id="education_description" v-model="form.description" class="min-h-20" />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save education' }}
                    </Button>
                    <Button type="button" variant="ghost" @click="close">Cancel</Button>
                </div>
            </form>
        </template>

        <article v-for="item in props.items" :key="item.id" class="rounded-lg border p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">
                        {{ item.qualification
                        }}<span v-if="item.subject"> · {{ item.subject }}</span>
                    </p>
                    <p class="text-sm text-muted-foreground">{{ item.institution }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ dateRange(item) }}<span v-if="item.grade"> · {{ item.grade }}</span>
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

            <p v-if="item.description" class="mt-3 text-sm leading-6">{{ item.description }}</p>
        </article>
    </CareerSectionShell>
</template>
