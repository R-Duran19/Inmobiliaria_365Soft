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


// Cargar proyectos al iniciar
loadProyectos();
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <i class="pi pi-sitemap text-6xl text-blue-500 mb-4"></i>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        Asignación de Entidades
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Selecciona a qué proyecto y entidades deseas importar los datos
      </p>
    </div>

    <!-- Import Type Info -->
    <Message severity="info" :closable="false">
      <div class="flex items-center gap-2">
        <i class="pi pi-info-circle"></i>
        <div>
          <strong>Tipo de importación:</strong> 
          {{ analysis.import_type }}
        </div>
      </div>
    </Message>

    <!-- Mapping Form -->
    <div class="space-y-6 max-w-2xl mx-auto">
      <!-- Mostrar Proyecto detectado en el archivo -->
<div v-if="proyectoDelArchivo" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800 mb-6">
  <p class="text-sm text-blue-800 dark:text-blue-200 flex items-center gap-2">
    <i class="pi pi-check-circle"></i>
    <strong>Proyecto detectado en el archivo:</strong> {{ proyectoDelArchivo }}
  </p>
</div>
      
      <!-- Proyecto Selector -->
      <!-- Proyecto Selector -->
<div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
  <label class="block mb-2 font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
    <i class="pi pi-building text-blue-500"></i>
    Proyecto * 
    <Tag 
      v-if="proyectoDelArchivo" 
      value="AUTO" 
      severity="success" 
      class="text-xs"
    />
  </label>
  <Skeleton v-if="loadingProyectos" height="3rem" />
  <Dropdown
    v-else
    v-model="selectedProyecto"
    :options="proyectos"
    optionLabel="nombre"
    optionValue="id"
    placeholder="Selecciona un proyecto"
    class="w-full"
    filter
    showClear
  />
  <small class="text-gray-500 dark:text-gray-400 mt-1 block">
    {{ proyectoDelArchivo ? 'Se usará el proyecto detectado en el archivo' : 'Los datos se importarán a este proyecto' }}
  </small>
</div>

      <!-- Barrio Selector (Condicional) -->
      <div 
        v-if="needsBarrio"
        class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700"
      >
        <label class="block mb-2 font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
          <i class="pi pi-map text-purple-500"></i>
          Barrio *
        </label>
        <Skeleton v-if="loadingBarrios" height="3rem" />
        <Dropdown
          v-else
          v-model="selectedBarrio"
          :options="barrios"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Selecciona un barrio"
          class="w-full"
          filter
          showClear
          :disabled="!selectedProyecto || barrios.length === 0"
        />
        <small class="text-gray-500 dark:text-gray-400 mt-1 block">
          <span v-if="!selectedProyecto">Primero selecciona un proyecto</span>
          <span v-else-if="barrios.length === 0">No hay barrios en este proyecto</span>
          <span v-else>Las cuadras/terrenos se importarán a este barrio</span>
        </small>
      </div>

      <!-- Cuadra Selector (Condicional) -->
      <!-- Mapeo de cuadras detectadas -->
<div v-if="needsCuadra" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
  <label class="block mb-2 font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
    <i class="pi pi-th-large text-green-500"></i>
    Mapeo de Cuadras detectadas
  </label>

  <p class="text-sm text-gray-500 mb-4">El archivo contiene las siguientes referencias de cuadra. Asigna la cuadra existente o crea una nueva.</p>

  <div v-for="(c, idx) in detectedCuadras" :key="idx" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
    <div class="md:col-span-1">
      <div class="font-mono text-sm">{{ c.cuadra_ref ?? c.nombre }}</div>
      <div class="text-xs text-gray-500">Features: {{ c.index }}</div>
    </div>

    <div class="md:col-span-1">
      <Dropdown
        :options="cuadras"
        optionLabel="nombre"
        optionValue="id"
        v-model="cuadraMap[c.cuadra_ref ?? c.nombre]"
        :placeholder="`Selecciona cuadra para ${c.cuadra_ref ?? c.nombre}`"
        class="w-full"
        filter
        showClear
      />
      <small class="text-gray-400">O deja vacío para crear nueva cuadra</small>
    </div>

    <div class="md:col-span-1 flex gap-2">
      <button type="button" class="btn" @click="openCreateCuadraDialog(c)">{{ cuadraMap[c.cuadra_ref ?? c.nombre] ? 'Reemplazar' : 'Crear nueva' }}</button>
      <button v-if="cuadraMap[c.cuadra_ref ?? c.nombre]" type="button" class="btn-ghost" @click="cuadraMap[c.cuadra_ref ?? c.nombre] = null">Desasignar</button>
    </div>
  </div>

  <small class="text-gray-500">Si creas nuevas cuadras, se generará cuadra nueva para el barrio seleccionado al importar.</small>
</div>


    </div>

    <!-- Validation Message -->
    <Message v-if="!isValid" severity="warn" :closable="false">
      Por favor completa todos los campos requeridos antes de continuar
    </Message>

    <Message v-else severity="success" :closable="false">
      <div class="flex items-center gap-2">
        <i class="pi pi-check-circle"></i>
        <span>Configuración válida. Puedes continuar al siguiente paso.</span>
      </div>
    </Message>
  </div>
</template>