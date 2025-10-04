<script setup>
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import ProyectosEditDialog from './ProyectosEditDialog.vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  proyecto: {
    type: Object,
    required: true,
    validator: (value) => {
      return [
        'id', 'nombre', 'descripcion', 'fecha_lanzamiento',
        'numero_lotes', 'ubicacion', 'fotografia', 'estado'
      ].every(key => key in value);
    },
  },
});

const emit = defineEmits(['refresh']);

const editDialogOpen = ref(false);
const fotoDialogOpen = ref(false);

const eliminarProyecto = async () => {
  if (confirm('¿Estás seguro de que deseas eliminar este proyecto?')) {
    try {
      await axios.delete(`/proyectos/${props.proyecto.id}`);
      router.reload({ only: ['proyectos'] });
    } catch (error) {
      console.error(error);
      alert('No se pudo eliminar el proyecto');
    }
  }
};

const toggleEstado = async () => {
  try {
    const endpoint = props.proyecto.estado ? 'desactivar' : 'activar';
    await axios.patch(`/proyectos/${props.proyecto.id}/${endpoint}`);
    router.reload({ only: ['proyectos'] });
  } catch (error) {
    console.error(error);
    alert('No se pudo cambiar el estado del proyecto');
  }
};

const imageError = ref(false);

const getImageUrl = (fotografia) => {
  if (!fotografia) return null;
  if (fotografia.startsWith('http')) return fotografia;
  if (fotografia.startsWith('/')) return fotografia;
  if (fotografia.startsWith('storage/')) return `/${fotografia}`;
  return `/${fotografia}`;
};

const handleImageError = () => {
  imageError.value = true;
  console.error('Error al cargar imagen:', props.proyecto.fotografia);
};
</script>

<template>
  <tr>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      <div class="flex gap-2">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="editDialogOpen = true"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Editar</TooltipContent>
        </Tooltip>

        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="eliminarProyecto"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Eliminar</TooltipContent>
        </Tooltip>

        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="sm"
              class="hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="toggleEstado"
            >
              <svg v-if="proyecto.estado" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ proyecto.estado ? 'Desactivar' : 'Activar' }}</TooltipContent>
        </Tooltip>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ proyecto.nombre }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ proyecto.fecha_lanzamiento ? new Date(proyecto.fecha_lanzamiento).toLocaleDateString() : '—' }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ proyecto.numero_lotes || '—' }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ proyecto.ubicacion || '—' }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      <Badge
        v-if="proyecto.fotografia"
        variant="outline"
        class="cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
        @click="fotoDialogOpen = true"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        Ver Foto
      </Badge>
      <span v-else>—</span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
      <Badge :variant="proyecto.estado ? 'default' : 'secondary'">
        {{ proyecto.estado ? 'Activo' : 'Inactivo' }}
      </Badge>
    </td>
  </tr>

  <ProyectosEditDialog
    v-model:open="editDialogOpen"
    :proyecto="proyecto"
  />

  <Dialog v-model:open="fotoDialogOpen">
    <DialogContent class="sm:max-w-[500px] dark:bg-gray-900 dark:text-gray-100">
      <DialogHeader>
        <DialogTitle>Fotografía del Proyecto</DialogTitle>
      </DialogHeader>
      <div class="p-4">
        <div v-if="imageError" class="flex flex-col items-center justify-center p-8 bg-gray-100 dark:bg-gray-800 rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-gray-500 dark:text-gray-400">No se pudo cargar la imagen</p>
        </div>
        <img 
          v-else
          :src="getImageUrl(proyecto.fotografia)" 
          :alt="`Fotografía de ${proyecto.nombre}`" 
          class="w-full h-auto rounded-lg"
          @error="handleImageError"
        >
      </div>
    </DialogContent>
  </Dialog>
</template>