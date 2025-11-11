<script setup lang="ts">
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import { useForm } from '@inertiajs/vue3';
import L from 'leaflet';

import 'leaflet/dist/leaflet.css';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

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
    idcuadra: number;
    nombre: string;
    numero_terreno: number;
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
const terrenosOriginales = ref<Terreno[]>([]);

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

const barrioSeleccionado = ref<number | null>(null);
const cuadraSeleccionada = ref<number | null>(null);
const terrenoSeleccionado = ref<number | null>(null);

const mostrarPoligonosFiltrados = async ({
    tipo,
    id,
}: {
    tipo: string;
    id: number;
}) => {
    if (!map) return;

    map.eachLayer((layer) => {
        if (!(layer instanceof L.TileLayer)) map!.removeLayer(layer);
    });

    try {
        const response = await fetch(
            `/polygon-editor/poligonos/${props.selectedProyectoId}`,
        );
        const data = await response.json();

        if (!data.success) return;

        const { barrios, cuadras, terrenos } = data.data;
        let seleccionados: any[] = [];

        if (tipo === 'barrio') {
            const barrio = barrios.find((b: any) => b.id === id);
            const cuadrasBarrio = cuadras.filter((c: any) => c.idbarrio === id);
            const terrenosBarrio = terrenos.filter((t: any) =>
                cuadrasBarrio.some((c: any) => c.id === t.idcuadra),
            );

            seleccionados = [
                {
                    tipo: 'barrio',
                    geometry: barrio.geometry,
                    nombre: barrio.nombre,
                    id: barrio.id,
                },
                ...cuadrasBarrio.map((c: any) => ({
                    tipo: 'cuadra',
                    geometry: c.geometry,
                    nombre: c.nombre,
                    id: c.id,
                })),
                ...terrenosBarrio.map((t: any) => ({
                    tipo: 'terreno',
                    geometry: t.geometry,
                    nombre: `Terreno ${t.numero}`,
                    id: t.id,
                })),
            ];
        }

        if (tipo === 'cuadra') {
            const cuadra = cuadras.find((c: any) => c.id === id);
            const terrenosCuadra = terrenos.filter(
                (t: any) => t.idcuadra === id,
            );

            seleccionados = [
                {
                    tipo: 'cuadra',
                    geometry: cuadra.geometry,
                    nombre: cuadra.nombre,
                    id: cuadra.id,
                },
                ...terrenosCuadra.map((t: any) => ({
                    tipo: 'terreno',
                    geometry: t.geometry,
                    nombre: `Terreno ${t.numero}`,
                    id: t.id,
                })),
            ];
        }

        if (tipo === 'terreno') {
            const terreno = terrenos.find((t: any) => t.id === id);
            if (terreno) {
                seleccionados = [
                    {
                        tipo: 'terreno',
                        geometry: terreno.geometry,
                        nombre: `Terreno ${terreno.numero}`,
                        id: terreno.id,
                    },
                ];
            }
        }

        const group = L.featureGroup();
        seleccionados.forEach((item) => {
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
                if (selectedLayer) selectedLayer.pm.disable();
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

        if (group.getLayers().length > 0) {
            map.fitBounds(group.getBounds(), { padding: [20, 20] });
        }
    } catch (error) {
        console.error('Error al mostrar polígonos filtrados:', error);
    }
};

const seleccionarBarrio = async (barrioId: number) => {
    if (barrioSeleccionado.value === barrioId) {
        barrioSeleccionado.value = null;
        cuadraSeleccionada.value = null;
        terrenoSeleccionado.value = null;
        cuadras.value = [];
        terrenos.value = [];
        await cargarPoligonosGuardados(Number(props.selectedProyectoId));
        return;
    }

    barrioSeleccionado.value = barrioId;
    cuadraSeleccionada.value = null;
    terrenoSeleccionado.value = null;
    cuadras.value = [];
    terrenos.value = [];

    await getCuadras(barrioId);
    const cuadrasIds = cuadras.value.map((c) => c.id);
    terrenos.value = terrenosOriginales.value.filter((t) =>
        cuadrasIds.includes(t.idcuadra),
    );

    await mostrarPoligonosFiltrados({ tipo: 'barrio', id: barrioId });
};

const seleccionarCuadra = async (cuadraId: number) => {
    if (cuadraSeleccionada.value === cuadraId) {
        cuadraSeleccionada.value = null;
        terrenoSeleccionado.value = null;
        terrenos.value = [];
        await cargarPoligonosGuardados(Number(props.selectedProyectoId));
        return;
    }

    cuadraSeleccionada.value = cuadraId;
    terrenoSeleccionado.value = null;

    terrenos.value = terrenosOriginales.value.filter(
        (t) => t.idcuadra === cuadraId,
    );

    await mostrarPoligonosFiltrados({ tipo: 'cuadra', id: cuadraId });
};

const seleccionarTerreno = async (terrenoID: number) => {
    if (terrenoSeleccionado.value === terrenoID) {
        terrenoSeleccionado.value = null;
        await cargarPoligonosGuardados(Number(props.selectedProyectoId));
        return;
    }

    terrenoSeleccionado.value = terrenoID;
    await mostrarPoligonosFiltrados({ tipo: 'terreno', id: terrenoID });
};

const initMap = () => {
    map = L.map('map').setView([-17.0, -65.0], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 22,
        maxNativeZoom: 19,
    }).addTo(map);

    map.pm.addControls({
        position: 'topleft',
        drawPolygon: false,
        editMode: false,
    });
};

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
                <button id="editar-btn-${item.id}" style="
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
                        const btn = document.getElementById(
                            `editar-btn-${item.id}`,
                        );
                        if (btn) {
                            btn.addEventListener('click', () => {
                                activarEdicion(e.target, item);
                                e.target.closePopup();
                            });
                        }
                    }, 100);
                });
            });

            if (group.getLayers().length > 0) {
                map.fitBounds(group.getBounds(), { padding: [20, 20] });
            }
        }
    } catch (error) {
        console.error('Error al cargar los polígonos guardados:', error);
    }
};

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

