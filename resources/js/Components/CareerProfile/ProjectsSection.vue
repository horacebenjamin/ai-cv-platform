<script setup lang="ts">
import CareerSectionShell from '@/Components/CareerProfile/CareerSectionShell.vue';
import TagListInput from '@/Components/CareerProfile/TagListInput.vue';
import InputError from '@/Components/InputError.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import type { ProfileProjectItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface ProjectForm {
    name: string;
    role: string;
    description: string;
    context: string;
    responsibilities: string;
    outcomes: string;
    technologies: string[];
    url: string;
    repository_url: string;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    items: ProfileProjectItem[];
}>();

const blankProject = (): ProjectForm => ({
    name: '',
    role: '',
    description: '',
    context: '',
    responsibilities: '',
    outcomes: '',
    technologies: [],
    url: '',
    repository_url: '',
    start_date: '',
    end_date: '',
});

const form = useForm<ProjectForm>(blankProject());
const editingId = ref<number | null>(null);
const isFormOpen = ref(false);
const confirmingId = ref<number | null>(null);

const formTitle = computed(() => (editingId.value === null ? 'Add project' : 'Edit project'));

const openAdd = (): void => {
    form.defaults(blankProject());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    isFormOpen.value = true;
};

const openEdit = (item: ProfileProjectItem): void => {
    form.defaults({
        name: item.name,
        role: item.role ?? '',
        description: item.description ?? '',
        context: item.context ?? '',
        responsibilities: item.responsibilities ?? '',
        outcomes: item.outcomes ?? '',
        technologies: [...item.technologies],
        url: item.url ?? '',
        repository_url: item.repositoryUrl ?? '',
        start_date: item.startDate ?? '',
        end_date: item.endDate ?? '',
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
        form.post(route('career-profile.projects.store'), {
            preserveScroll: true,
            onSuccess: close,
        });

        return;
    }

    form.patch(route('career-profile.projects.update', editingId.value), {
        preserveScroll: true,
        onSuccess: close,
    });
};

const remove = (item: ProfileProjectItem): void => {
    router.delete(route('career-profile.projects.destroy', item.id), {
        preserveScroll: true,
        onFinish: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <CareerSectionShell
        title="Projects"
        description="Projects count as factual evidence, which matters if you have limited employment history."
        add-label="Add project"
        :form-title="formTitle"
        :is-form-open="isFormOpen"
        :is-empty="props.items.length === 0 && !isFormOpen"
        empty-message="No projects recorded yet. Add work you can describe and evidence."
        @add="openAdd"
        @cancel="close"
    >
        <template #form>
            <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <Label for="project_name">Project name</Label>
                    <Input id="project_name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="project_role">Your role</Label>
                    <Input id="project_role" v-model="form.role" placeholder="For example, lead developer" />
                    <InputError :message="form.errors.role" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="project_description">Description</Label>
                    <Textarea id="project_description" v-model="form.description" class="min-h-24" />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="project_context">Business or problem context</Label>
                    <Textarea
                        id="project_context"
                        v-model="form.context"
                        class="min-h-20"
                        placeholder="What problem the project addressed."
                    />
                    <InputError :message="form.errors.context" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="project_responsibilities">Key responsibilities</Label>
                    <Textarea id="project_responsibilities" v-model="form.responsibilities" class="min-h-20" />
                    <InputError :message="form.errors.responsibilities" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="project_outcomes">Measurable outcomes</Label>
                    <Textarea
                        id="project_outcomes"
                        v-model="form.outcomes"
                        class="min-h-20"
                        placeholder="Outcomes you can evidence."
                    />
                    <InputError :message="form.errors.outcomes" />
                </div>
                <div class="flex flex-col gap-2 sm:col-span-2">
                    <Label for="project_technologies">Technologies used</Label>
                    <TagListInput
                        id="project_technologies"
                        v-model="form.technologies"
                        placeholder="Add a technology and press Enter"
                    />
                    <InputError :message="form.errors.technologies" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="project_url">Project URL</Label>
                    <Input id="project_url" v-model="form.url" type="url" placeholder="https://example.com" />
                    <InputError :message="form.errors.url" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="project_repository_url">Repository URL</Label>
                    <Input
                        id="project_repository_url"
                        v-model="form.repository_url"
                        type="url"
                        placeholder="https://github.com/…"
                    />
                    <InputError :message="form.errors.repository_url" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="project_start_date">Start date</Label>
                    <Input id="project_start_date" v-model="form.start_date" type="date" />
                    <InputError :message="form.errors.start_date" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="project_end_date">End date</Label>
                    <Input id="project_end_date" v-model="form.end_date" type="date" />
                    <InputError :message="form.errors.end_date" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save project' }}
                    </Button>
                    <Button type="button" variant="ghost" @click="close">Cancel</Button>
                </div>
            </form>
        </template>

        <article v-for="item in props.items" :key="item.id" class="rounded-lg border p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ item.name }}</p>
                    <p v-if="item.role" class="text-sm text-muted-foreground">{{ item.role }}</p>
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
            <p v-if="item.outcomes" class="mt-2 text-sm leading-6 text-muted-foreground">
                Outcomes: {{ item.outcomes }}
            </p>

            <div v-if="item.technologies.length" class="mt-3 flex flex-wrap gap-1.5">
                <Badge v-for="technology in item.technologies" :key="technology" variant="secondary">
                    {{ technology }}
                </Badge>
            </div>
        </article>
    </CareerSectionShell>
</template>
