<template>
  <AppLayout>
    <input ref="fileInput" type="file" class="d-none" multiple @change="handleFileUpload" />
    
    <!-- Drop zone overlay -->
    <div 
      v-if="isDragOver"
      class="drop-zone-overlay"
      @drop="handleDrop"
      @dragover.prevent
      @dragenter.prevent
      @dragleave="handleDragLeave"
    >
      <div class="drop-zone-content">
        <v-icon size="64" color="primary">mdi-cloud-upload</v-icon>
        <div class="text-h5 mt-4">Solte os arquivos aqui</div>
        <div class="text-body-1 mt-2">Os arquivos serão enviados para esta pasta</div>
      </div>
    </div>

    <!-- Estado de carregamento inicial -->
    <template v-if="!initialLoaded">
      <v-card class="pa-6" elevation="1">
        <div class="d-flex align-center">
          <v-progress-circular indeterminate color="primary" class="mr-3" />
          <span>Carregando...</span>
        </div>
      </v-card>
    </template>

    <!-- Conteúdo principal -->
    <template v-else>
      <!-- Toolbar -->
      <div class="mb-4">
        <v-toolbar density="compact" class="px-4 toolbar-transparent" flat>
          <!-- Breadcrumb -->
          <v-breadcrumbs :items="breadcrumbItems" class="pa-0">
            <template #item="{ item }">
              <v-breadcrumbs-item
                :disabled="item.disabled"
                @click="item.disabled ? null : navigateToFolder(item.value)"
                style="cursor: pointer;"
              >
                {{ item.title }}
              </v-breadcrumbs-item>
            </template>
          </v-breadcrumbs>
          
          <v-spacer />
          
          <!-- Search -->
          <v-text-field
            v-model="searchQuery"
            prepend-inner-icon="mdi-magnify"
            placeholder="Buscar arquivos..."
            variant="outlined"
            density="compact"
            hide-details
            class="mr-4 modern-search"
            style="max-width: 320px;"
            clearable
            rounded="lg"
          />
          
          <!-- Selection actions -->
          <template v-if="selectedItems.length > 0">
            <v-btn
              icon
              variant="text"
              @click="downloadSelected"
              :disabled="!hasSelectedFiles"
              title="Baixar selecionados"
            >
              <v-icon>mdi-download</v-icon>
            </v-btn>
            <v-btn
              icon
              variant="text"
              @click="shareSelected"
              title="Compartilhar selecionados"
            >
              <v-icon>mdi-share-variant</v-icon>
            </v-btn>
            <v-btn
              icon
              variant="text"
              color="error"
              @click="deleteSelected"
              title="Excluir selecionados"
            >
              <v-icon>mdi-delete</v-icon>
            </v-btn>
            <v-divider vertical class="mx-2" />
            <span class="text-body-2 mr-2">{{ selectedItems.length }} selecionado(s)</span>
            <v-btn
              icon
              variant="text"
              @click="clearSelection"
              title="Limpar seleção"
            >
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </template>
          
          <!-- View mode toggle -->
          <v-btn-toggle v-model="viewMode" mandatory class="ml-2">
            <v-btn value="list" size="small">
              <v-icon>mdi-view-list</v-icon>
            </v-btn>
            <v-btn value="grid" size="small">
              <v-icon>mdi-view-grid</v-icon>
            </v-btn>
          </v-btn-toggle>
        </v-toolbar>
      </div>

      <!-- Estado de carregamento de conteúdo -->
      <div v-if="loading">
            <v-card class="d-flex flex-column align-center justify-center text-center py-16" elevation="1">
              <v-progress-circular indeterminate color="primary" size="48" class="mb-4" />
              <div class="text-h6 mb-2">Carregando conteúdo...</div>
              <div class="text-body-2 text-medium-emphasis">Buscando arquivos e pastas</div>
            </v-card>
      </div>

      <!-- Estado vazio somente após carregamento -->
      <div v-else-if="!hasItems">
        <v-card class="d-flex flex-column align-center justify-center text-center py-16" elevation="1">
          <v-icon color="primary" size="64" class="mb-4">mdi-folder-outline</v-icon>
          <div class="text-h6 mb-2">Nenhum arquivo ou pasta</div>
          <div class="text-body-2 text-medium-emphasis mb-6">Crie uma nova pasta ou envie arquivos para começar.</div>
          <div>
            <v-btn color="primary" class="mr-2" @click="showCreateFolderModal = true">
              <v-icon start>mdi-folder-plus</v-icon>
              Nova pasta
            </v-btn>
            <v-btn color="secondary" variant="tonal" @click="fileInput?.click()">
              <v-icon start>mdi-upload</v-icon>
              Upload de arquivos
            </v-btn>
          </div>
        </v-card>
      </div>

      <!-- Modo de visualização em lista -->
      <div v-else-if="viewMode === 'list'">
          <v-card elevation="1" style="position: relative;">
            <!-- Indicador de loading sutil -->
            <div v-if="loading" class="loading-indicator">
              <v-progress-linear indeterminate color="primary" height="2" />
            </div>

            <v-data-table
              :headers="headers"
              :items="tableItems"
              :search="searchQuery"
              item-key="uid"
              class="elevation-0"
              :item-class="(item) => item.isProcessing ? 'processing-item' : ''"
              v-model="selectedItems"
              show-select
              @dblclick:row="onRowDoubleClick"
              @contextmenu:row="onContextMenu"
            >
              <template #item.name="{ item }">
                <div class="d-flex align-center" style="cursor: pointer;">
                  <v-icon :color="item.type === 'folder' ? 'primary' : 'grey'" class="mr-2">
                    {{ item.type === 'folder' ? 'mdi-folder' : 'mdi-file' }}
                  </v-icon>
                  <span>{{ item.name }}</span>
                </div>
              </template>
              <template #item.size="{ item }">
                <span v-if="item.type === 'file'">{{ item.formatted_size }}</span>
                <span v-else>-</span>
              </template>
              <template #item.permissions="{ item }">
                <span class="font-monospace text-body-2">
                  {{ item.type === 'folder' ? 'drwxr-xr-x' : '-rw-r--r--' }}
                </span>
              </template>
              <template #item.actions="{ item }">
                <div class="d-flex align-center">
                  <v-btn icon variant="text" @click="onDownload(item)" :disabled="item.type==='folder'">
                    <v-icon>mdi-download</v-icon>
                  </v-btn>
                  <v-btn icon variant="text" @click="openRename(item)">
                    <v-icon>mdi-rename</v-icon>
                  </v-btn>
                  <v-btn icon variant="text" color="primary" @click="onToggleShare(item)">
                    <v-icon>{{ item.is_shared ? 'mdi-share-off' : 'mdi-share-variant' }}</v-icon>
                  </v-btn>
                  <v-btn icon variant="text" color="error" @click="askDelete(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </div>
              </template>
            </v-data-table>
          </v-card>
      </div>

      <!-- Modo de visualização em grid -->
      <div v-else>
        <!-- Indicador de loading sutil para grid -->
        <div v-if="loading" class="mb-2">
          <v-progress-linear indeterminate color="primary" height="2" />
        </div>

        <!-- Grid moderno estilo Google Drive -->
        <div class="modern-grid">
          <div 
            v-for="item in tableItems" 
            :key="item.uid" 
            class="grid-item"
            :class="{ 
              'processing-item': item.isProcessing,
              'selected': selectedItems.includes(item)
            }"
            @dblclick="openItem(item)"
            @click="toggleSelection(item, $event)"
            @contextmenu="onContextMenu($event, { item })"
          >
            <!-- Preview do arquivo/pasta -->
            <div class="item-preview">
              <!-- Ícone do tipo de arquivo no canto superior esquerdo -->
              <div class="file-type-icon">
                <v-icon 
                  :color="getFileTypeColor(item)" 
                  size="16"
                >
                  {{ getFileTypeIcon(item) }}
                </v-icon>
              </div>

          <!-- Menu de contexto no canto superior direito -->
          <v-menu
            location="bottom end"
            :close-on-content-click="false"
            :z-index="9999"
            :offset="[0, 8]"
          >
            <template #activator="{ props }">
              <v-btn 
                icon 
                variant="text" 
                size="small"
                class="context-menu-btn"
                v-bind="props"
                @click.stop
              >
                <v-icon size="16">mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" class="context-menu-list">
              <v-list-item v-if="item.type==='file'" title="Baixar" @click="onDownload(item)" />
              <v-list-item title="Renomear" @click="openRename(item)" />
              <v-list-item title="Compartilhar" @click="onToggleShare(item)" />
              <v-list-item title="Excluir" @click="askDelete(item)" />
            </v-list>
          </v-menu>

              <!-- Conteúdo do preview -->
              <div class="preview-content">
                <div v-if="item.type === 'folder'" class="folder-preview">
                  <v-icon color="primary" size="64">mdi-folder</v-icon>
                </div>
                <div v-else-if="isVideoFile(item)" class="video-preview">
                  <div class="video-thumbnail">
                    <v-icon color="white" size="48">mdi-play-circle</v-icon>
                  </div>
                </div>
                <div v-else-if="isImageFile(item)" class="image-preview">
              <img 
              :src="getImageUrl(item)" 
              :alt="item.name"
              class="image-thumbnail"
              @error="handleImageError($event, item)"
              />
            </div>
            <div v-else-if="isDocumentFile(item)" class="document-preview">
              <div class="document-thumbnail">
                <v-icon color="blue" size="24" class="doc-icon">mdi-file-document</v-icon>
              </div>
            </div>
            <div v-else class="generic-preview">
              <v-icon color="grey" size="48">mdi-file</v-icon>
            </div>
          </div>
        </div>
        
        <!-- Informações do arquivo -->
        <div class="item-info">
              <div class="item-name">{{ item.name }}</div>
              <div class="item-meta">
                <span class="user-action">{{ getActionText(item) }}</span>
                <span class="separator">•</span>
                <span class="date">{{ item.updated_at }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Botão "Mais" se houver mais itens -->
        <div class="load-more-container" v-if="hasMoreItems">
          <v-btn variant="outlined" class="load-more-btn">
            <v-icon start>mdi-plus</v-icon>
            Mais
          </v-btn>
        </div>
      </div>
    </template>

    <!-- Confirmação de exclusão de arquivo/pasta -->
    <v-dialog v-model="showDelete" max-width="420">
      <v-card>
        <v-card-title>Confirmar exclusão</v-card-title>
        <v-card-text>
          Tem certeza que deseja excluir <strong>{{ deleteTarget?.name }}</strong>?
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDelete=false">Cancelar</v-btn>
          <v-btn color="error" @click="confirmDelete">Excluir</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Nova Pasta -->
    <v-dialog v-model="showCreateFolderModal" max-width="420">
      <v-card>
        <v-card-title>Nova Pasta</v-card-title>
        <v-card-text>
          <v-text-field v-model="newFolderName" label="Nome da pasta" autofocus />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCreateFolderModal=false">Cancelar</v-btn>
          <v-btn color="primary" @click="createFolder">Criar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Renomear -->
    <v-dialog v-model="showRename" max-width="420">
      <v-card>
        <v-card-title>Renomear</v-card-title>
        <v-card-text>
          <v-text-field v-model="renameValue" label="Novo nome" autofocus />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showRename=false">Cancelar</v-btn>
          <v-btn color="primary" @click="confirmRename">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Preview de imagem -->
    <v-dialog v-model="imagePreview.open" max-width="90vw" max-height="90vh" persistent>
      <v-card class="image-preview-dialog" rounded="lg">
        <v-toolbar color="primary" flat>
          <v-toolbar-title class="text-white">{{ imagePreview.title }}</v-toolbar-title>
          <v-spacer />
          <v-btn icon @click="imagePreview.open=false" color="white">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-img 
          :src="imagePreview.src" 
          contain 
          max-height="70vh"
          class="image-preview-content"
        />
      </v-card>
    </v-dialog>

    <!-- Context Menu -->
    <v-menu
      v-model="contextMenu.open"
      :position-x="contextMenu.x"
      :position-y="contextMenu.y"
      absolute
      offset-y
    >
      <v-list density="compact">
        <v-list-item @click="openItem(contextMenu.item)" v-if="contextMenu.item">
          <template #prepend>
            <v-icon>{{ contextMenu.item?.type === 'folder' ? 'mdi-folder-open' : 'mdi-eye' }}</v-icon>
          </template>
          <v-list-item-title>{{ contextMenu.item?.type === 'folder' ? 'Abrir' : 'Visualizar' }}</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="onDownload(contextMenu.item)" v-if="contextMenu.item?.type === 'file'">
          <template #prepend>
            <v-icon>mdi-download</v-icon>
          </template>
          <v-list-item-title>Baixar</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="openRename(contextMenu.item)" v-if="contextMenu.item">
          <template #prepend>
            <v-icon>mdi-rename</v-icon>
          </template>
          <v-list-item-title>Renomear</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="onToggleShare(contextMenu.item)" v-if="contextMenu.item">
          <template #prepend>
            <v-icon>mdi-share-variant</v-icon>
          </template>
          <v-list-item-title>Compartilhar</v-list-item-title>
        </v-list-item>
        
        <v-divider />
        
        <v-list-item @click="askDelete(contextMenu.item)" v-if="contextMenu.item" class="text-error">
          <template #prepend>
            <v-icon color="error">mdi-delete</v-icon>
          </template>
          <v-list-item-title>Excluir</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import VueCookies from 'vue-cookies'

// Helper function para rotas
const route = (name: string, params: any = {}) => {
  const routes: Record<string, string> = {
    'files.index': '/',
    'files.navigate': '/files/navigate',
    'files.upload': '/files/upload',
    'files.rename': `/files/${params.file}/rename`,
    'files.delete': `/files/${params.file}`,
    'files.download': `/files/${params.file}/download`,
    'files.view': `/files/${params.file}/view`,
    'folders.create': '/folders',
    'folders.rename': `/folders/${params.folder}/rename`,
    'folders.delete': `/folders/${params.folder}`,
  }
  return routes[name] || '/'
}

// Props do Inertia
const props = defineProps<{
  folders: Array<{
    id: number
    name: string
    path: string
    parent_id: number | null
    created_by: number
    is_shared: boolean
    created_at: string
    updated_at: string
  }>
  files: Array<{
    id: number
    name: string
    original_name: string
    path: string
    mime_type: string
    size: number
    formatted_size: string
    folder_id: number | null
    uploaded_by: number
    is_shared: boolean
    created_at: string
    updated_at: string
  }>
  currentFolderId: number | null | undefined
  currentPath: Array<{ id: number, name: string }>
  currentFolderPath?: string
  viewMode: 'list' | 'grid'
  searchQuery: string
  loading: boolean
}>()

const initialLoaded = ref(false)
const showCreateFolderModal = ref(false)
const newFolderName = ref('')
const fileInput = ref<HTMLInputElement>()
const showRename = ref(false)
const renameTarget = ref<{ type: 'file'|'folder', id: number } | null>(null)
const renameValue = ref('')
const showDelete = ref(false)
const deleteTarget = ref<any | null>(null)

// Google Drive features
const isDragOver = ref(false)
const selectedItems = ref<any[]>([])
const contextMenu = ref<{ open: boolean, x: number, y: number, item: any }>({
  open: false,
  x: 0,
  y: 0,
  item: null
})
const searchQuery = ref(props.searchQuery)

// Inicializar viewMode com valor do cookie ou props
const savedViewMode = VueCookies.get('filemanager_view_mode')
const viewMode = ref(savedViewMode || props.viewMode || 'list')

// Watcher para persistir mudanças no viewMode
watch(viewMode, (newValue) => {
  VueCookies.set('filemanager_view_mode', newValue, '30d')
}, { immediate: false })

const headers = [
  { title: 'Nome', value: 'name', sortable: true },
  { title: 'Tamanho', value: 'size', align: 'start', width: 120, sortable: true },
  { title: 'Última modificação', value: 'updated_at', width: 180, sortable: true },
  { title: 'Permissões', value: 'permissions', width: 120, sortable: false },
  { title: 'Ações', value: 'actions', sortable: false, width: 150 },
]

const tableItems = computed(() => {
  // Função auxiliar para comparar IDs considerando null/undefined
  const isSameFolder = (itemFolderId: number | null | undefined, currentFolderId: number | null | undefined) => {
    if (currentFolderId === undefined || currentFolderId === null) {
      return itemFolderId === null || itemFolderId === undefined
    }
    return itemFolderId === currentFolderId
  }

  // Filtrar pastas pela pasta atual (usando dados locais)
  const folderItems = localFolders.value
    .filter(f => isSameFolder(f.parent_id, currentFolderId.value))
    .map(f => ({
      ...f, // ✅ Manter todos os campos originais do backend
      uid: `folder-${f.id}`,
      type: 'folder',
      name: f.name,
      formatted_size: '-',
      updated_at: formatDate(f.updated_at),
      isProcessing: false,
    }))

  // Filtrar arquivos pela pasta atual (usando dados locais)
  const fileItems = localFiles.value
    .filter(f => isSameFolder(f.folder_id, currentFolderId.value))
    .map(f => ({
      ...f, // ✅ Manter todos os campos originais do backend
      uid: `file-${f.id}`,
      type: 'file',
      name: f.original_name,
      formatted_size: f.formatted_size,
      updated_at: formatDate(f.updated_at),
      isProcessing: false,
    }))

  return [...folderItems, ...fileItems]
})

const hasItems = computed(() => tableItems.value.length > 0)

const breadcrumbItems = computed(() => {
  const items = [
    { title: 'Início', value: null as number | null, disabled: currentFolderId.value === null || currentFolderId.value === undefined }
  ]

  // Construir breadcrumb baseado na pasta atual
  if (currentFolderId.value) {
    const currentFolder = localFolders.value.find(f => f.id === currentFolderId.value)
    if (currentFolder) {
      const path = buildBreadcrumbPath(currentFolder)
      path.forEach(folder => {
        items.push({
          title: folder.name,
          value: folder.id as number | null,
          disabled: currentFolderId.value === folder.id
        })
      })
    }
  }

  return items
})

// Função para construir caminho do breadcrumb
const buildBreadcrumbPath = (folder: any): any[] => {
  const path = []
  let current = folder
  
  while (current) {
    path.unshift(current)
    current = localFolders.value.find(f => f.id === current.parent_id)
  }
  
  return path
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const selectedFiles = target.files

  if (selectedFiles) {
    for (const file of selectedFiles) {
      const formData = new FormData()
      formData.append('file', file)
      if (props.currentFolderId) {
        formData.append('folder_id', props.currentFolderId.toString())
      }

      router.post(route('files.upload'), formData, {
        preserveScroll: true,
        onSuccess: () => {
          // Sucesso será tratado pelo backend
        }
      })
    }
  }
}

const createFolder = async () => {
  if (newFolderName.value.trim()) {
    const folderName = newFolderName.value
    showCreateFolderModal.value = false
    newFolderName.value = ''

    router.post(route('folders.create'), {
      name: folderName,
      parent_id: props.currentFolderId
    }, {
      preserveScroll: true
    })
  }
}

const openRename = (item: any) => {
  renameTarget.value = { type: item.type, id: item.id }
  renameValue.value = item.name
  showRename.value = true
}

const confirmRename = async () => {
  if (!renameTarget.value) return

  const target = renameTarget.value
  const newName = renameValue.value

  showRename.value = false
  renameTarget.value = null
  renameValue.value = ''

  if (target.type === 'file') {
    router.patch(route('files.rename', { file: target.id }), {
      name: newName
    }, {
      preserveScroll: true
    })
  } else {
    router.patch(route('folders.rename', { folder: target.id }), {
      name: newName
    }, {
      preserveScroll: true
    })
  }
}

const askDelete = (item: any) => {
  deleteTarget.value = item
  showDelete.value = true
}

const confirmDelete = async () => {
  if (!deleteTarget.value) return
  const item = deleteTarget.value

  showDelete.value = false
  deleteTarget.value = null

  if (item.type === 'file') {
    router.delete(route('files.delete', { file: item.id }), {
      preserveScroll: true
    })
  } else {
    router.delete(route('folders.delete', { folder: item.id }), {
      preserveScroll: true
    })
  }
}

const onDownload = async (item: any) => {
  if (item.type === 'file') {
    window.location.href = route('files.download', { file: item.id })
  }
}

// Função para construir o caminho da pasta baseado no ID
const getFolderPath = (folderId: number | null | undefined): string => {
  if (!folderId) return '/'
  
  // Encontrar a pasta pelo ID
  const folder = props.folders.find(f => f.id === folderId)
  if (!folder) return '/'
  
  // Construir o caminho completo
  const buildPath = (folder: any): string => {
    if (!folder.parent_id) {
      return folder.name
    }
    
    const parent = props.folders.find(f => f.id === folder.parent_id)
    if (!parent) return folder.name
    
    return `${buildPath(parent)}/${folder.name}`
  }
  
  return buildPath(folder)
}

// Estado local para navegação instantânea
const currentFolderId = ref<number | null>(props.currentFolderId || null)
const localFolders = ref([...props.folders])
const localFiles = ref([...props.files])

// Listener para navegação instantânea do sidebar
onMounted(() => {
  window.addEventListener('navigateToRoot', () => {
    navigateToFolder(null)
  })
})

onUnmounted(() => {
  window.removeEventListener('navigateToRoot', () => {
    navigateToFolder(null)
  })
})

const navigateToFolder = async (folderId: number | null | undefined) => {
  // Navegação instantânea - atualiza estado local imediatamente
  currentFolderId.value = folderId
  
  // Atualizar URL sem recarregar a página
  const folderPath = folderId ? getFolderPath(folderId) : ''
  const newUrl = folderId ? `/${folderPath}` : '/'
  
  // Usar history API para navegação instantânea
  window.history.pushState({}, '', newUrl)
  
  // Carregar dados da pasta em background (se necessário)
  if (folderId && !hasFolderData(folderId)) {
    loadFolderData(folderId)
  }
}

// Verificar se já temos dados da pasta
const hasFolderData = (folderId: number) => {
  return localFolders.value.some(f => f.parent_id === folderId) || 
         localFiles.value.some(f => f.folder_id === folderId)
}

// Carregar dados da pasta em background
const loadFolderData = async (folderId: number) => {
  try {
    const response = await fetch(`/api/folders/${folderId}/contents`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      // Adicionar novos dados ao cache local
      localFolders.value.push(...data.folders)
      localFiles.value.push(...data.files)
    }
  } catch (error) {
    console.error('Erro ao carregar dados da pasta:', error)
  }
}

const onToggleShare = async (item: any) => {
  // Placeholder para funcionalidade de compartilhamento
}

const isImage = (mime: string) => mime?.startsWith('image/')
const isPdf = (mime: string) => mime === 'application/pdf'

const imagePreview = ref<{ open: boolean, src: string, title: string }>({ open: false, src: '', title: '' })

const onRowDoubleClick = (event: any, { item }: { item: any }) => {
  openItem(item)
}

const openItem = async (item: any) => {
  if (item.type === 'folder') {
    await navigateToFolder(item.id)
    return
  }

  // Buscar metadados do arquivo
  const file = props.files.find(f => f.id === item.id)
  const mime = file?.mime_type || ''

  if (isImage(mime)) {
    const url = route('files.view', { file: item.id })
    imagePreview.value = { open: true, src: url, title: item.name }
    return
  }

  if (isPdf(mime)) {
    const url = route('files.view', { file: item.id })
    window.open(url, '_blank')
    return
  }

  await onDownload(item)
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 1) {
    return 'há 1 dia'
  } else if (diffDays < 7) {
    return `há ${diffDays} dias`
  } else {
    return date.toLocaleDateString('pt-BR')
  }
}

