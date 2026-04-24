/**
 * Fetch wrapper composable for Nuxt SPA.
 * All API responses follow { success: boolean; data: T; message?: string; meta?, links? }
 *
 * Features:
 * - Simple in-memory cache with configurable TTL
 * - Deduplication of in-flight requests
 */
const cache = new Map<string, { data: any; expireAt: number }>()

function getCache(key: string): any | null {
  const entry = cache.get(key)
  if (!entry) return null
  if (Date.now() > entry.expireAt) {
    cache.delete(key)
    return null
  }
  return entry.data
}

function setCache(key: string, data: any, ttlMs: number) {
  cache.set(key, { data, expireAt: Date.now() + ttlMs })
}

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

  // Track in-flight requests to deduplicate
  const inflight = new Map<string, Promise<any>>()

  async function get(path: string, params?: Record<string, any>, opts: { cacheMs?: number } = {}) {
    const url = buildUrl(path, params)
    const cacheMs = opts.cacheMs ?? 0

    // Check cache first
    if (cacheMs > 0) {
      const cached = getCache(url)
      if (cached) return cached
    }

    // Deduplicate in-flight requests
    if (inflight.has(url)) {
      return inflight.get(url)!
    }

    const promise = (async () => {
      try {
        const res = await fetch(url)
        if (!res.ok) throw new Error(`HTTP ${res.status}: ${res.statusText}`)
        const body = await res.json()
        if (!body.success) throw new Error(body.message ?? 'API error')

        if (cacheMs > 0) {
          setCache(url, body, cacheMs)
        }

        return body
      } finally {
        inflight.delete(url)
      }
    })()

    inflight.set(url, promise)
    return promise
  }

  return { get, buildUrl }
}
