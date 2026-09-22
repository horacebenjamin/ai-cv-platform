<script setup lang="ts">
import CareerSectionShell from '@/Components/CareerProfile/CareerSectionShell.vue';
import InputError from '@/Components/InputError.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import type { ProfileCertificationItem } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { ExternalLink } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface CertificationForm {
    name: string;
    organisation: string;
    issue_date: string;
    expiry_date: string;
    credential_id: string;
    credential_url: string;
}

const props = defineProps<{
    items: ProfileCertificationItem[];
}>();

const blankCertification = (): CertificationForm => ({
    name: '',
    organisation: '',
    issue_date: '',
    expiry_date: '',
    credential_id: '',
    credential_url: '',
});

const form = useForm<CertificationForm>(blankCertification());
const editingId = ref<number | null>(null);
const isFormOpen = ref(false);
const confirmingId = ref<number | null>(null);

const formTitle = computed(() =>
    editingId.value === null ? 'Add certification' : 'Edit certification',
);

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
    month: 'short',
    year: 'numeric',
});

const formatDate = (value: string | null): string =>
    value ? dateFormatter.format(new Date(`${value}T00:00:00`)) : '';

const openAdd = (): void => {
    form.defaults(blankCertification());
    form.reset();
    form.clearErrors();
    editingId.value = null;
    isFormOpen.value = true;
};

const openEdit = (item: ProfileCertificationItem): void => {
    form.defaults({
        name: item.name,
        organisation: item.organisation ?? '',
        issue_date: item.issueDate ?? '',
        expiry_date: item.expiryDate ?? '',
        credential_id: item.credentialId ?? '',
        credential_url: item.credentialUrl ?? '',
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
        form.post(route('career-profile.certifications.store'), {
            preserveScroll: true,
            onSuccess: close,
        });

        return;
    }

    form.patch(route('career-profile.certifications.update', editingId.value), {
        preserveScroll: true,
        onSuccess: close,
    });
};

const remove = (item: ProfileCertificationItem): void => {
    router.delete(route('career-profile.certifications.destroy', item.id), {
        preserveScroll: true,
        onFinish: () => (confirmingId.value = null),
    });
};
</script>

<template>
    <CareerSectionShell
        title="Certifications"
        description="Record certifications you hold, including expiry dates and credential references where they exist."
        add-label="Add certification"
        :form-title="formTitle"
        :is-form-open="isFormOpen"
        :is-empty="props.items.length === 0 && !isFormOpen"
        empty-message="No certifications recorded yet."
        @add="openAdd"
        @cancel="close"
    >
        <template #form>
            <form class="grid gap-5 sm:grid-cols-2" @submit.prevent="submit">
                <div class="flex flex-col gap-2">
                    <Label for="certification_name">Certification</Label>
                    <Input id="certification_name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="certification_organisation">Issuing organisation</Label>
                    <Input id="certification_organisation" v-model="form.organisation" />
                    <InputError :message="form.errors.organisation" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="certification_issue_date">Date obtained</Label>
                    <Input id="certification_issue_date" v-model="form.issue_date" type="date" />
                    <InputError :message="form.errors.issue_date" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="certification_expiry_date">Expiry date</Label>
                    <Input id="certification_expiry_date" v-model="form.expiry_date" type="date" />
                    <InputError :message="form.errors.expiry_date" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="certification_credential_id">Credential ID</Label>
                    <Input id="certification_credential_id" v-model="form.credential_id" />
                    <InputError :message="form.errors.credential_id" />
                </div>
                <div class="flex flex-col gap-2">
                    <Label for="certification_credential_url">Credential URL</Label>
                    <Input
                        id="certification_credential_url"
                        v-model="form.credential_url"
                        type="url"
                        placeholder="https://example.com/credential"
                    />
                    <InputError :message="form.errors.credential_url" />
                </div>
                <div class="flex items-center gap-3 sm:col-span-2">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save certification' }}
                    </Button>
                    <Button type="button" variant="ghost" @click="close">Cancel</Button>
                </div>
            </form>
        </template>

        <article v-for="item in props.items" :key="item.id" class="rounded-lg border p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold">{{ item.name }}</p>
                    <p v-if="item.organisation" class="text-sm text-muted-foreground">
                        {{ item.organisation }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        <span v-if="item.issueDate">Obtained {{ formatDate(item.issueDate) }}</span>
                        <span v-if="item.expiryDate"> · Expires {{ formatDate(item.expiryDate) }}</span>
                        <span v-if="item.credentialId"> · ID {{ item.credentialId }}</span>
                    </p>
                    <a
                        v-if="item.credentialUrl"
                        :href="item.credentialUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
                    >
                        View credential
                        <ExternalLink class="size-3.5" aria-hidden="true" />
                    </a>
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
        </article>
    </CareerSectionShell>
</template>
