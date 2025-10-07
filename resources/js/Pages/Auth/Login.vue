<template>
  <v-app>
    <v-main>
      <v-container fluid class="fill-height">
        <v-row justify="center" align="center">
          <v-col cols="12" sm="8" md="6" lg="4">
            <v-card elevation="8" class="pa-8">
              <v-card-title class="text-center mb-6">
                <h1 class="text-h4 font-weight-bold text-primary">
                  ExpertSeg Drive
                </h1>
                <p class="text-subtitle1 text-medium-emphasis mt-2">
                  Faça login em sua conta
                </p>
              </v-card-title>

              <v-form @submit.prevent="submit">
                <v-text-field
                  v-model="form.email"
                  label="Email"
                  type="email"
                  variant="outlined"
                  prepend-inner-icon="mdi-email"
                  required
                  class="mb-4"
                />

                <v-text-field
                  v-model="form.password"
                  label="Senha"
                  type="password"
                  variant="outlined"
                  prepend-inner-icon="mdi-lock"
                  required
                  class="mb-4"
                />

                <v-checkbox
                  v-model="form.remember"
                  label="Lembrar de mim"
                  color="primary"
                  class="mb-6"
                />

                <v-btn
                  type="submit"
                  color="primary"
                  size="large"
                  block
                  :loading="processing"
                  class="mb-4"
                >
                  <span v-if="processing">Entrando...</span>
                  <span v-else>Entrar</span>
                </v-btn>

                <div class="text-center">
                  <p class="text-body2">
                    Não tem uma conta?
                    <a @click="navigateTo('/register')" class="text-primary text-decoration-none cursor-pointer">
                      Registre-se aqui
                    </a>
                  </p>
                </div>
              </v-form>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const processing = ref(false)

const submit = () => {
  processing.value = true
  router.post('/login', form.value, {
    onFinish: () => processing.value = false,
  })
}

const navigateTo = (url) => {
  router.visit(url)
}
</script>
