<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import 'leaflet/dist/leaflet.css';
import { useToast } from 'primevue/usetoast';
import { ConfirmacionModal } from '@/components/ui/confirmacionModal';

const toast = useToast();
const estadoDialogos = reactive({
  confirmacionVisible: false,
});
// Tipos
interface BreadcrumbItem {
  title: string;
  href: string;
}

interface Proyecto {
  id: number;
  nombre: string;
}

interface Barrio {
  id: number;
  nombre: string;
}

interface Cuadra {
  id: number;
  nombre: string;
}

interface Categoria {
  id: number;
  nombre: string;
  color: string;
  idproyecto: number;
}

interface PoligonoGuardado {
  tipo: 'barrio' | 'cuadra' | 'terreno';
  properties: any;
  geometry: any;
  layer?: any;
}

// Breadcrumbs


// Estado
const loading = ref(false);
const saving = ref(false);
const proyectos = ref<Proyecto[]>([]);
const barrios = ref<Barrio[]>([]);
const cuadras = ref<Cuadra[]>([]);
const categorias = ref<Categoria[]>([]);

// Formulario
const selectedProyecto = ref<number | null>(null);
const tipoPoligono = ref<'barrio' | 'cuadra' | 'terreno' | ''>('');
const selectedBarrio = ref<number | null>(null);
const selectedCuadra = ref<number | null>(null);
const selectedCategoria = ref<number | null>(null);

// Campos dinámicos (ya no se usan para barrio/cuadra, solo terreno)
const numeroTerreno = ref('');
const superficieTerreno = ref('');

// Para terreno, necesitamos seleccionar barrio y cuadra
const selectedBarrioForTerreno = ref<number | null>(null);
const selectedCuadraForTerreno = ref<number | null>(null);
const cuadrasForTerreno = ref<Cuadra[]>([]);

// Mapa
let map: L.Map | null = null;
let currentLayer: L.Layer | null = null;
const poligonosGuardados = ref<PoligonoGuardado[]>([]);

// Computadas
const categoriasDelProyecto = computed(() => {
  if (!selectedProyecto.value) return [];
  return categorias.value.filter(c => c.idproyecto === selectedProyecto.value);
});

const puedeGuardar = computed(() => {
  return poligonosGuardados.value.length > 0;
});

// Watchers
watch(selectedProyecto, async (newVal) => {
  if (newVal) {
    await cargarBarrios(newVal);
    limpiarCampos();
  }
});

watch(selectedBarrio, async (newVal) => {
  if (newVal) {
    await cargarCuadras(newVal);
  }
});

watch(selectedBarrioForTerreno, async (newVal) => {
  if (newVal) {
    await cargarCuadrasForTerreno(newVal);
  } else {
    cuadrasForTerreno.value = [];
    selectedCuadraForTerreno.value = null;
  }
});

watch(tipoPoligono, () => {
  limpiarCampos();
  // Limpiar también las variables de terreno
  selectedBarrioForTerreno.value = null;
  selectedCuadraForTerreno.value = null;
  cuadrasForTerreno.value = [];
});

// Métodos
const cargarDatosIniciales = async () => {
  loading.value = true;
  try {
    const response = await fetch('/polygon-editor/initial-data');
    const data = await response.json();
    
    if (data.success) {
      proyectos.value = data.data.proyectos;
      categorias.value = data.data.categorias;
    }
  } catch (error) {
    console.error('Error cargando datos:', error);
  } finally {
    loading.value = false;
  }
};

const cargarBarrios = async (idProyecto: number) => {
  try {
    const response = await fetch(`/polygon-editor/barrios/${idProyecto}`);
    const data = await response.json();
    
    if (data.success) {
      barrios.value = data.barrios;
      selectedBarrio.value = null;
      cuadras.value = [];
    }
  } catch (error) {
    console.error('Error cargando barrios:', error);
  }
};

const cargarCuadras = async (idBarrio: number) => {
  try {
    const response = await fetch(`/polygon-editor/cuadras/${idBarrio}`);
    const data = await response.json();
    
    if (data.success) {
      cuadras.value = data.cuadras;
      selectedCuadra.value = null;
    }
  } catch (error) {
    console.error('Error cargando cuadras:', error);
  }
};
const cargarCuadrasForTerreno = async (idBarrio: number) => {
  try {
    const response = await fetch(`/polygon-editor/cuadras/${idBarrio}`);
    const data = await response.json();
    
    if (data.success) {
      cuadrasForTerreno.value = data.cuadras;
      selectedCuadraForTerreno.value = null;
    }
  } catch (error) {
    console.error('Error cargando cuadras para terreno:', error);
  }
};

