<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Separator } from '@/Components/ui/separator';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    Bot,
    BriefcaseBusiness,
    ChevronUp,
    CircleUserRound,
    FileText,
    Files,
    LayoutDashboard,
    Settings,
    Sparkles,
} from 'lucide-vue-next';
import type { Component } from 'vue';

type NavigationItem = {
    label: string;
    routeName: string | null;
    activePattern?: string;
    icon: Component;
};

defineProps<{
    user: User;
}>();

const emit = defineEmits<{
    navigate: [];
}>();

const navigationItems: NavigationItem[] = [
    { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard },
    {
        label: 'Career Profile',
        routeName: 'career-profile.edit',
        activePattern: 'career-profile.*',
        icon: CircleUserRound,
    },
    { label: 'My CVs', routeName: null, icon: Files },
    { label: 'Job Tracker', routeName: null, icon: BriefcaseBusiness },
    { label: 'Cover Letters', routeName: null, icon: FileText },
    { label: 'AI Tools', routeName: null, icon: Bot },
    {
        label: 'Settings',
        routeName: 'profile.edit',
        activePattern: 'profile.*',
        icon: Settings,
    },
];

const isActive = (item: NavigationItem): boolean => {
    if (!item.routeName) {
        return false;
    }

    return route().current(item.activePattern ?? item.routeName) === true;
};

const userInitials = (name: string): string =>
    name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
</script>

<template>
    <aside
        class="flex h-full w-72 flex-col border-e border-border bg-card text-card-foreground"
        aria-label="Application navigation"
    >
        <div class="flex h-20 shrink-0 items-center px-5">
            <Link
                :href="route('dashboard')"
                class="flex items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                @click="emit('navigate')"
            >
                <span
                    class="relative flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                    aria-hidden="true"
                >
                    <FileText class="size-5" :stroke-width="1.8" />
                    <Sparkles
                        class="absolute -right-1 -top-1 size-4 rounded-full bg-card p-0.5 text-primary"
                        :stroke-width="2"
                    />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold tracking-tight">
                        AI CV Platform
                    </span>
                    <span class="block text-xs text-muted-foreground">
                        Application workspace
                    </span>
                </span>
            </Link>
        </div>

        <Separator />

        <nav class="flex-1 overflow-y-auto px-3 py-5" aria-label="Primary">
            <p
                class="px-3 pb-2 text-xs font-medium uppercase tracking-[0.12em] text-muted-foreground"
            >
                Workspace
            </p>

            <ul class="flex flex-col gap-1">
                <li v-for="item in navigationItems" :key="item.label">
                    <Link
                        v-if="item.routeName"
                        :href="route(item.routeName)"
                        class="group flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="
                            isActive(item)
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        :aria-current="isActive(item) ? 'page' : undefined"
                        @click="emit('navigate')"
                    >
                        <component
                            :is="item.icon"
                            class="size-[18px] shrink-0"
                            :stroke-width="isActive(item) ? 2 : 1.8"
                            aria-hidden="true"
                        />
                        <span>{{ item.label }}</span>
                    </Link>

                    <div
                        v-else
                        class="flex min-h-10 cursor-default items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground/60"
                        aria-disabled="true"
                    >
                        <component
                            :is="item.icon"
                            class="size-[18px] shrink-0"
                            :stroke-width="1.8"
                            aria-hidden="true"
                        />
                        <span>{{ item.label }}</span>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="shrink-0 p-3">
            <Separator class="mb-3" />

            <Dropdown
                align="left"
                direction="up"
                width="48"
                content-classes="border border-border bg-popover p-1 text-popover-foreground shadow-md"
            >
                <template #trigger="{ open }">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg p-2 text-start transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label="Open account menu"
                        :aria-expanded="open"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                            aria-hidden="true"
                        >
                            {{ userInitials(user.name) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">
                                {{ user.name }}
                            </span>
                            <span class="block truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </span>
                        </span>
                        <ChevronUp
                            class="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </button>
                </template>

                <template #content>
                    <DropdownLink
                        :href="route('profile.edit')"
                        class="rounded-sm text-foreground hover:bg-accent focus:bg-accent dark:text-foreground"
                        @click="emit('navigate')"
                    >
                        Account settings
                    </DropdownLink>
                    <DropdownLink
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-sm text-foreground hover:bg-accent focus:bg-accent dark:text-foreground"
                        @click="emit('navigate')"
                    >
                        Log out
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>
    </aside>
</template>
