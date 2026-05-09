<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiChevronDown from '@iconify-icons/mdi/chevron-down'
import mdiChevronLeft from '@iconify-icons/mdi/chevron-left'
import mdiChevronRight from '@iconify-icons/mdi/chevron-right'
import mdiTableLarge from '@iconify-icons/mdi/table-large'
import { computed } from 'vue'
import { useAppLocale } from '../composables/useAppLocale'

type Column = {
  key: string
  label: string
  sortable?: boolean
  align?: 'left' | 'center' | 'right'
}

type PaginationMeta = {
  current_page: number
  per_page: number
  last_page: number
  total: number
  from: number | null
  to: number | null
}

const props = withDefaults(defineProps<{
  columns: readonly Column[]
  rows: readonly Record<string, unknown>[]
  rowKey: string
  meta: PaginationMeta
  loading?: boolean
  sortBy?: string | null
  sortDirection?: 'asc' | 'desc'
  emptyMessage?: string
  perPageOptions?: number[]
  clickableRows?: boolean
}>(), {
  loading: false,
  sortBy: null,
  sortDirection: 'desc',
  emptyMessage: '',
  perPageOptions: () => [10, 25, 50],
  clickableRows: false,
})

const emit = defineEmits<{
  sortChange: [payload: { sortBy: string; sortDirection: 'asc' | 'desc' }]
  pageChange: [page: number]
  perPageChange: [perPage: number]
  rowClick: [row: Record<string, unknown>]
}>()
const { text } = useAppLocale()
const resolvedEmptyMessage = computed(() => props.emptyMessage || text('Belum ada data.', 'No data available yet.'))
const loadingLabel = computed(() => text('Memuat data...', 'Loading data...'))
const totalRowsLabel = computed(() => text('total baris', 'total rows'))
const rowsLabel = computed(() => text('Rows', 'Rows'))

const pageNumbers = computed(() => {
  const lastPage = props.meta.last_page
  const currentPage = props.meta.current_page
  const start = Math.max(1, currentPage - 2)
  const end = Math.min(lastPage, start + 4)
  const adjustedStart = Math.max(1, end - 4)

  return Array.from({ length: Math.max(0, end - adjustedStart + 1) }, (_, index) => adjustedStart + index)
})

const toggleSort = (column: Column) => {
  if (!column.sortable) {
    return
  }

  const nextDirection = props.sortBy === column.key && props.sortDirection === 'asc' ? 'desc' : 'asc'
  emit('sortChange', {
    sortBy: column.key,
    sortDirection: nextDirection,
  })
}

const changePage = (page: number) => {
  if (page < 1 || page > props.meta.last_page || page === props.meta.current_page) {
    return
  }

  emit('pageChange', page)
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('perPageChange', Number(target.value))
}

const handleRowClick = (row: Record<string, unknown>) => {
  if (!props.clickableRows) {
    return
  }

  emit('rowClick', row)
}
</script>

