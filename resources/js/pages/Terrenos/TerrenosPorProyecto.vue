<script setup lang="ts">
import { TooltipProvider } from 'reka-ui';
import AppLayout from '@/layouts/AppLayout.vue';
import { terrenos as terrenosRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { computed, reactive, ref, onMounted } from 'vue';
import { Terreno } from '@/types/terrenos';
import TerrenosHeader from '../Terrenos/TerrenosHeader.vue';
import TerrenosTable from '@/pages/Terrenos/TablaTerrenos/TerrenosTable.vue';
import ConfirmacionModal from '@/components/ui/confirmacionModal/ConfirmacionModal.vue';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import CostosDrawer from '../Terrenos/CostosDrawer.vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const terrenos = ref<Terreno[]>([]);
const { props } = usePage();
const proyectoId = props.proyectoId; // viene de la ruta Inertia

async function cargarTerrenos() {
    try {
        const { data } = await axios.get(`/api/proyectos/${proyectoId}/terrenos`);
        terrenos.value = data;
        console.log(`Terrenos recibidos para el proyecto ID ${proyectoId}:`, data);
    } catch (err) {
        console.error(err);
    }
}

onMounted(() => {
    cargarTerrenos();
});



interface Proyecto {
    id: number;
    nombre: string;
    ubicacion: string; // Ya lo tenías
    barrios: number; // Nuevo campo
    fecha_lanzamiento: string; // Nuevo campo (tipo string/date)
    numero_lotes: number; // Nuevo campo
}

// Eliminamos 'vistaActual' ya que el DataView reemplaza el dropdown, no la tabla.

const form = reactive({
    idproyecto: null as number | null,
    idcategoria: 0 as number,
    idbarrio: 0 as number,
    idcuadra: 0 as number,
    numero_terreno: '' as string,
    ubicacion: '' as string,
    superficie: '' as string,
    precio_venta: null as number | null,
    cuota_inicial: null as number | null,
    cuota_mensual: null as number | null,
    estado: 0,
    condicion: true,
});

const proyectos = ref<Proyecto[]>([]);


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Terrenos', href: terrenosRoute().url },
];

const search = reactive({ ubicacion: '', nombreCuadra: '', nombreBarrio: '' });
const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const emit = defineEmits<{
    (e: 'updated', terreno: Terreno): void
}>();

const estadoDialogos = reactive({
    confirmacionVisible: false,
    idTerrenoAEliminar: null as number | null,
    costosDialogVisible: false,
    selectedTerreno: null as Terreno | null,
    nuevoTerrenoVisible: false
});


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




async function guardarTerreno(formData: any) {
    try {
        // Verificar que haya un proyecto seleccionado
        if (!form.idproyecto) {
            mostrarNotificacion('error', 'Debe seleccionar un proyecto antes de crear un nuevo terreno');
            return;
        }

        // Asegurarse de enviar el idproyecto seleccionado
        formData.idproyecto = form.idproyecto;

        if (!formData.idcategoria) {
            mostrarNotificacion('error', 'Debe seleccionar una categoría antes de crear un nuevo terreno');
            return;
        }

        if (!formData.idbarrio) {
            mostrarNotificacion('error', 'Debe seleccionar un barrio antes de crear un nuevo terreno');
            return;
        }

        if (!formData.idcuadra) {
            mostrarNotificacion('error', 'Debe seleccionar una cuadra antes de crear un nuevo terreno');
            return;
        }

        const { data } = await axios.post('/terrenos', formData);

        terrenos.value.push(data.terreno);
        estadoDialogos.nuevoTerrenoVisible = false;

        mostrarNotificacion('success', 'Terreno guardado correctamente');
    } catch (error) {
        console.error('Error guardando terreno:', error);
        mostrarNotificacion('error', 'Error al guardar el terreno');
    }
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
        // Filtro por ID de proyecto seleccionado en el DataView
        const matchProyectoId = !form.idproyecto || t.idproyecto === form.idproyecto;

        // Filtro de búsqueda por ubicación
        const ubicacion = (t.ubicacion ?? '').toLowerCase();
        const filterUbicacion = (search.ubicacion ?? '').toLowerCase().trim();
        const matchUbicacion = !filterUbicacion || ubicacion.includes(filterUbicacion);
        const nombreBarrio = (t.cuadra?.barrio?.nombre ?? '').toLowerCase();
        const filterBarrio = (search.nombreBarrio ?? '').toLowerCase().trim();
        const matchBarrio = !filterBarrio || nombreBarrio.includes(filterBarrio);
        // Filtro por cuadra (usando nombre de la cuadra)
        const nombreCuadra = (t.cuadra?.nombre ?? '').toLowerCase();
        const filterCuadra = (search.nombreCuadra ?? '').toLowerCase().trim();
        const matchCuadra = !filterCuadra || nombreCuadra.includes(filterCuadra);

        return matchProyectoId && matchUbicacion && matchBarrio && matchCuadra;
    })
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
  <TooltipProvider>
    <div class="py-6">

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
    </div>
  </TooltipProvider>
</template>