// Google Drive features functions
const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    for (let i = 0; i < files.length; i++) {
      const formData = new FormData()
      formData.append('file', files[i])
      if (props.currentFolderId) {
        formData.append('folder_id', props.currentFolderId.toString())
      }
      
      router.post(route('files.upload'), formData, {
        preserveScroll: true
      })
    }
  }
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
}

const toggleSelection = (item: any, event: MouseEvent) => {
  if (event.ctrlKey || event.metaKey) {
    const index = selectedItems.value.findIndex(selected => selected.uid === item.uid)
    if (index > -1) {
      selectedItems.value.splice(index, 1)
    } else {
      selectedItems.value.push(item)
    }
  } else {
    selectedItems.value = [item]
  }
}

const clearSelection = () => {
  selectedItems.value = []
}

const onContextMenu = (event: MouseEvent, { item }: { item: any }) => {
  event.preventDefault()
  contextMenu.value = {
    open: true,
    x: event.clientX,
    y: event.clientY,
    item: item
  }
}

const downloadSelected = () => {
  const files = selectedItems.value.filter(item => item.type === 'file')
  files.forEach(file => {
    window.open(route('files.download', { file: file.id }), '_blank')
  })
}

const shareSelected = () => {
  // Implementar compartilhamento em lote
  console.log('Compartilhar selecionados:', selectedItems.value)
}

