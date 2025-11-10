<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { terrenos as terrenosRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';

import ConfirmacionModal from '@/components/ui/confirmacionModal/ConfirmacionModal.vue';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import TerrenosTable from '@/pages/Terrenos/TablaTerrenos/TerrenosTable.vue';
import { Terreno } from '@/types/terrenos';
import CostosDrawer from './Terrenos/CostosDrawer.vue';
import TerrenosHeader from './Terrenos/TerrenosHeader.vue';

interface Proyecto {
    id: number;
    nombre: string;
    ubicacion: string;
    barrios: number;
    fecha_lanzamiento: string;
    numero_lotes: number;
}

const form = reactive({
    idproyecto: null as number | null,
    idcategoria: 0,
    idbarrio: 0,
    idcuadra: 0,
    numero_terreno: '',
    ubicacion: '',
    superficie: '',
    precio_venta: null as number | null,
    cuota_inicial: null as number | null,
    cuota_mensual: null as number | null,
    estado: 0,
    condicion: true,
});

const proyectos = ref<Proyecto[]>([]);
const props = defineProps<{ terrenos: Terreno[] }>();
const terrenos = ref([...props.terrenos]);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Terrenos', href: terrenosRoute().url },
];

const search = reactive({ ubicacion: '', nombreCuadra: '', nombreBarrio: '' });
const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const emit = defineEmits<{ (e: 'updated', terreno: Terreno): void }>();

const estadoDialogos = reactive({
    confirmacionVisible: false,
    idTerrenoAEliminar: null as number | null,
    costosDialogVisible: false,
    selectedTerreno: null as Terreno | null,
    nuevoTerrenoVisible: false,
});

// 👇 Nueva variable para controlar la vista
const mostrandoTerrenos = ref(false);

async function cargarProyectos() {
    try {
        const { data } = await axios.get('/api/proyectos');
        proyectos.value = data;
        console.log('Proyectos cargados:', proyectos.value);
    } catch (error) {
        console.error('Error al obtener proyectos:', error);
    }
}

onMounted(() => {
    cargarProyectos();
});

function seleccionarProyecto(id: number) {
    form.idproyecto = id;
    const proyecto = proyectos.value.find((p) => p.id === id);
    mostrandoTerrenos.value = true; // 👈 Ocultar lista de proyectos y mostrar terrenos
    mostrarNotificacion(
        'success',
        `Proyecto seleccionado: ${proyecto?.nombre}`,
    );
}

function volverAProyectos() {
    form.idproyecto = null;
    mostrandoTerrenos.value = false;
}

function guardarTerreno(terreno: any) {
    // El POST ya se hizo en NuevoTerrenoDrawer, solo agregamos a la lista
    terrenos.value.push(terreno);
    estadoDialogos.nuevoTerrenoVisible = false;
    mostrarNotificacion('success', 'Terreno guardado correctamente');
}

function abrirCostos(terreno: Terreno) {
    estadoDialogos.selectedTerreno = terreno;
    estadoDialogos.costosDialogVisible = true;
}

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}

const filteredTerrenos = computed(() =>
    terrenos.value.filter((t) => {
        const matchProyectoId =
            !form.idproyecto || t.idproyecto === form.idproyecto;
        const ubicacion = (t.ubicacion ?? '').toLowerCase();
        const filterUbicacion = (search.ubicacion ?? '').toLowerCase().trim();
        const matchUbicacion =
            !filterUbicacion || ubicacion.includes(filterUbicacion);

        const nombreBarrio = (t.cuadra?.barrio?.nombre ?? '').toLowerCase();
        const filterBarrio = (search.nombreBarrio ?? '').toLowerCase().trim();
        const matchBarrio =
            !filterBarrio || nombreBarrio.includes(filterBarrio);

        const nombreCuadra = (t.cuadra?.nombre ?? '').toLowerCase();
        const filterCuadra = (search.nombreCuadra ?? '').toLowerCase().trim();
        const matchCuadra =
            !filterCuadra || nombreCuadra.includes(filterCuadra);

        return matchProyectoId && matchUbicacion && matchBarrio && matchCuadra;
    }),
);

