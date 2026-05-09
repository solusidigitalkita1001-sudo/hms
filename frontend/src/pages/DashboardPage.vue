<script setup lang="ts">
import { Icon } from '@iconify/vue'
import mdiArrowTopRight from '@iconify-icons/mdi/arrow-top-right'
import { computed } from 'vue'

import AppShell from '../components/AppShell.vue'
import { getDashboardPanels, getDashboardQuickLinks } from '../config/workspace'
import { useAppLocale } from '../composables/useAppLocale'

const { language, isEnglish } = useAppLocale()

const dashboardPanels = computed(() => getDashboardPanels(language.value))
const dashboardQuickLinks = computed(() => getDashboardQuickLinks(language.value))

const copy = computed(() => {
  if (isEnglish.value) {
    return {
      title: 'Dashboard',
      eyebrow: 'Operational Command Center',
      summary: 'Premium dashboard to monitor reservations, front desk, room board, housekeeping, billing, and inventory in one business flow aligned with requirements and data design.',
      metrics: ['Today Occupancy', 'Revenue Today', 'Pending Housekeeping', 'Outstanding Payment'],
      updatedLive: 'Updated live',
      demandTrend: 'Demand trend',
      occupancy7Days: 'Occupancy for the last 7 days',
      revenueMix: 'Revenue mix',
      revenueMixTitle: 'Channel composition today',
      quickAccess: 'Quick access',
      quickAccessTitle: 'Most-used daily actions',
      businessFlow: 'Business flow',
      businessFlowTitle: 'Core flows already mapped to DB',
      operationalPulse: 'Operational pulse',
      operationalPulseTitle: 'Current shift readiness score',
      frontDeskPulse: 'Front desk pulse',
      frontDeskPulseTitle: 'Highlights for this shift',
      frontDeskChips: ['9 arrivals ready for check-in', '3 dirty rooms with high priority', '2 inquiries ready for follow-up', '1 major outstanding invoice'],
      roomBoard: 'Room board',
      roomBoardTitle: 'Real-time room status',
      openBoard: 'Open board',
      housekeeping: 'Housekeeping',
      housekeepingTitle: 'Task queue and verification',
      openTasks: 'Open tasks',
      frontDesk: 'Front desk',
      frontDeskTitle: 'Today arrivals and departures',
      arrivals: 'Arrivals',
      departures: 'Departures',
      room: 'Room',
      inventoryAlerts: 'Inventory alerts',
      inventoryAlertsTitle: 'Minimum stock requiring action',
      openInventory: 'Open inventory',
      days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      revenueChannels: ['Direct Web', 'Walk-in', 'OTA', 'Corporate'],
      operationalPulseLabels: ['Check-in readiness', 'Housekeeping SLA', 'Collection progress'],
    }
  }

  return {
    title: 'Dashboard',
    eyebrow: 'Operational Command Center',
    summary: 'Dashboard premium untuk memantau reservasi, front desk, room board, housekeeping, billing, dan inventori dalam satu flow bisnis yang mengikuti requirement serta desain data.',
    metrics: ['Occupancy Hari Ini', 'Revenue Today', 'Pending Housekeeping', 'Outstanding Payment'],
    updatedLive: 'Updated live',
    demandTrend: 'Demand trend',
    occupancy7Days: 'Occupancy 7 hari terakhir',
    revenueMix: 'Revenue mix',
    revenueMixTitle: 'Komposisi channel hari ini',
    quickAccess: 'Quick access',
    quickAccessTitle: 'Aksi harian paling sering dipakai',
    businessFlow: 'Business flow',
    businessFlowTitle: 'Alur inti yang sudah dipetakan ke DB',
    operationalPulse: 'Operational pulse',
    operationalPulseTitle: 'Skor kesiapan shift saat ini',
    frontDeskPulse: 'Front desk pulse',
    frontDeskPulseTitle: 'Highlights shift ini',
    frontDeskChips: ['9 arrival siap check-in', '3 kamar dirty prioritas tinggi', '2 inquiry siap follow up', '1 invoice outstanding besar'],
    roomBoard: 'Room board',
    roomBoardTitle: 'Status kamar real-time',
    openBoard: 'Open board',
    housekeeping: 'Housekeeping',
    housekeepingTitle: 'Task queue dan verifikasi',
    openTasks: 'Open tasks',
    frontDesk: 'Front desk',
    frontDeskTitle: 'Arrival dan departure hari ini',
    arrivals: 'Arrivals',
    departures: 'Departures',
    room: 'Room',
    inventoryAlerts: 'Inventory alerts',
    inventoryAlertsTitle: 'Minimum stock yang perlu tindakan',
    openInventory: 'Open inventory',
    days: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
    revenueChannels: ['Direct Web', 'Walk-in', 'OTA', 'Corporate'],
    operationalPulseLabels: ['Check-in readiness', 'Housekeeping SLA', 'Collection progress'],
  }
})

