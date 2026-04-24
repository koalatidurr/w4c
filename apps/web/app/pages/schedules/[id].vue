<template>
  <div>
    <NuxtLink to="/" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 mb-4">
      <ChevronLeft class="h-4 w-4" /> Kembali ke Jadwal
    </NuxtLink>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-6">
      <Skeleton class="h-10 w-64" />
      <Skeleton class="h-48 w-full" />
      <Skeleton class="h-48 w-full" />
    </div>

    <!-- Error state -->
    <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
      <div class="text-destructive text-center">
        <AlertCircle class="h-8 w-8 mx-auto mb-2" />
        <p>{{ errorMsg }}</p>
        <Button variant="outline" size="sm" class="mt-2" @click="fetchSchedule">Coba Lagi</Button>
      </div>
    </div>

    <template v-else-if="schedule">
      <h1 class="text-3xl font-bold tracking-tight mb-6">Detail Jadwal #{{ route.params.id }}</h1>

      <!-- Schedule info -->
      <Card class="mb-6">
        <CardHeader>
          <CardTitle>Informasi Jadwal</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
              <div class="text-xs font-medium text-muted-foreground">ID Jadwal</div>
              <div class="text-sm font-mono font-medium">{{ schedule.id }}</div>
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
              <Badge :variant="schedule.has_collect ? (schedule.collect.status === 'DONE' ? 'success' : 'warning') : 'outline'">
                {{ schedule.has_collect ? (schedule.collect.status === 'DONE' ? 'Sudah Diangkut' : 'Dilewati') : 'Belum Diangkut' }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Collect data -->
      <Card class="mb-6">
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Data Pengangkutan</CardTitle>
            <div v-if="schedule.collect" class="flex items-center gap-2">
              <Badge :variant="schedule.collect.status === 'DONE' ? 'success' : 'warning'">
                {{ schedule.collect.status }}
              </Badge>
              <span class="text-sm font-mono text-muted-foreground">{{ schedule.collect.code }}</span>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div v-show="collectOpen" class="mb-4">
            <Table v-if="schedule.collect?.collect_items?.length">
              <TableHeader>
                <TableRow>
                  <TableHead>ID</TableHead>
                  <TableHead>Kantong Sampah</TableHead>
                  <TableHead class="text-right">Jumlah</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="item in schedule.collect.collect_items" :key="item.id">
                  <TableCell class="font-mono">{{ item.id }}</TableCell>
                  <TableCell>{{ item.trashbag_name || 'Kantong #' + item.trashbag_id }}</TableCell>
                  <TableCell class="text-right font-mono">{{ item.quantity }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
            <div v-else class="text-muted-foreground text-sm py-4 text-center">
              Tidak ada item pengangkutan
            </div>
          </div>
          <Button variant="ghost" size="sm" class="mt-2" @click="collectOpen = !collectOpen">
            <component :is="collectOpen ? ChevronUp : ChevronDown" class="h-4 w-4 mr-2" />
            {{ collectOpen ? 'Sembunyikan' : 'Tampilkan' }} Item Pengangkutan
          </Button>
        </CardContent>
      </Card>

      <!-- Sort data -->
      <Card v-if="schedule.collect">
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle>Data Pemilahan</CardTitle>
            <span v-if="schedule.collect.sort" class="text-sm font-mono text-muted-foreground">
              {{ schedule.collect.sort.code }}
            </span>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="schedule.collect.sort">
            <div v-show="sortOpen" class="mb-4">
              <Table v-if="schedule.collect.sort.sort_items?.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Material</TableHead>
                    <TableHead class="text-right">Berat (kg)</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="item in schedule.collect.sort.sort_items" :key="item.id">
                    <TableCell class="font-mono">{{ item.id }}</TableCell>
                    <TableCell>{{ item.waste_name || 'Waste #' + item.waste_id }}</TableCell>
                    <TableCell class="text-right font-mono">{{ item.weight }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <div class="mt-4 text-right font-semibold">
                Total Berat: {{ schedule.collect.sort.total_weight || 0 }} kg
              </div>
            </div>
            <Button variant="ghost" size="sm" class="mt-2" @click="sortOpen = !sortOpen">
              <component :is="sortOpen ? ChevronUp : ChevronDown" class="h-4 w-4 mr-2" />
              {{ sortOpen ? 'Sembunyikan' : 'Tampilkan' }} Item Pemilahan
            </Button>
          </div>
          <div v-else class="py-8 text-center text-muted-foreground">
            Data pemilahan belum tersedia
          </div>
        </CardContent>
      </Card>
    </template>

    <!-- Not found -->
    <div v-else class="flex items-center justify-center p-12">
      <div class="text-muted-foreground text-center">
        <Inbox class="h-12 w-12 mx-auto mb-3" />
        <div>Jadwal tidak ditemukan</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ChevronLeft, ChevronUp, ChevronDown, AlertCircle, Inbox } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'

const route = useRoute()
const api = useApi()
const loading = ref(false)
const errorMsg = ref('')
const schedule = ref<any>(null)
const collectOpen = ref(false)
const sortOpen = ref(false)

async function fetchSchedule() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get(`/schedules/${route.params.id}`, {}, { cacheMs: 2 * 60 * 1000 })
    schedule.value = res.data
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
