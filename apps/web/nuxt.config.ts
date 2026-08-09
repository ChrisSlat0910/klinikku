export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@pinia/nuxt',
    '@vueuse/nuxt'
  ],
  devtools: {
    enabled: true
  },
  css: ['~/assets/css/main.css'],
  runtimeConfig: {
    public: {
      apiBaseUrl: '',
      reverbAppKey: '',
      reverbHost: 'localhost',
      reverbPort: '8080',
      reverbScheme: 'http'
    }
  },
  routeRules: {
    '/': { prerender: true }
  },
  compatibilityDate: '2026-06-30',
  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  }
})
