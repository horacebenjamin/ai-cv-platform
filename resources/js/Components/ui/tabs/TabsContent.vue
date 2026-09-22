<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { TabsContent } from 'reka-ui';
import type { TabsContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<TabsContentProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
  <TabsContent
    v-bind="delegatedProps"
    :class="
      cn(
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
        props.class,
      )
    "
  >
    <slot />
  </TabsContent>
</template>
