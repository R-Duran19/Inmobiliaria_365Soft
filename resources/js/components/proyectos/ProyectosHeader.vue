<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ProyectosFormSheet from './ProyectosFormSheet.vue';

const search = ref('');
</script>

<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mx-6 my-4">
    <div class="w-full sm:flex-grow">
      <Input
        v-model="search"
        placeholder="Buscar proyectos..."
        class="w-full max-w-3xl dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
    </div>
    <div class="flex gap-2 w-full sm:w-auto justify-end">
      <!-- Botón para abrir el panel deslizable -->
      <ProyectosFormSheet>
        <Button variant="default" class="w-full sm:w-auto">
          Agregar Proyecto
        </Button>
      </ProyectosFormSheet>
      <Button variant="outline" class="w-full sm:w-auto dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700">
        Exportar
      </Button>
    </div>
  </div>
</template>