const deleteSelected = () => {
  if (selectedItems.value.length > 0) {
    deleteTarget.value = { 
      type: 'multiple', 
      items: selectedItems.value,
      name: `${selectedItems.value.length} item(s)`
    }
    showDelete.value = true
  }
}

const hasSelectedFiles = computed(() => {
  return selectedItems.value.some(item => item.type === 'file')
})

const hasMoreItems = computed(() => {
  return false // Implementar lógica de paginação se necessário
})

// Funções para o grid moderno
const getFileTypeIcon = (item: any) => {
  if (item.type === 'folder') return 'mdi-folder'
  if (isVideoFile(item)) return 'mdi-video'
  if (isImageFile(item)) return 'mdi-image'
  if (isDocumentFile(item)) return 'mdi-file-document'
  return 'mdi-file'
}

const getFileTypeColor = (item: any) => {
  if (item.type === 'folder') return 'primary'
  if (isVideoFile(item)) return 'orange'
  if (isImageFile(item)) return 'pink'
  if (isDocumentFile(item)) return 'blue'
  return 'grey'
}

const isVideoFile = (item: any) => {
  const videoExtensions = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.webm']
  return videoExtensions.some(ext => item.name.toLowerCase().endsWith(ext))
}

const isImageFile = (item: any) => {
  const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp', '.svg']
  return imageExtensions.some(ext => item.name.toLowerCase().endsWith(ext))
}

