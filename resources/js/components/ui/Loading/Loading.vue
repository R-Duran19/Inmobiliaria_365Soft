<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: {
    type: String,
    default: 'Cargando...'
  },
  color: {
    type: String,
    default: 'blue', 
    validator: (value) => ['blue', 'indigo', 'purple', 'pink', 'green', 'red', 'orange', 'gradient'].includes(value)
  }
});

const gradientClass = computed(() => {
  const gradients = {
    blue: 'bg-gradient-to-br from-blue-400 to-blue-600',
    indigo: 'bg-gradient-to-br from-indigo-400 to-indigo-600',
    purple: 'bg-gradient-to-br from-purple-400 to-purple-600',
    pink: 'bg-gradient-to-br from-pink-400 to-pink-600',
    green: 'bg-gradient-to-br from-green-400 to-green-600',
    red: 'bg-gradient-to-br from-red-400 to-red-600',
    orange: 'bg-gradient-to-br from-orange-400 to-orange-600',
    gradient: 'bg-gradient-to-br from-indigo-400 via-purple-500 to-pink-600'
  };
  
  return gradients[props.color] || gradients.blue;
});
</script>

<style scoped>

@keyframes custom-pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

</style>

<template>
  <div class="flex justify-center items-center py-20">
    <div class="text-center">
      
      <div class="relative inline-block">
        
        <div 
          class="w-20 h-20 rounded-full animate-pulse"
          :class="gradientClass"
        ></div>
        
        
        <i 
          class="pi pi-spin pi-spinner text-5xl text-white absolute inset-0 flex items-center justify-center"
        ></i>
      </div>
      
      
      <p class="text-gray-600 dark:text-gray-400 mt-6 text-lg font-medium">
        {{ message }}
      </p>
    </div>
  </div>
</template>

