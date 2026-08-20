<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

type TextareaValue = string | number;

const props = defineProps<{
  class?: HTMLAttributes['class'];
  defaultValue?: TextareaValue;
  modelValue?: TextareaValue;
}>();

const emits = defineEmits<{
  'update:modelValue': [value: TextareaValue];
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
});
</script>

<template>
  <textarea
    v-model="modelValue"
    :class="
      cn(
        'flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        props.class,
      )
    "
  />
</template>
