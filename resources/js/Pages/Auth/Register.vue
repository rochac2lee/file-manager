<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          ExpertSeg Drive
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Crie sua conta
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
            <input
              id="name"
              name="name"
              type="text"
              required
              v-model="form.name"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Seu nome completo"
            />
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
              id="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              v-model="form.email"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Endereço de email"
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
            <input
              id="password"
              name="password"
              type="password"
              required
              v-model="form.password"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Mínimo 8 caracteres"
            />
          </div>
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
            <input
              id="password_confirmation"
              name="password_confirmation"
              type="password"
              required
              v-model="form.password_confirmation"
              class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Confirme sua senha"
            />
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="processing"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="processing">Criando conta...</span>
            <span v-else>Criar Conta</span>
          </button>
        </div>

        <div class="text-center">
          <p class="text-sm text-gray-600">
            Já tem uma conta?
            <a @click="navigateTo('/login')" class="font-medium text-indigo-600 hover:text-indigo-500 cursor-pointer">
              Faça login aqui
            </a>
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const processing = ref(false)

const submit = () => {
  processing.value = true
  router.post('/register', form.value, {
    onFinish: () => processing.value = false,
  })
}

const navigateTo = (url) => {
  router.visit(url)
}
</script>
