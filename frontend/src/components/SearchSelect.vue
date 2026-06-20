<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiChevronDown from '@iconify-icons/mdi/chevron-down'
import mdiMagnify from '@iconify-icons/mdi/magnify'
import mdiCheck from '@iconify-icons/mdi/check'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useAppLocale } from '../composables/useAppLocale'

type SelectOption = {
  value: string
  label: string
  description?: string
}

const props = withDefaults(defineProps<{
  modelValue: string
  options: SelectOption[]
  placeholder?: string
  searchPlaceholder?: string
  disabled?: boolean
}>(), {
  placeholder: '',
  searchPlaceholder: '',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const root = ref<HTMLElement | null>(null)
const search = ref('')
const open = ref(false)
const { text } = useAppLocale()
const resolvedPlaceholder = computed(() => props.placeholder || text('Pilih opsi', 'Select an option'))
const resolvedSearchPlaceholder = computed(() => props.searchPlaceholder || text('Cari opsi...', 'Search options...'))
const emptyStateLabel = computed(() => text('Tidak ada opsi yang cocok.', 'No matching options found.'))

const selectedOption = computed(() => props.options.find((option) => option.value === props.modelValue) ?? null)

const filteredOptions = computed(() => {
  const keyword = search.value.trim().toLowerCase()

  if (!keyword) {
    return props.options
  }

  return props.options.filter((option) =>
    [option.label, option.description ?? '']
      .join(' ')
      .toLowerCase()
      .includes(keyword),
  )
})

const close = () => {
  open.value = false
  search.value = ''
}

const toggle = () => {
  if (props.disabled) {
    return
  }

  open.value = !open.value

  if (!open.value) {
    search.value = ''
  }
}

const selectOption = (value: string) => {
  emit('update:modelValue', value)
  close()
}

const handleOutside = (event: MouseEvent) => {
  if (!root.value?.contains(event.target as Node)) {
    close()
  }
}

watch(() => props.modelValue, () => {
  search.value = ''
})

onMounted(() => {
  document.addEventListener('mousedown', handleOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleOutside)
})
</script>

<template>
  <div ref="root" class="search-select" :class="{ 'search-select--open': open, 'search-select--disabled': disabled }">
    <button type="button" class="search-select__trigger" :disabled="disabled" @click="toggle">
      <div class="search-select__value">
        <strong>{{ selectedOption?.label || resolvedPlaceholder }}</strong>
        <span v-if="selectedOption?.description">{{ selectedOption.description }}</span>
      </div>
      <Icon :icon="mdiChevronDown" class="search-select__chevron" />
    </button>

    <div v-if="open" class="search-select__dropdown">
      <label class="search-select__search">
        <Icon :icon="mdiMagnify" />
        <input v-model="search" type="text" :placeholder="resolvedSearchPlaceholder" />
      </label>

      <div class="search-select__options">
        <button
          v-for="option in filteredOptions"
          :key="option.value"
          type="button"
          class="search-select__option"
          :class="{ 'search-select__option--active': option.value === modelValue }"
          @click="selectOption(option.value)"
        >
          <div>
            <strong>{{ option.label }}</strong>
            <span v-if="option.description">{{ option.description }}</span>
          </div>
          <Icon v-if="option.value === modelValue" :icon="mdiCheck" class="search-select__check" />
        </button>

        <div v-if="!filteredOptions.length" class="search-select__empty">{{ emptyStateLabel }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.search-select {
  position: relative;
  width: 100%;
}

.search-select--open {
  z-index: 140;
}

.search-select__trigger,
.search-select__search,
.search-select__option,
.search-select__empty {
  border: 1px solid var(--color-border, var(--portal-border));
}

.search-select__trigger {
  width: 100%;
  min-height: 54px;
  padding: 12px 14px 12px 16px;
  border-radius: 18px;
  background: var(--color-surface-strong, var(--portal-surface-strong));
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.search-select__value {
  min-width: 0;
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 4px;
}

.search-select__value strong {
  color: var(--color-text, var(--portal-text));
  font-size: 0.92rem;
  font-weight: 600;
  line-height: 1.2;
}

.search-select__value span {
  color: var(--color-text-soft, var(--portal-text-soft));
  font-size: 0.8rem;
  line-height: 1.35;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-select__chevron {
  flex-shrink: 0;
  color: var(--color-text-soft, var(--portal-text-soft));
  transition: transform 0.18s ease;
}

.search-select--open .search-select__chevron {
  transform: rotate(180deg);
}

.search-select--open .search-select__trigger,
.search-select__trigger:focus-visible {
  outline: none;
  border-color: color-mix(in srgb, var(--color-primary, var(--portal-accent)) 32%, transparent);
  box-shadow: var(--shadow-focus);
}

.search-select__dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  z-index: 160;
  padding: 10px;
  border-radius: 22px;
  background: var(--color-surface, var(--portal-surface));
  border: 1px solid var(--color-border, var(--portal-border));
  box-shadow: var(--shadow-lg, var(--portal-shadow));
}

.search-select__search {
  min-height: 48px;
  padding: 0 14px;
  border-radius: 6px;
  background: var(--color-surface-strong, var(--portal-surface-strong));
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-select__search svg {
  color: var(--color-text-soft, var(--portal-text-soft));
}

.search-select__search input {
  width: 100%;
  border: 0;
  outline: none;
  background: transparent;
  color: var(--color-text, var(--portal-text));
  font: inherit;
  font-size: 0.9rem;
}

.search-select__options {
  max-height: 260px;
  overflow: auto;
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.search-select__option,
.search-select__empty {
  width: 100%;
  padding: 12px 14px;
  border-radius: 16px;
  background: var(--color-surface-strong, var(--portal-surface-strong));
}

.search-select__option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
  transition: border-color 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
}

.search-select__option:hover {
  transform: translateY(-1px);
}

.search-select__option div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.search-select__option strong {
  color: var(--color-text, var(--portal-text));
  font-size: 0.9rem;
  font-weight: 600;
}

.search-select__option span,
.search-select__empty {
  color: var(--color-text-soft, var(--portal-text-soft));
  font-size: 0.8rem;
  line-height: 1.4;
}

.search-select__option--active {
  border-color: color-mix(in srgb, var(--color-primary, var(--portal-accent)) 26%, transparent);
  background: color-mix(in srgb, var(--color-primary, var(--portal-accent)) 10%, white 90%);
}

.search-select__check {
  flex-shrink: 0;
  color: var(--color-primary, var(--portal-accent));
}

.search-select--disabled .search-select__trigger {
  opacity: 0.64;
  cursor: not-allowed;
}
</style>