const limpiarCampos = () => {
  superficieTerreno.value = '';
  selectedCategoria.value = null;
  // selectedBarrioForTerreno.value = null;
  // selectedCuadraForTerreno.value = null;
  // cuadrasForTerreno.value = [];
};

const validarFormulario = (): string | null => {
  if (!selectedProyecto.value) return 'Selecciona un proyecto';
  if (!tipoPoligono.value) return 'Selecciona un tipo de polígono';
  
  if (tipoPoligono.value === 'barrio' && !selectedBarrio.value) {
    return 'Selecciona un barrio';
  }
  
  if (tipoPoligono.value === 'cuadra') {
    if (!selectedBarrio.value) return 'Selecciona un barrio';
    if (!selectedCuadra.value) return 'Selecciona una cuadra';
  }
  
  if (tipoPoligono.value === 'terreno') {
    if (!selectedBarrioForTerreno.value) return 'Selecciona un barrio';
    if (!selectedCuadraForTerreno.value) return 'Selecciona una cuadra';
    if (!numeroTerreno.value) return 'Ingresa el número del terreno';
    if (!selectedCategoria.value) return 'Selecciona una categoría';
  }
  
  return null;
};

const guardarPoligono = () => {
  if (!currentLayer) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'Debes dibujar un polígono primero',
      life: 3000
    });
    return;
  }
  
  const error = validarFormulario();
  if (error) {
    toast.add({
      severity: 'error',
      summary: 'Error de Validación',
      detail: error,
      life: 3000
    });
    return;
  }
  
  const geojson = (currentLayer as any).toGeoJSON();
  
let properties: any = { tipo: tipoPoligono.value };
  
  if (tipoPoligono.value === 'barrio') {
    const barrioSeleccionado = barrios.value.find(b => b.id === selectedBarrio.value);
    properties.nombre = barrioSeleccionado?.nombre || '';
    properties.idproyecto = selectedProyecto.value;
    selectedBarrio.value = null;
  } else if (tipoPoligono.value === 'cuadra') {
    const cuadraSeleccionada = cuadras.value.find(c => c.id === selectedCuadra.value);
    properties.nombre = cuadraSeleccionada?.nombre || '';
    properties.idbarrio = selectedBarrio.value;
    selectedCuadra.value = null;
  } else if (tipoPoligono.value === 'terreno') {
    properties.numero = numeroTerreno.value;
    properties.idcuadra = selectedCuadraForTerreno.value;
    properties.idproyecto = selectedProyecto.value;
    properties.superficie = superficieTerreno.value || '0';
    properties.idcategoria = selectedCategoria.value;
  }
  
  const poligono: PoligonoGuardado = {
    tipo: tipoPoligono.value as any,
    properties,
    geometry: geojson.geometry,
    layer: currentLayer,
  };
  
  poligonosGuardados.value.push(poligono);
  
  // Cambiar color según tipo
  (currentLayer as any).setStyle({
    color: getColorByType(tipoPoligono.value),
    fillColor: getColorByType(tipoPoligono.value),
    fillOpacity: 0.5,
    weight: 2,
  });
  
  // Agregar tooltip
  (currentLayer as any).bindTooltip(
    properties.nombre || properties.numero || 'Sin nombre',
    { permanent: true, direction: 'center' }
  );
  
currentLayer = null;
  limpiarCampos();
  
  toast.add({
    severity: 'success',
    summary: 'Éxito',
    detail: 'Polígono agregado a la lista',
    life: 3000
  });
};

const getColorByType = (tipo: string) => {
  const colors: Record<string, string> = {
    barrio: '#3b82f6',
    cuadra: '#8b5cf6',
    terreno: '#f59e0b',
  };
  return colors[tipo] || '#6b7280';
};

const limpiarDibujo = () => {
  if (currentLayer && map) {
    map.removeLayer(currentLayer);
    currentLayer = null;
  }
};

