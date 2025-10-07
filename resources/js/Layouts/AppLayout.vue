<template>
  <v-app>
    <!-- Header Bar -->
    <v-app-bar color="white" elevation="1" height="64">
      <v-container fluid class="d-flex align-center pa-4">
        <!-- Nome do Sistema -->
        <div class="d-flex align-center">
          <v-icon color="primary" size="28" class="mr-2">mdi-cloud</v-icon>
          <h1 class="text-h5 font-weight-bold text-primary ma-0">ExpertSeg Drive</h1>
        </div>

        <v-spacer />

        <!-- Header Actions -->
        <div class="d-flex align-center header-actions">
          <!-- Grid/Lista Toggle -->
          <v-btn 
            variant="outlined"
            size="small"
            color="primary"
            @click="toggleViewMode"
            :title="viewMode === 'grid' ? 'Visualização em Lista' : 'Visualização em Grid'"
            class="modern-btn mr-3"
          >
            <v-icon start size="18">
              {{ viewMode === 'grid' ? 'mdi-view-list' : 'mdi-view-grid' }}
            </v-icon>
            {{ viewMode === 'grid' ? 'Lista' : 'Grid' }}
          </v-btn>
          
          <!-- Nova Pasta -->
          <v-btn 
            variant="outlined"
            size="small"
            color="primary"
            @click="createFolder"
            title="Nova Pasta"
            class="modern-btn mr-3"
          >
            <v-icon start size="18">mdi-folder-plus</v-icon>
            Nova Pasta
          </v-btn>
          
          <!-- Upload -->
          <v-btn 
            variant="outlined"
            size="small"
            color="primary"
            @click="uploadFile"
            title="Upload de Arquivo"
            class="modern-btn mr-3"
          >
            <v-icon start size="18">mdi-upload</v-icon>
            Upload
          </v-btn>

          <!-- User Menu -->
          <v-menu>
            <template #activator="{ props }">
              <div 
                v-bind="props" 
                class="user-menu-trigger d-flex align-center cursor-pointer"
              >
                <v-avatar size="36" color="pink-lighten-4" class="mr-2">
                  <v-img 
                    v-if="safeAuth.user?.avatar" 
                    :src="safeAuth.user.avatar"
                    :alt="safeAuth.user.name"
                  />
                  <span v-else class="avatar-text">{{ getUserInitials() }}</span>
                </v-avatar>
                <div class="user-info-text">
                  <div class="user-name">{{ safeAuth.user?.name || 'Usuário' }}</div>
                </div>
              </div>
            </template>
            <v-list class="user-menu">
              <v-list-item class="user-info">
                <template #prepend>
                  <v-avatar size="48" color="pink-lighten-4">
                    <v-img 
                      v-if="safeAuth.user?.avatar" 
                      :src="safeAuth.user.avatar"
                      :alt="safeAuth.user.name"
                    />
                    <span v-else class="avatar-text-large">{{ getUserInitials() }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-medium">{{ safeAuth.user?.name || 'Usuário' }}</v-list-item-title>
                <v-list-item-subtitle>
                  <v-chip size="small" color="pink-lighten-4" variant="flat" class="mt-1">
                    {{ safeAuth.user?.role || 'Usuário' }}
                  </v-chip>
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider />
              <v-list-item @click="goToProfile" class="profile-item">
                <template #prepend>
                  <v-icon color="primary">mdi-account</v-icon>
                </template>
                <v-list-item-title>Meu Perfil</v-list-item-title>
              </v-list-item>
              <v-list-item @click="logout" class="logout-item">
                <template #prepend>
                  <v-icon color="error">mdi-exit-to-app</v-icon>
                </template>
                <v-list-item-title>Sair</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </div>
      </v-container>
    </v-app-bar>

    <!-- Navigation Drawer -->
    <v-navigation-drawer
      permanent
      width="280"
      color="grey-lighten-4"
    >
      <v-list density="compact" class="py-2">
        <!-- Navigation Items -->
        <v-list-item
          prepend-icon="mdi-folder"
          title="Arquivos"
          @click="navigateTo('/')"
          class="mb-2"
          color="primary"
        />
        
        <v-list-item
          prepend-icon="mdi-account-group"
          title="Usuários"
          @click="navigateTo('/users')"
          class="mb-2"
          color="primary"
        />
        
        <v-list-item
          prepend-icon="mdi-shield-account"
          title="Permissões"
          @click="navigateTo('/permissions')"
          class="mb-2"
          color="primary"
        />
        
        <v-list-item
          prepend-icon="mdi-history"
          title="Logs"
          @click="navigateTo('/activity-logs')"
          class="mb-2"
          color="primary"
        />
        
        <v-list-item
          prepend-icon="mdi-delete"
          title="Lixeira"
          @click="navigateTo('/trash')"
          class="mb-2"
          color="primary"
        />

        <v-divider class="my-4" />

        <!-- Resource Usage -->
        <v-list-subheader>Recursos</v-list-subheader>
        
        <v-list-item>
          <template #prepend>
            <v-icon color="primary">mdi-harddisk</v-icon>
          </template>
          <v-list-item-title>Espaço</v-list-item-title>
          <v-list-item-subtitle>
            <v-progress-linear
              :model-value="spaceUsage"
              color="primary"
              height="6"
              class="mt-1"
            />
            <div class="text-caption mt-1">{{ spaceUsed }} / {{ spaceTotal }}</div>
          </v-list-item-subtitle>
        </v-list-item>


        <v-divider class="my-4" />

        <!-- App Info -->
        <v-list-item class="text-caption">
          <v-list-item-subtitle>
            ExpertSeg Drive v1.0.0
          </v-list-item-subtitle>
        </v-list-item>
        
        <v-list-item class="text-caption">
          <v-list-item-subtitle>
            Ajuda
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- Flash messages -->
    <v-alert
      v-if="flash.success"
      type="success"
      variant="elevated"
      closable
      class="ma-4"
    >
      {{ flash.success }}
    </v-alert>
    <v-alert
      v-if="flash.error"
      type="error"
      variant="elevated"
      closable
      class="ma-4"
    >
      {{ flash.error }}
    </v-alert>

    <!-- Main Content -->
    <v-main class="content-background">
      <v-container fluid class="pa-4">
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  auth: {
    type: Object,
    default: () => ({ user: null })
  },
  flash: {
    type: Object,
    default: () => ({})
  }
})