const isDocumentFile = (item: any) => {
  const docExtensions = ['.pdf', '.doc', '.docx', '.txt', '.rtf', '.odt']
  return docExtensions.some(ext => item.name.toLowerCase().endsWith(ext))
}

const getImageUrl = (item: any) => {
  // Se já tem URL, usar ela
  if (item.url) {
    return item.url
  }
  
  // Se tem path, construir URL
  if (item.path) {
    // Codificar apenas as partes do caminho, não o caminho completo
    const pathParts = item.path.split('/')
    const encodedParts = pathParts.map(part => encodeURIComponent(part))
    const encodedPath = encodedParts.join('/')
    return `http://localhost:8000/storage/${encodedPath}`
  }
  
  // Fallback
  return item.preview_url || ''
}

const getActionText = (item: any) => {
  // Implementar lógica baseada no histórico do arquivo
  return 'Você fez o upload'
}

const handleImageError = (event: any, item: any) => {
  // Se a imagem falhar ao carregar, mostrar ícone genérico
  const img = event.target
  img.style.display = 'none'
  
  // Criar um ícone de fallback
  const fallback = document.createElement('div')
  fallback.className = 'image-fallback'
  fallback.innerHTML = '<v-icon color="primary" size="48">mdi-image</v-icon>'
  img.parentNode.appendChild(fallback)
}

