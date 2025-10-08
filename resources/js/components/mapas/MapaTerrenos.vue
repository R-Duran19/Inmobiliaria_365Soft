<script setup lang="ts">
import { onMounted, ref, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

interface Props {
  proyectoId: number;
  proyecto: any;
}

const props = defineProps<Props>();

interface Categoria {
  id: number;
  nombre: string;
  color: string;
  total_terrenos: number;
}

const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
const terrenos = ref<any[]>([]);
const categorias = ref<Categoria[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Detectar tema actual (claro/oscuro)
const isDark = ref(document.documentElement.classList.contains('dark'));
const observer = new MutationObserver(() => {
  isDark.value = document.documentElement.classList.contains('dark');
});

// Observar cambios de clase "dark" en <html>
onMounted(() => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => observer.disconnect());

const getTileLayer = () => {
  return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  });
};

const getEstadoTexto = (estado: number) => {
  switch (estado) {
    case 0:
      return 'Disponible';
    case 1:
      return 'Vendido';
    case 2:
      return 'Reservado';
    default:
      return 'Desconocido';
  }
};

const getEstadoBadgeColor = (estado: number) => {
  switch (estado) {
    case 0:
      return '#10b981'; // Verde - Disponible
    case 1:
      return '#ef4444'; // Rojo - Vendido
    case 2:
      return '#f59e0b'; // Naranja - Reservado
    default:
      return '#6b7280'; // Gris
  }
};

// Cargar categorías del proyecto
const cargarCategorias = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/categorias`);
    if (response.data && Array.isArray(response.data)) {
      categorias.value = response.data;
      console.log('✅ Categorías cargadas:', categorias.value.length); // Debug
    } else {
      console.warn('⚠️ Respuesta de categorías no es un array:', response.data);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar categorías:', err);
    error.value = 'Error al cargar las categorías'; // Mostrar error al usuario
  }
};

// Cargar terrenos desde API
const cargarTerrenos = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/terrenos`);
    
    console.log('📦 Respuesta terrenos:', response.data); // Debug

    if (response.data && response.data.type === 'FeatureCollection' && Array.isArray(response.data.features)) {
      terrenos.value = response.data.features;
      console.log('✅ Terrenos cargados:', terrenos.value.length); // Debug
      dibujarTerrenos();
    } else {
      console.warn('⚠️ Formato de respuesta inválido:', response.data);
      error.value = 'Formato de datos de terrenos inválido';
    }
  } catch (err: any) {
    console.error('❌ Error al cargar terrenos:', err);
    error.value = 'Error al cargar los terrenos';
  } finally {
    loading.value = false;
  }
};

