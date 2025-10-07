<template>
  <AppLayout>
    <v-card elevation="2">
      <v-card-title class="d-flex justify-space-between align-center">
        <h2 class="text-h5 font-weight-bold">Lixeira</h2>
        <div class="d-flex align-center">
          <v-btn
            v-if="trashItems.length > 0"
            color="error"
            variant="tonal"
            prepend-icon="mdi-delete-sweep"
            @click="showEmptyModal = true"
            class="mr-2"
          >
            Esvaziar
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-refresh"
            @click="loadTrash"
            :loading="loading"
          >
            Atualizar
          </v-btn>
        </div>
      </v-card-title>

      <v-card-text>
        <!-- Estado vazio -->
        <div v-if="trashItems.length === 0" class="text-center py-12">
          <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-delete-empty</v-icon>
          <h3 class="text-h6 text-medium-emphasis mb-2">Lixeira vazia</h3>
          <p class="text-body-2 text-medium-emphasis">
            Não há itens na lixeira no momento.
          </p>
        </div>

        <!-- Lista de itens -->
        <v-data-table
          v-else
          :headers="headers"
          :items="trashItems"
          :loading="loading"
          class="elevation-0"
          :items-per-page="25"
        >
          <template #item.name="{ item }">
            <div class="d-flex align-center">
              <v-icon 
                :color="item.type === 'file' ? 'primary' : 'secondary'" 
                class="mr-2"
              >
                {{ item.type === 'file' ? 'mdi-file' : 'mdi-folder' }}
              </v-icon>
              <div>
                <div class="text-body-2 font-weight-medium">
                  {{ item.name || item.original_name }}
                </div>
                <div v-if="item.type === 'file'" class="text-caption text-medium-emphasis">
                  {{ formatFileSize(item.size) }}
                </div>
              </div>
            </div>
          </template>

          <template #item.type="{ item }">
            <v-chip
              :color="item.type === 'file' ? 'primary' : 'secondary'"
              size="small"
              variant="tonal"
            >
              {{ item.type === 'file' ? 'Arquivo' : 'Pasta' }}
            </v-chip>
          </template>

          <template #item.deleted_at="{ item }">
            <div class="text-body-2">
              {{ formatDate(item.deleted_at) }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ formatTime(item.deleted_at) }}
            </div>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex align-center">
              <v-btn
                icon
                variant="text"
                size="small"
                @click="restoreItem(item)"
                class="mr-1"
                color="success"
              >
                <v-icon>mdi-restore</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                @click="deletePermanently(item)"
                color="error"
              >
                <v-icon>mdi-delete-forever</v-icon>
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Modal de Confirmação de Restauração -->
    <v-dialog v-model="showRestoreModal" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Restaurar Item
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja restaurar <strong>{{ itemToRestore?.name || itemToRestore?.original_name }}</strong>?
          O item será movido de volta para sua localização original.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showRestoreModal = false"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="success"
            @click="confirmRestore"
            :loading="restoring"
          >
            Restaurar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Confirmação de Exclusão Permanente -->
    <v-dialog v-model="showDeleteModal" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Excluir Permanentemente
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja excluir permanentemente <strong>{{ itemToDelete?.name || itemToDelete?.original_name }}</strong>?
          Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showDeleteModal = false"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="error"
            @click="confirmDelete"
            :loading="deleting"
          >
            Excluir
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Confirmação de Esvaziamento -->
    <v-dialog v-model="showEmptyModal" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Esvaziar Lixeira
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja esvaziar a lixeira? Todos os {{ trashItems.length }} itens serão excluídos permanentemente.
          Esta ação não pode ser desfeita.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="showEmptyModal = false"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="error"
            @click="confirmEmpty"
            :loading="emptying"
          >
            Esvaziar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  files: {
    type: Array,
    default: () => []
  },
  folders: {
    type: Array,
    default: () => []
  }
})

const loading = ref(false)
const restoring = ref(false)
const deleting = ref(false)
const emptying = ref(false)
const showRestoreModal = ref(false)
const showDeleteModal = ref(false)
const showEmptyModal = ref(false)
const itemToRestore = ref(null)
const itemToDelete = ref(null)

const headers = [
  { title: 'Nome', value: 'name', sortable: true },
  { title: 'Tipo', value: 'type', sortable: true },
  { title: 'Excluído em', value: 'deleted_at', sortable: true },
  { title: 'Ações', value: 'actions', sortable: false }
]

const trashItems = computed(() => {
  const items = []
  
  // Adicionar arquivos
  props.files.forEach(file => {
    items.push({
      ...file,
      type: 'file'
    })
  })
  
  // Adicionar pastas
  props.folders.forEach(folder => {
    items.push({
      ...folder,
      type: 'folder'
    })
  })
  
  // Ordenar por data de exclusão
  return items.sort((a, b) => new Date(b.deleted_at) - new Date(a.deleted_at))
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-BR')
}

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('pt-BR')
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = bytes
  let unitIndex = 0
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`
}

const loadTrash = () => {
  loading.value = true
  router.reload({
    onFinish: () => {
      loading.value = false
    }
  })
}

const restoreItem = (item) => {
  itemToRestore.value = item
  showRestoreModal.value = true
}

const confirmRestore = () => {
  if (!itemToRestore.value) return
  
  restoring.value = true
  
  const url = itemToRestore.value.type === 'file'
    ? `/trash/files/${itemToRestore.value.id}/restore`
    : `/trash/folders/${itemToRestore.value.id}/restore`
  
  router.post(url, {}, {
    onFinish: () => {
      restoring.value = false
      showRestoreModal.value = false
      itemToRestore.value = null
    }
  })
}

const deletePermanently = (item) => {
  itemToDelete.value = item
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!itemToDelete.value) return
  
  deleting.value = true
  
  const url = itemToDelete.value.type === 'file'
    ? `/trash/files/${itemToDelete.value.id}`
    : `/trash/folders/${itemToDelete.value.id}`
  
  router.delete(url, {
    onFinish: () => {
      deleting.value = false
      showDeleteModal.value = false
      itemToDelete.value = null
    }
  })
}

const confirmEmpty = () => {
  emptying.value = true
  
  router.delete('/trash/empty', {
    onFinish: () => {
      emptying.value = false
      showEmptyModal.value = false
    }
  })
}
</script>