// Removido verificação de autenticação - deixar o backend fazer o redirecionamento

// Marcar como carregado após o mount
nextTick(() => {
  initialLoaded.value = true
})
</script>

<style scoped>
.loading-indicator {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1;
}

/* Shimmer effect para itens em processamento */
.processing-item {
  position: relative !important;
  overflow: hidden !important;
}

.processing-item::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.4) 50%,
    transparent 100%
  );
  animation: shimmer-sweep 2s infinite;
  z-index: 1;
}

@keyframes shimmer-sweep {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

/* Para tema escuro */
.v-theme--dark .processing-item::after {
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.2) 50%,
    transparent 100%
  );
}

/* Drop zone overlay */
.drop-zone-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.drop-zone-content {
  background: white;
  padding: 48px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border: 2px dashed #1976d2;
}

/* File item selection */
.file-item {
  transition: all 0.2s ease;
  cursor: pointer;
}

.file-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.file-item.selected {
  border-color: #1976d2 !important;
  background-color: rgba(25, 118, 210, 0.08) !important;
}

/* Context menu */
.v-menu__content {
  border-radius: 8px !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
}

/* Toolbar improvements */
.v-toolbar {
  border-bottom: none !important;
}

/* Search field */
.v-text-field--outlined .v-field {
  border-radius: 8px;
}

