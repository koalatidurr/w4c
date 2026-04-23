<template>
  <Card>
    <CardContent class="pt-6">
      <div class="text-sm font-medium text-muted-foreground">{{ title }}</div>
      <div :class="['text-3xl font-bold', color]">
        {{ value }}
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  color?: string
}>()
</script>
