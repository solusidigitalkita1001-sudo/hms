<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiArrowTopRight from '@iconify-icons/mdi/arrow-top-right'
import mdiContentSaveOutline from '@iconify-icons/mdi/content-save-outline'
import mdiPlus from '@iconify-icons/mdi/plus'
import mdiRefresh from '@iconify-icons/mdi/refresh'
import mdiTrashCanOutline from '@iconify-icons/mdi/trash-can-outline'
import { computed, onMounted, reactive, ref } from 'vue'

import AppShell from '../components/AppShell.vue'
import { useAppLocale } from '../composables/useAppLocale'
import { useAuthSession } from '../composables/useAuthSession'
import { buildApiUrl } from '../lib/api'

type NavItem = {
  label: string
  url: string
}

type DestinationItem = {
  title: string
  subtitle: string
  image_url: string
}

type ExploreFilter = {
  title: string
  items: string[]
}

type FeaturedHotel = {
  brand: string
  name: string
  description: string
  image_url: string
  rating: string
  location: string
}

type PortalCmsForm = {
  property_code: string
  announcement_badge: string
  announcement_text: string
  announcement_link_label: string
  announcement_link_url: string
  hero_title: string
  hero_subtitle: string
  hero_image_url: string
  hero_search_destination_label: string
  hero_search_destination_value: string
  hero_search_date_label: string
  hero_search_date_value: string
  hero_search_room_label: string
  hero_search_room_value: string
  hero_search_button_label: string
  destinations_title: string
  explore_title: string
  cta_title: string
  cta_description: string
  cta_primary_label: string
  nav_items: NavItem[]
  destinations: DestinationItem[]
  explore_filters: ExploreFilter[]
  featured_hotels: FeaturedHotel[]
}

const { state: authState } = useAuthSession()
const { isEnglish } = useAppLocale()
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Portal CMS',
      eyebrow: 'Portal Content Management',
      summary: 'Manage the public portal landing page structure, from the announcement bar, hero, destinations, sidebar filters, to highlight stay cards.',
      metrics: ['Property', 'Sections', 'Status'],
      sectionValues: ['Draft sync'],
      newMenu: 'New menu',
      newArea: 'New area',
      newAreaDesc: 'Short description for the area or destination category.',
      newFilter: 'New filter',
      newHighlight: 'New highlight',
      newHighlightDesc: 'Short description for the stay highlight or primary card.',
      builder: 'Portal builder',
      builderTitle: 'CMS for all landing portal content',
      previewPortal: 'Preview portal',
      savePortal: 'Save portal CMS',
      saving: 'Saving...',
      loading: 'Loading CMS...',
      announcement: 'Announcement',
      announcementTitle: 'Top announcement bar',
      hero: 'Hero',
      heroTitle: 'Main portal content',
      navigation: 'Navigation',
      navigationTitle: 'Portal header menu',
      addMenu: 'Add menu',
      searchStrip: 'Search strip',
      searchStripTitle: 'Content fields in the hero booking bar',
      destinations: 'Destinations',
      destinationsTitle: 'Destination and area cards',
      addDestination: 'Add destination',
      explore: 'Explore',
      exploreTitle: 'Sidebar filters',
      addFilter: 'Add filter',
      cta: 'CTA',
      ctaTitle: 'Footer call to action',
      featuredCards: 'Featured cards',
      featuredCardsTitle: 'Stay highlight cards in the explore section',
      addCard: 'Add card',
      addFilterItem: 'Add filter item',
    }
  }

  return {
    title: 'Portal CMS',
    eyebrow: 'Portal Content Management',
    summary: 'Kelola struktur landing page portal publik, mulai dari announcement bar, hero, destinasi, filter sidebar, sampai kartu highlight stay.',
    metrics: ['Property', 'Sections', 'Status'],
    sectionValues: ['Draft sync'],
    newMenu: 'Menu baru',
    newArea: 'Area baru',
    newAreaDesc: 'Deskripsi singkat area atau kategori destinasi.',
    newFilter: 'Filter baru',
    newHighlight: 'Highlight baru',
    newHighlightDesc: 'Deskripsi singkat highlight stay atau card utama.',
    builder: 'Portal builder',
    builderTitle: 'CMS untuk isi seluruh konten landing portal',
    previewPortal: 'Preview portal',
    savePortal: 'Simpan portal CMS',
    saving: 'Menyimpan...',
    loading: 'Loading CMS...',
    announcement: 'Announcement',
    announcementTitle: 'Bar pengumuman atas',
    hero: 'Hero',
    heroTitle: 'Konten utama portal',
    navigation: 'Navigation',
    navigationTitle: 'Menu header portal',
    addMenu: 'Tambah menu',
    searchStrip: 'Search strip',
    searchStripTitle: 'Field konten di booking bar hero',
    destinations: 'Destinations',
    destinationsTitle: 'Kartu destinasi dan area',
    addDestination: 'Tambah destinasi',
    explore: 'Explore',
    exploreTitle: 'Filter sidebar',
    addFilter: 'Tambah filter',
    cta: 'CTA',
    ctaTitle: 'Call to action footer',
    featuredCards: 'Featured cards',
    featuredCardsTitle: 'Kartu highlight stay di section explore',
    addCard: 'Tambah kartu',
    addFilterItem: 'Tambah item filter',
  }
})

