<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                    Dashboard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Your job application workspace.
                </p>
            </div>
        </template>

        <div
            class="rounded-xl border border-border bg-card p-6 text-card-foreground"
        >
            <p class="text-sm text-muted-foreground">You're logged in.</p>
        </div>
    </AuthenticatedLayout>
</template>
