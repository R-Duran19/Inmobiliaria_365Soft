<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted, computed } from 'vue';
import MapaTerrenos from '../../components/mapas/MapaTerrenos.vue';
import axios from 'axios';

interface Props {
    proyectoId: number;
}

interface Proyecto {
    id: number;
    nombre: string;
    descripcion: string;
    ubicacion: string;
    total_terrenos: number;
    terrenos_disponibles: number;
    terrenos_vendidos: number;
    terrenos_reservados: number;
}

const props = defineProps<Props>();

const proyecto = ref<Proyecto | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [
        {
            title: 'Proyectos',
            href: '/dashboard',
        }
    ];
    
    if (proyecto.value) {
        items.push({
            title: proyecto.value.nombre,
            href: '#',
        });
    }
    
    return items;
});

// Cargar información del proyecto
const cargarProyecto = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}`);
        proyecto.value = response.data;
        
        console.log('Proyecto cargado:', proyecto.value);
    } catch (err: any) {
        console.error('Error al cargar proyecto:', err);
        error.value = 'Error al cargar la información del proyecto';
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    cargarProyecto();
});
</script>

<template>
    <Head :title="proyecto?.nombre || 'Mapa de Proyecto'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center h-screen">
            <div class="text-center">
                <i class="pi pi-spin pi-spinner text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-600">Cargando mapa...</p>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="p-6">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="pi pi-exclamation-triangle text-red-500 mr-2"></i>
                    <p class="text-red-700">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Mapa - Solo renderiza cuando proyecto está cargado -->
        <div v-else-if="proyecto" class="h-[calc(100vh-120px)]">
            <MapaTerrenos :proyecto-id="proyectoId" :proyecto="proyecto" />
        </div>
    </AppLayout>
</template>