<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Moneda } from '@/types/Moneda';

import { ref } from 'vue';

const props = defineProps<{ moneda: Moneda }>();

// const emit = defineEmits(['updated', 'deleted', 'costos']);

const camposTerreno = [
    { key: 'nombre', label: 'Moneda' },
    { key: 'abreviacion', label: 'Código' },
    { key: 'pais', label: 'País' },
    { key: 'tipo_cambio', label: 'Tipo cambio' },
    { key: 'updated_at', label: 'Fecha actualización' },
    { key: 'activo', label: 'Estado' },
];

const editarVisible = ref(false);

const abrirSubidaDocumento = () => {
    // const id = props.terreno.id;
    // window.open(`/documentos/${id}`, '_blank');
};

const abrirVisualizarDocumentos = () => {
    // const id = props.terreno.id;
    // window.open(`/documentos/visualizar/${id}`);
};

const confirmarEliminar = () => {
    // emit('deleted', props.terreno.id);
};

function obtenerValor(obj: any, ruta: string) {
    return ruta.split('.').reduce((acc, key) => acc?.[key], obj);
}

function formatearFecha(fecha?: string | null): string {
    if (!fecha || fecha.startsWith('-000001')) {
        return 'Sin modificación';
    }
    try {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return 'Sin modificación';
    }
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
            </div>
        </td>

        <td
            v-for="campo in camposTerreno"
            :key="campo.key"
            class="px-6 py-4 text-gray-900 dark:text-gray-100"
        >
            <template v-if="campo.key === 'nombre'">
                {{ props.moneda.nombre ?? '' }}
            </template>
            <template v-else-if="campo.key === 'abreviacion'">
                {{ props.moneda.abreviacion ?? '' }}
            </template>
            <template v-else-if="campo.key === 'pais'">
                {{ props.moneda.pais ?? '' }}
            </template>
            <template v-else-if="campo.key === 'tipo_cambio'">
                {{ props.moneda.tipo_cambio ?? '' }}
            </template>
            <template v-else-if="campo.key === 'updated_at'">
                <span
                    :class="[
                        props.moneda.updated_at
                            ? ''
                            : 'text-gray-400',
                    ]"
                >
                    {{ formatearFecha(props.moneda.updated_at) }}
                </span>
            </template>

            <template v-else-if="campo.key === 'activo'">
                <span
                    :class="
                        props.moneda.activo
                            ? 'font-semibold text-green-600'
                            : 'font-semibold text-red-900'
                    "
                >
                    {{ props.moneda.activo ? 'Activo' : 'Inactivo' }}
                </span>
            </template>
        </td>
    </tr>
    <tr v-if="camposTerreno.length === 0">
        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
            No hay registros de monedas.
        </td>
    </tr>
</template>
