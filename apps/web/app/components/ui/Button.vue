<template>
  <button
    :class="cn(
      variant === 'default' && 'bg-primary text-primary-foreground hover:bg-primary/90',
      variant === 'outline' && 'border border-input bg-background hover:bg-accent',
      variant === 'secondary' && 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
      variant === 'ghost' && 'hover:bg-accent',
      variant === 'destructive' && 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
      'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2',
      className
    )"
    :disabled="disabled"
    @click="$emit('click', $event)"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { cn } from '~/utils/cn'
withDefaults(defineProps<{
  variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive'
  disabled?: boolean
  className?: string
}>(), {
  variant: 'default',
  disabled: false,
  className: '',
})
defineEmits(['click'])
</script>
