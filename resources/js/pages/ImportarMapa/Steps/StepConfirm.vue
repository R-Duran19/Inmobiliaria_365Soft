<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import Divider from 'primevue/divider';

interface WizardData {
  geojson: any;
  analysis: any;
  mapping: any;
  conflicts: any;
  conflictResolution: 'overwrite_all' | 'skip_all' | 'ask';
  conflictDecisions: Record<number, 'overwrite' | 'skip'>;
}

const props = defineProps<{
  wizardData: WizardData;
}>();

const emit = defineEmits<{
  'execute': [result: any];
}>();

const executing = ref(false);
const error = ref<string | null>(null);

const typeLabel = (type: string) => {
  const labels: Record<string, string> = {
    complete: 'Proyecto Completo',
    barrios_only: 'Solo Barrios',
    cuadras_terrenos: 'Cuadras con Terrenos',
    cuadras_only: 'Solo Cuadras',
    terrenos_only: 'Solo Terrenos',
  };
  return labels[type] || type;
};

const resolutionLabel = (resolution: string) => {
  const labels: Record<string, string> = {
    overwrite_all: 'Reemplazar todos los conflictos',
    skip_all: 'Omitir todos los conflictos',
    ask: 'Decidir caso por caso',
  };
  return labels[resolution] || resolution;
};

const getConflictCount = computed(() => {
  if (!props.wizardData.conflicts) return 0;
  const c = props.wizardData.conflicts;
  return (c.barrios?.length || 0) + (c.cuadras?.length || 0) + (c.terrenos?.length || 0);
});

