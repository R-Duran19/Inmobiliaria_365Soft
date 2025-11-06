<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import EstadisticaItem from './Estadisticaitem.vue';
interface Estadisticas {
    total: number;
    con_poligono: number;
}

interface Proyecto {
    id: number;
    nombre: string;
    ubicacion: string | null;
    estado: boolean;
    estadisticas: {
        barrios: Estadisticas;
        cuadras: Estadisticas;
        terrenos: Estadisticas;
    };
}

const props = defineProps<{
    proyecto: Proyecto;
}>();

const emit = defineEmits<{
    importar: [id: number];
}>();
</script>

<template>
    <Card
        class="transition-shadow hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <CardHeader>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex-1">
                    <div class="flex gap-2 sm:flex-row sm:items-center">
                        <CardTitle
                            class="text-lg font-semibold text-gray-800 dark:text-gray-100"
                        >
                            {{ proyecto.nombre }}
                        </CardTitle>
                        <Badge
                            :variant="proyecto.estado ? 'default' : 'secondary'"
                        >
                            {{ proyecto.estado ? 'Activo' : 'Inactivo' }}
                        </Badge>
                    </div>
                    <CardDescription
                        class="mt-1 text-gray-600 dark:text-gray-400"
                    >
                        {{ proyecto.ubicacion || 'Sin ubicación' }}
                    </CardDescription>
                </div>

                <Button
                    @click="router.visit(`/mapa-editor/${props.proyecto.id}`)"
                    class="flex w-full items-center justify-center gap-2 sm:w-auto"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    Dibujar mapa
                </Button>
                <Button
                    @click="router.visit(`/editar-mapa-seleccionado/${props.proyecto.id}`)"
                    class="flex w-full items-center justify-center gap-2 border border-green-400 bg-white dark:bg-gray-800 hover:bg-gray-100 text-color-white dark:border-white dark:hover:bg-gray-600 sm:w-auto"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        />
                    </svg>

                    Editar mapa
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <EstadisticaItem
                    titulo="Barrios"
                    :total="proyecto.estadisticas.barrios.total"
                    :con-poligono="proyecto.estadisticas.barrios.con_poligono"
                />

                <EstadisticaItem
                    titulo="Cuadras"
                    :total="proyecto.estadisticas.cuadras.total"
                    :con-poligono="proyecto.estadisticas.cuadras.con_poligono"
                />

                <EstadisticaItem
                    titulo="Terrenos"
                    :total="proyecto.estadisticas.terrenos.total"
                    :con-poligono="proyecto.estadisticas.terrenos.con_poligono"
                />
            </div>
        </CardContent>
    </Card>
</template>
