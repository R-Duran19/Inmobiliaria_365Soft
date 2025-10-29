<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Terreno } from '@/types/terrenos';
import { ref } from 'vue';
import EditarTerrenosDrawer from '../EditarTerrenosDrawer.vue';

const props = defineProps<{ terreno: Terreno }>();
const emit = defineEmits(['updated', 'deleted', 'costos']);
const editarVisible = ref(false);

const camposTerreno = [
    { key: 'proyecto.nombre', label: 'Proyecto' },
    { key: 'numero_terreno', label: 'N° Terreno' },
    { key: 'ubicacion', label: 'Ubicación' },
    { key: 'categoria', label: 'Categoría' },
    { key: 'barrio', label: 'Barrio' },
    { key: 'cuadra', label: 'Cuadra' },
    { key: 'superficie', label: 'Superficie' },
];

const abrirSubidaDocumento = () => {
    const id = props.terreno.id;
    window.open(`/documentos/${id}`, '_blank');
};

const abrirVisualizarDocumentos = () => {
    const id = props.terreno.id;
    window.open(`/documentos/visualizar/${id}`);
};

const confirmarEliminar = () => {
    emit('deleted', props.terreno.id);
};

function obtenerValor(obj: any, ruta: string) {
    return ruta.split('.').reduce((acc, key) => acc?.[key], obj);
}
</script>

<template>
    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex gap-1">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="editarVisible = true"
                        >
                            <svg
                                xmlns="http://www/w3.org/2000/svg"
                                class="h-4 w-4 text-blue-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
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
                            @click="confirmarEliminar"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-red-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
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
                            @click="abrirSubidaDocumento"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-green-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12V4m0 0l-4 4m4-4l4 4"
                                />
                            </svg>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Subir Documento</TooltipContent>
                </Tooltip>
            </div>
        </td>

        <td
            v-for="campo in camposTerreno"
            :key="campo.key"
            class="px-6 py-4 text-gray-900 dark:text-gray-100"
        >
            {{
                campo.key === 'proyecto.nombre'
                    ? (terreno.proyecto?.nombre ?? '')
                    : campo.key === 'categoria'
                      ? (terreno.categorias_terrenos?.nombre ?? '')
                      : campo.key === 'barrio'
                        ? (terreno.cuadra?.barrio?.nombre ?? '')
                        : campo.key === 'cuadra'
                          ? (terreno.cuadra?.nombre ?? '')
                          : terreno[campo.key as keyof Terreno]
            }}
        </td>

        <td class="px-8 py-4 whitespace-nowrap">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="emit('costos', terreno)"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-indigo-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-2.21 0-4 1.79-4 4m4-8v4m0 8v4m8-8a8 8 0 11-16 0 8 8 0 0116 0z"
                            />
                        </svg>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Costos</TooltipContent>
            </Tooltip>
        </td>
    </tr>
    <EditarTerrenosDrawer
        :visible="editarVisible"
        :terreno="terreno"
        @update:visible="editarVisible = $event"
        @updated="emit('updated', $event)"
    />
</template>
