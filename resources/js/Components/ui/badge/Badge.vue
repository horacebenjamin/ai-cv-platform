<script setup lang="ts">
import { cn } from '@/lib/utils';
import { badgeVariants } from '.';
import type { VariantProps } from 'class-variance-authority';
import type { HTMLAttributes } from 'vue';

type BadgeVariants = VariantProps<typeof badgeVariants>;

const props = defineProps<{
  variant?: BadgeVariants['variant'];
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div :class="cn(badgeVariants({ variant }), props.class)">
    <slot />
  </div>
</template>
