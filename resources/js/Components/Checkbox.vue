<script setup lang="ts">
import { computed } from 'vue';

type CheckedValue = boolean | unknown[];

const emit = defineEmits<{
    'update:checked': [value: CheckedValue];
}>();

const props = withDefaults(
    defineProps<{
        checked: CheckedValue;
        value?: string | number | boolean | object | null;
    }>(),
    {
        value: null,
    },
);

const proxyChecked = computed<CheckedValue>({
    get() {
        return props.checked;
    },

    set(value) {
        emit('update:checked', value);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
    />
</template>
