<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import ProyectoCard from './ImportarMapa/ProyectoCard.vue';

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
  proyectos: Proyecto[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Importar Mapa', href: '/importarmapa' },
];

const irAImportar = (proyectoId: number) => {
  router.visit(`/importarmapa/${proyectoId}`);
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Importar Mapa" />
    
    <div class="py-6">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
          Gestión de Importación GeoJSON
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Administra y visualiza el estado de los polígonos por proyecto
        </p>
      </div>

      <!-- Información general -->
      <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
              Selecciona un proyecto para gestionar su importación
            </p>
            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
              Podrás ver el estado de barrios, cuadras y terrenos, y configurar la importación personalizada
            </p>
          </div>
        </div>
      </div>

      <!-- Lista de proyectos -->
      <div v-if="proyectos.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay proyectos</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crea un proyecto primero para poder importar GeoJSON</p>
      </div>

      <div v-else class="grid gap-4">
        <ProyectoCard
          v-for="proyecto in proyectos"
          :key="proyecto.id"
          :proyecto="proyecto"
          @importar="irAImportar"
        />
      </div>
    </div>
  </AppLayout>
</template>