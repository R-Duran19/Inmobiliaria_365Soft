<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import ProyectosTableRow from './ProyectosTableRow.vue';
import { router } from '@inertiajs/vue3';

defineProps<{
  proyectos: Array<{
    id: number;
    nombre: string;
    descripcion: string;
    fecha_lanzamiento: string | null;
    numero_lotes: number | null;
    ubicacion: string | null;
    fotografia: string | null;
    estado: boolean;
  }>;
}>();

const handleRefresh = () => {
  router.reload({ only: ['proyectos'] });
};
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mx-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Lanzamiento</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Número de Lotes</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ubicación</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fotografía</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <ProyectosTableRow
          v-for="proyecto in proyectos"
          :key="proyecto.id"
          :proyecto="proyecto"
          @refresh="handleRefresh"
        />
      </tbody>
    </table>
  </div>
</template>
