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
            <Button variant="outline" @click="resetFilters">Reset</Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="p-0">
        <div v-if="loading" class="flex items-center justify-center p-12">
          <div class="text-muted-foreground">Memuat...</div>
        </div>
        <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
          <div class="text-destructive">{{ errorMsg }}</div>
        </div>
        <div v-else-if="!schedules.length" class="flex items-center justify-center p-12">
          <div class="text-muted-foreground">Tidak ada jadwal ditemukan</div>
        </div>
        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Klien</TableHead>
              <TableHead>Tanggal</TableHead>
              <TableHead>Status Angkut</TableHead>
              <TableHead>Status Pilah</TableHead>
              <TableHead>Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="s in schedules" :key="s.id">
              <TableCell>{{ s.id }}</TableCell>
              <TableCell>{{ s.client_name || 'Klien #' + s.client_id }}</TableCell>
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

        <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
          <div class="text-sm text-muted-foreground">
            Halaman {{ meta.current_page }} dari {{ meta.last_page }} ({{ meta.total }} total)
          </div>
          <div class="flex gap-2">
            <Button variant="outline" size="sm" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">Previous</Button>
            <Button variant="outline" size="sm" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">Next</Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useApi } from '~/composables/useApi'

const api = useApi()

const filters = reactive({ date_from: '', date_to: '' })
const schedules = ref<any[]>([])
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const errorMsg = ref('')

async function fetchSchedules() {
  loading.value = true
  errorMsg.value = ''
  try {
    const params: Record<string, any> = { page: 1, per_page: 15 }
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    const res = await api.get<PaginatedResult<any>>('/schedules', params)
    schedules.value = res.data
    Object.assign(meta, res.meta)
  } catch (e: any) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  fetchSchedules()
}

function goToPage(page: number) {
  meta.current_page = page
  fetchSchedules()
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

watch([() => filters.date_from, () => filters.date_to], () => {
  meta.current_page = 1
  fetchSchedules()
})

fetchSchedules()
</script>
