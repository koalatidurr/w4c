<template>
  <div>
    <div class="mb-6">
      <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
      <p class="text-muted-foreground">Laporan dan statistik pengangkutan sampah</p>
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
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Group By</label>
            <Input type="select" v-model="filters.group_by">
              <option value="day">Harian</option>
              <option value="month">Bulanan</option>
              <option value="year">Tahunan</option>
            </Input>
          </div>
          <div class="flex items-end">
            <Button variant="outline" @click="resetFilters">Reset</Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <div v-if="loading" class="flex items-center justify-center p-12">
      <div class="text-muted-foreground">Memuat data dashboard...</div>
    </div>
    <div v-else-if="errorMsg" class="flex items-center justify-center p-12">
      <div class="text-destructive">Gagal memuat dashboard: {{ errorMsg }}</div>
    </div>

    <div v-else-if="dash" class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Total Selesai (DONE)" :value="dash.transport_summary.done" color="text-green-600" />
        <StatCard title="Total Dilewati (SKIP)" :value="dash.transport_summary.skip" color="text-yellow-600" />
        <StatCard title="Belum Diangkut" :value="dash.transport_summary.not_collected" />
        <StatCard title="% Selesai" :value="dash.done_skip_percentage.done_pct + '%'" />
      </div>

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

      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Status Pemilahan</CardTitle></CardHeader>
          <CardContent>
            <div class="space-y-4">
              <ProgressBar label="Sudah Dipilah" :value="sortingPct" color="bg-green-600" />
              <ProgressBar label="Belum Dipilah" :value="100 - sortingPct" color="bg-yellow-500" />
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
import { reactive, ref, computed, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import { useApi } from '~/composables/useApi'

const api = useApi()

const filters = reactive({ date_from: '', date_to: '', group_by: 'day' })
const dash = ref<any>(null)
const loading = ref(false)
const errorMsg = ref('')

const sortingPct = computed(() => {
  if (!dash.value) return 0
  const t = dash.value.sorting_status.total
  return t > 0 ? Math.round(dash.value.sorting_status.sorted / t * 100) : 0
})

const transportSeries = computed(() =>
  dash.value ? [dash.value.transport_summary.done, dash.value.transport_summary.skip, dash.value.transport_summary.not_collected] : []
)

const donutOptions = {
  chart: { type: 'donut' as const },
  labels: ['DONE', 'SKIP', 'Belum Diangkut'],
  colors: ['#16a34a', '#f59e0b', '#94a3b8'],
  legend: { position: 'bottom' as const },
  dataLabels: { enabled: true, formatter: (v: number) => `${Math.round(v)}%` },
}

const weightSeries = computed(() => {
  if (!dash.value?.waste_weight_chart?.datasets) return []
  return dash.value.waste_weight_chart.datasets.slice(0, 5).map((s: any) => ({ name: s.waste, data: s.data.slice(0, 30) }))
})
const weightOptions = computed(() => ({
  chart: { id: 'waste-weight', toolbar: { show: false }, zoom: { enabled: false } },
  xaxis: { categories: dash.value?.waste_weight_chart?.periods?.slice(0, 30) || [] },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth' },
  colors: ['#16a34a', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
  legend: { position: 'bottom' as const },
}))

const topWastesSeries = computed(() => [{
  name: 'Total Berat (kg)',
  data: dash.value?.top5_heaviest_wastes?.map((w: any) => w.total_weight) || [],
}])
const topWastesOptions = computed(() => ({
  chart: { id: 'top-wastes', toolbar: { show: false },
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
  chart: { id: 'trend', toolbar: { show: false }, zoom: { enabled: false },
  xaxis: { categories: dash.value?.schedule_realization_trend?.slice(0, 30).map((d: any) => d.period) || [] },
  stroke: { curve: 'smooth' as const },
  colors: ['#3b82f6', '#16a34a'],
  legend: { position: 'bottom' as const },
}))

async function fetchDashboard() {
  loading.value = true
  errorMsg.value = ''
  try {
    const params: Record<string, string> = {}
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    if (filters.group_by) params.group_by = filters.group_by
    dash.value = await api.get('/dashboard', params)
  } catch (e: any) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.group_by = 'day'
  fetchDashboard()
}

watch([() => filters.date_from, () => filters.date_to, () => filters.group_by], fetchDashboard)

fetchDashboard()
</script>
