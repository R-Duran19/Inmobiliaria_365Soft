<script setup lang="ts">
import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import { useForm } from '@inertiajs/vue3';

import 'leaflet/dist/leaflet.css';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Proyecto {
    id: number;
    nombre: string;
}

// Extiende la interfaz de Leaflet para incluir los métodos de Geoman
declare module 'leaflet' {
    interface Layer {
        pm?: any;
    }
}
const form = useForm({
    tipo: '',
    id: 0,
    poligono: {},
});

interface Barrio {
    id: number;
    nombre: string;
    geometry?: any;
}
interface Cuadra {
    id: number;
    nombre: string;
    geometry?: any;
}
interface Terreno {
    id: number;
    numero?: string;
    geometry?: any;
}

const props = defineProps({
    selectedProyectoId: {
        type: [Number, String],
        required: true,
    },
});

const editarActivo = ref(false);
let poligonoEditado: { tipo: string; id: number; geometry: any } | null = null;

const barrios = ref<Barrio[]>([]);
const cuadras = ref<Cuadra[]>([]);
const terrenos = ref<Terreno[]>([]);

let map: L.Map | null = null;
let selectedLayer: L.Layer | null = null;

const getColorByType = (tipo: string) => {
    const colors: Record<string, string> = {
        barrio: '#3b82f6',
        cuadra: '#8b5cf6',
        terreno: '#f59e0b',
    };
    return colors[tipo] || '#6b7280';
};

// Inicializa el mapa
const initMap = () => {
    map = L.map('map').setView([-17.0, -65.0], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 22,
        maxNativeZoom: 19,
    }).addTo(map);

    // Desactivar herramientas de dibujo inicialmente
    map.pm.addControls({
        position: 'topleft',
        drawPolygon: false,
        editMode: false,
    });
};

// Carga y dibuja los polígonos
const cargarPoligonosGuardados = async (idProyecto: number) => {
    try {
        const response = await fetch(`/polygon-editor/poligonos/${idProyecto}`);
        const data = await response.json();
        if (data.success && map) {
            const { barrios, cuadras, terrenos } = data.data;
            const todos = [
                ...barrios.map((b: any) => ({
                    tipo: 'barrio',
                    geometry: b.geometry,
                    nombre: b.nombre,
                    id: b.id,
                })),
                ...cuadras.map((c: any) => ({
                    tipo: 'cuadra',
                    geometry: c.geometry,
                    nombre: c.nombre,
                    id: c.id,
                })),
                ...terrenos.map((t: any) => ({
                    tipo: 'terreno',
                    geometry: t.geometry,
                    nombre: `Terreno ${t.numero}`,
                    id: t.id,
                })),
            ];

            console.log('📦 Polígonos recibidos:', {
                barrios,
                cuadras,
                terrenos,
            });

            // Crear un grupo de capas para calcular los bounds
            const group = L.featureGroup();

            todos.forEach((item) => {
                if (!item.id)
                    console.warn('⚠️ Polígono sin ID detectado:', item);
                const layer = L.geoJSON(item.geometry, {
                    style: {
                        color: getColorByType(item.tipo),
                        fillColor: getColorByType(item.tipo),
                        fillOpacity: 0.5,
                        weight: 2,
                    },
                }).addTo(map!);

                group.addLayer(layer);

                layer.on('click', (e) => {
                    if (selectedLayer) {
                        selectedLayer.pm.disable();
                    }
                    selectedLayer = e.target;
                    const popupContent = `
            <div style="text-align:center;">
              <strong>${item.tipo.toUpperCase()}</strong><br>
              ${item.nombre}<br>
              <button id="editar-btn" style="
                margin-top:4px;
                background:#4f46e5;
                color:white;
                border:none;
                padding:4px 8px;
                border-radius:4px;
                cursor:pointer;">Editar</button>
            </div>`;
                    e.target.bindPopup(popupContent).openPopup();
                    setTimeout(() => {
                        const btn = document.getElementById('editar-btn');
                        if (btn) {
                            btn.addEventListener('click', () =>
                                activarEdicion(e.target, item),
                            );
                        }
                    }, 100);
                });
            });

            // Ajustar el zoom para que todos los polígonos sean visibles
            if (group.getLayers().length > 0) {
                map.fitBounds(group.getBounds(), { padding: [20, 20] });
            }
        }
    } catch (error) {
        console.error('Error al cargar los polígonos guardados:', error);
    }
};

// Activa el modo edición para un polígono
const activarEdicion = (layer: any, item: any) => {
    console.log('🧩 Activando edición para:', { layer, item });

    layer.pm.enable({ allowSelfIntersection: false });

    layer.on('pm:edit', () => {
        const geojson = layer.toGeoJSON();
        console.log('📦 Resultado de layer.toGeoJSON():', geojson);

        const geometry =
            geojson.type === 'FeatureCollection'
                ? geojson.features[0].geometry
                : geojson.geometry;

        poligonoEditado = {
            tipo: item.tipo,
            id: item.id,
            geometry,
        };

        console.log('🟢 Polígono listo para guardar:', poligonoEditado);
        editarActivo.value = true;
    });
};

