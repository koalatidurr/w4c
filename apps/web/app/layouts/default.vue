<template>
  <div class="min-h-screen bg-background">
    <header class="sticky top-0 z-50 border-b bg-white">
      <div class="container mx-auto flex h-16 items-center px-4">
        <nav class="flex items-center space-x-6 text-sm font-medium">
          <NuxtLink to="/" class="flex items-center gap-2 text-foreground transition-colors hover:text-primary">
            <Trash2 class="h-5 w-5" />
            <span class="font-bold">Waste4Change</span>
          </NuxtLink>
          <NuxtLink to="/" class="text-muted-foreground transition-colors hover:text-foreground" active-class="text-foreground font-medium">
            Jadwal
          </NuxtLink>
          <NuxtLink to="/dashboard" class="text-muted-foreground transition-colors hover:text-foreground" active-class="text-foreground font-medium">
            Dashboard
          </NuxtLink>
        </nav>
      </div>
    </header>
    <main class="container mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next'
</script>