// Mock data for resource usage - você pode integrar com dados reais depois
const spaceUsed = ref('487.82 MiB')
const spaceTotal = ref('10 GiB')

// View mode state
const viewMode = ref('grid')

// Computed para garantir que auth sempre tenha uma estrutura válida
const safeAuth = computed(() => {
  return props.auth || { user: null }
})

const spaceUsage = computed(() => {
  // Converter para bytes para cálculo
  const usedBytes = parseFloat(spaceUsed.value) * (spaceUsed.value.includes('GiB') ? 1024 * 1024 * 1024 : 1024 * 1024)
  const totalBytes = parseFloat(spaceTotal.value) * 1024 * 1024 * 1024 // GiB to bytes
  return (usedBytes / totalBytes) * 100
})

const logout = () => {
  router.post('/logout')
}

const createFolder = () => {
  // Implementar criação de pasta
  console.log('Criar pasta')
}

const uploadFile = () => {
  // Implementar upload de arquivo
  console.log('Upload arquivo')
}

const navigateTo = (url) => {
  router.visit(url)
}

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
  // Aqui você pode emitir um evento ou usar um store para comunicar com os componentes filhos
  console.log('View mode changed to:', viewMode.value)
}

const getUserInitials = () => {
  const name = safeAuth.value.user?.name || 'Usuário'
  return name.charAt(0).toUpperCase()
}

const goToProfile = () => {
  // Implementar navegação para o perfil do usuário
  console.log('Ir para perfil do usuário')
}
</script>

<style scoped>
.v-app-bar {
  border-bottom: 1px solid #e0e0e0;
}

.v-text-field {
  flex-shrink: 0;
}

.header-actions {
  flex-shrink: 0;
  white-space: nowrap;
}

/* Estilos modernos para os botões */
.modern-btn {
  border-radius: 8px !important;
  text-transform: none !important;
  font-weight: 500 !important;
  letter-spacing: 0.01em !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
  transition: all 0.2s ease-in-out !important;
}

.modern-btn:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  transform: translateY(-1px) !important;
}

/* User menu trigger */
.user-menu-trigger {
  padding: 6px 8px;
  border-radius: 8px;
  background-color: transparent;
  transition: all 0.2s ease-in-out;
  min-width: auto;
}

.user-menu-trigger:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.user-info-text {
  flex: 1;
  text-align: left;
}

.user-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #333;
  line-height: 1.2;
}

/* Avatar text styles */
.avatar-text {
  font-size: 1rem;
  font-weight: 600;
  color: #c2185b;
}

.avatar-text-large {
  font-size: 1.25rem;
  font-weight: 600;
  color: #c2185b;
}

.cursor-pointer {
  cursor: pointer;
}

/* Menu do usuário */
.user-menu {
  border-radius: 12px !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
  overflow: hidden !important;
  min-width: 200px !important;
}

.user-info {
  padding: 16px !important;
  background-color: white !important;
}

.profile-item {
  color: #1976d2 !important;
}

.profile-item:hover {
  background-color: rgba(25, 118, 210, 0.08) !important;
}

.logout-item {
  color: #dc3545 !important;
}

.logout-item:hover {
  background-color: rgba(220, 53, 69, 0.08) !important;
}

/* Avatar responsivo */
.v-avatar {
  border: 2px solid rgba(255, 255, 255, 0.2) !important;
}

/* Cor de fundo do conteúdo */
.content-background {
  background-color: #f8f9fa !important;
}

/* Espaçamento entre botões */
.mr-3 {
  margin-right: 16px !important;
}

/* Garantir que os ícones apareçam */
.v-icon {
  color: inherit !important;
}

.v-btn .v-icon {
  color: inherit !important;
}

.v-list-item .v-icon {
  color: inherit !important;
}

/* Cor primária para elementos sem cor definida */
.v-icon:not([color]) {
  color: rgb(var(--v-theme-primary)) !important;
}

.v-btn:not([color]) .v-icon {
  color: rgb(var(--v-theme-primary)) !important;
}

/* Responsividade */
@media (max-width: 768px) {
  .modern-btn {
    min-width: auto !important;
    padding: 0 12px !important;
  }
  
  .user-btn {
    min-width: 120px !important;
  }
  
  .modern-btn .v-btn__content {
    font-size: 0.875rem !important;
  }
}
</style>