const eliminarPoligono = (index: number) => {
  const poligono = poligonosGuardados.value[index];
  if (poligono.layer && map) {
    map.removeLayer(poligono.layer);
  }
  poligonosGuardados.value.splice(index, 1);
};
const guardarTodo = () => {
  if (poligonosGuardados.value.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'No hay polígonos para guardar',
      life: 3000
    });
    return;
  }
  
  saving.value = true;
  
  const payload = poligonosGuardados.value.map(p => ({
    tipo: p.tipo,
    properties: p.properties,
    geometry: p.geometry,
  }));
  
  router.post('/polygon-editor/save', 
    { poligonos: payload },
    {
      preserveScroll: true,
      onSuccess: (page: any) => {
        // Inertia pone los datos directamente en props
        const success = page.props.success;
        const message = page.props.message;
        const resultado = page.props.resultado;
        
        if (success) {
          const mensaje = `Barrios: ${resultado?.barrios?.creados || 0} creados, ${resultado?.barrios?.actualizados || 0} actualizados | Cuadras: ${resultado?.cuadras?.creadas || 0} creadas, ${resultado?.cuadras?.actualizadas || 0} actualizadas | Terrenos: ${resultado?.terrenos?.creados || 0} creados, ${resultado?.terrenos?.actualizados || 0} actualizados`;
          
          toast.add({
            severity: 'success',
            summary: '✅ Polígonos Guardados',
            detail: mensaje,
            life: 6000
          });
          
          // Limpiar todo
          poligonosGuardados.value = [];
          if (map) {
            map.eachLayer((layer) => {
              if (layer instanceof L.Polygon || layer instanceof L.Rectangle) {
                map!.removeLayer(layer);
              }
            });
          }
        }
      },
      onError: (errors: any) => {
        console.error('Error guardando:', errors);
        
        let errorMsg = 'Error al guardar polígonos';
        if (errors.message) {
          errorMsg = errors.message;
        } else if (typeof errors === 'string') {
          errorMsg = errors;
        } else if (errors.poligonos) {
          errorMsg = errors.poligonos;
        }
        
        toast.add({
          severity: 'error',
          summary: '❌ Error al Guardar',
          detail: errorMsg,
          life: 5000
        });
      },
      onFinish: () => {
        saving.value = false;
      },
    }
  );
};

const initMap = () => {
  map = L.map('map').setView([-17.0, -65.0], 6);
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 22,
    maxNativeZoom: 19,
  }).addTo(map);
  
  (map as any).pm.addControls({
    position: 'topleft',
    drawPolygon: true,
    drawMarker: false,
    drawCircle: false,
    drawPolyline: false,
    drawRectangle: true,
    drawCircleMarker: false,
    editMode: true,
    dragMode: true,
    cutPolygon: false,
    removalMode: false,
  });
  
  map.on('pm:create', (e: any) => {
    if (currentLayer && map) {
      map.removeLayer(currentLayer);
    }
    
    currentLayer = e.layer;
    currentLayer.setStyle({
      color: '#6366f1',
      fillColor: '#6366f1',
      fillOpacity: 0.3,
      weight: 3,
    });
  });
};

const eliminarPendientes = () => {
  // Borra los polígonos del mapa
  // Si existen polígonos guardados
  if (poligonosGuardados.value.length > 0) {
    // Eliminar del mapa cada capa
    poligonosGuardados.value.forEach(p => {
      if (p.layer && map) {
        map.removeLayer(p.layer);
      }
    });
  }

  // Limpia la lista interna de polígonos
  poligonosGuardados.value = [];

  toast.add({
    severity: 'success',
    summary: 'Limpieza completada',
    detail: 'Se eliminaron todos los polígonos pendientes',
    life: 3000
  });

  // Cierra el modal
  estadoDialogos.confirmacionVisible = false;
};