function pedirConfirmacionEliminar(id: number) {
    estadoDialogos.idTerrenoAEliminar = id;
    estadoDialogos.confirmacionVisible = true;
}

async function eliminarTerreno() {
    try {
        const id = estadoDialogos.idTerrenoAEliminar;
        if (!id) return;

        await axios.delete(`/terrenos/${id}`);
        const index = terrenos.value.findIndex((t) => t.id === id);
        if (index !== -1) terrenos.value.splice(index, 1);

        mostrarNotificacion('success', 'Terreno eliminado correctamente');
    } catch (error) {
        mostrarNotificacion('error', 'Error al eliminar el terreno');
    } finally {
        estadoDialogos.confirmacionVisible = false;
        estadoDialogos.idTerrenoAEliminar = null;
    }
}

async function actualizarTerrenoEnLista() {
    try {
        const { data } = await axios.get('api/terrenos');
        if (data.success) {
            terrenos.value = data.terrenos;
        }
    } catch (err) {
        console.error('Error cargando terrenos:', err);
    }
}

function mostrarTerrenos() {
    console.log('terrenos: ', terrenos.value);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- 🔹 Mostrar PROYECTOS -->
        <div v-if="!mostrandoTerrenos" class="mb-6 p-4">
            <h2 class="mb-3 text-xl font-semibold dark:text-white">
                Proyectos Disponibles
            </h2>
            <div
                class="flex flex-col rounded-lg bg-white shadow-md dark:bg-gray-800"
            >
                <div
                    v-for="(p, index) in proyectos"
                    :key="p.id"
                    @click="seleccionarProyecto(p.id)"
                    class="flex cursor-pointer items-center gap-4 p-4 transition-colors duration-150 sm:p-5"
                    :class="{
                        'border-t border-gray-200 dark:border-gray-700':
                            index !== 0,
                        'border-l-4 border-blue-600 bg-blue-50 dark:bg-blue-900':
                            form.idproyecto === p.id,
                        'hover:bg-gray-50 dark:hover:bg-gray-700':
                            form.idproyecto !== p.id,
                    }"
                >
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-700"
                    >
                        <i
                            class="pi pi-briefcase text-xl text-blue-600 dark:text-blue-100"
                        ></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="mt-0.5 truncate text-lg font-bold text-gray-900 dark:text-white"
                        >
                            {{ p.nombre }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i
                            v-if="form.idproyecto === p.id"
                            class="pi pi-check-circle text-2xl text-green-600"
                            title="Proyecto Seleccionado"
                        ></i>
                    </div>
                </div>

                <div
                    v-if="proyectos.length === 0"
                    class="p-6 text-center text-gray-500 dark:text-gray-400"
                >
                    No hay proyectos cargados.
                </div>
            </div>
        </div>

        <!-- 🔹 Mostrar TERRENOS del proyecto seleccionado -->
        <div v-else class="py-6">
            <div class="mb-4 flex items-center justify-between gap-1">
                <h2
                    class="ml-6 text-xl font-semibold text-gray-900 dark:text-white"
                >
                    Terrenos del Proyecto Seleccionado
                </h2>
                <button
                    @click="volverAProyectos"
                    class="mr-6 rounded-lg bg-gray-200 px-3 py-1.5 text-gray-800 transition hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
                >
                    ← Volver a Proyectos
                </button>
            </div>

            <TerrenosHeader
                v-model:search="search"
                :proyecto-seleccionado="form.idproyecto"
                @created="guardarTerreno"
            />

            <CostosDrawer
                v-model:visible="estadoDialogos.costosDialogVisible"
                :terreno="estadoDialogos.selectedTerreno"
            />

            <TerrenosTable
                :terrenos="filteredTerrenos"
                @costos="abrirCostos"
                @deleted="pedirConfirmacionEliminar"
                @updated="actualizarTerrenoEnLista()"
            />
        </div>

        <ConfirmacionModal
            v-model="estadoDialogos.confirmacionVisible"
            title="Confirmar eliminación"
            message="¿Estás seguro de que deseas eliminar este terreno?"
            @confirm="eliminarTerreno"
        />

        <NotificacionToast
            v-model="notificacion.visible"
            :type="notificacion.tipo"
            :message="notificacion.mensaje"
        />
    </AppLayout>
</template>