const metrics = computed(() => [
  { label: copy.value.metrics[0], value: '78%', tone: 'primary' as const },
  { label: copy.value.metrics[1], value: 'Rp 12.400.000', tone: 'success' as const },
  { label: copy.value.metrics[2], value: isEnglish.value ? '5 Tasks' : '5 Tasks', tone: 'warning' as const },
  { label: copy.value.metrics[3], value: 'Rp 5.400.000', tone: 'danger' as const },
])

const occupancyTrend = computed(() => copy.value.days.map((day, index) => ({
  day,
  value: [68, 72, 75, 70, 82, 91, 86][index],
})))
const revenueChannels = computed(() => [
  { label: copy.value.revenueChannels[0], value: 'Rp 4,8 Jt', percentage: 38 },
  { label: copy.value.revenueChannels[1], value: 'Rp 2,1 Jt', percentage: 16 },
  { label: copy.value.revenueChannels[2], value: 'Rp 4,3 Jt', percentage: 34 },
  { label: copy.value.revenueChannels[3], value: 'Rp 1,4 Jt', percentage: 12 },
])

const operationalPulse = computed(() => [
  { label: copy.value.operationalPulseLabels[0], value: 84, tone: 'primary' as const },
  { label: copy.value.operationalPulseLabels[1], value: 76, tone: 'success' as const },
  { label: copy.value.operationalPulseLabels[2], value: 63, tone: 'warning' as const },
])
</script>

