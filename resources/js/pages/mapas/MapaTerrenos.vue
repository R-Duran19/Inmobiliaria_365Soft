<script setup lang="ts">
import { onMounted, ref, onUnmounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

interface Props {
  proyectoId: number;
}

const props = defineProps<Props>();

interface Categoria {
  id: number;
  nombre: string;
  color: string;
  total_terrenos: number;
}

interface Proyecto {
  id: number;
  nombre: string;
  descripcion: string;
  ubicacion: string;
  fecha_lanzamiento: string;
  numero_lotes: number;
  fotografia: string | null;
  total_terrenos: number;
  terrenos_disponibles: number;
  terrenos_vendidos: number;
  terrenos_reservados: number;
  poligono?: any; // Agregado para evitar el error de propiedad faltante
}

const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;

// Layers para cada nivel de zoom
let barriosLayer: L.GeoJSON | null = null;
let cuadrasLayer: L.GeoJSON | null = null;
let terrenosLayer: L.GeoJSON | null = null;

// Datos
const barrios = ref<any[]>([]);
const cuadras = ref<any[]>([]);
const terrenos = ref<any[]>([]);
const categorias = ref<Categoria[]>([]);
const proyecto = ref<Proyecto | null>(null);

const loading = ref(true);
const error = ref<string | null>(null);
const currentZoomLevel = ref<'barrios' | 'cuadras' | 'terrenos' | null>(null);

// Detectar tema actual
const isDark = ref(document.documentElement.classList.contains('dark'));
const observer = new MutationObserver(() => {
  isDark.value = document.documentElement.classList.contains('dark');
});

onMounted(() => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
onUnmounted(() => observer.disconnect());

const getTileLayer = () => {
  return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  });
};

const getEstadoTexto = (estado: number) => {
  switch (estado) {
    case 0: return 'Disponible';
    case 1: return 'Vendido';
    case 2: return 'Reservado';
    default: return 'Desconocido';
  }
};

const getEstadoBadgeColor = (estado: number) => {
  switch (estado) {
    case 0: return '#10b981';
    case 1: return '#ef4444';
    case 2: return '#f59e0b';
    default: return '#6b7280';
  }
};

// Cargar información del proyecto
const cargarProyecto = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}`);
    if (response.data) {
      proyecto.value = response.data;
      console.log('✅ Proyecto cargado:', proyecto.value);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar proyecto:', err);
    error.value = 'Error al cargar información del proyecto';
  }
};

// Cargar categorías
const cargarCategorias = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/categorias`);
    if (response.data && Array.isArray(response.data)) {
      categorias.value = response.data;
      console.log('✅ Categorías cargadas:', categorias.value.length);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar categorías:', err);
  }
};

// Cargar barrios
const cargarBarrios = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/barrios`);
    if (response.data?.type === 'FeatureCollection') {
      barrios.value = response.data.features;
      console.log('✅ Barrios cargados:', barrios.value.length);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar barrios:', err);
  }
};

// Cargar cuadras
const cargarCuadras = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/cuadras`);
    if (response.data?.type === 'FeatureCollection') {
      cuadras.value = response.data.features;
      console.log('✅ Cuadras cargadas:', cuadras.value.length);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar cuadras:', err);
  }
};

// Cargar terrenos
const cargarTerrenos = async () => {
  try {
    const response = await axios.get(`/api/mapa/proyectos/${props.proyectoId}/terrenos`);
    if (response.data?.type === 'FeatureCollection') {
      terrenos.value = response.data.features;
      console.log('✅ Terrenos cargados:', terrenos.value.length);
    }
  } catch (err: any) {
    console.error('❌ Error al cargar terrenos:', err);
  }
};

