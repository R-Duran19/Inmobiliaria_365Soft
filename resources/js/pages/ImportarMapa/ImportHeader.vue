<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
  proyectoNombre: string;
}>();

const emit = defineEmits<{
  volver: [];
}>();
</script>

<template>
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        Importar GeoJSON
      </h1>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Proyecto: <span class="font-medium">{{ proyectoNombre }}</span>
      </p>
    </div>
    <Button variant="outline" @click="emit('volver')">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      Volver
    </Button>
  </div>
</template>