/* Selection counter */
.v-btn-toggle {
  border-radius: 8px;
}

/* Toolbar transparente */
.toolbar-transparent {
  background-color: transparent !important;
  box-shadow: none !important;
}

.toolbar-transparent .v-toolbar__content {
  background-color: transparent !important;
}

/* Campo de busca moderno */
.modern-search .v-field {
  background-color: rgba(255, 255, 255, 0.9) !important;
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
  border-radius: 12px !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
  transition: all 0.3s ease !important;
}

.modern-search .v-field:hover {
  border-color: rgba(25, 118, 210, 0.3) !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.modern-search .v-field--focused {
  border-color: #1976d2 !important;
  box-shadow: 0 4px 16px rgba(25, 118, 210, 0.15) !important;
  background-color: rgba(255, 255, 255, 1) !important;
}

.modern-search .v-field__input {
  padding: 8px 12px !important;
  font-size: 14px !important;
}

.modern-search .v-field__prepend-inner {
  padding-left: 12px !important;
}

.modern-search .v-field__append-inner {
  padding-right: 12px !important;
}

.modern-search .v-icon {
  color: rgba(0, 0, 0, 0.6) !important;
}

.modern-search .v-field--focused .v-icon {
  color: #1976d2 !important;
}

/* Grid moderno estilo Google Drive */
.modern-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  padding: 16px 0;
}

