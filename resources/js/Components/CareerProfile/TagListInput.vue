<script setup lang="ts">
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    id: string;
    modelValue: string[];
    placeholder?: string;
    suggestions?: string[];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const draft = ref('');

const add = (value: string): void => {
    const tag = value.trim();

    if (tag === '' || props.modelValue.includes(tag)) {
        draft.value = '';

        return;
    }

    emit('update:modelValue', [...props.modelValue, tag]);
    draft.value = '';
};

const remove = (tag: string): void => {
    emit(
        'update:modelValue',
        props.modelValue.filter((item) => item !== tag),
    );
};

const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        add(draft.value);
    }
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-if="modelValue.length" class="flex flex-wrap gap-2">
            <Badge
                v-for="tag in modelValue"
                :key="tag"
                variant="secondary"
                class="gap-1 py-1 pe-1"
            >
                {{ tag }}
                <button
                    type="button"
                    class="rounded-full p-0.5 hover:bg-background/70"
                    :aria-label="`Remove ${tag}`"
                    @click="remove(tag)"
                >
                    <X class="size-3" aria-hidden="true" />
                </button>
            </Badge>
        </div>

        <Input
            :id="id"
            v-model="draft"
            :placeholder="placeholder ?? 'Type and press Enter'"
            @keydown="onKeydown"
            @blur="add(draft)"
        />

        <div v-if="suggestions?.length" class="flex flex-wrap gap-1.5">
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                type="button"
                class="rounded-full border border-dashed px-2.5 py-1 text-xs text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                @click="add(suggestion)"
            >
                {{ suggestion }}
            </button>
        </div>
    </div>
</template>
