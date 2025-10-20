<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import Dropdown from 'primevue/dropdown';
import Message from 'primevue/message';
import Skeleton from 'primevue/skeleton';

const props = defineProps<{
  analysis: any;
  mapping: any;
}>();

const emit = defineEmits<{
  'update:mapping': [mapping: any];
  'next': [];
}>();

const proyectos = ref<any[]>([]);
const barrios = ref<any[]>([]);
const cuadras = ref<any[]>([]);

const selectedProyecto = ref<number | null>(null);
const selectedBarrio = ref<number | null>(null);
const selectedCuadra = ref<number | null>(null);

const loadingProyectos = ref(false);
const loadingBarrios = ref(false);
const loadingCuadras = ref(false);

// Determinar qué selectores mostrar según el tipo de importación
const needsProyecto = computed(() => true); // Siempre necesita proyecto
const proyectoDelArchivo = computed(() => {
  return props.analysis?.proyecto?.nombre || null;
});

// Nuevo: si hay proyecto en el archivo, preseleccionarlo
watch(proyectoDelArchivo, async (nombreProyecto) => {
  if (nombreProyecto && !selectedProyecto.value) {
    // Buscar ese proyecto en la lista
    const proyecto = proyectos.value.find(p => p.nombre === nombreProyecto);
    if (proyecto) {
      selectedProyecto.value = proyecto.id;
    }
  }
});
const needsBarrio = computed(() => {
  return ['cuadras_only', 'cuadras_terrenos', 'terrenos_only'].includes(props.analysis?.import_type);
});
const needsCuadra = computed(() => {
  return props.analysis?.import_type === 'terrenos_only';
});

const isValid = computed(() => {
  if (!selectedProyecto.value) return false;
  if (needsBarrio.value && !selectedBarrio.value) return false;
  if (needsCuadra.value && !selectedCuadra.value) return false;
  return true;
});

// Cargar proyectos al montar
const loadProyectos = async () => {
  loadingProyectos.value = true;
  try {
    const response = await axios.post('/api/import/entities', {
      type: 'proyectos',
    });
    if (response.data.success) {
      proyectos.value = response.data.entities;
    }
  } catch (error) {
    console.error('Error cargando proyectos:', error);
  } finally {
    loadingProyectos.value = false;
  }
};

const cuadraMap = ref<Record<string, number | null>>({}); // ex: { "MZ 007": 123, "MZ 008": null }
const detectedCuadras = computed(() => {
  return props.analysis?.cuadras?.items ?? []; // cada item debe contener 'nombre' o 'cuadra_ref'
});

// Cargar barrios cuando se selecciona un proyecto
watch(selectedProyecto, async (newVal) => {
  if (newVal && needsBarrio.value) {
    loadingBarrios.value = true;
    selectedBarrio.value = null;
    selectedCuadra.value = null;
    cuadras.value = [];
    
    try {
      const response = await axios.post('/api/import/entities', {
        type: 'barrios',
        idproyecto: newVal,
      });
      if (response.data.success) {
        barrios.value = response.data.entities;
      }
    } catch (error) {
      console.error('Error cargando barrios:', error);
    } finally {
      loadingBarrios.value = false;
    }
  }
  updateMapping();
});

// Cargar cuadras cuando se selecciona un barrio
watch(selectedBarrio, async (newVal) => {
  if (newVal && needsCuadra.value) {
    loadingCuadras.value = true;
    selectedCuadra.value = null;
    
    try {
      const response = await axios.post('/api/import/entities', {
        type: 'cuadras',
        idbarrio: newVal,
      });
      if (response.data.success) {
        cuadras.value = response.data.entities;
      }
    } catch (error) {
      console.error('Error cargando cuadras:', error);
    } finally {
      loadingCuadras.value = false;
    }
  }
  updateMapping();
});

watch(selectedCuadra, updateMapping);
watch(cuadraMap, updateMapping, { deep: true });

