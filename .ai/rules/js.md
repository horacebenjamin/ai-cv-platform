---
paths:
  - 'resources/js/**'
---

# Js

## Build the customer frontend with Inertia Vue
Implement customer pages as Vue components under resources/js/Pages, using Inertia navigation and forms with shared layouts and components. Keep Blade for the Inertia shell and Filament views.

## Keep customer UI primitives in shadcn-vue
Use shadcn-vue as the intended default for reusable customer-facing UI primitives, and compose those primitives into custom Vue components for product- or domain-specific UI. Keep Filament reserved for administrative interfaces, and do not introduce another general-purpose Vue component library without approval.

## Default customer Vue code to TypeScript
Write new customer-facing Vue scripts with `<script setup lang="ts">` and place shared frontend types under `resources/js/types`. Keep Inertia shared props globally typed through the declaration file, and keep shadcn-vue generation configured for TypeScript.
