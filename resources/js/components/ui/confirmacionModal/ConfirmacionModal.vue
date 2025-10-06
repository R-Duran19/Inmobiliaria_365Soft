<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  modelValue: boolean
  title?: string
  message: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const visible = ref(props.modelValue)

watch(() => props.modelValue, (val) => (visible.value = val))
watch(visible, (val) => emit('update:modelValue', val))

function close() {
  visible.value = false
  emit('cancel')
}

function confirm() {
  emit('confirm')
  visible.value = false
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-md"
  >
    <div
      class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-[90%] max-w-md p-6 transform transition-all scale-100"
    >
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
        {{ title || 'Confirmar acción' }}
      </h3>
      <p class="text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
        {{ message }}
      </p>

      <div class="flex justify-end gap-3">
        <button
          @click="close"
          class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        >
          Cancelar
        </button>
        <button
          @click="confirm"
          class="px-4 py-2 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition"
        >
          Aceptar
        </button>
      </div>
    </div>
  </div>
</template>