const executeImport = async () => {
  executing.value = true;
  error.value = null;

  try {
    const response = await axios.post('/api/import/execute', {
      geojson: props.wizardData.geojson,
      mapping: props.wizardData.mapping,
      conflictResolution: props.wizardData.conflictResolution,
      conflictDecisions: props.wizardData.conflictDecisions,
    });

    if (response.data.success) {
      emit('execute', response.data);
    } else {
      error.value = response.data.message || 'Error durante la importación';
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Error al ejecutar la importación';
    console.error(err);
  } finally {
    executing.value = false;
  }
};
</script>

<template>
  <div class="space-y-8 p-6 max-w-5xl mx-auto">
    
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-green-500 to-green-600 shadow-lg shadow-green-500/30 mb-5">
        <i class="pi pi-check-circle text-white text-4xl"></i>
      </div>
      <h3 class="text-3xl font-bold text-green-600 dark:text-gray-100 mb-2 tracking-tight">
        Revisar Importación
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Revisa los detalles antes de ejecutar la importación
      </p>
    </div>

    
    <div v-if="error" class="mb-6 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl relative">
      <div class="flex justify-between items-start">
        <p>{{ error }}</p>
        <button @click="error = null" class="text-red-700 hover:text-red-900 font-bold">×</button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <div class="rounded-2xl border border-blue-200 dark:border-blue-800 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="px-6 py-3 bg-blue-100 dark:bg-blue-900/30 rounded-t-2xl">
          <h4 class="font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2 text-lg">
            <i class="pi pi-info-circle"></i> Tipo de Importación
          </h4>
        </div>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-b-2xl flex items-center justify-between">
          <span class="text-gray-700 dark:text-gray-300">Tipo detectado:</span>
          <span class="font-semibold text-blue-600 dark:text-blue-400">
            {{ typeLabel(wizardData.analysis?.import_type) }}
          </span>
        </div>
      </div>

      
      <div class="rounded-2xl border border-green-200 dark:border-green-800 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="px-6 py-3 bg-green-100 dark:bg-green-900/30 rounded-t-2xl">
          <h4 class="font-semibold text-green-800 dark:text-green-200 flex items-center gap-2 text-lg">
            <i class="pi pi-th"></i> Elementos a Importar
          </h4>
        </div>
        <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <div class="text-3xl font-extrabold text-blue-600">{{ wizardData.analysis?.barrios?.count || 0 }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 uppercase tracking-wide">Barrios</p>
          </div>
          <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
            <div class="text-3xl font-extrabold text-orange-600">{{ wizardData.analysis?.cuadras?.count || 0 }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 uppercase tracking-wide">Cuadras</p>
          </div>
          <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
            <div class="text-3xl font-extrabold text-purple-600">{{ wizardData.analysis?.terrenos?.count || 0 }}</div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 uppercase tracking-wide">Terrenos</p>
          </div>
          <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <div class="text-3xl font-extrabold text-gray-700 dark:text-gray-200">
              {{
                (wizardData.analysis?.barrios?.count || 0) +
                (wizardData.analysis?.cuadras?.count || 0) +
                (wizardData.analysis?.terrenos?.count || 0)
              }}
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 uppercase tracking-wide">Total</p>
          </div>
        </div>
      </div>

      
      <div class="rounded-2xl border border-indigo-200 dark:border-indigo-800 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="px-6 py-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-t-2xl">
          <h4 class="font-semibold text-indigo-800 dark:text-indigo-200 flex items-center gap-2 text-lg">
            <i class="pi pi-sitemap"></i> Asignación de Entidades
          </h4>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-800">
            <span class="text-gray-700 dark:text-gray-300">Proyecto:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">ID {{ wizardData.mapping?.idproyecto }}</span>
          </div>
          <div v-if="wizardData.mapping?.idbarrio" class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-800">
            <span class="text-gray-700 dark:text-gray-300">Barrio:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">ID {{ wizardData.mapping?.idbarrio }}</span>
          </div>
          <div v-if="wizardData.mapping?.idcuadra" class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-b-xl">
            <span class="text-gray-700 dark:text-gray-300">Cuadra:</span>
            <span class="font-medium text-gray-900 dark:text-gray-100">ID {{ wizardData.mapping?.idcuadra }}</span>
          </div>
        </div>
      </div>

      
      <div class="rounded-2xl border border-yellow-200 dark:border-yellow-800 shadow-sm hover:shadow-md transition-all duration-300">
        <div class="px-6 py-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-t-2xl">
          <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center gap-2 text-lg">
            <i class="pi pi-exclamation-triangle"></i> Gestión de Conflictos
          </h4>
        </div>
        <div class="p-4 space-y-4">
          <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
            <span class="text-gray-700 dark:text-gray-300">Conflictos detectados:</span>
            <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ getConflictCount }}</span>
          </div>
          <hr class="border-gray-200 dark:border-gray-700" />
          <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
            <span class="text-gray-700 dark:text-gray-300">Estrategia:</span>
            <span class="font-semibold text-blue-600 dark:text-blue-400">
              {{ resolutionLabel(wizardData.conflictResolution) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    
    <div class="mt-8">
      <div
        v-if="wizardData.conflictResolution === 'ask' && getConflictCount > 0"
        class="flex items-start gap-3 p-4 bg-yellow-100 border border-yellow-300 rounded-xl dark:bg-gray-800 
        dark:border-yellow-600"
      >
        <i class="pi pi-exclamation-circle text-yellow-600 mt-1"></i>
        <div>
          <p class="font-semibold text-yellow-700 dark:text-yellow-500">Decisiones pendientes</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Debes marcar qué hacer con cada uno de los {{ getConflictCount }} conflictos
          </p>
        </div>
      </div>

      <div
        v-else
        class="flex items-start gap-3 p-4 bg-green-100 border border-green-300 rounded-xl"
      >
        <i class="pi pi-check-circle text-green-600 mt-1"></i>
        <div>
          <p class="font-semibold text-gray-800 dark:text-gray-100">Listo para importar</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Todos los parámetros están configurados correctamente
          </p>
        </div>
      </div>
    </div>

    
    <div v-if="executing" class="flex flex-col items-center justify-center py-10">
      <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="mt-4 text-gray-600 dark:text-gray-400">Ejecutando importación...</p>
    </div>

    
    <div v-if="!executing" class="flex justify-center pt-6">
      <button
        @click="executeImport"
        class="flex items-center gap-2 bg-green-600 text-white px-8 py-3 rounded-xl shadow-md hover:bg-green-700 hover:shadow-lg transition-all text-lg font-semibold"
      >
        <i class="pi pi-check"></i> Ejecutar Importación
      </button>
    </div>
  </div>
</template>
