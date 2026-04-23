<template>
  <table :class="cn('w-full caption-bottom text-sm', className)">
    <slot />
  </table>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
