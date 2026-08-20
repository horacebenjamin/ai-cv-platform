import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Ref } from 'vue';

type Updater<T> = T | ((value: T) => T);

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}

export function valueUpdater<T>(updaterOrValue: Updater<T>, ref: Ref<T>): void {
  ref.value =
    typeof updaterOrValue === 'function'
      ? (updaterOrValue as (value: T) => T)(ref.value)
      : updaterOrValue;
}
