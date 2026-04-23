<template>
  <tbody :class="cn('', className)">
    <slot />
  </tbody>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