function openCreateCuadraDialog(cuadraItem: any) {
  // abre un modal con un formulario para crear la cuadra (nombre, polígono opcional)
  // por simplicidad aquí solo seteamos cuadraMap a null para indicar crear nueva
  const key = cuadraItem.cuadra_ref ?? cuadraItem.nombre;
  cuadraMap.value[key] = null;
}

// Llamar updateMapping para incluir cuadra_map
function updateMapping() {
  const mapping: any = {
    idproyecto: selectedProyecto.value,
    proyecto_nombre: proyectoDelArchivo.value,
  };

  if (needsBarrio.value && selectedBarrio.value) mapping.idbarrio = selectedBarrio.value;

  if (needsCuadra.value) {
    mapping.cuadra_map = { ...cuadraMap.value }; // copia
  } else if (selectedCuadra.value) {
    mapping.idcuadra = selectedCuadra.value; // fallback legacy
  }

  emit('update:mapping', mapping);
}



loadProyectos();
</script>

<template>
  <div class="space-y-8 p-6 max-w-5xl mx-auto">
  
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/30 mb-5">
        <i class="pi pi-sitemap text-4xl text-white"></i>
      </div>
      <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-3">
        Asignación de Entidades
      </h3>
      <p class="text-lg text-gray-600 dark:text-gray-400">
        Selecciona a qué proyecto y entidades deseas importar los datos
      </p>
    </div>

  
    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 rounded-xl p-5 border-l-4 border-blue-500 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
          <i class="pi pi-info-circle text-white text-lg"></i>
        </div>
        <div>
          <p class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">Tipo de importación</p>
          <p class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ analysis.import_type }}</p>
        </div>
      </div>
    </div>

  
    <div class="space-y-6">
    
      <div 
        v-if="proyectoDelArchivo" 
        class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-5 border border-emerald-300 dark:border-emerald-700 shadow-sm"
      >
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-check-circle text-white text-lg"></i>
          </div>
          <div>
            <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100 mb-1">Proyecto detectado automáticamente</p>
            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ proyectoDelArchivo }}</p>
          </div>
        </div>
      </div>


      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200">
        <label class="block mb-4 text-base font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
            <i class="pi pi-building text-blue-600 dark:text-blue-400"></i>
          </div>
          <span>Proyecto</span>
          <span class="text-red-500">*</span>
          <Tag v-if="proyectoDelArchivo" value="AUTO" severity="success" class="text-xs font-semibold" />
        </label>

        <Skeleton v-if="loadingProyectos" height="3.5rem" borderRadius="0.75rem" />

        <select
          v-else
          v-model="selectedProyecto"
          class="w-full h-12 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-3"
        >
          <option disabled value="">Selecciona un proyecto</option>
          <option
            v-for="p in proyectos"
            :key="p.id"
            :value="p.id"
          >
            {{ p.nombre }}
          </option>
        </select>

        <div class="mt-3 flex items-start gap-2">
          <i class="pi pi-info-circle text-gray-400 text-sm mt-0.5"></i>
          <small class="text-gray-600 dark:text-gray-400">
            {{ proyectoDelArchivo ? 'Se usará el proyecto detectado en el archivo' : 'Los datos se importarán a este proyecto' }}
          </small>
        </div>
      </div>


      <div 
        v-if="needsBarrio"
        class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-200"
      >
        <label class="block mb-4 text-base font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
            <i class="pi pi-map text-purple-600 dark:text-purple-400"></i>
          </div>
          <span>Barrio</span>
          <span class="text-red-500">*</span>
        </label>

        <Skeleton v-if="loadingBarrios" height="3.5rem" borderRadius="0.75rem" />

        <select
          v-else
          v-model="selectedBarrio"
          class="w-full h-12 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-3"
          :disabled="!selectedProyecto || barrios.length === 0"
        >
          <option disabled value="">Selecciona un barrio</option>
          <option
            v-for="b in barrios"
            :key="b.id"
            :value="b.id"
          >
            {{ b.nombre }}
          </option>
        </select>

        <div class="mt-3 flex items-start gap-2">
          <i class="pi pi-info-circle text-gray-400 text-sm mt-0.5"></i>
          <small class="text-gray-600 dark:text-gray-400">
            <span v-if="!selectedProyecto">Primero selecciona un proyecto</span>
            <span v-else-if="barrios.length === 0">No hay barrios disponibles en este proyecto</span>
            <span v-else>Las cuadras/terrenos se importarán a este barrio</span>
          </small>
        </div>
      </div>

    
      <div 
        v-if="needsCuadra" 
        class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-md border border-gray-200 dark:border-gray-700"
      >
        <label class="block mb-4 text-base font-bold text-gray-800 dark:text-gray-200 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
            <i class="pi pi-th-large text-green-600 dark:text-green-400"></i>
          </div>
          <span>Mapeo de Cuadras</span>
        </label>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-5 border border-blue-200 dark:border-blue-800">
          <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center gap-2">
            <i class="pi pi-lightbulb"></i>
            El archivo contiene las siguientes referencias de cuadra. Asigna la cuadra existente o crea una nueva.
          </p>
        </div>

        <div class="space-y-4">
          <div 
            v-for="(c, idx) in detectedCuadras" 
            :key="idx" 
            class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-5 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
          >
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
            
              <div class="lg:col-span-3">
                <div class="flex items-center gap-2 mb-1">
                  <i class="pi pi-box text-gray-500 text-sm"></i>
                  <span class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {{ c.cuadra_ref ?? c.nombre }}
                  </span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                  <i class="pi pi-database text-xs"></i>
                  <span>Features: {{ c.index }}</span>
                </div>
              </div>

            
              <div class="lg:col-span-5">
                <select
                  v-model="cuadraMap[c.cuadra_ref ?? c.nombre]"
                  class="w-full h-12 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-3"
                >
                  <option disabled value="">Selecciona cuadra</option>
                  <option
                    v-for="cu in cuadras"
                    :key="cu.id"
                    :value="cu.id"
                  >
                    {{ cu.nombre }}
                  </option>
                </select>
                <small class="text-gray-500 dark:text-gray-400 mt-1 block flex items-center gap-1">
                  <i class="pi pi-arrow-right text-xs"></i>
                  O deja vacío para crear nueva cuadra
                </small>
              </div>

            
              <div class="lg:col-span-4 flex gap-2 justify-end">
                <button 
                  type="button" 
                  class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2" 
                  @click="openCreateCuadraDialog(c)"
                >
                  <i class="pi pi-plus text-sm"></i>
                  {{ cuadraMap[c.cuadra_ref ?? c.nombre] ? 'Reemplazar' : 'Crear nueva' }}
                </button>
                <button 
                  v-if="cuadraMap[c.cuadra_ref ?? c.nombre]" 
                  type="button" 
                  class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors flex items-center gap-2" 
                  @click="cuadraMap[c.cuadra_ref ?? c.nombre] = null"
                >
                  <i class="pi pi-times text-sm"></i>
                  Desasignar
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
          <p class="text-sm text-amber-800 dark:text-amber-200 flex items-center gap-2">
            <i class="pi pi-exclamation-triangle"></i>
            Si creas nuevas cuadras, se generará una cuadra nueva para el barrio seleccionado al importar.
          </p>
        </div>
      </div>
    </div>

  
    <div class="mt-8">
      <div 
        v-if="!isValid" 
        class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-lg p-5 shadow-sm"
      >
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center">
            <i class="pi pi-exclamation-circle text-white text-lg"></i>
          </div>
          <div>
            <p class="font-semibold text-amber-900 dark:text-amber-100 mb-1">Campos incompletos</p>
            <p class="text-sm text-amber-800 dark:text-amber-200">Por favor completa todos los campos requeridos antes de continuar</p>
          </div>
        </div>
      </div>

      <div 
        v-else 
        class="bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500 rounded-lg p-5 shadow-sm"
      >
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-check-circle text-white text-lg"></i>
          </div>
          <div>
            <p class="font-semibold text-emerald-900 dark:text-emerald-100 mb-1">Configuración válida</p>
            <p class="text-sm text-emerald-800 dark:text-emerald-200">Puedes continuar al siguiente paso</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
