<template>
  <AppLayout>
    <v-card elevation="2">
      <v-card-title class="d-flex justify-space-between align-center">
        <h2 class="text-h5 font-weight-bold">Gerenciamento de Permissões</h2>
        <v-btn
          color="primary"
          prepend-icon="mdi-shield-plus"
          @click="showCreateModal = true"
        >
          Definir Permissões
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-tabs v-model="activeTab" color="primary">
          <v-tab value="files">
            <v-icon start>mdi-file</v-icon>
            Arquivos
          </v-tab>
          <v-tab value="folders">
            <v-icon start>mdi-folder</v-icon>
            Pastas
          </v-tab>
        </v-tabs>

        <v-tabs-window v-model="activeTab">
          <!-- Tab de Arquivos -->
          <v-tabs-window-item value="files">
            <v-data-table
              :headers="fileHeaders"
              :items="files"
              :loading="loading"
              class="elevation-0 mt-4"
            >
              <template #item.name="{ item }">
                <div class="d-flex align-center">
                  <v-icon color="primary" class="mr-2">mdi-file</v-icon>
                  <span>{{ item.name }}</span>
                </div>
              </template>

              <template #item.permissions="{ item }">
                <v-chip-group>
                  <v-chip
                    v-if="item.is_shared"
                    color="success"
                    size="small"
                    variant="tonal"
                  >
                    Compartilhado
                  </v-chip>
                  <v-chip
                    v-else
                    color="grey"
                    size="small"
                    variant="tonal"
                  >
                    Privado
                  </v-chip>
                </v-chip-group>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex align-center">
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="manageFilePermissions(item)"
                    class="mr-1"
                  >
                    <v-icon color="primary">mdi-cog</v-icon>
                  </v-btn>
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="toggleFileShare(item)"
                  >
                    <v-icon :color="item.is_shared ? 'success' : 'grey'">
                      {{ item.is_shared ? 'mdi-share-variant' : 'mdi-share-off' }}
                    </v-icon>
                  </v-btn>
                </div>
              </template>
            </v-data-table>
          </v-tabs-window-item>

          <!-- Tab de Pastas -->
          <v-tabs-window-item value="folders">
            <v-data-table
              :headers="folderHeaders"
              :items="folders"
              :loading="loading"
              class="elevation-0 mt-4"
            >
              <template #item.name="{ item }">
                <div class="d-flex align-center">
                  <v-icon color="primary" class="mr-2">mdi-folder</v-icon>
                  <span>{{ item.name }}</span>
                </div>
              </template>

              <template #item.permissions="{ item }">
                <v-chip-group>
                  <v-chip
                    v-if="item.is_shared"
                    color="success"
                    size="small"
                    variant="tonal"
                  >
                    Compartilhado
                  </v-chip>
                  <v-chip
                    v-else
                    color="grey"
                    size="small"
                    variant="tonal"
                  >
                    Privado
                  </v-chip>
                </v-chip-group>
              </template>

              <template #item.actions="{ item }">
                <div class="d-flex align-center">
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="manageFolderPermissions(item)"
                    class="mr-1"
                  >
                    <v-icon color="primary">mdi-cog</v-icon>
                  </v-btn>
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="toggleFolderShare(item)"
                  >
                    <v-icon :color="item.is_shared ? 'success' : 'grey'">
                      {{ item.is_shared ? 'mdi-share-variant' : 'mdi-share-off' }}
                    </v-icon>
                  </v-btn>
                </div>
              </template>
            </v-data-table>
          </v-tabs-window-item>
        </v-tabs-window>
      </v-card-text>
    </v-card>

    <!-- Modal de Gerenciamento de Permissões -->
    <v-dialog v-model="showPermissionModal" max-width="700px" persistent>
      <v-card>
        <v-card-title>
          <span class="text-h5">Gerenciar Permissões</span>
          <v-spacer />
          <v-chip color="primary" variant="tonal">
            {{ selectedItem?.name }}
          </v-chip>
        </v-card-title>

        <v-card-text>
          <v-row>
            <v-col cols="12">
              <v-select
                v-model="selectedUser"
                :items="users"
                item-title="name"
                item-value="id"
                label="Selecionar Usuário"
                variant="outlined"
                prepend-icon="mdi-account"
              />
            </v-col>

            <v-col cols="12">
              <v-divider class="my-2" />
              <h4 class="text-subtitle1 mb-3">Permissões</h4>
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="permissions.can_view"
                label="Visualizar"
                color="primary"
                inset
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="permissions.can_edit"
                label="Editar"
                color="primary"
                inset
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="permissions.can_delete"
                label="Excluir"
                color="primary"
                inset
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="permissions.can_rename"
                label="Renomear"
                color="primary"
                inset
              />
            </v-col>
          </v-row>

          <!-- Lista de Permissões Existentes -->
          <div v-if="existingPermissions.length > 0" class="mt-6">
            <h4 class="text-subtitle1 mb-3">Permissões Atuais</h4>
            <v-list density="compact">
              <v-list-item
                v-for="perm in existingPermissions"
                :key="perm.id"
                class="border rounded mb-2"
              >
                <template #prepend>
                  <v-avatar size="32" color="primary" variant="tonal">
                    <span class="text-caption">{{ perm.user.name.charAt(0) }}</span>
                  </v-avatar>
                </template>
                
                <v-list-item-title>{{ perm.user.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ perm.user.email }}</v-list-item-subtitle>
                
                <template #append>
                  <v-chip-group>
                    <v-chip
                      v-if="perm.can_view"
                      color="success"
                      size="x-small"
                      variant="tonal"
                    >
                      Visualizar
                    </v-chip>
                    <v-chip
                      v-if="perm.can_edit"
                      color="warning"
                      size="x-small"
                      variant="tonal"
                    >
                      Editar
                    </v-chip>
                    <v-chip
                      v-if="perm.can_delete"
                      color="error"
                      size="x-small"
                      variant="tonal"
                    >
                      Excluir
                    </v-chip>
                    <v-chip
                      v-if="perm.can_rename"
                      color="info"
                      size="x-small"
                      variant="tonal"
                    >
                      Renomear
                    </v-chip>
                  </v-chip-group>
                  
                  <v-btn
                    icon
                    variant="text"
                    size="small"
                    @click="removePermission(perm)"
                    class="ml-2"
                  >
                    <v-icon color="error" size="small">mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-list-item>
            </v-list>
          </div>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="closePermissionModal"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            @click="savePermissions"
            :loading="saving"
            :disabled="!selectedUser"
          >
            Salvar Permissões
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const activeTab = ref('files')
const loading = ref(false)
const saving = ref(false)
const showPermissionModal = ref(false)
const selectedItem = ref(null)
const selectedUser = ref(null)
const users = ref([])
const files = ref([])
const folders = ref([])
const existingPermissions = ref([])

