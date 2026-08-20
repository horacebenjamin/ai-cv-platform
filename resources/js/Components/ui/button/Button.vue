<script setup lang="ts">
import { Primitive } from 'reka-ui';
import type { PrimitiveProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '.';
import type { VariantProps } from 'class-variance-authority';
import type { HTMLAttributes } from 'vue';

type ButtonVariants = VariantProps<typeof buttonVariants>;

interface Props extends PrimitiveProps {
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
});
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
