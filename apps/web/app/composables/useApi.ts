/**
 * Simple fetch wrapper composable for Nuxt SPA.
 * All API responses follow { success: boolean; data: T; message?: string; meta?, links? }
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

  async function get(path: string, params?: Record<string, any>) {
    const res = await fetch(buildUrl(path, params))
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`)
    const body = await res.json()
    if (!body.success) throw new Error(body.message ?? 'API error')
    return body
  }

  return { get }
}
