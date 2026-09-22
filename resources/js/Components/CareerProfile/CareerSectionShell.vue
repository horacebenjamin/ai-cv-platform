<script setup lang="ts">
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Plus, X } from 'lucide-vue-next';

defineProps<{
    title: string;
    description: string;
    addLabel: string;
    isFormOpen: boolean;
    formTitle: string;
    isEmpty: boolean;
    emptyMessage: string;
}>();

const emit = defineEmits<{
    add: [];
    cancel: [];
}>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader class="gap-2 sm:p-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="max-w-2xl">
                    <CardTitle class="text-lg">{{ title }}</CardTitle>
                    <CardDescription class="mt-1 leading-6">
                        {{ description }}
                    </CardDescription>
                </div>
                <Button
                    v-if="!isFormOpen"
                    type="button"
                    variant="outline"
                    class="shrink-0"
                    @click="emit('add')"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    {{ addLabel }}
                </Button>
            </div>
        </CardHeader>

        <CardContent class="flex flex-col gap-5 p-6 pt-0 sm:p-7 sm:pt-0">
            <div v-if="isFormOpen" class="rounded-lg border border-primary/25 bg-primary/[0.02] p-4 sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <p class="text-sm font-semibold">{{ formTitle }}</p>
                    <Button type="button" variant="ghost" size="sm" @click="emit('cancel')">
                        <X class="size-4" aria-hidden="true" />
                        Cancel
                    </Button>
                </div>
                <div class="mt-4">
                    <slot name="form" />
                </div>
            </div>

            <p v-if="isEmpty" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                {{ emptyMessage }}
            </p>

            <div v-else class="flex flex-col gap-3">
                <slot />
            </div>
        </CardContent>
    </Card>
</template>
