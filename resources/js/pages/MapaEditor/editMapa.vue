<script setup lang="ts">
import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

import 'leaflet/dist/leaflet.css';

import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, Ref, ref } from 'vue';


const toast = useToast();

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
    idbarrio:number;
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

const proyectoPoligonoLayer: Ref<L.Layer | null> = ref(null);


const editarProyectoActivo = ref(false);

const currentZoomLevel = ref<'barrios' | 'cuadras' | 'terrenos'>('barrios');

const editarActivo = ref(false);
let poligonoEditado: { tipo: string; id: number; geometry: any } | null = null;

const barrios = ref<Barrio[]>([]);
const cuadras = ref<Cuadra[]>([]);
const terrenos = ref<Terreno[]>([]);
const proyecto_ = ref<any[]>([]);
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
        const { barrios, cuadras, terrenos, proyecto } = data.data;
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
            const barrioAsociado = barrios.find((b: any) => b.id === cuadra.idbarrio);
            const terrenosCuadra = terrenos.filter((t: any) => t.idcuadra === id);

            seleccionados = [
                {
                    tipo: 'barrio',
                    geometry: barrioAsociado.geometry,
                    nombre: barrioAsociado.nombre,
                    id: barrioAsociado.id,
                    esAsociado: true, // Marca para aplicar estilo especial
                },
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
            console.log('llegando aqui: ' , terrenos)
            const terreno = terrenos.find((t: any) => t.id === id);
            console.log('terreno: fasfda', terreno)
            const cuadraAsociada = cuadras.find((c: any) => c.id === terreno.idcuadra);
            console.log('cuadrita: ', cuadraAsociada);
            console.log('cuadras:dfsad ', cuadras.value)
            const barrioAsociado = barrios.find((b: any) => b.id === cuadraAsociada.idbarrio);

            seleccionados = [
                {
                    tipo: 'barrio',
                    geometry: barrioAsociado.geometry,
                    nombre: barrioAsociado.nombre,
                    id: barrioAsociado.id,
                    esAsociado: true, // Marca para aplicar estilo especial
                },
                {
                    tipo: 'cuadra',
                    geometry: cuadraAsociada.geometry,
                    nombre: cuadraAsociada.nombre,
                    id: cuadraAsociada.id,
                    esAsociado: true, // Marca para aplicar estilo especial
                },
                {
                    tipo: 'terreno',
                    geometry: terreno.geometry,
                    nombre: `LT ${terreno.numero}`,
                    id: terreno.id,
                },
            ];
        }

        const group = L.featureGroup();
        seleccionados.forEach((item) => {
            const layer = L.geoJSON(item.geometry, {
                style: {
                    color: getColorByType(item.tipo),
                    fillColor: getColorByType(item.tipo),
                    fillOpacity: item.esAsociado ? 0 : 0.5, // Sin relleno si es asociado
                    weight: 2,
                },
            }).addTo(map!);

            layer.bindTooltip(item.nombre, {
                permanent: true,
                direction: 'center',
                className: `${item.tipo}-tooltip`,
            });

            (layer as any).tooltipType = item.tipo;
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
                    nombre: `LT ${t.numero}`,
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
                if (!item.id) console.warn('⚠️ Polígono sin ID detectado:', item);
                const layer = L.geoJSON(item.geometry, {
                    style: {
                        color: getColorByType(item.tipo),
                        fillColor: getColorByType(item.tipo),
                        fillOpacity: 0.5,
                        weight: 2,
                    },
                }).addTo(map!);
                group.addLayer(layer);

                layer.bindTooltip(item.nombre, {
                    permanent: true,
                    direction: 'center',
                    className: `${item.tipo}-tooltip`,
                });
                (layer as any).tooltipType = item.tipo;
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
                        const btn = document.getElementById(`editar-btn-${item.id}`);
                        if (btn) {
                            btn.addEventListener('click', () => {
                                activarEdicion(e.target, item);
                                e.target.closePopup();
                            });
                        }
                    }, 100);
                });
            });

            // Añadir el polígono del proyecto al mapa

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

