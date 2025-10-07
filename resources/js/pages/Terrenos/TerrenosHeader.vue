<script setup lang="ts">
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import axios from 'axios';
import { onMounted, reactive, ref, toRef, watch } from 'vue';
import ExportExcelModal from './ExportExcelModal/ExportExcelModal.vue';
import NuevoTerrenoDrawer from './TablaTerrenos/NuevoTerrenoDrawer.vue';

interface Search {
    ubicacion: string;
    nombreProyecto: string;
}

const props = defineProps<{
    search: Search;
}>();

const emit = defineEmits(['update:search', 'created']);

const search = toRef(props, 'search');

watch(
    search,
    (nuevoValor) => {
        emit('update:search', nuevoValor);
    },
    { deep: true },
);

// --- ESTADOS ---
const drawerVisible = ref(false);
const mostrarModal = ref(false); // 🟢 Ahora sí existe el control del modal
const proyectos = ref<any[]>([]);

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

// --- CARGAR PROYECTOS ---
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

// --- NUEVO TERRENO ---
function handleCreated(formData: any) {
    emit('created', { ...formData });
    drawerVisible.value = false;
}

// --- EXPORTAR ---
function handleExport() {
    if (!proyectos.value.length) {
        mostrarNotificacion(
            'error',
            'No hay proyectos disponibles para exportar.',
        );
        return;
    }
    mostrarModal.value = true
}



// --- FILTROS Y BOTONES ---
const filtros: {
    model: keyof Search;
    placeholder: string;
    width: string;
}[] = [
    { model: 'ubicacion', placeholder: 'Buscar ubicación', width: 'sm:w-52' },
    {
        model: 'nombreProyecto',
        placeholder: 'Buscar Proyecto',
        width: 'sm:w-52',
    },
];

const botones = [
    {
        label: 'Nuevo Terreno',
        icon: 'pi pi-plus',
        color: 'blue',
        action: () => (drawerVisible.value = true),
    },
    {
        label: 'Exportar',
        icon: 'pi pi-file-export',
        color: 'green',
        action: handleExport,
    },
];
</script>

<template>
    <div
        class="mx-6 mb-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <template v-for="filtro in filtros" :key="filtro.model">
                <input
                    v-model="search[filtro.model]"
                    :placeholder="filtro.placeholder"
                    :class="`w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-800 transition focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 ${filtro.width}`"
                />
            </template>
        </div>

        <div class="flex justify-end gap-2">
            <template v-for="btn in botones" :key="btn.label">
                <button
                    @click="btn.action"
                    :class="[
                        'flex items-center gap-2 rounded-lg px-4 py-2 font-medium text-white shadow-sm transition active:scale-95',
                        btn.color === 'blue'
                            ? 'bg-blue-600 hover:bg-blue-700'
                            : '',
                        btn.color === 'green'
                            ? 'bg-green-600 hover:bg-green-700'
                            : '',
                    ]"
                >
                    <i :class="btn.icon"></i>
                    <span>{{ btn.label }}</span>
                </button>
            </template>
        </div>

        <NuevoTerrenoDrawer
            v-if="drawerVisible"
            :visible="drawerVisible"
            :proyectos="proyectos"
            @close="drawerVisible = false"
            @created="handleCreated"
        />

        <ExportExcelModal
            v-model:visible="mostrarModal"
            :proyectos="proyectos"
            @notificar="mostrarNotificacion"
        />

        <NotificacionToast
            v-model="notificacion.visible"
            :type="notificacion.tipo"
            :message="notificacion.mensaje"
        />
    </div>
</template>