// Cargar datos del backend
async function getBarrios(idProyecto: number) {
    try {
        const response = await fetch(`/barrios/proyecto/${idProyecto}`);
        const data = await response.json();
        if (data.success && Array.isArray(data.barrios)) {
            // Asigna directamente los datos de la API a barrios.value
            barrios.value = data.barrios;
            console.log("Barrios asignados:", barrios.value); // Verifica que los IDs estén presentes
        }
    } catch (error) {
        console.error('Error al cargar los barrios:', error);
    }
}



async function getCuadras(idBarrio: number) {
    try {
        const response = await fetch(`/cuadras/barrio/${idBarrio}`);
        const data = await response.json();
        if (data.success && Array.isArray(data.cuadras))
            cuadras.value.push(...data.cuadras);
    } catch (error) {
        console.error('Error al cargar las cuadras:', error);
    }
}

async function getTerrenos(idProyecto: number) {
    try {
        const response = await fetch(`/api/proyectos/${idProyecto}/terrenos`);
        const data = await response.json();
        if (Array.isArray(data)) terrenos.value = data;
    } catch (error) {
        console.error('Error al cargar los terrenos:', error);
    }
}

async function guardarPoligonoEditado(tipo: string, id: number, poligono: any) {
    try {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        const response = await fetch('/polygon-editor/update-poligono', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({ tipo, id, poligono }),
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
        } else {
            console.error(data.message || data.errors);
        }
    } catch (error) {
        console.error('Error al guardar polígono:', error);
    }
}

async function guardarCambios() {
    if (!poligonoEditado) return;

    const token = document.querySelector('meta[name="csrf-token"]')!.getAttribute('content')!;

    const response = await fetch('/polygon-editor/update-poligono', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            tipo: poligonoEditado.tipo,
            id: poligonoEditado.id,
            poligono: poligonoEditado.geometry
        })
    });

    const data = await response.json();

    if (data.success) {
        alert('✅ ' + data.message);
        editarActivo.value = false;
    } else {
        alert('⚠️ Error: ' + (data.message || 'No se pudo guardar'));
    }
}





onMounted(async () => {
    initMap();
    const idProyecto = Number(props.selectedProyectoId);

    await getBarrios(idProyecto);
    await getTerrenos(idProyecto);

    for (const b of barrios.value) {
        await getCuadras(b.id);
    }

    await cargarPoligonosGuardados(idProyecto);
});
</script>

<template>
    <AppLayout>
        <Head title="Mapa del Proyecto" />

        <div class="flex h-screen flex-col">
            <div
                class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-4 text-white shadow-lg"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm"
                    >
                        <i class="pi pi-map text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Mapa del Proyecto</h1>
                        <p class="text-sm text-purple-100">
                            Visualiza y edita polígonos del proyecto
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden">
                <div
                    class="w-96 overflow-y-auto border-r border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                    <details
                        open
                        class="mb-4 rounded-lg border p-3 dark:border-gray-700"
                    >
                        <summary
                            class="cursor-pointer font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            🏘️ Barrios ({{ barrios.length }})
                        </summary>
                        <ul
                            class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <li
                                v-for="b in barrios"
                                :key="b.id"
                                class="rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-700"
                            >
                                {{ b.nombre }}
                            </li>
                        </ul>
                    </details>

                    <details
                        open
                        class="mb-4 rounded-lg border p-3 dark:border-gray-700"
                    >
                        <summary
                            class="cursor-pointer font-bold text-purple-600 dark:text-purple-400"
                        >
                            🏗️ Cuadras ({{ cuadras.length }})
                        </summary>
                        <ul
                            class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <li
                                v-for="c in cuadras"
                                :key="c.id"
                                class="rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-700"
                            >
                                {{ c.nombre }}
                            </li>
                        </ul>
                    </details>

                    <details
                        open
                        class="rounded-lg border p-3 dark:border-gray-700"
                    >
                        <summary
                            class="cursor-pointer font-bold text-amber-600 dark:text-amber-400"
                        >
                            🏠 Terrenos ({{ terrenos.length }})
                        </summary>
                        <ul
                            class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300"
                        >
                            <li
                                v-for="t in terrenos"
                                :key="t.id"
                                class="rounded-md bg-gray-100 px-2 py-1 dark:bg-gray-700"
                            >
                                Terreno {{ t.numero ?? t.id }}
                            </li>
                        </ul>
                    </details>
                    <button
                        @click="guardarCambios"
                        :disabled="!editarActivo"
                        class="mt-4 w-full rounded-xl bg-indigo-600 px-4 py-2 text-white shadow-lg disabled:cursor-not-allowed disabled:bg-gray-400"
                    >
                        💾 Guardar cambios
                    </button>
                </div>

                <div class="relative flex-1">
                    <div id="map" class="h-full w-full"></div>
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
#map {
    z-index: 0;
}
summary::-webkit-details-marker {
    display: none;
}
summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>