const fileHeaders = [
  { title: 'Nome', value: 'name', sortable: true },
  { title: 'Tamanho', value: 'size', sortable: true },
  { title: 'Status', value: 'permissions', sortable: false },
  { title: 'Criado em', value: 'created_at', sortable: true },
  { title: 'Ações', value: 'actions', sortable: false }
]

const folderHeaders = [
  { title: 'Nome', value: 'name', sortable: true },
  { title: 'Arquivos', value: 'files_count', sortable: true },
  { title: 'Status', value: 'permissions', sortable: false },
  { title: 'Criado em', value: 'created_at', sortable: true },
  { title: 'Ações', value: 'actions', sortable: false }
]

const permissions = reactive({
  can_view: false,
  can_edit: false,
  can_delete: false,
  can_rename: false
})

const loadData = async () => {
  loading.value = true
  try {
    // Carregar usuários, arquivos e pastas
    const [usersRes, filesRes, foldersRes] = await Promise.all([
      fetch('/users').then(r => r.json()),
      fetch('/files').then(r => r.json()),
      fetch('/folders').then(r => r.json())
    ])
    
    users.value = usersRes.data || []
    files.value = filesRes.data || []
    folders.value = foldersRes.data || []
  } catch (error) {
    console.error('Erro ao carregar dados:', error)
  } finally {
    loading.value = false
  }
}

