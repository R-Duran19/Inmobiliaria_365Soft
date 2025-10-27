<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';

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
    poligono?: any;
}

const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;

let barriosLayer: L.GeoJSON | null = null;
let cuadrasLayer: L.GeoJSON | null = null;
let terrenosLayer: L.GeoJSON | null = null;


const idTerrenoSeleccionado = ref(0);
const barrios = ref<any[]>([]);
const cuadras = ref<any[]>([]);
const terrenos = ref<any[]>([]);
const categorias = ref<Categoria[]>([]);
const proyecto = ref<Proyecto | null>(null);

const loading = ref(true);
const error = ref<string | null>(null);
const currentZoomLevel = ref<'barrios' | 'cuadras' | 'terrenos' | null>(null);

const isDark = ref(document.documentElement.classList.contains('dark'));
const observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

onMounted(() => {
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
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
            return '#10b981';
        case 1:
            return '#ef4444';
        case 2:
            return '#f59e0b';
        default:
            return '#6b7280';
    }
};

const cargarProyecto = async () => {
    try {
        const response = await axios.get(
            `/api/mapa/proyectos/${props.proyectoId}`,
        );
        if (response.data) {
            proyecto.value = response.data;
            console.log('✅ Proyecto cargado:', proyecto.value);
        }
    } catch (err: any) {
        console.error('❌ Error al cargar proyecto:', err);
        error.value = 'Error al cargar información del proyecto';
    }
};

const cargarCategorias = async () => {
    try {
        const response = await axios.get(
            `/api/mapa/proyectos/${props.proyectoId}/categorias`,
        );
        if (response.data && Array.isArray(response.data)) {
            categorias.value = response.data;
            console.log('✅ Categorías cargadas:', categorias.value.length);
        }
    } catch (err: any) {
        console.error('❌ Error al cargar categorías:', err);
    }
};

const cargarBarrios = async () => {
    try {
        const response = await axios.get(
            `/api/mapa/proyectos/${props.proyectoId}/barrios`,
        );
        if (response.data?.type === 'FeatureCollection') {
            barrios.value = response.data.features;
            console.log('✅ Barrios cargados:', barrios.value.length);
        }
    } catch (err: any) {
        console.error('❌ Error al cargar barrios:', err);
    }
};

const cargarCuadras = async () => {
    try {
        const response = await axios.get(
            `/api/mapa/proyectos/${props.proyectoId}/cuadras`,
        );
        if (response.data?.type === 'FeatureCollection') {
            cuadras.value = response.data.features;
            console.log('✅ Cuadras cargadas:', cuadras.value.length);
        }
    } catch (err: any) {
        console.error('❌ Error al cargar cuadras:', err);
    }
};

const cargarTerrenos = async () => {
    try {
        const response = await axios.get(
            `/api/mapa/proyectos/${props.proyectoId}/terrenos`,
        );
        if (response.data?.type === 'FeatureCollection') {
            terrenos.value = response.data.features;
            console.log('✅ Terrenos cargados:', terrenos.value.length);
        }
    } catch (err: any) {
        console.error('❌ Error al cargar terrenos:', err);
    }
};

