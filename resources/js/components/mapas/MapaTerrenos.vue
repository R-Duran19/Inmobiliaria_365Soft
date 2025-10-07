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

const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
const terrenos = ref<any[]>([]);
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


const getColorByEstado = (estado: number) => {
  switch (estado) {
    case 0:
      return '#10b981'; // Disponible
    case 1:
      return '#ef4444'; // Vendido
    case 2:
      return '#f59e0b'; // Reservado
    default:
      return '#6b7280'; // Desconocido
  }
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

// Cargar terrenos desde API
const cargarTerrenos = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/terrenos`);

    if (response.data && Array.isArray(response.data.features)) {
      terrenos.value = response.data.features;
      dibujarTerrenos();
    }
  } catch (err: any) {
    console.error('Error al cargar terrenos:', err);
    error.value = 'Error al cargar los terrenos';
  } finally {
    loading.value = false;
  }
};

// Dibujar terrenos
const dibujarTerrenos = () => {
  if (!map || terrenos.value.length === 0) return;

  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: terrenos.value,
  };

  const terrenosLayer = L.geoJSON(geojsonObject, {
    style: (feature) => {
      const estado = feature?.properties?.estado || 0;
      return {
        color: getColorByEstado(estado),
        weight: 2,
        fillOpacity: 0.5,
        fillColor: getColorByEstado(estado),
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
          <div style="background-color: ${getColorByEstado(props.estado)}; color: white; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 10px; font-size: 12px; font-weight: bold;">
            ${getEstadoTexto(props.estado)}
          </div>
          <div style="border-top: 1px solid ${dividerColor}; padding-top: 10px; margin-top: 10px;">
            <p style="margin: 5px 0; font-size: 14px;"><strong>Categoría:</strong> ${props.categoria}</p>
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

    <!-- Panel Info -->
    <div
      class="absolute top-4 left-4 z-[1000] bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg 
             max-w-xs text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"
    >
      <h3 class="font-bold text-lg mb-2">{{ proyecto.nombre }}</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ proyecto.ubicacion }}</p>

      <div class="grid grid-cols-3 gap-2 text-center">
        <div>
          <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ proyecto.terrenos_disponibles }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Disponibles</div>
        </div>
        <div>
          <div class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ proyecto.terrenos_reservados }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Reservados</div>
        </div>
        <div>
          <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ proyecto.terrenos_vendidos }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Vendidos</div>
        </div>
      </div>
    </div>

    <!-- Leyenda -->
    <div
      class="absolute bottom-4 right-4 z-[1000] bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg 
             text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"
    >
      <h4 class="font-bold mb-3 text-sm">Leyenda</h4>
      <div class="space-y-2 text-xs">
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded" style="background-color: #10b981; border: 2px solid #059669;"></div>
          <span>Disponible</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded" style="background-color: #f59e0b; border: 2px solid #d97706;"></div>
          <span>Reservado</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded" style="background-color: #ef4444; border: 2px solid #dc2626;"></div>
          <span>Vendido</span>
        </div>
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
