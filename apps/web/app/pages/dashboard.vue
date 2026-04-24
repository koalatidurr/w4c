<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
      <p class="text-muted-foreground">Laporan dan statistik pengangkutan sampah</p>
    </div>

    <Card class="mb-6">
      <CardContent class="pt-6">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Dari Tanggal</label>
            <Input type="date" v-model="filters.date_from" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Sampai Tanggal</label>
            <Input type="date" v-model="filters.date_to" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Group By</label>
            <Input type="select" v-model="filters.group_by" class="w-32">
              <option value="day">Harian</option>
              <option value="month">Bulanan</option>
              <option value="year">Tahunan</option>
            </Input>
          </div>
          <Button variant="outline" @click="resetFilters" :disabled="loading">
            <RotateCcw class="h-4 w-4 mr-2" />
            Reset
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Loading state -->
    <div v-if="loading" class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card v-for="i in 4" :key="i">
          <CardContent class="pt-6">
            <Skeleton class="h-4 w-24 mb-2" />
            <Skeleton class="h-8 w-16" />
          </CardContent>
        </Card>
      </div>
      <div class="grid gap-6 lg:grid-cols-2">
        <Card><CardContent class="pt-6"><Skeleton class="h-64 w-full" /></CardContent></Card>
        <Card><CardContent class="pt-6"><Skeleton class="h-64 w-full" /></CardContent></Card>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
      <div class="text-destructive text-center">
        <AlertCircle class="h-8 w-8 mx-auto mb-2" />
        <p>{{ errorMsg }}</p>
        <Button variant="outline" size="sm" class="mt-2" @click="debouncedFetch">Coba Lagi</Button>
      </div>
    </div>

    <!-- Dashboard content -->
    <div v-else-if="dash" class="space-y-6">
      <!-- Stat cards -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Selesai (DONE)" :value="dash.transport_summary.done.toLocaleString()" color="text-green-600" />
        <StatCard title="Total Dilewati (SKIP)" :value="dash.transport_summary.skip.toLocaleString()" color="text-yellow-600" />
        <StatCard title="Belum Diangkut" :value="dash.transport_summary.not_collected.toLocaleString()" />
        <StatCard title="% Selesai" :value="dash.done_skip_percentage.done_pct + '%'" />
      </div>

      <!-- Charts row 1 -->
      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Berat Material Terpilah</CardTitle></CardHeader>
          <CardContent>
            <ClientOnly><VueApexCharts type="bar" height="300" :options="weightOptions" :series="weightSeries" /></ClientOnly>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Status Pengangkutan</CardTitle></CardHeader>
          <CardContent>
            <ClientOnly><VueApexCharts type="donut" height="300" :options="donutOptions" :series="transportSeries" :labels="['DONE', 'SKIP', 'Belum']" /></ClientOnly>
          </CardContent>
        </Card>
      </div>

      <!-- Charts row 2 -->
      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Status Pemilahan</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-4">
              <ProgressBar label="Sudah Dipilah" :value="sortingPct" color="bg-green-600" />
              <ProgressBar label="Belum Dipilah" :value="100 - sortingPct" color="bg-yellow-500" />
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
              <div class="rounded-lg bg-green-50 p-3">
                <div class="text-2xl font-bold text-green-700">{{ dash.sorting_status.sorted.toLocaleString() }}</div>
                <div class="text-xs text-green-600">Sudah Dipilah</div>
              </div>
              <div class="rounded-lg bg-yellow-50 p-3">
                <div class="text-2xl font-bold text-yellow-700">{{ dash.sorting_status.unsorted.toLocaleString() }}</div>
                <div class="text-xs text-yellow-600">Belum Dipilah</div>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle>Top 5 Material Terberat</CardTitle></CardHeader>
          <CardContent>
            <ClientOnly><VueApexCharts type="bar" height="280" :options="topWastesOptions" :series="topWastesSeries" /></ClientOnly>
          </CardContent>
        </Card>
      </div>

      <!-- Trend chart -->
      <Card>
        <CardHeader><CardTitle>Tren Jadwal vs Realisasi</CardTitle></CardHeader>
        <CardContent>
          <ClientOnly><VueApexCharts type="line" height="300" :options="trendOptions" :series="trendSeries" /></ClientOnly>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { RotateCcw, AlertCircle } from 'lucide-vue-next'
