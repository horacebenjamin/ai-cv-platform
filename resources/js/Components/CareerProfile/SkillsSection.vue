<script setup lang="ts">
import CareerSectionShell from '@/Components/CareerProfile/CareerSectionShell.vue';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import type { ProfileSkillItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface SkillForm {
    name: string;
    category: string;
    proficiency: string;
}

const props = defineProps<{
    items: ProfileSkillItem[];
    categories: string[];
}>();

const blankSkill = (): SkillForm => ({ name: '', category: '', proficiency: '' });

const form = useForm<SkillForm>(blankSkill());
const editingId = ref<number | null>(null);
const isFormOpen = ref(false);
const confirmingId = ref<number | null>(null);

const formTitle = computed(() => (editingId.value === null ? 'Add skill' : 'Edit skill'));

const groupedSkills = computed(() => {
    const groups = new Map<string, ProfileSkillItem[]>();

    for (const skill of props.items) {
        const category = skill.category ?? 'Uncategorised';

        groups.set(category, [...(groups.get(category) ?? []), skill]);
    }

    return [...groups.entries()].map(([category, skills]) => ({ category, skills }));
});

const openAdd = (): void => {
    form.defaults(blankSkill());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    isFormOpen.value = true;
};

const openEdit = (item: ProfileSkillItem): void => {
    form.defaults({
        name: item.name,
        category: item.category ?? '',
        proficiency: item.proficiency ?? '',
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
        form.post(route('career-profile.skills.store'), {
            preserveScroll: true,
            onSuccess: () => {
                const category = form.category;

                close();
                form.defaults({ ...blankSkill(), category });
                form.reset();
            },
        });

        return;
    }

    form.patch(route('career-profile.skills.update', editingId.value), {
        preserveScroll: true,
        onSuccess: close,
    });
};

const remove = (item: ProfileSkillItem): void => {
    router.delete(route('career-profile.skills.destroy', item.id), {
        preserveScroll: true,
        onFinish: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <CareerSectionShell
        title="Skills"
        description="List the skills you can evidence. Categories are free text, so this structure suits any profession."
        add-label="Add skill"
        :form-title="formTitle"
        :is-form-open="isFormOpen"
        :is-empty="props.items.length === 0 && !isFormOpen"
        empty-message="No skills recorded yet. A CV needs at least three skills before it can be targeted."
        @add="openAdd"
        @cancel="close"
    >
        <template #form>
            <form class="grid gap-5 sm:grid-cols-3" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <Label for="skill_name">Skill</Label>
                    <Input id="skill_name" v-model="form.name" required placeholder="For example, Laravel" />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="skill_category">Category</Label>
                    <Input
                        id="skill_category"
                        v-model="form.category"
                        list="skill_category_options"
                        placeholder="For example, Frameworks"
                    />
                    <datalist id="skill_category_options">
                        <option v-for="category in props.categories" :key="category" :value="category" />
                    </datalist>
                    <InputError :message="form.errors.category" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="skill_proficiency">Proficiency</Label>
                    <Input
                        id="skill_proficiency"
                        v-model="form.proficiency"
                        placeholder="Optional, for example Advanced"
                    />
                    <InputError :message="form.errors.proficiency" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save skill' }}
                    </Button>
                    <Button type="button" variant="ghost" @click="close">Cancel</Button>
                </div>
            </form>
        </template>

        <section
            v-for="group in groupedSkills"
            :key="group.category"
            class="rounded-lg border p-4"
        >
            <p class="text-sm font-semibold">{{ group.category }}</p>
            <ul class="mt-3 flex flex-wrap gap-2">
                <li
                    v-for="skill in group.skills"
                    :key="skill.id"
                    class="flex items-center gap-2 rounded-full border bg-background py-1 pe-1.5 ps-3"
                >
                    <span class="text-sm">
                        {{ skill.name }}
                        <span v-if="skill.proficiency" class="text-xs text-muted-foreground">
                            · {{ skill.proficiency }}
                        </span>
                    </span>
                    <template v-if="confirmingId === skill.id">
                        <Button type="button" variant="destructive" size="xs" @click="remove(skill)">
                            Confirm
                        </Button>
                        <Button type="button" variant="ghost" size="xs" @click="confirmingId = null">
                            Keep
                        </Button>
                    </template>
                    <template v-else>
                        <Button type="button" variant="ghost" size="xs" @click="openEdit(skill)">
                            Edit
                        </Button>
                        <Button type="button" variant="ghost" size="xs" @click="confirmingId = skill.id">
                            Remove
                        </Button>
                    </template>
                </li>
            </ul>
        </section>
    </CareerSectionShell>
</template>
