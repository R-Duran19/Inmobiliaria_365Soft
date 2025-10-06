<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

interface Props {
  modelValue: boolean
  type?: 'success' | 'error'
  message: string
  duration?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const visible = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val && props.duration !== 0) {
    setTimeout(() => (visible.value = false), props.duration || 2500)
  }
})
watch(visible, (val) => emit('update:modelValue', val))
</script>

<template>
  <transition name="slide-fade">
    <div
      v-if="visible"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-white"
      :class="props.type === 'success'
        ? 'bg-green-600'
        : 'bg-red-600'"
    >
      <i
        :class="[
          props.type === 'success'
            ? 'pi pi-check-circle'
            : 'pi pi-times-circle',
          'text-xl',
        ]"
      ></i>
      <p class="font-medium">{{ message }}</p>
    </div>
  </transition>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.4s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
