<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import ProyectosTableRow from './ProyectosTableRow.vue';

defineProps<{
  proyectos: Array<{
    id: number;
    nombre: string;
    descripcion: string;
    estado: boolean;
  }>;
}>();
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mx-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <ProyectosTableRow
          v-for="proyecto in proyectos"
          :key="proyecto.id"
          :proyecto="proyecto"
        />
      </tbody>
    </table>
  </div>
</template>
