<template>
  <AppLayout>
    <v-card elevation="2">
      <v-card-title class="d-flex justify-space-between align-center">
        <h2 class="text-h5 font-weight-bold">Gerenciamento de Usuários</h2>
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="showCreateModal = true"
        >
          Novo Usuário
        </v-btn>
      </v-card-title>

      <v-card-text>
        <v-data-table
          :headers="headers"
          :items="users"
          :loading="loading"
          class="elevation-0"
        >
          <template #item.role="{ item }">
            <v-chip
              :color="getRoleColor(item.role)"
              size="small"
              variant="tonal"
            >
              {{ getRoleLabel(item.role) }}
            </v-chip>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex align-center">
              <v-btn
                icon
                variant="text"
                size="small"
                @click="editUser(item)"
                class="mr-1"
              >
                <v-icon color="primary">mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                @click="deleteUser(item)"
                :disabled="item.id === auth.user.id"
              >
                <v-icon color="error">mdi-delete</v-icon>
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card-text>
    </v-card>

    <!-- Modal de Criação/Edição -->
    <v-dialog v-model="showCreateModal" max-width="600px" persistent>
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ isEditing ? 'Editar Usuário' : 'Novo Usuário' }}</span>
        </v-card-title>

        <v-card-text>
          <v-form ref="form" @submit.prevent="saveUser">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="userForm.name"
                  label="Nome completo"
                  variant="outlined"
                  :rules="[v => !!v || 'Nome é obrigatório']"
                  required
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="userForm.email"
                  label="Email"
                  type="email"
                  variant="outlined"
                  :rules="[v => !!v || 'Email é obrigatório', v => /.+@.+\..+/.test(v) || 'Email deve ser válido']"
                  required
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="userForm.sector"
                  label="Setor"
                  variant="outlined"
                  hint="Ex: TI, RH, Financeiro"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="userForm.position"
                  label="Cargo"
                  variant="outlined"
                  hint="Ex: Desenvolvedor, Analista, Gerente"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="userForm.role"
                  label="Função"
                  :items="roleOptions"
                  variant="outlined"
                  :rules="[v => !!v || 'Função é obrigatória']"
                  required
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="userForm.password"
                  label="Senha"
                  type="password"
                  variant="outlined"
                  :rules="isEditing ? [] : [v => !!v || 'Senha é obrigatória', v => v.length >= 8 || 'Senha deve ter pelo menos 8 caracteres']"
                  :required="!isEditing"
                  :hint="isEditing ? 'Deixe em branco para manter a senha atual' : 'Mínimo 8 caracteres'"
                />
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="closeModal"
          >
            Cancelar
          </v-btn>
          <v-btn
            color="primary"
            @click="saveUser"
            :loading="saving"
          >
            {{ isEditing ? 'Atualizar' : 'Criar' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Confirmação de Exclusão -->
    <v-dialog v-model="showDeleteModal" max-width="400px">
      <v-card>
        <v-card-title class="text-h5">
          Confirmar Exclusão
        </v-card-title>
        <v-card-text>
          Tem certeza que deseja excluir o usuário <strong>{{ userToDelete?.name }}</strong>?
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
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  users: {
    type: Array,
    required: true
  },
  auth: {
    type: Object,
    required: true
  }
})

const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const isEditing = ref(false)
const userToDelete = ref(null)

const headers = [
  { title: 'Nome', value: 'name', sortable: true },
  { title: 'Email', value: 'email', sortable: true },
  { title: 'Setor', value: 'sector', sortable: true },
  { title: 'Cargo', value: 'position', sortable: true },
  { title: 'Função', value: 'role', sortable: true },
  { title: 'Criado em', value: 'created_at', sortable: true },
  { title: 'Ações', value: 'actions', sortable: false }
]

const roleOptions = [
  { title: 'Usuário', value: 'usuario' },
  { title: 'Gestor', value: 'gestor' },
  { title: 'Administrador', value: 'administrador' }
]

const userForm = reactive({
  name: '',
  email: '',
  sector: '',
  position: '',
  role: 'usuario',
  password: ''
})

const getRoleColor = (role) => {
  switch (role) {
    case 'administrador':
      return 'error'
    case 'gestor':
      return 'warning'
    case 'usuario':
    default:
      return 'primary'
  }
}

const getRoleLabel = (role) => {
  switch (role) {
    case 'administrador':
      return 'Administrador'
    case 'gestor':
      return 'Gestor'
    case 'usuario':
    default:
      return 'Usuário'
  }
}

const editUser = (user) => {
  isEditing.value = true
  userForm.name = user.name
  userForm.email = user.email
  userForm.sector = user.sector || ''
  userForm.position = user.position || ''
  userForm.role = user.role || 'usuario'
  userForm.password = ''
  showCreateModal.value = true
}

const deleteUser = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const confirmDelete = () => {
  deleting.value = true
  router.delete(`/users/${userToDelete.value.id}`, {
    onFinish: () => {
      deleting.value = false
      showDeleteModal.value = false
      userToDelete.value = null
    }
  })
}

const saveUser = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  saving.value = true

  const data = { ...userForm }
  if (isEditing.value && !data.password) {
    delete data.password
  }

  if (isEditing.value) {
    router.put(`/users/${userForm.id}`, data, {
      onFinish: () => {
        saving.value = false
        closeModal()
      }
    })
  } else {
    router.post('/users', data, {
      onFinish: () => {
        saving.value = false
        closeModal()
      }
    })
  }
}

const closeModal = () => {
  showCreateModal.value = false
  isEditing.value = false
  resetForm()
}

const resetForm = () => {
  userForm.name = ''
  userForm.email = ''
  userForm.sector = ''
  userForm.position = ''
  userForm.role = 'usuario'
  userForm.password = ''
}
</script>