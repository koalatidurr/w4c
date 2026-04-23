<template>
  <div :class="cn('rounded-lg border bg-card text-card-foreground shadow-sm', className)">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
