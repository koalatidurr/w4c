<template>
  <thead :class="cn('border-b bg-muted/50', className)">
    <slot />
  </thead>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