async function getBarrios(idProyecto: number) {
    try {
        const response = await fetch(`/barrios/proyecto/${idProyecto}`);
        const data = await response.json();
        if (data.success && Array.isArray(data.barrios)) {
            barrios.value = data.barrios;
            console.log('Barrios asignados:', barrios.value);
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
            cuadras.value = data.cuadras;
    } catch (error) {
        console.error('Error al cargar las cuadras:', error);
    }
}

async function getTerrenos(idProyecto: number) {
    try {
        const response = await fetch(`/api/proyectos/${idProyecto}/terrenos`);
        const data = await response.json();
        if (Array.isArray(data)) {
            terrenos.value = data;
            console.log('terrenos ', terrenos.value);
            terrenosOriginales.value = data;
        }
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

    const token = document
        .querySelector('meta[name="csrf-token"]')!
        .getAttribute('content')!;

    const response = await fetch('/polygon-editor/update-poligono', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            tipo: poligonoEditado.tipo,
            id: poligonoEditado.id,
            poligono: poligonoEditado.geometry,
        }),
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
    const idProyecto = Number(props.selectedProyectoId);
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    // Evitar duplicación si el mapa ya está montado
    if (map) {
        map.remove();
        map = null;
    }

    // Crear mapa centrado en Bolivia
    map = L.map(mapContainer).setView([-17.3895, -66.3167], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 22,
        maxNativeZoom: 19,
    }).addTo(map);

    // Cargar datos en paralelo
    await Promise.all([
        getBarrios(idProyecto),
        getTerrenos(idProyecto),
        cargarPoligonosGuardados(idProyecto),
    ]);

    // Cargar las cuadras de todos los barrios (de forma secuencial)
    for (const b of barrios.value) {
        await getCuadras(b.id);
    }

    // ==============================
    // CENTRAR MAPA SEGÚN DATOS
    // ==============================

    // Intentar primero con terrenos
    // --- Intentar primero con terrenos ---
    const geojsonTerrenos: GeoJSON.Feature[] = terrenos.value
        .filter((t) => t.geometry)
        .map((t) => ({
            type: 'Feature' as const,
            geometry: t.geometry as GeoJSON.Geometry,
            properties: {
                id: t.id,
                nombre: t.nombre ?? '',
                idcuadra: t.idcuadra,
                numero_terreno: t.numero_terreno,
            },
        }));

    if (geojsonTerrenos.length > 0) {
        const featureCollection: GeoJSON.FeatureCollection = {
            type: 'FeatureCollection',
            features: geojsonTerrenos,
        };

        const layerTerrenos = L.geoJSON(featureCollection);
        const bounds = layerTerrenos.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 });
        }
    }
    // --- Si no hay terrenos, probar con cuadras ---
    else if (cuadras.value.some((c) => c.geometry)) {
        const geojsonCuadras: GeoJSON.Feature[] = cuadras.value
            .filter((c) => c.geometry)
            .map((c) => ({
                type: 'Feature' as const,
                geometry: c.geometry as GeoJSON.Geometry,
                properties: { id: c.id, nombre: c.nombre ?? '' },
            }));

        const featureCollection: GeoJSON.FeatureCollection = {
            type: 'FeatureCollection',
            features: geojsonCuadras,
        };

        const layerCuadras = L.geoJSON(featureCollection);
        const bounds = layerCuadras.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 });
        }
    }
    // --- Si tampoco hay cuadras, probar con barrios ---
    else if (barrios.value.some((b) => b.geometry)) {
        const geojsonBarrios: GeoJSON.Feature[] = barrios.value
            .filter((b) => b.geometry)
            .map((b) => ({
                type: 'Feature' as const,
                geometry: b.geometry as GeoJSON.Geometry,
                properties: { id: b.id, nombre: b.nombre ?? '' },
            }));

        const featureCollection: GeoJSON.FeatureCollection = {
            type: 'FeatureCollection',
            features: geojsonBarrios,
        };

        const layerBarrios = L.geoJSON(featureCollection);
        const bounds = layerBarrios.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, { padding: [50, 50], duration: 1.5 });
        }
    }

    // ==============================
    // CONTROL DE ZOOM Y LÍMITES
    // ==============================
    map.on('zoomend', () => {
        console.log('Zoom actual:', map?.getZoom());
    });

    // Establecer límites del mapa ligeramente ampliados
    setTimeout(() => {
        const currentBounds = map?.getBounds();
        if (currentBounds && currentBounds.isValid()) {
            map!.setMaxBounds(currentBounds.pad(0.1));
        }
    }, 1800);
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
                            class="flex cursor-pointer items-center justify-between font-bold text-indigo-400"
                        >
                            🏘️ Barrios ({{ barrios.length }})
                        </summary>
                        <ul class="mt-2 space-y-1 text-sm text-gray-300">
                            <li
                                v-for="b in barrios"
                                :key="b.id"
                                @click="seleccionarBarrio(b.id)"
                                :class="[
                                    'cursor-pointer rounded-md px-2 py-1 transition',
                                    barrioSeleccionado === b.id
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-700 hover:bg-gray-600',
                                ]"
                            >
                                {{ b.nombre }}
                            </li>
                        </ul>
                    </details>

                    <details
                        class="mb-4 rounded-lg border p-3 dark:border-gray-700"
                    >
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-purple-400"
                        >
                            🏗️ Cuadras ({{ cuadras.length }})
                        </summary>
                        <ul class="mt-2 space-y-1 text-sm text-gray-300">
                            <li
                                v-for="c in cuadras"
                                :key="c.id"
                                @click="seleccionarCuadra(c.id)"
                                :class="[
                                    'cursor-pointer rounded-md px-2 py-1 transition',
                                    cuadraSeleccionada === c.id
                                        ? 'bg-purple-600 text-white'
                                        : 'bg-gray-700 hover:bg-gray-600',
                                ]"
                            >
                                {{ c.nombre }}
                            </li>
                        </ul>
                    </details>

                    <details class="rounded-lg border p-3 dark:border-gray-700">
                        <summary
                            class="flex cursor-pointer items-center justify-between font-bold text-amber-400"
                        >
                            🏠 Terrenos ({{ terrenos.length }})
                        </summary>
                        <ul class="mt-2 space-y-1 text-sm text-gray-300">
                            <li
                                v-for="t in terrenos"
                                :key="t.id"
                                @click="seleccionarTerreno(t.id)"
                                :class="[
                                    'cursor-pointer rounded-md px-2 py-1 transition',
                                    terrenoSeleccionado === t.id
                                        ? 'bg-amber-400 font-bold text-white'
                                        : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                                ]"
                            >
                                Terreno {{ t.numero_terreno }}
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