const metrics = ref([
  { label: 'Property', value: 'MAIN', tone: 'primary' as const },
  { label: 'Sections', value: '5', tone: 'success' as const },
  { label: 'Status', value: 'Draft sync', tone: 'neutral' as const },
])

const createNavItem = (): NavItem => ({
  label: copy.value.newMenu,
  url: '#section',
})

const createDestination = (): DestinationItem => ({
  title: copy.value.newArea,
  subtitle: copy.value.newAreaDesc,
  image_url: '',
})

const createExploreFilter = (): ExploreFilter => ({
  title: copy.value.newFilter,
  items: ['Item 1'],
})

const createFeaturedHotel = (): FeaturedHotel => ({
  brand: 'NEW',
  name: copy.value.newHighlight,
  description: copy.value.newHighlightDesc,
  image_url: '',
  rating: '4.8/5',
  location: 'Jakarta',
})

const createDefaultForm = (): PortalCmsForm => ({
  property_code: 'MAIN',
  announcement_badge: '',
  announcement_text: '',
  announcement_link_label: '',
  announcement_link_url: '',
  hero_title: '',
  hero_subtitle: '',
  hero_image_url: '',
  hero_search_destination_label: '',
  hero_search_destination_value: '',
  hero_search_date_label: '',
  hero_search_date_value: '',
  hero_search_room_label: '',
  hero_search_room_value: '',
  hero_search_button_label: '',
  destinations_title: '',
  explore_title: '',
  cta_title: '',
  cta_description: '',
  cta_primary_label: '',
  nav_items: [],
  destinations: [],
  explore_filters: [],
  featured_hotels: [],
})

const form = reactive<PortalCmsForm>(createDefaultForm())

const applyData = (data: PortalCmsForm) => {
  Object.assign(form, {
    ...data,
    nav_items: data.nav_items.map((item) => ({ ...item })),
    destinations: data.destinations.map((item) => ({ ...item })),
    explore_filters: data.explore_filters.map((item) => ({
      ...item,
      items: [...item.items],
    })),
    featured_hotels: data.featured_hotels.map((item) => ({ ...item })),
  })
}

const authHeaders = (includeJson = false) => ({
  ...(includeJson ? { 'Content-Type': 'application/json' } : {}),
  Authorization: `Bearer ${authState.token}`,
})

