import mdiAccountGroupOutline from '@iconify-icons/mdi/account-group-outline'
import mdiAccountsSwitch from '@iconify-icons/mdi/accounts-switch'
import mdiBadgeAccountOutline from '@iconify-icons/mdi/badge-account-outline'
import mdiBroom from '@iconify-icons/mdi/broom'
import mdiCalendarTextOutline from '@iconify-icons/mdi/calendar-text-outline'
import mdiCashMultiple from '@iconify-icons/mdi/cash-multiple'
import mdiChartBoxOutline from '@iconify-icons/mdi/chart-box-outline'
import mdiCogOutline from '@iconify-icons/mdi/cog-outline'
import mdiDoorClosed from '@iconify-icons/mdi/door-closed'
import mdiPackageVariantClosed from '@iconify-icons/mdi/package-variant-closed'
import mdiViewDashboardOutline from '@iconify-icons/mdi/view-dashboard-outline'

export const navigationItems = [
  {
    label: 'Dashboard',
    to: '/',
    shortLabel: 'DB',
    icon: mdiViewDashboardOutline,
  },
  {
    label: 'Reservasi',
    to: '/reservations',
    shortLabel: 'RS',
    icon: mdiCalendarTextOutline,
  },
  {
    label: 'Front Desk',
    to: '/front-desk',
    shortLabel: 'FD',
    icon: mdiAccountsSwitch,
  },
  {
    label: 'Kamar',
    to: '/rooms',
    shortLabel: 'KM',
    icon: mdiDoorClosed,
  },
  {
    label: 'Housekeeping',
    to: '/housekeeping',
    shortLabel: 'HK',
    icon: mdiBroom,
  },
  {
    label: 'Billing',
    to: '/billing',
    shortLabel: 'BL',
    icon: mdiCashMultiple,
  },
  {
    label: 'Tamu',
    to: '/guests',
    shortLabel: 'TM',
    icon: mdiAccountGroupOutline,
  },
  {
    label: 'Inventori',
    to: '/inventory',
    shortLabel: 'IV',
    icon: mdiPackageVariantClosed,
  },
  {
    label: 'Karyawan',
    to: '/employees',
    shortLabel: 'KR',
    icon: mdiBadgeAccountOutline,
  },
  {
    label: 'Laporan',
    to: '/reports',
    shortLabel: 'LP',
    icon: mdiChartBoxOutline,
  },
  {
    label: 'Settings',
    to: '/settings',
    shortLabel: 'ST',
    icon: mdiCogOutline,
  },
] as const
