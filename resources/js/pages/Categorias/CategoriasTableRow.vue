<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import axios from 'axios';

const props = defineProps<{
  categoria: {
    id: number;
    nombre: string;
    idproyecto: number | null;
    estado: boolean;
    proyecto?: { id: number; nombre: string };
  };
}>();

const emit = defineEmits(['refresh', 'edit']);

const toggleEstado = async () => {
  try {
    const endpoint = props.categoria.estado ? 'desactivar' : 'activar';
    await axios.patch(`/categorias_terrenos/${endpoint}/${props.categoria.id}`);
    emit('refresh');
  } catch (error) {
    console.error(error);
  }
};

const eliminarCategoria = async () => {
  if (confirm('¿Estás seguro de que deseas eliminar esta categoría?')) {
    try {
      await axios.delete(`/categorias_terrenos/${props.categoria.id}`);
      emit('refresh');
    } catch (error) {
      console.error(error);
    }
  }
};
</script>

<template>
  <tr>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      <div class="flex gap-2">
        <!-- Editar -->
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="$emit('edit', categoria)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Editar</TooltipContent>
        </Tooltip>
        <!-- Eliminar -->
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="eliminarCategoria"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Eliminar</TooltipContent>
        </Tooltip>
        <!-- Activar/desactivar -->
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="toggleEstado"
            >
              <svg v-if="categoria.estado" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ categoria.estado ? 'Desactivar' : 'Activar' }}</TooltipContent>
        </Tooltip>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ categoria.nombre }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ categoria.proyecto?.nombre || '—' }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
      <Badge :variant="categoria.estado ? 'default' : 'secondary'">
        {{ categoria.estado ? 'Activo' : 'Inactivo' }}
      </Badge>
    </td>
  </tr>
</template>
