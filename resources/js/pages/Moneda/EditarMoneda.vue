<script setup lang="ts">
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import { Moneda } from '@/types/Moneda';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';

interface CiudadConPoblacion {
    city: string;
    country: string;
    populationCounts: Array<{
        year: string;
        value: string;
        sex: string;
        reliabilty: string;
    }>;
}

interface ApiResponse {
    error: boolean;
    msg: string;
    data: CiudadConPoblacion[];
}

const paises = ref<string[]>([]);
const busqueda = ref<string>('');

const paisesFiltrados = computed(() => {
    if (!busqueda.value) return [];
    return paises.value.filter((pais) =>
        pais.toLowerCase().includes(busqueda.value.toLowerCase()),
    );
});

const props = defineProps<{
    visible: boolean;
    moneda: Moneda | null;
}>();

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const form = reactive({
    nombre: '' as string,
    abreviacion: '' as string,
    pais: '' as string,
    tipo_cambio: 0 as number,
    activo: false as boolean,
});

function seleccionarPais(pais: string) {
    form.pais = pais;
    busqueda.value = '';
}
const emit = defineEmits<{
    (e: 'update:visible', val: boolean): void;
    (e: 'updated', moneda: Moneda): void;
}>();

// Estado del drawer
const localVisible = ref(props.visible);
watch(
    () => props.visible,
    (val) => (localVisible.value = val),
);

// Cargar datos de la moneda al montar o cambiar
watch(
    () => props.moneda,
    (moneda) => {
        if (moneda) {
            form.nombre = moneda.nombre ?? '';
            form.abreviacion = moneda.abreviacion ?? '';
            form.pais = moneda.pais ?? '';
            form.tipo_cambio = moneda.tipo_cambio ?? 0;
            form.activo = moneda.activo ?? false;
        }
    },
    { immediate: true },
);

function closeDrawer() {
    localVisible.value = false;
    emit('update:visible', false);
}

const loading = ref(false);

async function actualizarMoneda() {
    if (!props.moneda) return;

    if (!form.nombre.trim()) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'El nombre de la moneda es requerido';
        notificacion.visible = true;
        return;
    }

    if (!form.abreviacion.trim()) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'La abreviación de la moneda es requerida';
        notificacion.visible = true;
        return;
    }

    if (!form.pais.trim()) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'El país es requerido';
        notificacion.visible = true;
        return;
    }

    if (form.tipo_cambio <= 0) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'El tipo de cambio debe ser mayor a 0';
        notificacion.visible = true;
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.put(`/monedas/${props.moneda.id}`, form);
        notificacion.tipo = 'success';
        notificacion.mensaje = 'Moneda actualizada correctamente';
        notificacion.visible = true;
        emit('updated', data);
        closeDrawer();
    } catch (err) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'Error al actualizar la moneda';
        notificacion.visible = true;
    } finally {
        loading.value = false;
    }
}
async function getPaises() {
    try {
        const response = await fetch(
            'https://countriesnow.space/api/v0.1/countries/population/cities',
        );
        const result: ApiResponse = await response.json();
        const omitidos = ['13', 'footnoteSeqID'];
        paises.value = [...new Set(result.data.map((item) => item.country))]
            .filter((country) => !omitidos.includes(country))
            .sort();
    } catch (error) {
        console.error('Error al obtener los países:', error);
    }
}

onMounted(async () => {
    await getPaises();
});
</script>

<template>
    <transition name="drawer">
        <div
            v-if="localVisible"
            class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-sm"
        >
            <div
                class="flex h-full w-96 flex-col overflow-auto bg-white p-6 shadow-2xl dark:bg-gray-900"
            >
                <div
                    class="mb-6 flex items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700"
                >
                    <h3
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Editar Moneda
                    </h3>
                    <button
                        @click="closeDrawer"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                    >
                        ✕
                    </button>
                </div>
                <div class="flex-1 space-y-4">
                    <label class="block font-medium">Nombre de la Moneda</label>
                    <input
                        v-model="form.nombre"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Abreviación</label>
                    <input
                        v-model="form.abreviacion"
                        class="w-full rounded border px-3 py-2"
                    />

                    <div class="relative grid gap-2">
                        <Label for="pais">País *</Label>

                        <input
                            id="pais"
                            type="text"
                            v-model="busqueda"
                            placeholder="Escribe para buscar un país..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        />

                        <div
                            v-if="busqueda && paisesFiltrados.length > 0"
                            class="absolute top-full z-10 mt-1 max-h-40 w-full space-y-1 overflow-y-auto rounded-md border border-gray-300 bg-gray-900 shadow"
                        >
                            <div
                                v-for="pais in paisesFiltrados"
                                :key="pais"
                                @click="seleccionarPais(pais)"
                                class="cursor-pointer px-3 py-2 hover:bg-gray-500"
                            >
                                {{ pais }}
                            </div>
                        </div>

                        <p v-if="form.pais" class="text-sm text-gray-500">
                            País seleccionado: <strong>{{ form.pais }}</strong>
                        </p>
                    </div>

                    <label class="block font-medium">Tipo de Cambio</label>
                    <input
                        type="number"
                        v-model.number="form.tipo_cambio"
                        class="w-full rounded border px-3 py-2"
                    />

                    <div class="mt-2 flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="activo"
                            v-model="form.activo"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="ml-2">{{
                            form.activo ? 'Activa' : 'Inactiva'
                        }}</span>
                    </div>
                </div>

                <button
                    @click="actualizarMoneda"
                    :disabled="loading"
                    class="mt-4 w-full rounded bg-blue-600 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ loading ? 'Guardando...' : 'Actualizar Moneda' }}
                </button>
            </div>
        </div>
    </transition>
    <NotificacionToast
        v-model="notificacion.visible"
        :type="notificacion.tipo"
        :message="notificacion.mensaje"
    />
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
    transition:
        transform 0.3s ease,
        opacity 0.3s ease;
}
.drawer-enter-from,
.drawer-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
.drawer-enter-to,
.drawer-leave-from {
    transform: translateX(0%);
    opacity: 1;
}
</style>
