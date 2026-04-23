<template>
  <div>
    <NuxtLink to="/" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 mb-2">
      <ChevronLeft class="h-4 w-4" /> Kembali ke Jadwal
    </NuxtLink>
    <h1 class="text-3xl font-bold tracking-tight">Detail Jadwal #{{ route.params.id }}</h1>
  </div>

  <div v-if="loading" class="flex items-center justify-center p-12">
    <div class="text-muted-foreground">Memuat...</div>
  </div>
  <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
    <div class="text-destructive">{{ errorMsg }}</div>
  </div>
  <div v-else-if="!schedule" class="flex items-center justify-center p-12">
    <div class="text-muted-foreground">Jadwal tidak ditemukan</div>
  </div>

  <div v-else class="space-y-6">
    <Card>
      <CardHeader><CardTitle>Informasi Jadwal</CardTitle></CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div>
            <div class="text-xs font-medium text-muted-foreground">ID Jadwal</div>
            <div class="text-sm font-medium">{{ schedule.id }}</div>
          </div>
          <div>
            <div class="text-xs font-medium text-muted-foreground">Klien</div>
            <div class="text-sm font-medium">{{ schedule.client_name || 'Klien #' + schedule.client_id }}</div>
          </div>
          <div>
            <div class="text-xs font-medium text-muted-foreground">Tanggal</div>
            <div class="text-sm font-medium">{{ formatDate(schedule.date) }}</div>
          </div>
          <div>
            <div class="text-xs font-medium text-muted-foreground">Status</div>
            <Badge :variant="schedule.has_collect ? 'success' : 'outline'">
              {{ schedule.has_collect ? 'Sudah Diangkut' : 'Belum Diangkut' }}
            </Badge>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Data Pengangkutan</CardTitle>
          <div v-if="schedule.collect" class="flex items-center gap-2">
            <Badge :variant="schedule.collect.status === 'DONE' ? 'success' : 'warning'">{{ schedule.collect.status }}</Badge>
            <span class="text-sm text-muted-foreground font-mono">{{ schedule.collect.code }}</span>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div v-show="collectOpen">
          <Table v-if="schedule.collect?.collect_items?.length">
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Kantong Sampah</TableHead>
                <TableHead>Jumlah</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in schedule.collect.collect_items" :key="item.id">
                <TableCell>{{ item.id }}</TableCell>
                <TableCell>{{ item.trashbag_name || 'Kantong #' + item.trashbag_id }}</TableCell>
                <TableCell>{{ item.quantity }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div v-else class="text-muted-foreground text-sm py-4">Tidak ada item pengangkutan</div>
        </div>
        <Button variant="ghost" size="sm" class="mt-2" @click="collectOpen = !collectOpen">
          {{ collectOpen ? 'Sembunyikan' : 'Tampilkan' }} Item Pengangkutan
        </Button>
      </CardContent>
    </Card>

    <Card v-if="schedule.collect">
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Data Pemilahan</CardTitle>
          <span class="text-sm font-mono text-muted-foreground">{{ schedule.collect.sort?.code }}</span>
        </div>
      </CardHeader>
      <CardContent v-if="schedule.collect.sort">
        <div v-show="sortOpen">
          <Table v-if="schedule.collect.sort.sort_items?.length">
            <TableHeader>
              <TableRow>
                <TableHead>ID</TableHead>
                <TableHead>Material</TableHead>
                <TableHead>Berat (kg)</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in schedule.collect.sort.sort_items" :key="item.id">
                <TableCell>{{ item.id }}</TableCell>
                <TableCell>{{ item.waste_name || 'Waste #' + item.waste_id }}</TableCell>
                <TableCell>{{ item.weight }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <div class="mt-4 text-right font-semibold">
            Total Berat: {{ schedule.collect.sort.total_weight || 0 }} kg
          </div>
        </div>
        <Button variant="ghost" size="sm" class="mt-2" @click="sortOpen = !sortOpen">
          {{ sortOpen ? 'Sembunyikan' : 'Tampilkan' }} Item Pemilahan
        </Button>
      </CardContent>
      <CardContent v-else class="py-8 text-center text-muted-foreground">
        Data pemilahan belum tersedia
      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronLeft } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'

const route = useRoute()
const api = useApi()

const schedule = ref<any>(null)
const loading = ref(false)
const errorMsg = ref('')
const collectOpen = ref(false)
const sortOpen = ref(false)

async function fetchSchedule() {
  loading.value = true
  errorMsg.value = ''
  try {
    schedule.value = await api.get(`/schedules/${route.params.id}`)
  } catch (e: any) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  })
}

fetchSchedule()
</script>
