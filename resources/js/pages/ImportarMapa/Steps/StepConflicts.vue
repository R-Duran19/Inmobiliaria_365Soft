<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import RadioButton from 'primevue/radiobutton';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

interface Conflict {
  index: number;
  nombre?: string;
  numero?: string;
  id: number;
  tiene_poligono: boolean;
}

interface Conflicts {
  barrios: Conflict[];
  cuadras: Conflict[];
  terrenos: Conflict[];
}

const props = defineProps<{
  geojson: any;
  mapping: any;
  conflicts: Conflicts | null;
  resolution: 'overwrite_all' | 'skip_all' | 'ask';
  decisions: Record<number, 'overwrite' | 'skip'>;
}>();

const emit = defineEmits<{
  'update:conflicts': [conflicts: Conflicts];
  'update:resolution': [resolution: 'overwrite_all' | 'skip_all' | 'ask'];
  'update:decisions': [decisions: Record<number, 'overwrite' | 'skip'>];
  'next': [];
}>();

const loading = ref(false);
const error = ref<string | null>(null);
const localResolution = ref<'overwrite_all' | 'skip_all' | 'ask'>(props.resolution);
const localDecisions = ref<Record<number, 'overwrite' | 'skip'>>({ ...props.decisions });

// Cargar conflictos
const loadConflicts = async () => {
  loading.value = true;
  try {
    const response = await axios.post('/api/import/conflicts', {
      geojson: props.geojson,
      mapping: props.mapping,
    });
    if (response.data.success) {
      emit('update:conflicts', response.data.conflicts);
    }
  } catch (err: any) {
    error.value = 'Error al detectar conflictos';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const allConflicts = computed(() => {
  if (!props.conflicts) return [];
  return [
    ...props.conflicts.barrios.map(c => ({ ...c, type: 'barrio', label: c.nombre })),
    ...props.conflicts.cuadras.map(c => ({ ...c, type: 'cuadra', label: c.nombre })),
    ...props.conflicts.terrenos.map(c => ({ ...c, type: 'terreno', label: `LT ${c.numero}` })),
  ];
});

const totalConflicts = computed(() => allConflicts.value.length);
const hasConflicts = computed(() => totalConflicts.value > 0);

const typeLabel = (type: string) => {
  const labels: Record<string, string> = {
    barrio: 'Barrio',
    cuadra: 'Cuadra',
    terreno: 'Terreno',
  };
  return labels[type] || type;
};

const typeIcon = (type: string) => {
  const icons: Record<string, string> = {
    barrio: 'pi-map',
    cuadra: 'pi-square',
    terreno: 'pi-home',
  };
  return `pi ${icons[type] || 'pi-circle'}`;
};

const getDecision = (index: number) => {
  return localDecisions.value[index] || null;
};

const setDecision = (index: number, decision: 'overwrite' | 'skip') => {
  localDecisions.value[index] = decision;
  emit('update:decisions', localDecisions.value);
};

const resolveAll = (action: 'overwrite' | 'skip') => {
  allConflicts.value.forEach(conflict => {
    localDecisions.value[conflict.index] = action;
  });
  emit('update:decisions', localDecisions.value);
};

onMounted(() => {
  loadConflicts();
});
</script>

<template>
  <div class="space-y-6">
    
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mb-4 shadow-lg">
        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h3 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 dark:from-gray-100 dark:to-gray-300 bg-clip-text text-transparent mb-2">
        Gestión de Conflictos
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Se encontraron elementos que ya existen en la base de datos
      </p>
    </div>

    
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <div class="relative w-16 h-16">
        <div class="absolute inset-0 border-4 border-orange-200 dark:border-orange-900 rounded-full"></div>
        <div class="absolute inset-0 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <p class="mt-4 text-gray-600 dark:text-gray-400 text-sm">Analizando conflictos...</p>
    </div>

    <div v-else class="space-y-6">
      
      <div 
        v-if="!hasConflicts"
        class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg p-6 shadow-md"
      >
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="font-semibold text-green-800 dark:text-green-200 text-lg">Sin conflictos detectados</p>
            <p class="text-sm text-green-700 dark:text-green-300 mt-1">Todos los elementos a importar son nuevos</p>
          </div>
        </div>
      </div>

      
      <div v-else class="space-y-6">
        
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-b border-yellow-200 dark:border-yellow-800">
            <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Estrategia de Resolución
            </h4>
          </div>
          <div class="p-6 space-y-3">
            
            <div 
              class="group relative flex items-center gap-4 p-4 border-2 rounded-xl transition-all cursor-pointer hover:border-orange-400 hover:shadow-lg"
              :class="localResolution === 'overwrite_all' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
              @click="localResolution = 'overwrite_all'; emit('update:resolution', 'overwrite_all')"
            >
              <div class="flex-shrink-0">
                <div class="relative w-6 h-6">
                  <div class="absolute inset-0 rounded-full border-2 transition-colors"
                       :class="localResolution === 'overwrite_all' ? 'border-orange-500' : 'border-gray-300 dark:border-gray-600'">
                  </div>
                  <div v-if="localResolution === 'overwrite_all'" 
                       class="absolute inset-1 rounded-full bg-orange-500"></div>
                </div>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-gray-800 dark:text-gray-200">Reemplazar Todos</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Sobrescribir todos los elementos que ya existen
                </p>
              </div>
              <div v-if="localResolution === 'overwrite_all'" class="flex-shrink-0">
                <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>

            
            <div 
              class="group relative flex items-center gap-4 p-4 border-2 rounded-xl transition-all cursor-pointer hover:border-blue-400 hover:shadow-lg"
              :class="localResolution === 'skip_all' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
              @click="localResolution = 'skip_all'; emit('update:resolution', 'skip_all')"
            >
              <div class="flex-shrink-0">
                <div class="relative w-6 h-6">
                  <div class="absolute inset-0 rounded-full border-2 transition-colors"
                       :class="localResolution === 'skip_all' ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'">
                  </div>
                  <div v-if="localResolution === 'skip_all'" 
                       class="absolute inset-1 rounded-full bg-blue-500"></div>
                </div>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-gray-800 dark:text-gray-200">Omitir Todos</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  No importar elementos que ya existen
                </p>
              </div>
              <div v-if="localResolution === 'skip_all'" class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>

            
            <div 
              class="group relative flex items-center gap-4 p-4 border-2 rounded-xl transition-all cursor-pointer hover:border-purple-400 hover:shadow-lg"
              :class="localResolution === 'ask' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 shadow-md' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
              @click="localResolution = 'ask'; emit('update:resolution', 'ask')"
            >
              <div class="flex-shrink-0">
                <div class="relative w-6 h-6">
                  <div class="absolute inset-0 rounded-full border-2 transition-colors"
                       :class="localResolution === 'ask' ? 'border-purple-500' : 'border-gray-300 dark:border-gray-600'">
                  </div>
                  <div v-if="localResolution === 'ask'" 
                       class="absolute inset-1 rounded-full bg-purple-500"></div>
                </div>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-gray-800 dark:text-gray-200">Decidir por Cada Uno</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  Elegir para cada elemento conflictivo
                </p>
              </div>
              <div v-if="localResolution === 'ask'" class="flex-shrink-0">
                <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        
        <div v-if="localResolution === 'ask'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-b border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between flex-wrap gap-4">
              <h4 class="font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Conflictos Detectados
                <span class="ml-2 px-2.5 py-0.5 bg-blue-500 text-white text-xs font-bold rounded-full">
                  {{ totalConflicts }}
                </span>
              </h4>
              <div class="flex gap-2">
                <button
                  class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all flex items-center gap-2"
                  @click="resolveAll('overwrite')"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Reemplazar Todos
                </button>
                <button
                  class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all flex items-center gap-2"
                  @click="resolveAll('skip')"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Omitir Todos
                </button>
              </div>
            </div>
          </div>
          
          
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Tipo</th>
                  <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Elemento</th>
                  <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Polígono</th>
                  <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr 
                  v-for="(data, idx) in allConflicts" 
                  :key="idx"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <td class="px-6 py-4">
                    
                    <span 
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium"
                      :class="{
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': data.type === 'barrio',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': data.type === 'cuadra',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': data.type !== 'barrio' && data.type !== 'cuadra'
                      }"
                    >
                      <i :class="typeIcon(data.type)"></i>
                      {{ typeLabel(data.type) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ data.label }}</span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="data.tiene_poligono ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                    >
                      {{ data.tiene_poligono ? 'Sí' : 'No' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-all flex items-center gap-1.5"
                        :class="getDecision(data.index) === 'overwrite' 
                          ? 'bg-orange-500 text-white shadow-md hover:bg-orange-600' 
                          : 'bg-transparent text-orange-500 border border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20'"
                        @click="setDecision(data.index, 'overwrite')"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reemplazar
                      </button>
                      <button
                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition-all flex items-center gap-1.5"
                        :class="getDecision(data.index) === 'skip' 
                          ? 'bg-orange-500 text-white shadow-md hover:bg-orange-600' 
                          : 'bg-transparent text-orange-500 border border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20'"
                        @click="setDecision(data.index, 'skip')"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Omitir
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        
        <div 
          v-if="localResolution === 'ask' && totalConflicts > 0"
          class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-l-4 border-blue-500 rounded-lg p-4 shadow-md"
        >
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">
              Debes decidir qué hacer con cada conflicto antes de continuar
            </p>
          </div>
        </div>

        <div 
          v-if="localResolution !== 'ask'"
          class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border-l-4 border-emerald-500 rounded-lg p-4 shadow-md"
        >
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="text-sm">
              <p v-if="localResolution === 'overwrite_all'" class="font-semibold text-emerald-800 dark:text-emerald-200">
                ✓ Se reemplazarán los {{ totalConflicts }} elementos existentes
              </p>
              <p v-if="localResolution === 'skip_all'" class="font-semibold text-emerald-800 dark:text-emerald-200">
                ✓ Se omitirán los {{ totalConflicts }} elementos existentes
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>