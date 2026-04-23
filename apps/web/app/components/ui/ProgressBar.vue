<template>
  <div>
    <div class="flex justify-between text-sm mb-1">
      <span>{{ label }}</span>
      <span class="font-medium">{{ value }}</span>
    </div>
    <div class="h-2 w-full rounded-full bg-secondary">
      <div :class="['h-2 rounded-full transition-all', color]" :style="{ width: value + '%' }" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  label: string
  value: number
  color?: string
}>()
</script>
