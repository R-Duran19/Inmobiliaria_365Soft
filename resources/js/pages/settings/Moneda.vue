<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import ConfirmacionModal from '@/components/ui/confirmacionModal/ConfirmacionModal.vue';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Moneda } from '@/types/Moneda';
import axios from 'axios';
import { onMounted, reactive, ref, Ref } from 'vue';
import FormMonedas from '../Moneda/FormMonedas.vue';
import MonedaTable from '../Moneda/MonedaTable.vue';

const monedas: Ref<Moneda[]> = ref([]);
const mostrarFormulario = ref(false);
const monedaEditar = ref<Moneda | null>(null);

const estadoDialogos = reactive({
    confirmacionVisible: false,
    idMonedaAEliminar: null as number | null,
    costosDialogVisible: false,
    selectedTerreno: null as Moneda | null,
    nuevoTerrenoVisible: false,
});

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}

async function getMonedas() {
    try {
        const { data } = await axios.get('/monedas');
        monedas.value = data.monedas;
    } catch (error) {
        console.error('Error al cargar las monedas:', error);
    }
}

onMounted(async () => {
    await getMonedas();
});

function mostrarForm() {
    mostrarFormulario.value = !mostrarFormulario.value;
}

async function registrarMoneda(monedaData: Partial<Moneda>) {
    try {
        const { data } = await axios.post('/monedas', monedaData);
        monedas.value.push(data.moneda);
        mostrarFormulario.value = false;
        mostrarNotificacion('success', 'Moneda registrada correctamente');
    } catch (error) {
        mostrarNotificacion('error', 'Error al registrar la moneda');
    }
}

function pedirConfirmacionEliminar(id: number) {
    estadoDialogos.idMonedaAEliminar = id;
    estadoDialogos.confirmacionVisible = true;
}

async function eliminarMoneda() {
    try {
        const id = estadoDialogos.idMonedaAEliminar;
        if (!id) return;

        await axios.delete(`/monedas/${id}`);
        const index = monedas.value.findIndex((m) => m.id === id);
        if (index !== -1) monedas.value.splice(index, 1);

        mostrarNotificacion('success', 'Moneda eliminada correctamente');
    } catch (error) {
        mostrarNotificacion('error', 'Error al eliminar la moneda');
    } finally {
        estadoDialogos.confirmacionVisible = false;
        estadoDialogos.idMonedaAEliminar = null;
    }
}

async function actualizarMonedasEnLista() {
    await getMonedas();
}
</script>

<template>
    <AppLayout>
        <Head
            title="Configuración de Moneda"
            description="Selecciona la moneda predeterminada para tu cuenta."
        />

        <SettingsLayout>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <NotificacionToast
                    v-model="notificacion.visible"
                    :type="notificacion.tipo"
                    :message="notificacion.mensaje"
                />
                <HeadingSmall
                    title="Configuración de Moneda"
                    description="Selecciona la moneda predeterminada para tu cuenta."
                />

                <div
                    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:gap-4"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded bg-white px-4 py-2 text-black hover:bg-gray-300 focus:outline-none sm:w-auto"
                        @click="mostrarForm"
                    >
                        <i class="pi pi-money-bill"></i>
                        Registrar Moneda
                    </button>
                </div>
            </div>

            <MonedaTable
                :monedas="monedas"
                @deleted="pedirConfirmacionEliminar"
                @updated="actualizarMonedasEnLista()"
                @refresh="actualizarMonedasEnLista()"
            />
            <FormMonedas
                v-model="mostrarFormulario"
                :moneda="monedaEditar"
                @guardar="registrarMoneda"
            />

            <ConfirmacionModal
                v-model="estadoDialogos.confirmacionVisible"
                title="Confirmar eliminación"
                message="¿Estás seguro de que deseas eliminar esta moneda?"
                @confirm="eliminarMoneda"
            />
        </SettingsLayout>
    </AppLayout>
</template>