const loadPortalCms = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/settings/portal-cms?property_code=MAIN'), {
      headers: authHeaders(),
    })

    const payload = (await response.json()) as {
      success: boolean
      message: string
      data: PortalCmsForm
    }

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || (isEnglish.value ? 'Failed to load Portal CMS.' : 'Portal CMS gagal dimuat.'))
    }

    applyData(payload.data)
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : (isEnglish.value ? 'Failed to load Portal CMS.' : 'Portal CMS gagal dimuat.')
  } finally {
    loading.value = false
  }
}

const savePortalCms = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch(buildApiUrl('/api/v1/settings/portal-cms'), {
      method: 'PUT',
      headers: authHeaders(true),
      body: JSON.stringify(form),
    })

    const payload = (await response.json()) as {
      success: boolean
      message: string
      data: PortalCmsForm
      errors?: Record<string, string[]>
    }

    if (!response.ok || !payload.success) {
      const firstError = Object.values(payload.errors ?? {})[0]?.[0]
      throw new Error(firstError || payload.message || (isEnglish.value ? 'Failed to save Portal CMS.' : 'Portal CMS gagal disimpan.'))
    }

    applyData(payload.data)
    successMessage.value = isEnglish.value ? 'Portal CMS saved successfully.' : 'Portal CMS berhasil disimpan.'
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : (isEnglish.value ? 'Failed to save Portal CMS.' : 'Portal CMS gagal disimpan.')
  } finally {
    saving.value = false
  }
}

const addFilterItem = (filterIndex: number) => {
  form.explore_filters[filterIndex]?.items.push(`Item ${form.explore_filters[filterIndex].items.length + 1}`)
}

const removeFilterItem = (filterIndex: number, itemIndex: number) => {
  if (form.explore_filters[filterIndex]?.items.length === 1) {
    return
  }

  form.explore_filters[filterIndex]?.items.splice(itemIndex, 1)
}

onMounted(loadPortalCms)
</script>