const limpiarCapas = () => {
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

    if (map) {
        map.eachLayer((layer) => {
            if (layer instanceof L.Tooltip) {
                map?.removeLayer(layer);
            }
        });
    }
};

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

            const bounds = (layer as L.Polygon).getBounds();
            const center = bounds.getCenter();

            const label = L.tooltip({
                permanent: true,
                direction: 'center',
                className: 'polygon-label barrio-label',
            })
                .setLatLng(center)
                .setContent(
                    `<div style="font-weight: 700; font-size: 14px;">${props.nombre}</div>`,
                );

            if (map) {
                label.addTo(map);
            }
        },
    }).addTo(map);

    currentZoomLevel.value = 'barrios';
};

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

            const bounds = (layer as L.Polygon).getBounds();
            const center = bounds.getCenter();

            const label = L.tooltip({
                permanent: true,
                direction: 'center',
                className: 'polygon-label cuadra-label',
            })
                .setLatLng(center)
                .setContent(
                    `<div style="font-weight: 700; font-size: 12px;">${props.nombre}</div>`,
                );

            if (map) {
                label.addTo(map);
            }
        },
    }).addTo(map);

    currentZoomLevel.value = 'cuadras';
};

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
            const bgColor = isDark.value ? '#1f2937' : '#ffffff';
            const textColor = isDark.value ? '#f3f4f6' : '#111827';
            const mutedColor = isDark.value ? '#9ca3af' : '#6b7280';

            const popupContent = `
        <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; min-width: 300px; background: ${bgColor}; border-radius: 16px; overflow: hidden;">
          <!-- Header con gradiente -->
          <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); padding: 20px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: 500px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <h3 style="margin: 0; font-size: 22px; font-weight: 900; color: white; position: relative; z-index: 1;">
              ${props.codigo || `Terreno #${props.id}`}
            </h3>
          </div>

          <!-- Content -->
          <div style="padding: 20px;">
            <!-- Badges -->
            <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
              <div style="background: ${getEstadoBadgeColor(props.estado)}; color: white; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <span style="width: 6px; height: 6px; background: white; border-radius: 50%; display: inline-block;"></span>
                ${getEstadoTexto(props.estado)}
              </div>
              <div style="background: ${props.categoria_color}; color: white; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                ${props.categoria}
              </div>
            </div>

            <!-- Info Section -->
            <div style="background: ${isDark.value ? '#374151' : '#f9fafb'}; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
              <div style="display: flex; align-items: start; gap: 12px; margin-bottom: 12px;">
                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                </div>
                <div style="flex: 1;">
                  <p style="margin: 0; font-size: 11px; font-weight: 600; color: ${mutedColor}; text-transform: uppercase; letter-spacing: 0.5px;">Ubicación</p>
                  <p style="margin: 4px 0 0 0; font-size: 14px; font-weight: 600; color: ${textColor};">${props.ubicacion}</p>
                </div>
              </div>
              
              <div style="display: flex; align-items: start; gap: 12px;">
                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #8b5cf6, #ec4899); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18"/>
                    <path d="M9 21V9"/>
                  </svg>
                </div>
                <div style="flex: 1;">
                  <p style="margin: 0; font-size: 11px; font-weight: 600; color: ${mutedColor}; text-transform: uppercase; letter-spacing: 0.5px;">Superficie</p>
                  <p style="margin: 4px 0 0 0; font-size: 14px; font-weight: 600; color: ${textColor};">${props.superficie}</p>
                </div>
              </div>
            </div>

            <!-- Pricing Section -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 16px; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
              <div style="margin-bottom: 12px;">
                <p style="margin: 0; font-size: 11px; font-weight: 600; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px;">Precio de Venta</p>
                <p style="margin: 4px 0 0 0; font-size: 24px; font-weight: 900;">$${Number(props.precio_venta).toLocaleString()}</p>
              </div>
              
              <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                  <p style="margin: 0; font-size: 10px; font-weight: 600; opacity: 0.8; text-transform: uppercase;">Cuota Inicial</p>
                  <p style="margin: 4px 0 0 0; font-size: 16px; font-weight: 700;">$${Number(props.cuota_inicial).toLocaleString()}</p>
                </div>
                <div>
                  <p style="margin: 0; font-size: 10px; font-weight: 600; opacity: 0.8; text-transform: uppercase;">Cuota Mensual</p>
                  <p style="margin: 4px 0 0 0; font-size: 16px; font-weight: 700;">$${Number(props.cuota_mensual).toLocaleString()}</p>
                </div>
              </div>
              
            </div>

            <div style="text-align: center; margin-top: 20px;">
              <button 
                id="btn-info-${props.id}" 
                data-terreno-id="${props.id}"
                style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; font-weight: 700; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; transition: 0.3s; font-size: 14px; box-shadow: 0 4px 10px rgba(99,102,241,0.4); width:300px">
                Quiero mas información
              </button>
            </div>
              
          </div>
          
        </div>
      `;

           layer.bindPopup(popupContent, {
                maxWidth: 350,
                className: 'custom-popup',
            });

            // ⭐ AGREGAR: Event listener cuando se abre el popup
            layer.on('popupopen', () => {
                idTerrenoSeleccionado.value = props.id;
                const button = document.getElementById(`btn-info-${props.id}`);
                if (button) {
                    button.addEventListener('click', () => {
                        console.log(router)
                        router.get(`/ventas/${props.id}`);
                    });
                }
            });

            // Label permanente
            const bounds = (layer as L.Polygon).getBounds();
            const center = bounds.getCenter();

            const label = L.tooltip({
                permanent: true,
                direction: 'center',
                className: 'polygon-label terreno-label',
            })
                .setLatLng(center)
                .setContent(
                    `<div style="font-weight: 700; font-size: 11px;">${props.codigo}</div>`,
                );

            if (map) {
                label.addTo(map);
            }
        },
    }).addTo(map);

    currentZoomLevel.value = 'terrenos';
};


const actualizarCapaPorZoom = () => {
    if (!map) return;

    const zoom = map.getZoom();
    console.log('🔍 Zoom actual:', zoom);

    if (zoom < 14) {
        if (currentZoomLevel.value !== 'barrios') {
            dibujarBarrios();
        }
    } else if (zoom >= 14 && zoom < 17) {
        if (currentZoomLevel.value !== 'cuadras') {
            dibujarCuadras();
        }
    } else {
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
    if (proyecto.value?.poligono && map) {
        const poligonoGeoJSON =
            typeof proyecto.value.poligono === 'string'
                ? JSON.parse(proyecto.value.poligono)
                : proyecto.value.poligono;

        const proyectoLayer = L.geoJSON(poligonoGeoJSON);
        const bounds = proyectoLayer.getBounds();

        if (bounds.isValid()) {
            // Vuela hacia el proyecto sin límites
            map.flyToBounds(bounds, {
                padding: [50, 50],
                duration: 1.5,
                easeLinearity: 0.25,
            });

            // Luego de la animación, fija límites y zoom
            setTimeout(() => {
                if (map) {
                    map.setMaxBounds(bounds.pad(0.1));
                    map.setZoom(13);
                }
            }, 1600);
        }
    } else if (terrenos.value.length > 0) {
        const geojsonObject: GeoJSON.FeatureCollection = {
            type: 'FeatureCollection',
            features: terrenos.value,
        };
        const tempLayer = L.geoJSON(geojsonObject);
        const bounds = tempLayer.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, {
                padding: [50, 50],
                maxZoom: 11,
                duration: 1.5,
            });
        }
    } else if (cuadras.value.length > 0) {
        const geojson: GeoJSON.FeatureCollection = {
            type: 'FeatureCollection',
            features: cuadras.value,
        };
        const tempLayer = L.geoJSON(geojson);
        const bounds = tempLayer.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, {
                padding: [50, 50],
                maxZoom: 11,
                duration: 1.5,
            });
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

const mostrarPanelProyecto = ref(false);
const mostrarLeyenda = ref(false);

// Función para cerrar paneles al hacer click fuera
const cerrarPaneles = () => {
    mostrarPanelProyecto.value = false;
    mostrarLeyenda.value = false;
};

onUnmounted(() => {
    if (map) {
        map.off('zoomend', actualizarCapaPorZoom);
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div class="relative h-screen w-full" @click="cerrarPaneles">
        <div
            v-if="loading"
            class="absolute top-4 left-1/2 z-[1000] w-[90%] -translate-x-1/2 transform sm:top-6 sm:w-auto"
        >
            <div
                class="relative overflow-hidden rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-4 py-3 shadow-2xl sm:rounded-2xl sm:px-8 sm:py-4"
            >
                <div class="absolute inset-0 bg-white/10"></div>
                <div class="relative flex items-center gap-2 sm:gap-3">
                    <div
                        class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-white/20 sm:h-8 sm:w-8"
                    >
                        <i
                            class="pi pi-spin pi-spinner text-sm text-white sm:text-lg"
                        ></i>
                    </div>
                    <p class="text-sm font-bold text-white sm:text-base">
                        Cargando mapa...
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="error"
            class="absolute top-4 left-1/2 z-[1000] w-[90%] -translate-x-1/2 transform sm:top-6 sm:max-w-md"
        >
            <div
                class="relative overflow-hidden rounded-xl border-2 border-red-300 bg-gradient-to-r from-red-50 to-orange-50 px-4 py-3 shadow-2xl sm:rounded-2xl sm:px-6 sm:py-4 dark:border-red-700 dark:from-red-900/30 dark:to-orange-900/30"
            >
                <div
                    class="absolute top-0 right-0 -mt-12 -mr-12 h-24 w-24 rounded-full bg-red-200 opacity-20 sm:-mt-16 sm:-mr-16 sm:h-32 sm:w-32 dark:bg-red-800"
                ></div>
                <div class="relative flex items-center gap-2 sm:gap-3">
                    <div
                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-500 shadow-lg sm:h-10 sm:w-10 sm:rounded-xl"
                    >
                        <i
                            class="pi pi-exclamation-triangle text-base text-white sm:text-xl"
                        ></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm font-bold text-red-800 sm:text-base dark:text-red-200"
                        >
                            Error
                        </p>
                        <p
                            class="truncate text-xs text-red-700 sm:text-sm dark:text-red-300"
                        >
                            {{ error }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="proyecto"
            class="absolute top-4 right-5 z-[1000] sm:top-6"
            @click.stop
        >
            <button
                v-if="!mostrarPanelProyecto"
                @click="mostrarPanelProyecto = true"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl transition-transform hover:scale-110 sm:h-14 sm:w-14"
            >
                <i class="pi pi-info-circle text-xl text-white sm:text-2xl"></i>
            </button>

            <div
                v-else
                class="animate-scale-in w-[calc(100vw-16px)] sm:w-auto sm:max-w-sm"
            >
                <div
                    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl sm:rounded-2xl sm:border-2 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-4 py-3 sm:px-6 sm:py-4"
                    >
                        <div
                            class="absolute top-0 right-0 -mt-12 -mr-12 h-24 w-24 rounded-full bg-white/10 sm:-mt-16 sm:-mr-16 sm:h-32 sm:w-32"
                        ></div>
                        <div class="relative flex items-center justify-between">
                            <div class="min-w-0 flex-1 pr-2">
                                <h3
                                    class="mb-1 truncate text-base font-black text-white sm:text-xl"
                                >
                                    {{ proyecto.nombre }}
                                </h3>
                                <div
                                    class="flex items-center gap-2 text-purple-100"
                                >
                                    <i
                                        class="pi pi-map-marker flex-shrink-0 text-xs sm:text-sm"
                                    ></i>
                                    <p
                                        class="truncate text-xs font-medium sm:text-sm"
                                    >
                                        {{ proyecto.ubicacion }}
                                    </p>
                                </div>
                            </div>

                            <button
                                @click="mostrarPanelProyecto = false"
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 transition-colors hover:bg-white/30"
                            >
                                <i class="pi pi-times text-sm text-white"></i>
                            </button>
                        </div>
                    </div>

                    <div class="p-3 sm:p-6">
                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            <div
                                class="rounded-lg border border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-2 text-center sm:rounded-xl sm:border-2 sm:p-3 dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
                            >
                                <div
                                    class="mb-0.5 text-lg font-black text-green-600 sm:mb-1 sm:text-2xl dark:text-green-400"
                                >
                                    {{ proyecto.terrenos_disponibles }}
                                </div>
                                <div
                                    class="text-[10px] leading-tight font-semibold text-gray-600 uppercase sm:text-xs dark:text-gray-400"
                                >
                                    Disponibles
                                </div>
                            </div>
                            <div
                                class="rounded-lg border border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 p-2 text-center sm:rounded-xl sm:border-2 sm:p-3 dark:border-orange-800 dark:from-orange-900/20 dark:to-amber-900/20"
                            >
                                <div
                                    class="mb-0.5 text-lg font-black text-orange-600 sm:mb-1 sm:text-2xl dark:text-orange-400"
                                >
                                    {{ proyecto.terrenos_reservados }}
                                </div>
                                <div
                                    class="text-[10px] leading-tight font-semibold text-gray-600 uppercase sm:text-xs dark:text-gray-400"
                                >
                                    Reservados
                                </div>
                            </div>
                            <div
                                class="rounded-lg border border-red-200 bg-gradient-to-br from-red-50 to-rose-50 p-2 text-center sm:rounded-xl sm:border-2 sm:p-3 dark:border-red-800 dark:from-red-900/20 dark:to-rose-900/20"
                            >
                                <div
                                    class="mb-0.5 text-lg font-black text-red-600 sm:mb-1 sm:text-2xl dark:text-red-400"
                                >
                                    {{ proyecto.terrenos_vendidos }}
                                </div>
                                <div
                                    class="text-[10px] leading-tight font-semibold text-gray-600 uppercase sm:text-xs dark:text-gray-400"
                                >
                                    Vendidos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="categorias.length > 0"
            class="absolute top-22 right-5 z-[1000]"
            @click.stop
        >
            <button
                v-if="!mostrarLeyenda"
                @click="mostrarLeyenda = true"
                class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl transition-transform hover:scale-110 sm:h-14 sm:w-14"
            >
                <i class="pi pi-tag text-xl text-white sm:text-2xl"></i>
            </button>

            <div
                v-else
                class="animate-scale-in w-[calc(100vw-16px)] sm:w-auto sm:max-w-xs"
            >
                <div
                    class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl sm:rounded-2xl sm:border-2 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-4 py-3 sm:px-6 sm:py-4"
                    >
                        <div
                            class="absolute top-0 right-0 -mt-10 -mr-10 h-20 w-20 rounded-full bg-white/10 sm:-mt-12 sm:-mr-12 sm:h-24 sm:w-24"
                        ></div>
                        <div class="relative flex items-center justify-between">
                            <div class="flex flex-1 items-center gap-2">
                                <div
                                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 sm:h-8 sm:w-8"
                                >
                                    <i class="pi pi-tag text-sm text-white"></i>
                                </div>
                                <h4
                                    class="text-sm font-black text-white sm:text-base"
                                >
                                    Categorías
                                </h4>
                            </div>

                            <button
                                @click="mostrarLeyenda = false"
                                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white/20 transition-colors hover:bg-white/30"
                            >
                                <i class="pi pi-times text-sm text-white"></i>
                            </button>
                        </div>
                    </div>

                    <div
                        class="max-h-60 space-y-2 overflow-y-auto p-3 sm:max-h-96 sm:space-y-3 sm:p-6"
                    >
                        <div
                            v-for="categoria in categorias"
                            :key="categoria.id"
                            class="group flex items-center gap-2 rounded-lg bg-gray-50 p-2 transition-colors hover:bg-gray-100 sm:gap-3 sm:rounded-xl sm:p-3 dark:bg-gray-700/50 dark:hover:bg-gray-700"
                        >
                            <div
                                class="h-8 w-8 flex-shrink-0 rounded-md shadow-md transition-transform group-hover:scale-110 sm:h-10 sm:w-10 sm:rounded-lg"
                                :style="{ backgroundColor: categoria.color }"
                            ></div>
                            <div class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-xs font-bold text-gray-800 sm:text-sm dark:text-gray-200"
                                >
                                    {{ categoria.nombre }}
                                </span>
                                <span
                                    class="text-[10px] text-gray-500 sm:text-xs dark:text-gray-400"
                                >
                                    {{ categoria.total_terrenos }} terrenos
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div ref="mapContainer" class="h-full w-full">
            
        </div>
    </div>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
    padding: 0 !important;
    overflow: hidden;
}

:deep(.leaflet-popup-content) {
    margin: 0 !important;
    width: auto !important;
}

:deep(.leaflet-popup-tip) {
    display: none;
}

:deep(.custom-popup .leaflet-popup-close-button) {
    color: white !important;
    font-size: 24px !important;
    font-weight: bold !important;
    padding: 8px 12px !important;
    opacity: 0.8;
    transition: opacity 0.2s;
    right: 20px;
}

:deep(.custom-popup .leaflet-popup-close-button:hover) {
    opacity: 1;
}

:deep(.leaflet-popup-content-wrapper) {
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

:deep(.leaflet-popup-content) {
    margin: 0;
}

:deep(.leaflet-popup-tip) {
    border-radius: 2px;
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
        1px 1px 2px rgba(255, 255, 255, 0.9),
        -1px -1px 2px rgba(255, 255, 255, 0.9),
        1px -1px 2px rgba(255, 255, 255, 0.9),
        -1px 1px 2px rgba(255, 255, 255, 0.9);
}

:deep(.cuadra-label) {
    color: #000000;
    font-size: 12px;
    font-weight: 700;
    text-shadow:
        1px 1px 2px rgba(255, 255, 255, 0.9),
        -1px -1px 2px rgba(255, 255, 255, 0.9),
        1px -1px 2px rgba(255, 255, 255, 0.9),
        -1px 1px 2px rgba(255, 255, 255, 0.9);
}

:deep(.terreno-label) {
    color: #000000 !important;
    font-size: 11px;
    font-weight: 700;
    text-shadow:
        1px 1px 2px rgba(255, 255, 255, 0.9),
        -1px -1px 2px rgba(255, 255, 255, 0.9),
        1px -1px 2px rgba(255, 255, 255, 0.9),
        -1px 1px 2px rgba(255, 255, 255, 0.9);
}

/* Scrollbar personalizado para categorías */
:deep(.overflow-y-auto::-webkit-scrollbar) {
    width: 6px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-track) {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-thumb) {
    background: linear-gradient(180deg, #6366f1, #a855f7, #ec4899);
    border-radius: 10px;
}

:deep(.overflow-y-auto::-webkit-scrollbar-thumb:hover) {
    background: linear-gradient(180deg, #4f46e5, #9333ea, #db2777);
}
</style>