// Limpiar todas las capas del mapa
// Limpiar todas las capas del mapa
const limpiarCapas = () => {
  // Remover layers GeoJSON
  if (barriosLayer && map) {
    map.removeLayer(barriosLayer);
    barriosLayer = null;
  }
  if (cuadrasLayer && map) {
    map.removeLayer(cuadrasLayer);
    cuadrasLayer = null;
  }
  if (terrenosLayer && map) {
    map.removeLayer(terrenosLayer);
    terrenosLayer = null;
  }
  
  // 🆕 CRÍTICO: Limpiar TODOS los tooltips/labels permanentes
  if (map) {
    map.eachLayer((layer) => {
      if (layer instanceof L.Tooltip) {
        map?.removeLayer(layer);
      }
    });
  }
};

// Dibujar barrios (zoom < 7)
// Dibujar barrios (zoom < 14)
const dibujarBarrios = () => {
  if (!map || barrios.value.length === 0) return;
  
  limpiarCapas();

  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: barrios.value,
  };

  barriosLayer = L.geoJSON(geojsonObject, {
    style: {
      color: '#3b82f6',
      weight: 3,
      fillOpacity: 0.2,
      fillColor: '#3b82f6',
    },
    onEachFeature: (feature, layer) => {
      const props = feature.properties;
      const textColor = isDark.value ? '#e5e7eb' : '#1f2937';
      
      const popupContent = `
        <div style="font-family: sans-serif; min-width: 200px; color: ${textColor};">
          <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold;">
            ${props.nombre}
          </h3>
          <p style="margin: 5px 0; font-size: 14px;"><strong>Tipo:</strong> Barrio</p>
        </div>
      `;
      
      layer.bindPopup(popupContent);

      // Label permanente
      const bounds = (layer as L.Polygon).getBounds();
      const center = bounds.getCenter();
      
      const label = L.tooltip({
        permanent: true,
        direction: 'center',
        className: 'polygon-label barrio-label',
      })
        .setLatLng(center)
        .setContent(`<div style="font-weight: 700; font-size: 14px;">${props.nombre}</div>`);
      
      if (map) {
        label.addTo(map);
      }
    },
  }).addTo(map);

  currentZoomLevel.value = 'barrios';
};

// Dibujar cuadras (zoom 7-14)
// Dibujar cuadras (zoom 14-16)
const dibujarCuadras = () => {
  if (!map || cuadras.value.length === 0) return;
  
  limpiarCapas();

  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: cuadras.value,
  };

  cuadrasLayer = L.geoJSON(geojsonObject, {
    style: {
      color: '#8b5cf6',
      weight: 2,
      fillOpacity: 0.3,
      fillColor: '#8b5cf6',
    },
    onEachFeature: (feature, layer) => {
      const props = feature.properties;
      const textColor = isDark.value ? '#e5e7eb' : '#1f2937';
      
      const popupContent = `
        <div style="font-family: sans-serif; min-width: 200px; color: ${textColor};">
          <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold;">
            ${props.nombre}
          </h3>
          <p style="margin: 5px 0; font-size: 14px;"><strong>Barrio:</strong> ${props.barrio || 'N/A'}</p>
        </div>
      `;
      
      layer.bindPopup(popupContent);

      // Label permanente
      const bounds = (layer as L.Polygon).getBounds();
      const center = bounds.getCenter();
      
      const label = L.tooltip({
        permanent: true,
        direction: 'center',
        className: 'polygon-label cuadra-label',
      })
        .setLatLng(center)
        .setContent(`<div style="font-weight: 700; font-size: 12px;">${props.nombre}</div>`);
      
      if (map) {
        label.addTo(map);
      }
    },
  }).addTo(map);

  currentZoomLevel.value = 'cuadras';
};

