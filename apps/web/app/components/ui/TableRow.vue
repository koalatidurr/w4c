<template>
  <tr :class="cn('border-b transition-colors hover:bg-muted/50', className)">
    <slot />
  </tr>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
