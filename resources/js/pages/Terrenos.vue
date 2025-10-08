<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { terrenos as terrenosRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { computed, reactive, ref, toRef } from 'vue';

import { Terreno } from '@/types/terrenos';
import TerrenosHeader from './Terrenos/TerrenosHeader.vue';
import TerrenosTable from '@/pages/Terrenos/TablaTerrenos/TerrenosTable.vue';
import ConfirmacionModal from '@/components/ui/confirmacionModal/ConfirmacionModal.vue';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import axios from 'axios';
import CostosDrawer from './Terrenos/CostosDrawer.vue';

const props = defineProps<{ terrenos: Terreno[] }>();
const terrenos = ref([...props.terrenos]);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Terrenos', href: terrenosRoute().url },
];

const search = reactive({ ubicacion: '', nombreProyecto: '' });
const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const emit = defineEmits<{
  (e: 'updated', terreno: Terreno): void
}>()

async function guardarTerreno(formData: any) {
  try {
    if (!formData.idcategoria) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'Debe seleccionar una categoría antes de crear un nuevo proyecto';
        notificacion.visible = true;
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

const estadoDialogos = reactive({
    confirmacionVisible: false,
    idTerrenoAEliminar: null as number | null,
    costosDialogVisible: false,
    selectedTerreno: null as Terreno | null,
    nuevoTerrenoVisible: false
});

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
    const nombreProyecto = t.proyecto?.nombre?.toLowerCase() ?? '';
    const filterProyecto = (search.nombreProyecto ?? '').toLowerCase().trim();

    const ubicacion = (t.ubicacion ?? '').toLowerCase();
    const filterUbicacion = (search.ubicacion ?? '').toLowerCase().trim();

    const matchProyecto = !filterProyecto || nombreProyecto.includes(filterProyecto);
    const matchUbicacion = !filterUbicacion || ubicacion.includes(filterUbicacion);

    return matchProyecto && matchUbicacion;
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
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <Button @click="mostrarTerrenos"> mostrar </Button>

            <TerrenosHeader
                v-model:search="search"
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
