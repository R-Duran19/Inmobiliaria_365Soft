<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

const props = defineProps({
  proyecto: {
    type: Object,
    required: true,
    validator: (value) => {
      return ['id', 'nombre', 'estado'].every(key => key in value);
    },
  },
});
</script>

<template>
  <tr>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      <div class="flex gap-2">
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm" class="hover:bg-gray-100 dark:hover:bg-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Editar</TooltipContent>
        </Tooltip>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="sm" class="hover:bg-gray-100 dark:hover:bg-gray-700">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </Button>
          </TooltipTrigger>
          <TooltipContent>Eliminar</TooltipContent>
        </Tooltip>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
      {{ proyecto.nombre }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
      <Badge :variant="proyecto.estado === 'Activo' ? 'default' : 'secondary'">
        {{ proyecto.estado }}
      </Badge>
    </td>
  </tr>
</template>
