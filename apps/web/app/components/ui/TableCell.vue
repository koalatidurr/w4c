<template>
  <td :class="cn('p-4 align-middle', className)">
    <slot />
  </td>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
defineProps<{ className?: string }>()
</script>