// Dibujar terrenos con colores de categoría
const dibujarTerrenos = () => {
  if (!map || terrenos.value.length === 0) return;

  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: terrenos.value,
  };

  const terrenosLayer = L.geoJSON(geojsonObject, {
    style: (feature) => {
      // Usar el color de la categoría del terreno
      const color = feature?.properties?.categoria_color || '#6b7280';
      return {
        color: color,
        weight: 2,
        fillOpacity: 0.5,
        fillColor: color,
      };
    },
    onEachFeature: (feature, layer) => {
      const props = feature.properties;
      const textColor = isDark.value ? '#e5e7eb' : '#1f2937';
      const dividerColor = isDark.value ? '#374151' : '#e5e7eb';

      const popupContent = `
        <div style="font-family: sans-serif; min-width: 250px; color: ${textColor};">
          <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: bold;">
            ${props.codigo || `Terreno #${props.id}`}
          </h3>
          
          <div style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;">
            <div style="background-color: ${getEstadoBadgeColor(props.estado)}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
              ${getEstadoTexto(props.estado)}
            </div>
            <div style="background-color: ${props.categoria_color}; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
              ${props.categoria}
            </div>
          </div>

          <div style="border-top: 1px solid ${dividerColor}; padding-top: 10px; margin-top: 10px;">
            <p style="margin: 5px 0; font-size: 14px;"><strong>Ubicación:</strong> ${props.ubicacion}</p>
            <p style="margin: 5px 0; font-size: 14px;"><strong>Superficie:</strong> ${props.superficie}</p>
          </div>
          
          <div style="border-top: 1px solid ${dividerColor}; padding-top: 10px; margin-top: 10px;">
            <p style="margin: 5px 0; font-size: 15px; color: #10b981;"><strong>Precio Venta:</strong> $${Number(
              props.precio_venta
            ).toLocaleString()}</p>
            <p style="margin: 5px 0; font-size: 13px; color: ${textColor};"><strong>Cuota Inicial:</strong> $${Number(
              props.cuota_inicial
            ).toLocaleString()}</p>
            <p style="margin: 5px 0; font-size: 13px; color: ${textColor};"><strong>Cuota Mensual:</strong> $${Number(
              props.cuota_mensual
            ).toLocaleString()}</p>
          </div>
        </div>
      `;

      layer.bindPopup(popupContent, {
        maxWidth: 300,
        className: 'custom-popup',
      });

      layer.on('mouseover', () => (layer as L.Path).setStyle({ weight: 4, fillOpacity: 0.7 }));
      layer.on('mouseout', () => (layer as L.Path).setStyle({ weight: 2, fillOpacity: 0.5 }));
    },
  }).addTo(map);

  const bounds = terrenosLayer.getBounds();
  if (bounds.isValid()) map.fitBounds(bounds, { padding: [50, 50] });
};

onMounted(async () => {
  if (!mapContainer.value) return;
  map = L.map(mapContainer.value).setView([-17.3895, -66.3167], 15);
  getTileLayer().addTo(map);
  
  // Cargar categorías y terrenos
  await cargarCategorias();
  await cargarTerrenos();
});

// Cuando cambia el tema, refrescar capa base
watch(isDark, (val) => {
  if (map) {
    map.eachLayer((layer) => map?.removeLayer(layer));
    getTileLayer().addTo(map);
    dibujarTerrenos();
  }
});

onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<template>
  <div class="relative h-full">
    <!-- Loading -->
    <div
      v-if="loading"
      class="absolute top-4 left-1/2 transform -translate-x-1/2 z-[1000] bg-white dark:bg-gray-800 
             px-6 py-3 rounded-lg shadow-lg text-gray-800 dark:text-gray-200"
    >
      <div class="flex items-center gap-2">
        <i class="pi pi-spin pi-spinner text-blue-500"></i>
        <p class="text-sm font-medium">Cargando terrenos...</p>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="absolute top-4 left-1/2 transform -translate-x-1/2 z-[1000] 
             bg-red-100 dark:bg-red-900/40 border border-red-400 dark:border-red-700 
             text-red-700 dark:text-red-300 px-6 py-3 rounded-lg shadow-lg"
    >
      <div class="flex items-center gap-2">
        <i class="pi pi-exclamation-triangle"></i>
        <p class="text-sm font-medium">{{ error }}</p>
      </div>
    </div>

    <!-- Panel Info del Proyecto (mantiene contadores de estados) -->
    <div
      class="absolute top-4 left-4 z-[1000] bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg 
             max-w-xs text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"
    >
      <h3 class="font-bold text-lg mb-2">{{ proyecto.nombre }}</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ proyecto.ubicacion }}</p>

      <div class="grid grid-cols-3 gap-2 text-center">
        <div>
          <div class="text-xl font-bold text-green-600 dark:text-green-400">
            {{ proyecto.terrenos_disponibles }}
          </div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Disponibles</div>
        </div>
        <div>
          <div class="text-xl font-bold text-orange-600 dark:text-orange-400">
            {{ proyecto.terrenos_reservados }}
          </div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Reservados</div>
        </div>
        <div>
          <div class="text-xl font-bold text-red-600 dark:text-red-400">
            {{ proyecto.terrenos_vendidos }}
          </div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Vendidos</div>
        </div>
      </div>
    </div>

    <!-- Leyenda de Categorías -->
    <div
      class="absolute bottom-4 right-4 z-[1000] bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg 
             text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 max-w-xs"
    >
      <h4 class="font-bold mb-3 text-sm">Categorías de Terrenos</h4>
      
      <div v-if="categorias.length > 0" class="space-y-2 text-xs max-h-64 overflow-y-auto">
        <div
          v-for="categoria in categorias"
          :key="categoria.id"
          class="flex items-center justify-between gap-2 py-1"
        >
          <div class="flex items-center gap-2 flex-1 min-w-0">
            <div
              class="w-5 h-5 rounded flex-shrink-0"
              :style="`background-color: ${categoria.color}; border: 2px solid ${categoria.color};`"
            ></div>
            <span class="truncate" :title="categoria.nombre">{{ categoria.nombre }}</span>
          </div>
          <span class="text-gray-500 dark:text-gray-400 text-xs font-medium flex-shrink-0">
            {{ categoria.total_terrenos }}
          </span>
        </div>
      </div>

      <div v-else class="text-center py-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          No hay categorías configuradas
        </p>
      </div>

      <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-600 dark:text-gray-400">
          Total: <strong>{{ terrenos.length }}</strong> terrenos
        </p>
      </div>
    </div>

    <!-- Mapa -->
    <div ref="mapContainer" class="w-full h-full rounded-lg"></div>
  </div>
</template>

<style scoped>
:deep(.leaflet-container) {
  font-family: inherit;
  z-index: 0;
}

:deep(.leaflet-popup-content) {
  margin: 0;
  padding: 0;
}

:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
  background-color: var(--popup-bg, white);
  color: var(--popup-text, black);
  transition: background-color 0.3s;
}

:deep(.custom-popup .leaflet-popup-content-wrapper) {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>