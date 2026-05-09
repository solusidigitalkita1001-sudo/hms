import { computed } from 'vue'

import { useUiPreferences } from './useUiPreferences'

export type BilingualText = {
  id: string
  en: string
}

export const bilingual = (id: string, en: string): BilingualText => ({ id, en })

export const resolveBilingualText = (value: string | BilingualText, language: 'id' | 'en') =>
  typeof value === 'string' ? value : value[language]

export function useAppLocale() {
  const { state } = useUiPreferences()

  const language = computed(() => state.language)
  const isEnglish = computed(() => state.language === 'en')

  const text = (id: string, en: string) => (isEnglish.value ? en : id)

  const pick = <T>(id: T, en: T) => (isEnglish.value ? en : id)

  const resolve = (value: string | BilingualText) => resolveBilingualText(value, language.value)

  return {
    language,
    isEnglish,
    text,
    pick,
    resolve,
  }
}