<template>
  <div class="server-data-table">
    <div class="server-data-table__scroll">
      <div class="server-data-table__header" :style="{ gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))` }">
        <button
          v-for="column in columns"
          :key="column.key"
          type="button"
          class="server-data-table__head"
          :class="[
            `server-data-table__head--${column.align ?? 'left'}`,
            { 'server-data-table__head--sortable': column.sortable, 'server-data-table__head--active': sortBy === column.key },
          ]"
          :disabled="!column.sortable"
          @click="toggleSort(column)"
        >
          <span>{{ column.label }}</span>
          <Icon
            v-if="column.sortable"
            :icon="mdiChevronDown"
            class="server-data-table__sort"
            :class="{
              'server-data-table__sort--asc': sortBy === column.key && sortDirection === 'asc',
              'server-data-table__sort--desc': sortBy === column.key && sortDirection === 'desc',
            }"
          />
        </button>
      </div>

      <div v-if="loading" class="server-data-table__state">
        <Icon :icon="mdiTableLarge" />
        <strong>{{ loadingLabel }}</strong>
      </div>

      <div v-else-if="!rows.length" class="server-data-table__state">
        <Icon :icon="mdiTableLarge" />
        <strong>{{ resolvedEmptyMessage }}</strong>
      </div>

      <button
        v-for="row in rows"
        v-else
        :key="String(row[rowKey])"
        type="button"
        class="server-data-table__row"
        :class="{ 'server-data-table__row--clickable': clickableRows }"
        :style="{ gridTemplateColumns: `repeat(${columns.length}, minmax(0, 1fr))` }"
        @click="handleRowClick(row)"
      >
        <div
          v-for="column in columns"
          :key="`${String(row[rowKey])}-${column.key}`"
          class="server-data-table__cell"
          :class="`server-data-table__cell--${column.align ?? 'left'}`"
        >
          <slot :name="`cell-${column.key}`" :row="row">
            {{ row[column.key] }}
          </slot>
        </div>
      </button>
    </div>

    <div class="server-data-table__footer">
      <div class="server-data-table__summary">
        <strong>{{ meta.total }}</strong>
        <span>{{ totalRowsLabel }}</span>
        <span v-if="meta.from !== null && meta.to !== null">· {{ meta.from }}-{{ meta.to }}</span>
      </div>

      <label class="server-data-table__per-page">
        <span>{{ rowsLabel }}</span>
        <select :value="meta.per_page" @change="changePerPage">
          <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
        </select>
      </label>

      <div class="server-data-table__pagination">
        <button type="button" class="ghost-button" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">
          <Icon :icon="mdiChevronLeft" />
        </button>
        <button
          v-for="page in pageNumbers"
          :key="page"
          type="button"
          class="segmented__button"
          :class="{ 'segmented__button--active': page === meta.current_page }"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          type="button"
          class="ghost-button"
          :disabled="meta.current_page >= meta.last_page"
          @click="changePage(meta.current_page + 1)"
        >
          <Icon :icon="mdiChevronRight" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.server-data-table {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.server-data-table__scroll {
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow: auto;
}

.server-data-table__header,
.server-data-table__row {
  display: grid;
  gap: 14px;
  min-width: 900px;
  border-radius: 18px;
  padding: 14px 16px;
}

.server-data-table__header {
  background: rgba(15, 23, 42, 0.04);
}

.server-data-table__head {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--color-text-soft);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.server-data-table__head--sortable {
  cursor: pointer;
}

.server-data-table__head:disabled {
  cursor: default;
}

.server-data-table__head--center,
.server-data-table__cell--center {
  justify-content: center;
  text-align: center;
}

.server-data-table__head--right,
.server-data-table__cell--right {
  justify-content: flex-end;
  text-align: right;
}

.server-data-table__sort {
  opacity: 0.42;
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.server-data-table__head--active {
  color: var(--color-primary-strong);
}

.server-data-table__sort--asc,
.server-data-table__sort--desc {
  opacity: 1;
}

.server-data-table__sort--asc {
  transform: rotate(180deg);
}

.server-data-table__row {
  border: 1px solid var(--color-border);
  background: var(--color-surface-strong);
  text-align: left;
  transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.server-data-table__row--clickable:hover {
  transform: translateY(-1px);
  border-color: color-mix(in srgb, var(--color-primary) 18%, var(--color-border));
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
}

.server-data-table__cell {
  display: flex;
  align-items: center;
  min-width: 0;
}

.server-data-table__state {
  min-height: 180px;
  border-radius: 22px;
  border: 1px dashed var(--color-border);
  background: var(--color-surface-strong);
  color: var(--color-text-soft);
  display: grid;
  place-items: center;
  gap: 8px;
  text-align: center;
}

.server-data-table__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
}

.server-data-table__summary,
.server-data-table__per-page,
.server-data-table__pagination {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.server-data-table__summary {
  color: var(--color-text-soft);
  font-size: 0.86rem;
}

.server-data-table__summary strong {
  color: var(--color-text);
}

.server-data-table__per-page span {
  color: var(--color-text-soft);
  font-size: 0.86rem;
}

.server-data-table__per-page select {
  min-height: 40px;
  padding: 0 12px;
  border-radius: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-surface-strong);
  color: var(--color-text);
  font: inherit;
}

@media (max-width: 720px) {
  .server-data-table__footer {
    align-items: stretch;
  }

  .server-data-table__summary,
  .server-data-table__per-page,
  .server-data-table__pagination {
    justify-content: center;
  }
}
</style>
