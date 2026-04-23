<template>
  <th :class="cn('h-12 px-4 text-left align-middle font-medium text-muted-foreground', className)">
    <slot />
  </th>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