async function getPoligonoProyecto(idProyecto: number) {
    try {
        const response = await fetch(`/polygon-editor/poligono/proyecto/${idProyecto}`);
        const data = await response.json();
        proyecto_.value = data.data.proyecto;

        console.log('proyecto final ', proyecto_.value)
    } catch (error) {
        console.error('Error al cargar los proy:', error);
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
const actualizarVisibilidadTooltips = (nivel: 'barrios' | 'cuadras' | 'terrenos') => {
    if (!map) return;
    map.eachLayer((layer: any) => {
        if (layer.tooltipType) {
            // Mostrar todos los tooltips
            if (layer.getTooltip) {
                const tooltipElement = layer.getTooltip()?.getElement();
                if (tooltipElement) {
                    tooltipElement.style.opacity = '1';
                }
            }

            // Ocultar el relleno de los polígonos que no corresponden al nivel actual
            if (nivel === 'barrios' && layer.tooltipType !== 'barrio') {
                layer.setStyle({ fillOpacity: 0 });
            } else if (nivel === 'cuadras' && layer.tooltipType !== 'cuadra') {
                layer.setStyle({ fillOpacity: 0 });
            } else if (nivel === 'terrenos' && layer.tooltipType !== 'terreno') {
                layer.setStyle({ fillOpacity: 0 });
            } else {
                // Restaurar el relleno para los polígonos del nivel actual
                layer.setStyle({ fillOpacity: 0.5 });
            }
        }
    });
};


const toggleEditarProyecto = () => {
    if (!proyectoPoligonoLayer.value) {
        toast.add({
            severity: 'warn',
            summary: 'Advertencia',
            detail: 'No hay polígono de proyecto cargado',
            life: 3000,
        });
        return;
    }

    editarProyectoActivo.value = !editarProyectoActivo.value;

    if (editarProyectoActivo.value) {
        (proyectoPoligonoLayer.value as any).pm.enable({ allowSelfIntersection: false });
        (proyectoPoligonoLayer.value as any).on('pm:edit', () => {
            const geojson = (proyectoPoligonoLayer.value as any).toGeoJSON();
            const geometry = geojson.type === 'FeatureCollection'
                ? geojson.features[0].geometry
                : geojson.geometry;
            guardarPoligonoProyecto(geometry);
        });
    } else {
        (proyectoPoligonoLayer.value as any).pm.disable();
    }
};


const guardarPoligonoProyecto = async (geometry: any) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`/proyectos/poligono/${Number(props.selectedProyectoId)}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({ poligono: geometry }),
        });
        const data = await response.json();
        if (data.success) {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Polígono del proyecto actualizado correctamente',
                life: 3000,
            });
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: data.message || 'No se pudo actualizar el polígono del proyecto',
                life: 3000,
            });
        }
    } catch (error) {
        console.error('Error al guardar el polígono del proyecto:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Fallo en la conexión con el servidor',
            life: 3000,
        });
    }
};

const cargarPoligonoProyecto = async (idProyecto: number) => {
    try {
        const response = await fetch(`/proyectos/poligono/${idProyecto}`);
        const data = await response.json();
        if (data.success && data.poligono) {
            if (proyectoPoligonoLayer.value) {
                map?.removeLayer(proyectoPoligonoLayer.value);
                proyectoPoligonoLayer.value = null;
            }

            const poligonoGeoJSON = typeof data.poligono === 'string' ? JSON.parse(data.poligono) : data.poligono;
            proyectoPoligonoLayer.value = L.geoJSON(poligonoGeoJSON, {
                style: {
                    color: '#22c55e',
                    fillColor: '#22c55e',
                    fillOpacity: 0.2,
                    weight: 3,
                }
            }).addTo(map!);

            if (editarProyectoActivo.value) {
                (proyectoPoligonoLayer.value as any).pm.enable({ allowSelfIntersection: false });
            }
        }
    } catch (error) {
        console.error('Error al cargar el polígono del proyecto:', error);
    }
};





onMounted(async () => {
    const idProyecto = Number(props.selectedProyectoId);
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    
    if (map) {
        map.remove();
        map = null;
    }

    
    map = L.map(mapContainer).setView([-17.3895, -66.3167], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 22,
        maxNativeZoom: 19,
    }).addTo(map);

    
    await Promise.all([
        getBarrios(idProyecto),
        getTerrenos(idProyecto),
        cargarPoligonosGuardados(idProyecto),
        getPoligonoProyecto(idProyecto)
    ]);

    
    for (const b of barrios.value) {
        await getCuadras(b.id);
    }

    console.log('proefdasfsday ', proyecto_)
    
    
    

    
    
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
    
    else if (cuadras.value.some((c) => c.geometry)) {
        const geojsonCuadras: GeoJSON.Feature[] = cuadras.value
            .filter((c) => c.geometry)
            .map((c) => ({
                type: 'Feature' as const,
                geometry: c.geometry as GeoJSON.Geometry,
                properties: { id: c.id, nombre: c.nombre ?? '' },
                idbarrio: c.idbarrio
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

    
    
    
    map.on('zoomend', () => {
        if (!map) return;
        const zoom = map.getZoom();
        console.log('🔍 Zoom actual:', zoom);

        if (zoom < 14) {
            if (currentZoomLevel.value !== 'barrios') {
                currentZoomLevel.value = 'barrios';
                actualizarVisibilidadTooltips('barrios');
            }
        } else if (zoom >= 14 && zoom < 17) {
            if (currentZoomLevel.value !== 'cuadras') {
                currentZoomLevel.value = 'cuadras';
                actualizarVisibilidadTooltips('cuadras');
            }
        } else {
            if (currentZoomLevel.value !== 'terrenos') {
                currentZoomLevel.value = 'terrenos';
                actualizarVisibilidadTooltips('terrenos');
            }
        }
    });

    // Dispara el evento zoomend al cargar el mapa
    setTimeout(() => map!.fire('zoomend'), 1000);
    await cargarPoligonoProyecto(idProyecto);
});
</script>

<template>
    <AppLayout>
        <Head title="Mapa del Proyecto" />

        <div class="flex h-screen flex-col">
            

            <div class="flex flex-1 overflow-hidden">
                
                <div
                    class="w-96 overflow-y-auto border-r border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
                >
                <h2
                        class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                    >
                        ✏️ Editar Mapas
                    </h2>
                    <div class="mt-4">
    <button
        @click="toggleEditarProyecto"
        :class="[
            'w-full rounded-lg px-4 py-2 font-bold text-white transition',
            editarProyectoActivo ? 'bg-red-600 hover:bg-red-700' : 'bg-purple-600 hover:bg-purple-700'
        ]"
    >
        {{ editarProyectoActivo ? '🚫 Detener Edición de Proyecto' : '✏️ Editar Polígono de Proyecto' }}
    </button>
</div>

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
                                LT {{ t.numero_terreno }}
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


:deep(.leaflet-tooltip) {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: #000000 !important;
    font-weight: 700 !important;
    text-shadow:
        1px 1px 2px rgba(255, 255, 255, 0.9),
        -1px -1px 2px rgba(255, 255, 255, 0.9),
        1px -1px 2px rgba(255, 255, 255, 0.9),
        -1px 1px 2px rgba(255, 255, 255, 0.9);
    pointer-events: none !important;
}


:deep(.leaflet-tooltip.barrio-tooltip) {
    font-size: 14px !important;
}

:deep(.leaflet-tooltip.cuadra-tooltip) {
    font-size: 12px !important;
}

:deep(.leaflet-tooltip.terreno-tooltip) {
    font-size: 11px !important;
}
</style>
