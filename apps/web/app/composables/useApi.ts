/**
 * Simple fetch wrapper composable for Nuxt SPA.
 */
export function useApi() {
  const config = useRuntimeConfig()
  const base = config.public.apiBase as string

  function buildUrl(path: string, params?: Record<string, any>): string {
    const url = new URL(`${base}${path}`)
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== null && v !== '') url.searchParams.set(k, String(v))
      }
    }
    return url.toString()
  }

  async function get<T>(path: string, params?: Record<string, any>): Promise<T> {
    const res = await fetch(buildUrl(path, params))
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`)
    const body = await res.json() as { success: boolean; data: T; message?: string }
    if (!body.success) throw new Error(body.message ?? 'API error')
    return body.data
  }

  return { get }
}

export interface PaginatedResult<T> {
  data: T[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
  links: Record<string, string | null>
}
