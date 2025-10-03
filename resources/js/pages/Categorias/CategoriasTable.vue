<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import CategoriasTableRow from './CategoriasTableRow.vue';

defineProps<{
  categorias: Array<{
    id: number;
    nombre: string;
    idproyecto: number | null;
    estado: boolean;
    proyecto?: { id: number; nombre: string };
  }>;
  loading: boolean;
}>();

defineEmits(['refresh', 'edit']);
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mx-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Proyecto</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <tr v-if="loading">
          <td colspan="4" class="px-6 py-4 text-center">Cargando...</td>
        </tr>
        <tr v-else-if="categorias.length === 0">
          <td colspan="4" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zm0 2v14h14V5H5z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 9h6m-6 4h6m-6 4h6" />
              </svg>
              <p class="text-gray-500 dark:text-gray-400">No hay categorías disponibles</p>
            </div>
          </td>
        </tr>
        <CategoriasTableRow
          v-for="categoria in categorias"
          :key="categoria.id"
          :categoria="categoria"
          @refresh="$emit('refresh')"
          @edit="$emit('edit', $event)"
        />
      </tbody>
      <tfoot class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <td colspan="4" class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 text-right">
            <div v-if="!loading && categorias.length > 0">
              Mostrando {{ categorias.length }} categorías
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>
