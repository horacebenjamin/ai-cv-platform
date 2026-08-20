<script setup lang="ts">
import AppSidebar from '@/Components/AppSidebar.vue';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, Menu, Sparkles, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const mobileSidebarOpen = ref(false);
const user = computed(() => usePage().props.auth.user!);

const closeMobileSidebar = (): void => {
    mobileSidebarOpen.value = false;
};

const closeOnEscape = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        closeMobileSidebar();
    }
};

watch(mobileSidebarOpen, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen bg-muted/40 text-foreground">
        <AppSidebar
            :user="user"
            class="fixed inset-y-0 start-0 z-40 hidden lg:flex"
        />

        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileSidebarOpen"
                class="fixed inset-0 z-50 bg-foreground/30 backdrop-blur-[1px] lg:hidden"
                aria-hidden="true"
                @click="closeMobileSidebar"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div
                v-if="mobileSidebarOpen"
                id="mobile-navigation"
                class="fixed inset-y-0 start-0 z-[60] lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Navigation menu"
            >
                <AppSidebar :user="user" @navigate="closeMobileSidebar" />
                <Button
                    variant="ghost"
                    size="icon"
                    class="absolute end-3 top-5 text-muted-foreground"
                    aria-label="Close navigation menu"
                    @click="closeMobileSidebar"
                >
                    <X aria-hidden="true" />
                </Button>
            </div>
        </Transition>

        <div class="min-h-screen lg:ps-72">
            <div
                class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-background/95 px-4 backdrop-blur sm:px-6 lg:hidden"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-2.5 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <span
                        class="relative flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                        aria-hidden="true"
                    >
                        <FileText class="size-4" />
                        <Sparkles
                            class="absolute -right-1 -top-1 size-3 rounded-full bg-background p-px text-primary"
                        />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">
                        AI CV Platform
                    </span>
                </Link>

                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Open navigation menu"
                    aria-controls="mobile-navigation"
                    :aria-expanded="mobileSidebarOpen"
                    @click="mobileSidebarOpen = true"
                >
                    <Menu aria-hidden="true" />
                </Button>
            </div>

            <header
                v-if="$slots.header"
                class="border-b border-border bg-background"
            >
                <div
                    class="mx-auto flex min-h-20 max-w-[1600px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8 xl:px-10"
                >
                    <slot name="header" />
                    <div v-if="$slots.actions" class="shrink-0">
                        <slot name="actions" />
                    </div>
                </div>
            </header>

            <main
                class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8 xl:px-10"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
