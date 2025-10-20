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
    
    <div class="py-6 px-4 sm:px-6 lg:px-8">
      
      <div class="mb-10 relative">
        
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 dark:from-indigo-900/10 dark:via-purple-900/10 dark:to-pink-900/10 rounded-2xl -z-10 transform -rotate-1"></div>
        
        <div class="relative p-6 sm:p-8 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-xl overflow-hidden">
          
          <div class="absolute top-0 right-0 w-32 sm:w-64 h-32 sm:h-64 bg-white/10 rounded-full -mr-16 sm:-mr-32 -mt-16 sm:-mt-32"></div>
          <div class="absolute bottom-0 left-0 w-24 sm:w-48 h-24 sm:h-48 bg-white/5 rounded-full -ml-12 sm:-ml-24 -mb-12 sm:-mb-24"></div>
          
          <div class="relative z-10">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 mb-3">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg flex-shrink-0">
                <i class="pi pi-map text-2xl sm:text-3xl text-white"></i>
              </div>
              <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white">
                  Gestión de Importación GeoJSON
                </h1>
                <p class="text-purple-100 text-sm sm:text-base lg:text-lg mt-1">
                  Administra y visualiza el estado de los polígonos por proyecto
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      
      <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 dark:from-indigo-900/20 dark:via-purple-900/20 dark:to-pink-900/20 border-2 border-indigo-200 dark:border-indigo-800 shadow-lg">
        
        <div class="absolute top-0 right-0 w-24 sm:w-40 h-24 sm:h-40 bg-purple-200 dark:bg-purple-700 rounded-full opacity-20 -mr-12 sm:-mr-20 -mt-12 sm:-mt-20"></div>
        
        <div class="relative p-4 sm:p-6 flex items-start gap-3 sm:gap-4">
          
          <div class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          
          
          <div class="flex-1 min-w-0">
            <p class="text-sm sm:text-base font-bold text-indigo-900 dark:text-indigo-200">
              Selecciona un proyecto para gestionar su importación
            </p>
            <p class="text-xs sm:text-sm text-indigo-700 dark:text-indigo-300 mt-2 leading-relaxed">
              Podrás ver el estado de barrios, cuadras y terrenos, y configurar la importación personalizada
            </p>
          </div>
        </div>
      </div>

      
      <div v-if="proyectos.length === 0" class="text-center py-16 sm:py-20">
        
        <div class="relative inline-block mb-6">
          <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center shadow-xl">
            <svg class="h-12 w-12 sm:h-16 sm:w-16 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
          </div>
          <div class="absolute -bottom-2 -right-2 w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
            <i class="pi pi-plus text-white text-lg sm:text-xl"></i>
          </div>
        </div>
        
        <h3 class="text-xl sm:text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">
          No hay proyectos disponibles
        </h3>
        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 max-w-md mx-auto px-4">
          Crea un proyecto primero para poder importar archivos GeoJSON y gestionar tus mapas
        </p>
      </div>

      
      <div v-else class="grid grid-cols-1 sm:grid-cols-2  gap-4 sm:gap-6 lg:gap-8">
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

<style scoped>
/* Asegurar que el contenido sea responsivo */
@media (max-width: 640px) {
  .pi {
    font-size: 1.25rem;
  }
}

/* Animación sutil para los elementos */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.grid > * {
  animation: fadeIn 0.3s ease-out;
}
</style>