<template>
  <AppShell
    :title="copy.title"
    :eyebrow="copy.eyebrow"
    :summary="copy.summary"
    :metrics="metrics"
  >
    <section class="content-grid">
      <article class="surface-card surface-card--wide">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.builder }}</span>
            <h2>{{ copy.builderTitle }}</h2>
          </div>

          <div class="action-pair">
            <a href="/portal/main" target="_blank" rel="noreferrer" class="secondary-button">
              {{ copy.previewPortal }}
              <Icon :icon="mdiArrowTopRight" />
            </a>
            <button type="button" class="primary-button" :disabled="saving" @click="savePortalCms">
              <Icon :icon="mdiContentSaveOutline" />
              {{ saving ? copy.saving : copy.savePortal }}
            </button>
          </div>
        </div>

        <div class="cms-status-row">
          <div v-if="loading" class="cms-status-pill">
            <Icon :icon="mdiRefresh" class="cms-spin" />
            {{ copy.loading }}
          </div>
          <div v-else-if="successMessage" class="cms-status-pill cms-status-pill--success">{{ successMessage }}</div>
          <div v-if="errorMessage" class="cms-status-pill cms-status-pill--error">{{ errorMessage }}</div>
        </div>
      </article>
    </section>

    <template v-if="!loading">
      <section class="content-grid">
        <article class="surface-card">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Announcement</span>
              <h2>{{ copy.announcementTitle }}</h2>
            </div>
          </div>

          <div class="cms-stack">
            <label class="field">
              <span>Badge</span>
              <input v-model="form.announcement_badge" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Teks utama</span>
              <input v-model="form.announcement_text" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Label link</span>
              <input v-model="form.announcement_link_label" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Target link</span>
              <input v-model="form.announcement_link_url" type="text" class="text-input" />
            </label>
          </div>
        </article>

        <article class="surface-card">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Hero</span>
              <h2>{{ copy.heroTitle }}</h2>
            </div>
          </div>

          <div class="cms-stack">
            <label class="field">
              <span>Headline</span>
              <input v-model="form.hero_title" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Subheadline</span>
              <textarea v-model="form.hero_subtitle" class="cms-textarea"></textarea>
            </label>
            <label class="field">
              <span>Hero image URL</span>
              <input v-model="form.hero_image_url" type="text" class="text-input" />
            </label>
          </div>
        </article>
      </section>

      <section class="content-grid">
        <article class="surface-card surface-card--wide">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Navigation</span>
              <h2>{{ copy.navigationTitle }}</h2>
            </div>
            <button type="button" class="secondary-button" @click="form.nav_items.push(createNavItem())">
              <Icon :icon="mdiPlus" />
              {{ copy.addMenu }}
            </button>
          </div>

          <div class="cms-repeater-grid cms-repeater-grid--two">
            <div v-for="(item, index) in form.nav_items" :key="`${item.label}-${index}`" class="cms-item-card">
              <div class="cms-item-card__header">
                <strong>Menu {{ index + 1 }}</strong>
                <button type="button" class="ghost-button" @click="form.nav_items.splice(index, 1)">
                  <Icon :icon="mdiTrashCanOutline" />
                </button>
              </div>
              <label class="field">
                <span>Label</span>
                <input v-model="item.label" type="text" class="text-input" />
              </label>
              <label class="field">
                <span>URL</span>
                <input v-model="item.url" type="text" class="text-input" />
              </label>
            </div>
          </div>
        </article>
      </section>

      <section class="content-grid">
        <article class="surface-card surface-card--wide">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Search strip</span>
              <h2>{{ copy.searchStripTitle }}</h2>
            </div>
          </div>

          <div class="cms-repeater-grid cms-repeater-grid--three">
            <label class="field">
              <span>Label destinasi</span>
              <input v-model="form.hero_search_destination_label" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Value destinasi</span>
              <input v-model="form.hero_search_destination_value" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Label tanggal</span>
              <input v-model="form.hero_search_date_label" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Value tanggal</span>
              <input v-model="form.hero_search_date_value" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Label kamar</span>
              <input v-model="form.hero_search_room_label" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Value kamar</span>
              <input v-model="form.hero_search_room_value" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Label tombol</span>
              <input v-model="form.hero_search_button_label" type="text" class="text-input" />
            </label>
          </div>
        </article>
      </section>

      <section class="content-grid">
        <article class="surface-card surface-card--wide">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Destinations</span>
              <h2>{{ copy.destinationsTitle }}</h2>
            </div>
            <button type="button" class="secondary-button" @click="form.destinations.push(createDestination())">
              <Icon :icon="mdiPlus" />
              {{ copy.addDestination }}
            </button>
          </div>

          <label class="field">
            <span>Judul section</span>
            <input v-model="form.destinations_title" type="text" class="text-input" />
          </label>

          <div class="cms-repeater-grid cms-repeater-grid--three">
            <div v-for="(item, index) in form.destinations" :key="`${item.title}-${index}`" class="cms-item-card">
              <div class="cms-item-card__header">
                <strong>Destinasi {{ index + 1 }}</strong>
                <button type="button" class="ghost-button" @click="form.destinations.splice(index, 1)">
                  <Icon :icon="mdiTrashCanOutline" />
                </button>
              </div>
              <label class="field">
                <span>Judul</span>
                <input v-model="item.title" type="text" class="text-input" />
              </label>
              <label class="field">
                <span>Subtitle</span>
                <textarea v-model="item.subtitle" class="cms-textarea"></textarea>
              </label>
              <label class="field">
                <span>Image URL</span>
                <input v-model="item.image_url" type="text" class="text-input" />
              </label>
            </div>
          </div>
        </article>
      </section>

      <section class="content-grid">
        <article class="surface-card">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Explore</span>
              <h2>{{ copy.exploreTitle }}</h2>
            </div>
            <button type="button" class="secondary-button" @click="form.explore_filters.push(createExploreFilter())">
              <Icon :icon="mdiPlus" />
              {{ copy.addFilter }}
            </button>
          </div>

          <label class="field">
            <span>Judul explore section</span>
            <input v-model="form.explore_title" type="text" class="text-input" />
          </label>

          <div class="cms-stack">
            <div v-for="(filter, filterIndex) in form.explore_filters" :key="`${filter.title}-${filterIndex}`" class="cms-item-card">
              <div class="cms-item-card__header">
                <strong>Filter {{ filterIndex + 1 }}</strong>
                <button type="button" class="ghost-button" @click="form.explore_filters.splice(filterIndex, 1)">
                  <Icon :icon="mdiTrashCanOutline" />
                </button>
              </div>

              <label class="field">
                <span>Judul filter</span>
                <input v-model="filter.title" type="text" class="text-input" />
              </label>

              <div class="cms-stack">
                <div v-for="(item, itemIndex) in filter.items" :key="`${item}-${itemIndex}`" class="cms-inline-row">
                  <input v-model="filter.items[itemIndex]" type="text" class="text-input" />
                  <button type="button" class="ghost-button" @click="removeFilterItem(filterIndex, itemIndex)">
                    <Icon :icon="mdiTrashCanOutline" />
                  </button>
                </div>
              </div>

              <button type="button" class="secondary-button" @click="addFilterItem(filterIndex)">
                <Icon :icon="mdiPlus" />
                {{ copy.addFilterItem }}
              </button>
            </div>
          </div>
        </article>

        <article class="surface-card">
          <div class="section-heading">
            <div>
              <span class="section-kicker">CTA</span>
              <h2>{{ copy.ctaTitle }}</h2>
            </div>
          </div>

          <div class="cms-stack">
            <label class="field">
              <span>Judul CTA</span>
              <input v-model="form.cta_title" type="text" class="text-input" />
            </label>
            <label class="field">
              <span>Deskripsi CTA</span>
              <textarea v-model="form.cta_description" class="cms-textarea"></textarea>
            </label>
            <label class="field">
              <span>Label tombol CTA</span>
              <input v-model="form.cta_primary_label" type="text" class="text-input" />
            </label>
          </div>
        </article>
      </section>

      <section class="content-grid">
        <article class="surface-card surface-card--wide">
          <div class="section-heading">
            <div>
              <span class="section-kicker">Featured cards</span>
              <h2>{{ copy.featuredCardsTitle }}</h2>
            </div>
            <button type="button" class="secondary-button" @click="form.featured_hotels.push(createFeaturedHotel())">
              <Icon :icon="mdiPlus" />
              {{ copy.addCard }}
            </button>
          </div>

          <div class="cms-repeater-grid cms-repeater-grid--two">
            <div v-for="(item, index) in form.featured_hotels" :key="`${item.name}-${index}`" class="cms-item-card">
              <div class="cms-item-card__header">
                <strong>Card {{ index + 1 }}</strong>
                <button type="button" class="ghost-button" @click="form.featured_hotels.splice(index, 1)">
                  <Icon :icon="mdiTrashCanOutline" />
                </button>
              </div>

              <label class="field">
                <span>Brand</span>
                <input v-model="item.brand" type="text" class="text-input" />
              </label>
              <label class="field">
                <span>Nama</span>
                <input v-model="item.name" type="text" class="text-input" />
              </label>
              <label class="field">
                <span>Deskripsi</span>
                <textarea v-model="item.description" class="cms-textarea"></textarea>
              </label>
              <label class="field">
                <span>Image URL</span>
                <input v-model="item.image_url" type="text" class="text-input" />
              </label>

              <div class="cms-inline-grid">
                <label class="field">
                  <span>Rating</span>
                  <input v-model="item.rating" type="text" class="text-input" />
                </label>
                <label class="field">
                  <span>Lokasi</span>
                  <input v-model="item.location" type="text" class="text-input" />
                </label>
              </div>
            </div>
          </div>
        </article>
      </section>
    </template>
  </AppShell>
</template>
