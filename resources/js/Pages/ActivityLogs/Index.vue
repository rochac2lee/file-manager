<template>
  <AppLayout>
    <v-card elevation="2">
      <v-card-title class="d-flex justify-space-between align-center">
        <h2 class="text-h5 font-weight-bold">Logs de Atividade</h2>
        <v-btn
          color="primary"
          prepend-icon="mdi-refresh"
          @click="loadLogs"
          :loading="loading"
        >
          Atualizar
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-data-table
          :headers="headers"
          :items="logs"
          :loading="loading"
          class="elevation-0"
          :items-per-page="25"
        >
          <template #item.user="{ item }">
            <div class="d-flex align-center">
              <v-avatar size="32" color="primary" variant="tonal" class="mr-2">
                <span class="text-caption">{{ item.user?.name?.charAt(0) || 'S' }}</span>
              </v-avatar>
              <div>
                <div class="text-body-2 font-weight-medium">
                  {{ item.user?.name || 'Sistema' }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ item.user?.email || '' }}
                </div>
              </div>
            </div>
          </template>

          <template #item.action="{ item }">
            <v-chip
              :color="getActionColor(item.action)"
              size="small"
              variant="tonal"
            >
              {{ getActionLabel(item.action) }}
            </v-chip>
          </template>

          <template #item.model_type="{ item }">
            <v-chip
              :color="getModelTypeColor(item.model_type)"
              size="small"
              variant="tonal"
            >
              {{ getModelTypeLabel(item.model_type) }}
            </v-chip>
          </template>

          <template #item.description="{ item }">
            <div class="text-body-2">
              {{ item.description }}
            </div>
            <div v-if="item.metadata" class="text-caption text-medium-emphasis">
              {{ formatMetadata(item.metadata) }}
            </div>
          </template>

          <template #item.created_at="{ item }">
            <div class="text-body-2">
              {{ formatDate(item.created_at) }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ formatTime(item.created_at) }}
            </div>
          </template>

          <template #item.actions="{ item }">
            <v-btn
              icon
              variant="text"
              size="small"
              @click="viewDetails(item)"
            >
              <v-icon color="primary">mdi-eye</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Modal de Detalhes -->
    <v-dialog v-model="showDetailsModal" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">Detalhes do Log</span>
        </v-card-title>

        <v-card-text v-if="selectedLog">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                :model-value="selectedLog.user?.name || 'Sistema'"
                label="Usuário"
                variant="outlined"
                readonly
                prepend-icon="mdi-account"
              />
            </v-col>
            
            <v-col cols="12" md="6">
              <v-text-field
                :model-value="getActionLabel(selectedLog.action)"
                label="Ação"
                variant="outlined"
                readonly
                prepend-icon="mdi-cog"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                :model-value="getModelTypeLabel(selectedLog.model_type)"
                label="Tipo"
                variant="outlined"
                readonly
                prepend-icon="mdi-tag"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                :model-value="selectedLog.model_id"
                label="ID do Item"
                variant="outlined"
                readonly
                prepend-icon="mdi-identifier"
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                :model-value="selectedLog.description"
                label="Descrição"
                variant="outlined"
                readonly
                rows="3"
                prepend-icon="mdi-text"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                :model-value="formatDate(selectedLog.created_at)"
                label="Data"
                variant="outlined"
                readonly
                prepend-icon="mdi-calendar"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                :model-value="formatTime(selectedLog.created_at)"
                label="Hora"
                variant="outlined"
                readonly
                prepend-icon="mdi-clock"
              />
            </v-col>

            <v-col v-if="selectedLog.metadata" cols="12">
              <v-card variant="outlined">
                <v-card-title class="text-subtitle1">
                  <v-icon start>mdi-information</v-icon>
                  Metadados
                </v-card-title>
                <v-card-text>
                  <pre class="text-body-2">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            color="primary"
            @click="showDetailsModal = false"
          >
            Fechar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  logs: {
    type: Array,
    required: true
  }
})

const loading = ref(false)
const showDetailsModal = ref(false)
const selectedLog = ref(null)

const headers = [
  { title: 'Usuário', value: 'user', sortable: false },
  { title: 'Ação', value: 'action', sortable: true },
  { title: 'Tipo', value: 'model_type', sortable: true },
  { title: 'Descrição', value: 'description', sortable: false },
  { title: 'Data/Hora', value: 'created_at', sortable: true },
  { title: 'Ações', value: 'actions', sortable: false }
]

const getActionColor = (action) => {
  switch (action) {
    case 'created':
    case 'create_folder':
    case 'upload_file':
      return 'success'
    case 'updated':
    case 'rename_file':
    case 'rename_folder':
      return 'warning'
    case 'deleted':
    case 'delete_file':
    case 'delete_folder':
    case 'permanent_delete':
      return 'error'
    case 'restore':
      return 'info'
    case 'download_file':
      return 'primary'
    default:
      return 'grey'
  }
}

const getActionLabel = (action) => {
  switch (action) {
    case 'created':
      return 'Criado'
    case 'updated':
      return 'Atualizado'
    case 'deleted':
      return 'Excluído'
    case 'create_folder':
      return 'Pasta Criada'
    case 'upload_file':
      return 'Arquivo Enviado'
    case 'rename_file':
      return 'Arquivo Renomeado'
    case 'rename_folder':
      return 'Pasta Renomeada'
    case 'delete_file':
      return 'Arquivo Excluído'
    case 'delete_folder':
      return 'Pasta Excluída'
    case 'download_file':
      return 'Arquivo Baixado'
    case 'restore':
      return 'Restaurado'
    case 'permanent_delete':
      return 'Excluído Permanentemente'
    case 'set_file_permissions':
      return 'Permissões de Arquivo'
    case 'set_folder_permissions':
      return 'Permissões de Pasta'
    case 'remove_file_permissions':
      return 'Permissões Removidas (Arquivo)'
    case 'remove_folder_permissions':
      return 'Permissões Removidas (Pasta)'
    case 'empty_trash':
      return 'Lixeira Esvaziada'
    default:
      return action
  }
}

const getModelTypeColor = (type) => {
  switch (type.toLowerCase()) {
    case 'file':
      return 'primary'
    case 'folder':
      return 'secondary'
    case 'user':
      return 'success'
    case 'trash':
      return 'error'
    default:
      return 'grey'
  }
}

const getModelTypeLabel = (type) => {
  switch (type.toLowerCase()) {
    case 'file':
      return 'Arquivo'
    case 'folder':
      return 'Pasta'
    case 'user':
      return 'Usuário'
    case 'trash':
      return 'Lixeira'
    default:
      return type
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-BR')
}

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('pt-BR')
}

const formatMetadata = (metadata) => {
  if (!metadata) return ''
  
  try {
    const parsed = typeof metadata === 'string' ? JSON.parse(metadata) : metadata
    const parts = []
    
    if (parsed.original_name) parts.push(`Arquivo: ${parsed.original_name}`)
    if (parsed.from && parsed.to) parts.push(`${parsed.from} → ${parsed.to}`)
    if (parsed.target_user_id) parts.push(`Usuário: ${parsed.target_user_id}`)
    
    return parts.join(' • ')
  } catch {
    return ''
  }
}

const loadLogs = () => {
  loading.value = true
  router.reload({
    onFinish: () => {
      loading.value = false
    }
  })
}

const viewDetails = (log) => {
  selectedLog.value = log
  showDetailsModal.value = true
}

onMounted(() => {
  // Logs já vêm do backend via props
})
</script>