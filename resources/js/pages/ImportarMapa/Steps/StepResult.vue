<script setup lang="ts">
import { computed } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Divider from 'primevue/divider';

interface Result {
  summary?: {
    barrios: { creados: number; actualizados: number; omitidos: number };
    cuadras: { creadas: number; actualizadas: number; omitidas: number };
    terrenos: { creados: number; actualizados: number; omitidos: number };
  };
  message?: string;
}

const props = defineProps<{
  result: Result | null;
}>();

const emit = defineEmits<{
  reset: [];
}>();

const summary = computed(() => props.result?.summary || null);

const totalCreated = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.creados || 0) +
    (summary.value.cuadras?.creadas || 0) +
    (summary.value.terrenos?.creados || 0)
  );
});

const totalUpdated = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.actualizados || 0) +
    (summary.value.cuadras?.actualizadas || 0) +
    (summary.value.terrenos?.actualizados || 0)
  );
});

const totalSkipped = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.omitidos || 0) +
    (summary.value.cuadras?.omitidas || 0) +
    (summary.value.terrenos?.omitidos || 0)
  );
});

const totalProcessed = computed(() => {
  return totalCreated.value + totalUpdated.value + totalSkipped.value;
});
</script>

<template>
  <div class="space-y-8 p-6">
    <!-- Success Header with Animation -->
    <div class="text-center mb-10 relative">
      <!-- Animated Background Circle -->
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="w-32 h-32 bg-green-100 dark:bg-green-900/30 rounded-full animate-ping opacity-20"></div>
      </div>
      
      <!-- Icon Container with Gradient Background -->
      <div class="relative inline-block">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-2xl animate-bounce">
          <i class="pi pi-check text-5xl text-white"></i>
        </div>
      </div>
      
      <h3 class="text-4xl font-extrabold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-3">
        ¡Importación Completada!
      </h3>
      <p class="text-lg text-gray-600 dark:text-gray-400 font-medium">
        Los datos se han importado exitosamente
      </p>
      
      <!-- Decorative Line -->
      <div class="flex items-center justify-center gap-2 mt-4">
        <div class="h-1 w-12 bg-gradient-to-r from-transparent to-green-500 rounded"></div>
        <div class="h-1 w-12 bg-green-500 rounded"></div>
        <div class="h-1 w-12 bg-gradient-to-l from-transparent to-green-500 rounded"></div>
      </div>
    </div>

    <!-- Success Message with Modern Design -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 shadow-lg">
      <div class="absolute top-0 right-0 w-40 h-40 bg-green-300 dark:bg-green-700 rounded-full -mr-20 -mt-20 opacity-20"></div>
      <div class="relative p-6 flex items-start gap-4">
        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-green-500 flex items-center justify-center shadow-lg">
          <i class="pi pi-check-circle text-white text-2xl"></i>
        </div>
        <div class="flex-1">
          <p class="font-bold text-xl text-green-800 dark:text-green-200">Proceso exitoso</p>
          <p class="text-green-700 dark:text-green-300 mt-1">{{ result?.message }}</p>
        </div>
      </div>
    </div>

    <!-- Summary Cards with Modern Design -->
    <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Created Card -->
      <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-400 to-emerald-500"></div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm uppercase tracking-wide">Creados</span>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <i class="pi pi-plus text-white text-xl"></i>
            </div>
          </div>
          <div class="text-5xl font-black bg-gradient-to-br from-green-600 to-emerald-600 bg-clip-text text-transparent">
            {{ totalCreated }}
          </div>
          <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Registros nuevos</div>
        </div>
      </div>

      <!-- Updated Card -->
      <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-500"></div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm uppercase tracking-wide">Actualizados</span>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <i class="pi pi-refresh text-white text-xl"></i>
            </div>
          </div>
          <div class="text-5xl font-black bg-gradient-to-br from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            {{ totalUpdated }}
          </div>
          <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Registros modificados</div>
        </div>
      </div>

      <!-- Skipped Card -->
      <div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-gray-400 to-gray-500"></div>
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <span class="text-gray-600 dark:text-gray-400 font-semibold text-sm uppercase tracking-wide">Omitidos</span>
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
              <i class="pi pi-minus text-white text-xl"></i>
            </div>
          </div>
          <div class="text-5xl font-black bg-gradient-to-br from-gray-600 to-gray-700 bg-clip-text text-transparent">
            {{ totalSkipped }}
          </div>
          <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Sin cambios</div>
        </div>
      </div>
    </div>

    <!-- Detailed Results Section -->
    <div v-if="summary" class="shadow-2xl rounded-2xl overflow-hidden border-0 bg-white dark:bg-gray-800">
      <!-- Header -->
      <div class="px-8 py-6 bg-gradient-to-r from-indigo-600 to-purple-600">
        <h4 class="font-bold text-2xl text-white flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
            <i class="pi pi-th text-xl"></i>
          </div>
          Detalles por Tipo
        </h4>
        <p class="text-indigo-100 text-sm mt-2">Resumen detallado de la importación</p>
      </div>

      <!-- Content -->
      <div class="space-y-8 p-6">
        <!-- Barrios Section -->
        <div class="relative">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg">
              <i class="pi pi-map text-white text-xl"></i>
            </div>
            <div>
              <h5 class="font-bold text-xl text-gray-800 dark:text-gray-200">Barrios</h5>
              <p class="text-xs text-gray-500 dark:text-gray-400">Zonas geográficas</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 ml-2">
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-green-600">
                {{ summary.barrios?.creados || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Creados</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-blue-600">
                {{ summary.barrios?.actualizados || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Actualizados</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-gray-600">
                {{ summary.barrios?.omitidos || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Omitidos</div>
            </div>
          </div>
        </div>

        <!-- Divider Line -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-6"></div>

        <!-- Cuadras Section -->
        <div class="relative">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center shadow-lg">
              <i class="pi pi-th-large text-white text-xl"></i>
            </div>
            <div>
              <h5 class="font-bold text-xl text-gray-800 dark:text-gray-200">Cuadras</h5>
              <p class="text-xs text-gray-500 dark:text-gray-400">Divisiones de terreno</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 ml-2">
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-green-600">
                {{ summary.cuadras?.creadas || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Creadas</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-blue-600">
                {{ summary.cuadras?.actualizadas || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Actualizadas</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-gray-600">
                {{ summary.cuadras?.omitidas || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Omitidas</div>
            </div>
          </div>
        </div>

        <!-- Divider Line -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-6"></div>

        <!-- Terrenos Section -->
        <div class="relative">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center shadow-lg">
              <i class="pi pi-home text-white text-xl"></i>
            </div>
            <div>
              <h5 class="font-bold text-xl text-gray-800 dark:text-gray-200">Terrenos</h5>
              <p class="text-xs text-gray-500 dark:text-gray-400">Propiedades individuales</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 ml-2">
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-green-600">
                {{ summary.terrenos?.creados || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Creados</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-blue-600">
                {{ summary.terrenos?.actualizados || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Actualizados</div>
            </div>
            <div class="group relative overflow-hidden p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all">
              <div class="text-3xl font-black text-gray-600">
                {{ summary.terrenos?.omitidos || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold">Omitidos</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Total Summary Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl">
      <div class="absolute inset-0 bg-black/10"></div>
      <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-white/10 rounded-full -ml-32 -mb-32"></div>
      
      <div class="relative p-8">
        <div class="text-center mb-6">
          <h4 class="text-2xl font-bold text-white mb-2">Resumen Total</h4>
          <p class="text-indigo-100">Estadísticas completas del proceso</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
            <div class="text-5xl font-black text-white mb-2">{{ totalProcessed }}</div>
            <div class="text-sm text-indigo-100 font-semibold">Procesados</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
            <div class="text-5xl font-black text-white mb-2">{{ totalCreated }}</div>
            <div class="text-sm text-green-200 font-semibold">Creados</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
            <div class="text-5xl font-black text-white mb-2">{{ totalUpdated }}</div>
            <div class="text-sm text-blue-200 font-semibold">Actualizados</div>
          </div>
          <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur-sm">
            <div class="text-5xl font-black text-white mb-2">{{ totalSkipped }}</div>
            <div class="text-sm text-gray-200 font-semibold">Omitidos</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Button -->
    <div class="text-center mt-8">
      <button
        @click="emit('reset')"
        class="px-8 py-4 rounded-xl font-bold shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-lg inline-flex items-center gap-2 border-0 cursor-pointer"
      >
        <i class="pi pi-refresh"></i>
        Realizar Nueva Importación
      </button>
    </div>
  </div>
</template>