// Dibujar terrenos (zoom 15-19)
// Dibujar terrenos (zoom >= 17)
const dibujarTerrenos = () => {
  if (!map || terrenos.value.length === 0) return;
  
  limpiarCapas();

  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: terrenos.value,
  };

  terrenosLayer = L.geoJSON(geojsonObject, {
    style: (feature) => {
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
            <p style="margin: 5px 0; font-size: 15px; color: #10b981;"><strong>Precio Venta:</strong> $${Number(props.precio_venta).toLocaleString()}</p>
            <p style="margin: 5px 0; font-size: 13px; color: ${textColor};"><strong>Cuota Inicial:</strong> $${Number(props.cuota_inicial).toLocaleString()}</p>
            <p style="margin: 5px 0; font-size: 13px; color: ${textColor};"><strong>Cuota Mensual:</strong> $${Number(props.cuota_mensual).toLocaleString()}</p>
          </div>
        </div>
      `;

      layer.bindPopup(popupContent);

      // Label permanente
      const bounds = (layer as L.Polygon).getBounds();
      const center = bounds.getCenter();
      
      const label = L.tooltip({
        permanent: true,
        direction: 'center',
        className: 'polygon-label terreno-label',
      })
        .setLatLng(center)
        .setContent(`<div style="font-weight: 700; font-size: 11px;">${props.codigo}</div>`);
      
      if (map) {
        label.addTo(map);
      }
    },
  }).addTo(map);

  currentZoomLevel.value = 'terrenos';
};

// Actualizar capa según nivel de zoom
// CONFIGURACIÓN DE NIVELES DE ZOOM:
// - Barrios: zoom < 14 (vista general)
// - Cuadras: zoom 14-16 (vista media)
// - Terrenos: zoom >= 17 (vista detallada)
// Actualizar capa según nivel de zoom
const actualizarCapaPorZoom = () => {
  if (!map) return;
  
  const zoom = map.getZoom();
  console.log('🔍 Zoom actual:', zoom);

  if (zoom < 14) {
    if (currentZoomLevel.value !== 'barrios') {
      dibujarBarrios();
    }
  } else if (zoom >= 14 && zoom < 17) {  // ⬅️ CAMBIO: era < 16, ahora < 17
    if (currentZoomLevel.value !== 'cuadras') {
      dibujarCuadras();
    }
  } else {  // zoom >= 17
    if (currentZoomLevel.value !== 'terrenos') {
      dibujarTerrenos();
    }
  }
};

onMounted(async () => {
  if (!mapContainer.value) return;
  
  loading.value = true;
  
  // Inicializar mapa
  map = L.map(mapContainer.value).setView([-17.3895, -66.3167], 5);
  getTileLayer().addTo(map);

  // Cargar datos
  await Promise.all([
    cargarProyecto(),
    cargarCategorias(),
    cargarBarrios(),
    cargarCuadras(),
    cargarTerrenos(),
  ]);

  loading.value = false;

  // Dibujar capa inicial según zoom
  actualizarCapaPorZoom();

  // Escuchar cambios de zoom
  map.on('zoomend', actualizarCapaPorZoom);

  // Configurar límites del mapa según el polígono del proyecto
// Configurar límites del mapa según el polígono del proyecto
if (proyecto.value?.poligono && map) {
  const poligonoGeoJSON = typeof proyecto.value.poligono === 'string' 
    ? JSON.parse(proyecto.value.poligono) 
    : proyecto.value.poligono;

  const proyectoLayer = L.geoJSON(poligonoGeoJSON);
  const bounds = proyectoLayer.getBounds();

  if (bounds.isValid()) {
  // 1️⃣ Vuela hacia el proyecto sin límites
  map.flyToBounds(bounds, {
    padding: [50, 50],
    duration: 1.5,
    easeLinearity: 0.25
  });

  // 2️⃣ Luego de la animación (~1.5s), fija límites y zoom
  setTimeout(() => {
    if (map) {
      map.setMaxBounds(bounds.pad(0.1));
      // Forzamos el zoom exacto deseado
      map.setZoom(13);
    }
  }, 1600); // un poco más que la duración del fly
}

} else if (terrenos.value.length > 0) {
  // Si no hay polígono, intentar centrar en los terrenos
  const geojsonObject: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: terrenos.value,
  };
  const tempLayer = L.geoJSON(geojsonObject);
  const bounds = tempLayer.getBounds();
  if (bounds.isValid()) {
    map.flyToBounds(bounds, { padding: [50, 50], maxZoom: 11, duration: 1.5 });
  }
} else if (cuadras.value.length > 0) {
  const geojson: GeoJSON.FeatureCollection = {
    type: 'FeatureCollection',
    features: cuadras.value,
  };
  const tempLayer = L.geoJSON(geojson);
  const bounds = tempLayer.getBounds();
  if (bounds.isValid()) {
    map.flyToBounds(bounds, { padding: [50, 50], maxZoom: 11, duration: 1.5 });
  }


  }
});

// Cuando cambia el tema, refrescar
watch(isDark, () => {
  if (map) {
    map.eachLayer((layer) => map?.removeLayer(layer));
    getTileLayer().addTo(map);
    actualizarCapaPorZoom();
  }
});

onUnmounted(() => {
  if (map) {
    map.off('zoomend', actualizarCapaPorZoom);
    map.remove();
    map = null;
  }
});
</script>

<template>
  <div class="relative h-screen w-full">
    <!-- Loading -->
    <div
      v-if="loading"
      class="absolute top-4 left-1/2 transform -translate-x-1/2 z-[1000] bg-white dark:bg-gray-800 
             px-6 py-3 rounded-lg shadow-lg text-gray-800 dark:text-gray-200"
    >
      <div class="flex items-center gap-2">
        <i class="pi pi-spin pi-spinner text-blue-500"></i>
        <p class="text-sm font-medium">Cargando datos del mapa...</p>
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

    <!-- Panel Info del Proyecto -->
    <div
      v-if="proyecto"
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
      v-if="categorias.length > 0"
      class="absolute top-4 right-4 z-[1000] bg-white dark:bg-gray-800 p-4 rounded-lg shadow-lg 
             max-w-xs text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"
    >
      <h4 class="font-bold text-sm mb-3">Categorías de Terrenos</h4>
      <div class="space-y-2">
        <div
          v-for="categoria in categorias"
          :key="categoria.id"
          class="flex items-center gap-2"
        >
          <div
            class="w-4 h-4 rounded-sm"
            :style="{ backgroundColor: categoria.color }"
          ></div>
          <span class="text-sm flex-1">{{ categoria.nombre }}</span>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            ({{ categoria.total_terrenos }})
          </span>
        </div>
      </div>
    </div>

    <!-- Mapa -->
    <div ref="mapContainer" class="w-full h-full"></div>
  </div>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 8px;
}

:deep(.leaflet-popup-content) {
  margin: 0;
}

/* Estilos para labels de polígonos */
:deep(.polygon-label) {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  pointer-events: none;
}

:deep(.polygon-label::before) {
  display: none;
}

:deep(.barrio-label) {
  color: #000000;
  font-size: 14px;
  font-weight: 700;
  text-shadow: 
    1px 1px 2px rgba(255,255,255,0.9), 
    -1px -1px 2px rgba(255,255,255,0.9),
    1px -1px 2px rgba(255,255,255,0.9),
    -1px 1px 2px rgba(255,255,255,0.9);
}

:deep(.cuadra-label) {
  color: #000000;
  font-size: 12px;
  font-weight: 700;
  text-shadow: 
    1px 1px 2px rgba(255,255,255,0.9), 
    -1px -1px 2px rgba(255,255,255,0.9),
    1px -1px 2px rgba(255,255,255,0.9),
    -1px 1px 2px rgba(255,255,255,0.9);
}

:deep(.terreno-label) {
  color: #000000 !important;
  font-size: 11px;
  font-weight: 700;
  text-shadow: 
    1px 1px 2px rgba(255,255,255,0.9), 
    -1px -1px 2px rgba(255,255,255,0.9),
    1px -1px 2px rgba(255,255,255,0.9),
    -1px 1px 2px rgba(255,255,255,0.9);
}
</style>