<template>
  <AppShell
    :title="copy.title"
    :eyebrow="copy.eyebrow"
    :summary="copy.summary"
    :metrics="metrics"
    hero-variant="plain"
  >
    <section class="dashboard-grid dashboard-grid--hero">
      <article class="surface-card surface-card--accent">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.demandTrend }}</span>
            <h2>{{ copy.occupancy7Days }}</h2>
          </div>
          <span class="status-badge status-badge--soft">{{ copy.updatedLive }}</span>
        </div>

        <div class="trend-chart">
          <div class="trend-chart__canvas">
            <div
              v-for="point in occupancyTrend"
              :key="point.day"
              class="trend-chart__bar"
              :style="{ height: `${point.value}%` }"
            ></div>
          </div>

          <div class="trend-chart__labels">
            <div v-for="point in occupancyTrend" :key="`label-${point.day}`" class="trend-chart__label">
              <strong>{{ point.value }}%</strong>
              <span>{{ point.day }}</span>
            </div>
          </div>
        </div>
      </article>

      <article class="surface-card surface-card--soft">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.revenueMix }}</span>
            <h2>{{ copy.revenueMixTitle }}</h2>
          </div>
        </div>

        <div class="stack-list">
          <div v-for="channel in revenueChannels" :key="channel.label" class="stack-list__row">
            <div class="stack-list__copy">
              <strong>{{ channel.label }}</strong>
              <span>{{ channel.value }}</span>
            </div>
            <div class="stack-meter">
              <div class="stack-meter__fill" :style="{ width: `${channel.percentage}%` }"></div>
            </div>
            <strong class="stack-list__value">{{ channel.percentage }}%</strong>
          </div>
        </div>
      </article>
    </section>

    <section class="dashboard-grid dashboard-grid--hero">
      <article class="surface-card surface-card--soft">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.quickAccess }}</span>
            <h2>{{ copy.quickAccessTitle }}</h2>
          </div>
        </div>

        <div class="quick-link-grid">
          <RouterLink v-for="item in dashboardQuickLinks" :key="item.to" :to="item.to" class="quick-link-card">
            <span class="quick-link-card__icon">
              <Icon :icon="item.icon" />
            </span>
            <strong>{{ item.label }}</strong>
            <Icon :icon="mdiArrowTopRight" class="quick-link-card__arrow" />
          </RouterLink>
        </div>
      </article>

      <article class="surface-card surface-card--accent">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.businessFlow }}</span>
            <h2>{{ copy.businessFlowTitle }}</h2>
          </div>
        </div>

        <div class="flow-grid">
          <article v-for="flow in dashboardPanels.businessFlow" :key="flow.title" class="flow-card">
            <span class="flow-card__icon">
              <Icon :icon="flow.icon" />
            </span>
            <div>
              <strong>{{ flow.title }}</strong>
              <p>{{ flow.summary }}</p>
            </div>
          </article>
        </div>
      </article>
    </section>

    <section class="dashboard-grid">
      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.operationalPulse }}</span>
            <h2>{{ copy.operationalPulseTitle }}</h2>
          </div>
        </div>

        <div class="pulse-grid">
          <div v-for="item in operationalPulse" :key="item.label" class="pulse-card" :data-tone="item.tone">
            <div class="pulse-card__head">
              <span>{{ item.label }}</span>
              <strong>{{ item.value }}%</strong>
            </div>
            <div class="pulse-card__meter">
              <div class="pulse-card__fill" :style="{ width: `${item.value}%` }"></div>
            </div>
          </div>
        </div>
      </article>

      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.frontDeskPulse }}</span>
            <h2>{{ copy.frontDeskPulseTitle }}</h2>
          </div>
        </div>

        <div class="chip-grid">
          <div v-for="item in copy.frontDeskChips" :key="item" class="info-chip">{{ item }}</div>
        </div>
      </article>
    </section>

    <section class="dashboard-grid">
      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.roomBoard }}</span>
            <h2>{{ copy.roomBoardTitle }}</h2>
          </div>
          <RouterLink to="/rooms/board" class="ghost-button">{{ copy.openBoard }}</RouterLink>
        </div>

        <div class="status-grid">
          <div v-for="status in dashboardPanels.roomStatus" :key="status.label" class="status-card" :data-tone="status.tone">
            <span>{{ status.label }}</span>
            <strong>{{ status.value }}</strong>
          </div>
        </div>
      </article>

      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.housekeeping }}</span>
            <h2>{{ copy.housekeepingTitle }}</h2>
          </div>
          <RouterLink to="/housekeeping/board" class="ghost-button">{{ copy.openTasks }}</RouterLink>
        </div>

        <div class="feed-list">
          <div v-for="task in dashboardPanels.housekeeping" :key="task.task" class="feed-row">
            <div>
              <strong>{{ task.task }}</strong>
              <span>{{ task.assignee }}</span>
            </div>
            <span class="status-badge status-badge--soft">{{ task.status }}</span>
          </div>
        </div>
      </article>
    </section>

    <section class="dashboard-grid">
      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.frontDesk }}</span>
            <h2>{{ copy.frontDeskTitle }}</h2>
          </div>
        </div>

        <div class="split-feed">
          <div class="mini-panel">
            <strong class="mini-panel__title">{{ copy.arrivals }}</strong>
            <div v-for="item in dashboardPanels.arrivals" :key="item.code" class="feed-row feed-row--compact">
              <div>
                <strong>{{ item.guest }}</strong>
                <span>{{ item.code }} • {{ item.room }}</span>
              </div>
              <span class="status-badge">{{ item.status }}</span>
            </div>
          </div>

          <div class="mini-panel">
            <strong class="mini-panel__title">{{ copy.departures }}</strong>
            <div v-for="item in dashboardPanels.departures" :key="item.room" class="feed-row feed-row--compact">
              <div>
                <strong>{{ item.guest }}</strong>
                <span>{{ copy.room }} {{ item.room }} • {{ item.balance }}</span>
              </div>
              <span class="status-badge status-badge--soft">{{ item.status }}</span>
            </div>
          </div>
        </div>
      </article>

      <article class="surface-card">
        <div class="section-heading">
          <div>
            <span class="section-kicker">{{ copy.inventoryAlerts }}</span>
            <h2>{{ copy.inventoryAlertsTitle }}</h2>
          </div>
          <RouterLink to="/inventory/items" class="ghost-button">{{ copy.openInventory }}</RouterLink>
        </div>

        <div class="feed-list">
          <div v-for="item in dashboardPanels.lowStock" :key="item.item" class="feed-row">
            <div>
              <strong>{{ item.item }}</strong>
              <span>{{ item.stock }}</span>
            </div>
            <span class="status-badge status-badge--warning">{{ item.level }}</span>
          </div>
        </div>
      </article>
    </section>
  </AppShell>
</template>
