<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Jadwal Pengangkutan</h1>
      <p class="text-muted-foreground">Daftar jadwal pengangkutan dan pemilahan sampah</p>
    </div>

    <Card class="mb-6">
      <CardContent class="pt-6">
        <div class="flex flex-wrap gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Dari Tanggal</label>
            <Input type="date" v-model="filters.date_from" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Sampai Tanggal</label>
            <Input type="date" v-model="filters.date_to" />
          </div>
          <div class="flex items-end">
            <Button variant="outline" @click="resetFilters" :disabled="loading">
              <RotateCcw class="h-4 w-4 mr-2" />
              Reset
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="p-0">
        <!-- Loading skeleton -->
        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 5" :key="i" class="flex gap-4 items-center">
            <Skeleton class="h-4 w-12" />
            <Skeleton class="h-4 w-40" />
            <Skeleton class="h-4 w-28" />
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-8 w-16 ml-auto" />
          </div>
        </div>

        <!-- Error state -->
        <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
          <div class="text-destructive text-center">
            <AlertCircle class="h-8 w-8 mx-auto mb-2" />
            <p>{{ errorMsg }}</p>
            <Button variant="outline" size="sm" class="mt-2" @click="fetchSchedules">Coba Lagi</Button>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="!schedules.length" class="flex items-center justify-center p-12">
          <div class="text-center">
            <Inbox class="h-12 w-12 mx-auto mb-3 text-muted-foreground" />
            <div class="text-muted-foreground">Tidak ada jadwal ditemukan</div>
          </div>
        </div>

        <!-- Data table -->
        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead class="w-12">ID</TableHead>
              <TableHead>Klien</TableHead>
              <TableHead>Tanggal</TableHead>
              <TableHead>Status Angkut</TableHead>
              <TableHead>Status Pilah</TableHead>
              <TableHead class="w-20">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="s in schedules" :key="s.id">
              <TableCell class="font-mono text-xs">{{ s.id }}</TableCell>
              <TableCell class="font-medium">{{ s.client_name || 'Klien #' + s.client_id }}</TableCell>
              <TableCell>{{ formatDate(s.date) }}</TableCell>
              <TableCell>
                <Badge :variant="statusVariant(s)">
                  {{ s.has_collect ? s.collect.status : 'Belum' }}
                </Badge>
              </TableCell>
              <TableCell>
                <Badge :variant="s.has_sort ? 'success' : s.has_collect ? 'warning' : 'outline'">
                  {{ s.has_sort ? 'Sudah' : s.has_collect ? 'Belum' : '-' }}
                </Badge>
              </TableCell>
              <TableCell>
                <NuxtLink :to="'/schedules/' + s.id">
                  <Button variant="ghost" size="sm">Detail</Button>
                </NuxtLink>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
          <div class="text-sm text-muted-foreground">
            Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} total)
          </div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
              Previous
            </Button>
            <Button variant="outline" size="sm" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
              Next
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { RotateCcw, AlertCircle, Inbox } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'

const api = useApi()
const filters = reactive({ date_from: '', date_to: '' })
const currentPage = ref(1)

const queryParams = computed(() => {
  const p: Record<string, any> = { page: currentPage.value, per_page: 15 }
  if (filters.date_from) p.date_from = filters.date_from
  if (filters.date_to) p.date_to = filters.date_to
  return p
})

const loading = ref(false)
const errorMsg = ref('')
const scheduleData = ref<any>(null)

const schedules = computed(() => scheduleData.value?.data ?? [])
const meta = computed(() => scheduleData.value?.meta ?? { current_page: 1, last_page: 1, total: 0 })

// Debounced fetch to avoid too many API calls on filter changes
const debouncedFetch = useDebounceFn(async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    scheduleData.value = await api.get('/schedules', queryParams.value, { cacheMs: 2 * 60 * 1000 })
  } catch (e: any) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}, 300)

watch([() => filters.date_from, () => filters.date_to], () => {
  currentPage.value = 1
  debouncedFetch()
})

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  currentPage.value = 1
  debouncedFetch()
}

function goToPage(page: number) {
  currentPage.value = page
  debouncedFetch()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
  })
}

function statusVariant(s: any) {
  return s.has_collect
    ? s.collect.status === 'DONE' ? 'success' : 'warning'
    : 'outline'
}

// Initial fetch
debouncedFetch()
</script>