const manageFilePermissions = async (file) => {
  selectedItem.value = { ...file, type: 'file' }
  selectedUser.value = null
  
  // Carregar permissões existentes
  try {
    const response = await fetch(`/permissions/files/${file.id}`)
    const data = await response.json()
    existingPermissions.value = data || []
  } catch (error) {
    console.error('Erro ao carregar permissões:', error)
    existingPermissions.value = []
  }
  
  showPermissionModal.value = true
}

const manageFolderPermissions = async (folder) => {
  selectedItem.value = { ...folder, type: 'folder' }
  selectedUser.value = null
  
  // Carregar permissões existentes
  try {
    const response = await fetch(`/permissions/folders/${folder.id}`)
    const data = await response.json()
    existingPermissions.value = data || []
  } catch (error) {
    console.error('Erro ao carregar permissões:', error)
    existingPermissions.value = []
  }
  
  showPermissionModal.value = true
}

const toggleFileShare = async (file) => {
  try {
    await fetch(`/files/${file.id}/toggle-share`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    // Atualizar status local
    file.is_shared = !file.is_shared
  } catch (error) {
    console.error('Erro ao alterar compartilhamento:', error)
  }
}

const toggleFolderShare = async (folder) => {
  try {
    await fetch(`/folders/${folder.id}/toggle-share`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    // Atualizar status local
    folder.is_shared = !folder.is_shared
  } catch (error) {
    console.error('Erro ao alterar compartilhamento:', error)
  }
}

const savePermissions = async () => {
  if (!selectedUser.value || !selectedItem.value) return
  
  saving.value = true
  
  try {
    const url = selectedItem.value.type === 'file' 
      ? `/permissions/files/${selectedItem.value.id}`
      : `/permissions/folders/${selectedItem.value.id}`
    
    await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        user_id: selectedUser.value,
        ...permissions
      })
    })
    
    // Recarregar permissões
    await loadExistingPermissions()
    
    // Limpar formulário
    selectedUser.value = null
    Object.assign(permissions, {
      can_view: false,
      can_edit: false,
      can_delete: false,
      can_rename: false
    })
  } catch (error) {
    console.error('Erro ao salvar permissões:', error)
  } finally {
    saving.value = false
  }
}

const loadExistingPermissions = async () => {
  if (!selectedItem.value) return
  
  try {
    const url = selectedItem.value.type === 'file'
      ? `/permissions/files/${selectedItem.value.id}`
      : `/permissions/folders/${selectedItem.value.id}`
    
    const response = await fetch(url)
    const data = await response.json()
    existingPermissions.value = data || []
  } catch (error) {
    console.error('Erro ao carregar permissões:', error)
  }
}

const removePermission = async (permission) => {
  try {
    const url = selectedItem.value.type === 'file'
      ? `/permissions/files/${selectedItem.value.id}`
      : `/permissions/folders/${selectedItem.value.id}`
    
    await fetch(url, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        user_id: permission.user_id
      })
    })
    
    // Recarregar permissões
    await loadExistingPermissions()
  } catch (error) {
    console.error('Erro ao remover permissão:', error)
  }
}

const closePermissionModal = () => {
  showPermissionModal.value = false
  selectedItem.value = null
  selectedUser.value = null
  existingPermissions.value = []
  Object.assign(permissions, {
    can_view: false,
    can_edit: false,
    can_delete: false,
    can_rename: false
  })
}

onMounted(() => {
  loadData()
})
</script>