onMounted(() => {
  cargarDatosIniciales();
  initMap();
});
</script>
<template>
  <AppLayout>
    <Head title="Editor de Mapas" />
    
    <div class="h-screen flex flex-col">
      <ConfirmacionModal v-model="estadoDialogos.confirmacionVisible" title="Confirmar eliminación" message="¿Estás seguro de que deseas eliminar TODOS los poligonos creados recientemente?" @confirm="eliminarPendientes" />
      <!-- Header -->
      <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white p-4 shadow-lg">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
            <i class="pi pi-map text-2xl"></i>
          </div>
          <div>
            <h1 class="text-2xl font-bold">Editor de Mapas</h1>
            <p class="text-sm text-purple-100">Dibuja y asigna polígonos a proyectos</p>
          </div>
        </div>
      </div>
      
      <div class="flex-1 flex overflow-hidden">
        <!-- Panel Lateral -->
        <div class="w-96 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto p-6">
          <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
            ✏️ Configuración
          </h2>
          
          <!-- Proyecto -->
          <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Proyecto
            </label>
            <select 
              v-model="selectedProyecto" 
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option :value="null">-- Seleccionar --</option>
              <option v-for="p in proyectos" :key="p.id" :value="p.id">
                {{ p.nombre }}
              </option>
            </select>
          </div>
          
          <!-- Tipo -->
          <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Tipo de Polígono
            </label>
            <select 
              v-model="tipoPoligono" 
              :disabled="!selectedProyecto"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
            >
              <option value="">-- Seleccionar --</option>
              <option value="barrio">🏘️ Barrio</option>
              <option value="cuadra">🏗️ Cuadra</option>
              <option value="terreno">🏠 Terreno</option>
            </select>
          </div>
          
          <!-- Campos BARRIO -->
          <div v-if="tipoPoligono === 'barrio'" class="mb-4 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Seleccionar Barrio
              </label>
              <select 
                v-model="selectedBarrio"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="b in barrios" :key="b.id" :value="b.id">
                  {{ b.nombre }}
                </option>
              </select>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Solo barrios sin polígono asignado
              </p>
            </div>
          </div>
          
          <!-- Campos CUADRA -->
          <div v-if="tipoPoligono === 'cuadra'" class="mb-4 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Barrio
              </label>
              <select 
                v-model="selectedBarrio"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="b in barrios" :key="b.id" :value="b.id">
                  {{ b.nombre }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Seleccionar Cuadra
              </label>
              <select 
                v-model="selectedCuadra"
                :disabled="!selectedBarrio"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="c in cuadras" :key="c.id" :value="c.id">
                  {{ c.nombre }}
                </option>
              </select>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Solo cuadras sin polígono asignado
              </p>
            </div>
          </div>
          
          <!-- Campos TERRENO -->
          <div v-if="tipoPoligono === 'terreno'" class="mb-4 space-y-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Barrio
              </label>
              <select 
                v-model="selectedBarrioForTerreno"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="b in barrios" :key="b.id" :value="b.id">
                  {{ b.nombre }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Cuadra
              </label>
              <select 
                v-model="selectedCuadraForTerreno"
                :disabled="!selectedBarrioForTerreno"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="c in cuadrasForTerreno" :key="c.id" :value="c.id">
                  {{ c.nombre }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Número de Terreno
              </label>
              <input 
                v-model="numeroTerreno" 
                type="text" 
                placeholder="Ej: 001"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Superficie (m²)
              </label>
              <input 
                v-model="superficieTerreno" 
                type="text" 
                placeholder="Ej: 300"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Categoría
              </label>
              <select 
                v-model="selectedCategoria"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option :value="null">-- Seleccionar --</option>
                <option v-for="cat in categoriasDelProyecto" :key="cat.id" :value="cat.id">
                  {{ cat.nombre }}
                </option>
              </select>
            </div>
          </div>
          
          <!-- Botones -->
          <div class="space-y-2">
            <button 
              @click="guardarPoligono"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition"
            >
              💾 Agregar Polígono
            </button>
            
            <button 
              @click="estadoDialogos.confirmacionVisible = true"
              class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition"
            >
              🗑️ Limpiar Dibujo
            </button>
          </div>
          
          <!-- Lista de polígonos -->
          <div class="mt-6">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
              Polígonos Pendientes ({{ poligonosGuardados.length }})
            </h3>
            
            <div class="space-y-2 max-h-64 overflow-y-auto">
              <div 
                v-for="(p, i) in poligonosGuardados" 
                :key="i"
                class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg flex justify-between items-center"
              >
                <div>
                  <div class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                    {{ p.tipo.toUpperCase() }}
                  </div>
                  <div class="text-sm text-gray-700 dark:text-gray-300">
                    {{ p.properties.nombre || p.properties.numero || 'Sin nombre' }}
                  </div>
                </div>
                <button 
                  @click="eliminarPoligono(i)"
                  class="text-red-600 hover:text-red-700 font-bold"
                >
                  🗑️
                </button>
              </div>
            </div>
            
            <button 
              @click="guardarTodo"
              :disabled="!puedeGuardar || saving"
              class="w-full mt-4 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-3 px-4 rounded-lg transition"
            >
              {{ saving ? '⏳ Guardando...' : '📥 Guardar Todo' }}
            </button>
          </div>
        </div>
        
        <!-- Mapa -->
        <div class="flex-1 relative">
          <div id="map" class="w-full h-full"></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
#map {
  z-index: 0;
}
</style>