import VueApexCharts from 'vue3-apexcharts'
import { useApi } from '~/composables/useApi'

const api = useApi()

const filters = reactive({ date_from: '', date_to: '', group_by: 'day' })

const queryParams = computed(() => {
  const p: Record<string, string> = {}
  if (filters.date_from) p.date_from = filters.date_from
  if (filters.date_to) p.date_to = filters.date_to
  if (filters.group_by) p.group_by = filters.group_by
  return p
})

const loading = ref(false)
const errorMsg = ref('')
const dash = ref<any>(null)

async function doFetch() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await api.get('/dashboard', queryParams.value, { cacheMs: 5 * 60 * 1000 })
    dash.value = res.data
  } catch (e: any) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

// Debounced fetch - 400ms delay to avoid hammering API on filter changes
const debouncedFetch = useDebounceFn(doFetch, 400)

watch(filters, () => { debouncedFetch() }, { deep: true })

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.group_by = 'day'
}

// Chart data
const sortingPct = computed(() => {
  if (!dash.value) return 0
  const t = dash.value.sorting_status.total
  return t > 0 ? Math.round(dash.value.sorting_status.sorted / t * 100) : 0
})

const transportSeries = computed(() =>
  dash.value ? [
    dash.value.transport_summary.done,
    dash.value.transport_summary.skip,
    dash.value.transport_summary.not_collected,
  ] : []
)

const donutOptions = {
  chart: { type: 'donut' as const },
  labels: ['DONE', 'SKIP', 'Belum Diangkut'],
  colors: ['#16a34a', '#f59e0b', '#94a3b8'],
  legend: { position: 'bottom' as const },
  dataLabels: { enabled: true, formatter: (v: number) => `${Math.round(v)}%` },
  responsive: [{ breakpoint: 480, options: { chart: { height: 260 }, legend: { position: 'bottom' } } }],
}

const weightSeries = computed(() => {
  if (!dash.value?.waste_weight_chart?.datasets) return []
  return dash.value.waste_weight_chart.datasets.slice(0, 5).map((s: any) => ({
    name: s.waste,
    data: s.data.slice(0, 30),
  }))
})
const weightOptions = computed(() => ({
  chart: { id: 'waste-weight', toolbar: { show: false }, zoom: { enabled: false } },
  xaxis: { categories: dash.value?.waste_weight_chart?.periods?.slice(0, 30) || [] },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth' },
  colors: ['#16a34a', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
  legend: { position: 'bottom' as const },
  responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }],
}))

const topWastesSeries = computed(() => [{
  name: 'Total Berat (kg)',
  data: dash.value?.top5_heaviest_wastes?.map((w: any) => w.total_weight) || [],
}])
const topWastesOptions = computed(() => ({
  chart: { id: 'top-wastes', toolbar: { show: false } },
  xaxis: { categories: dash.value?.top5_heaviest_wastes?.map((w: any) => w.waste_name) || [] },
  colors: ['#16a34a'],
  dataLabels: { enabled: false },
}))

const trendSeries = computed(() => {
  if (!dash.value?.schedule_realization_trend) return []
  const d = dash.value.schedule_realization_trend.slice(0, 30)
  return [
    { name: 'Terjadwal', data: d.map((x: any) => x.scheduled) },
    { name: 'Terealisasi', data: d.map((x: any) => x.collected) },
  ]
})
const trendOptions = computed(() => ({
  chart: { id: 'trend', toolbar: { show: false }, zoom: { enabled: false } },
  xaxis: {
    categories: dash.value?.schedule_realization_trend?.slice(0, 30).map((d: any) => d.period) || [],
    labels: { rotate: -45, style: { fontSize: '11px' } },
  },
  stroke: { curve: 'smooth' as const },
  colors: ['#3b82f6', '#16a34a'],
  legend: { position: 'bottom' as const },
  responsive: [{ breakpoint: 640, options: { legend: { position: 'bottom' } } }],
}))

// Initial fetch
debouncedFetch()
</script>
