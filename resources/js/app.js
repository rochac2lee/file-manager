import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import vuetify from './plugins/vuetify'

// Importar páginas diretamente
import Login from './Pages/Auth/Login.vue'
import Register from './Pages/Auth/Register.vue'
import FileManager from './Pages/FileManager.vue'
import UsersIndex from './Pages/Users/Index.vue'
import PermissionsIndex from './Pages/Permissions/Index.vue'
import ActivityLogsIndex from './Pages/ActivityLogs/Index.vue'
import TrashIndex from './Pages/Trash/Index.vue'

createInertiaApp({
  title: (title) => `${title} - ExpertSeg Drive`,
  resolve: (name) => {
    const pages = {
      'Auth/Login': Login,
      'Auth/Register': Register,
      'FileManager': FileManager,
      'Users/Index': UsersIndex,
      'Permissions/Index': PermissionsIndex,
      'ActivityLogs/Index': ActivityLogsIndex,
      'Trash/Index': TrashIndex,
    }
    return pages[name] || resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
  },
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(vuetify)
      .mount(el)
  },
  progress: {
    color: '#4F46E5',
  },
  // Configurações para melhorar a reatividade e navegação
  resolveComponent: (name) => {
    const pages = {
      'Auth/Login': Login,
      'Auth/Register': Register,
      'FileManager': FileManager,
      'Users/Index': UsersIndex,
      'Permissions/Index': PermissionsIndex,
      'ActivityLogs/Index': ActivityLogsIndex,
      'Trash/Index': TrashIndex,
    }
    return pages[name] || resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
  },
})