.grid-item {
  background: #ffffff;
  border-radius: 12px;
  overflow: visible;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  position: relative;
}

.grid-item:hover {
  background: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #e3f2fd;
}

.grid-item.selected {
  border-color: rgb(var(--v-theme-primary));
  background: #e3f2fd;
}

.item-preview {
  position: relative;
  height: 140px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.file-type-icon {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
}

.context-menu-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s ease;
  color: white !important;
}

.context-menu-btn:hover {
  background: rgba(0, 0, 0, 0.8) !important;
}

.grid-item:hover .context-menu-btn {
  opacity: 1;
}

.context-menu-list {
  position: relative !important;
  z-index: 9999 !important;
  min-width: 150px !important;
}

/* Força o posicionamento correto do menu */
.v-menu .v-overlay__content {
  position: absolute !important;
  z-index: 9999 !important;
}

/* Correção específica para o posicionamento do menu */
.v-menu .v-list {
  position: relative !important;
  transform: none !important;
}

/* Garante que o menu apareça no local correto */
.v-overlay__content {
  position: fixed !important;
}

.preview-content {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.folder-preview {
  color: #1976d2;
}

.video-preview {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ff6b35, #f7931e);
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-thumbnail {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.3);
}

.image-preview {
  background: #f5f5f5;
  position: relative;
  overflow: hidden;
}

.image-thumbnail {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

.image-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}

.document-preview {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.document-thumbnail {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 4px;
  margin: 8px;
}

.doc-icon {
  color: #1976d2 !important;
}

.generic-preview {
  background: linear-gradient(135deg, #6b7280, #374151);
}

.item-info {
  padding: 12px;
  background: #ffffff;
}

.item-name {
  color: #1f2937;
  font-weight: 500;
  font-size: 14px;
  line-height: 1.4;
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-meta {
  color: #6b7280;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.user-action {
  color: #374151;
}

.separator {
  color: #9ca3af;
}

.date {
  color: #6b7280;
}

.load-more-container {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

.load-more-btn {
  background: #f8f9fa !important;
  border-color: #dee2e6 !important;
  color: #495057 !important;
}

.load-more-btn:hover {
  background: #e9ecef !important;
  border-color: #adb5bd !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
  .modern-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
  }
  
  .item-preview {
    height: 120px;
  }
  
  .drop-zone-content {
    padding: 24px;
    margin: 16px;
  }
  
  .file-item:hover {
    transform: none;
  }
}

/* Image Preview Dialog Styling */
.image-preview-dialog {
  border-radius: 16px !important;
  overflow: hidden;
}

.image-preview-dialog .v-toolbar {
  border-radius: 16px 16px 0 0 !important;
}

.image-preview-content {
  border-radius: 0 0 16px 16px !important;
}
</style>
