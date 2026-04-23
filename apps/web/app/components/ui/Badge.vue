<template>
  <span
    :class="cn(
      variant === 'default' && 'bg-primary text-primary-foreground',
      variant === 'secondary' && 'bg-secondary text-secondary-foreground',
      variant === 'success' && 'bg-green-100 text-green-800',
      variant === 'warning' && 'bg-yellow-100 text-yellow-800',
      variant === 'destructive' && 'bg-destructive text-destructive-foreground',
      variant === 'outline' && 'border border-input',
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors',
      className
    )"
  >
    <slot />
  </span>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
withDefaults(defineProps<{
  variant?: 'default' | 'secondary' | 'success' | 'warning' | 'destructive' | 'outline'
  className?: string
}>(), {
  variant: 'default',
  className: '',
})
</script>
