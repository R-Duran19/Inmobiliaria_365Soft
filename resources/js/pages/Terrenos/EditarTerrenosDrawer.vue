<script setup lang="ts">
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import { type Terreno } from '@/types/terrenos';
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    terreno: Terreno | null;
}>();

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

interface Proyecto {
    id: number;
    nombre: string;
}

interface Categoria {
    id: number;
    nombre: string;
}
interface Barrio {
    id: number;
    nombre: string;
}

interface Cuadra {
    id: number;
    nombre: string;
}

const form = reactive({
    idproyecto: '' as number | '',
    idcategoria: 0 as number,
    idbarrio: 0 as number, // ✅ NUEVO
    idcuadra: 0 as number, // ✅ NUEVO
    numero_terreno: '' as string, // ✅ NUEVO
    ubicacion: '' as string,
    superficie: null as string | null,
    precio_venta: null as number | null,
    cuota_inicial: null as number | null,
    cuota_mensual: null as number | null,
    estado: 0,
    condicion: true,
});

const categorias = ref<Categoria[]>([]);
const barrios = ref<Barrio[]>([]);
const cuadras = ref<Cuadra[]>([]);

const emit = defineEmits<{
    (e: 'update:visible', val: boolean): void;
    (e: 'updated', terreno: Terreno): void;
}>();

// Estado del drawer
const localVisible = ref(props.visible);
watch(
    () => props.visible,
    (val) => (localVisible.value = val),
);

// Lista de proyectos
const proyectos = ref<Proyecto[]>([]);

async function cargarCategorias() {
    if (!form.idproyecto) return;
    try {
        const { data } = await axios.get(
            `/categorias_terrenos/proyecto/${form.idproyecto}`,
        );
        categorias.value = data?.categorias ?? [];
    } catch (err) {
        console.error('Error cargando categorías:', err);
        categorias.value = [];
    }
}
async function cargarBarrios() {
    if (!form.idproyecto) return;
    try {
        const { data } = await axios.get(
            `/barrios/proyecto/${form.idproyecto}`,
        );
        barrios.value = data.barrios || [];
    } catch (error) {
        console.error('Error al obtener barrios:', error);
        barrios.value = [];
    }
}

async function cargarCuadras() {
    if (!form.idbarrio) return;
    try {
        const { data } = await axios.get(`/cuadras/barrio/${form.idbarrio}`);
        cuadras.value = data.cuadras || [];
    } catch (error) {
        console.error('Error al obtener cuadras:', error);
        cuadras.value = [];
    }
}

async function cargarProyectos() {
    try {
        const { data } = await axios.get('/api/proyectos');
        proyectos.value = data ?? [];
    } catch (error) {
        console.error('Error al obtener proyectos:', error);
        proyectos.value = [];
    }
}

watch(
    () => form.idproyecto,
    async (nuevoProyecto) => {
        // Cargar categorías del proyecto
        await cargarCategorias();

        // Cargar barrios del proyecto
        await cargarBarrios();

        // Resetear categoría si no hay disponibles
        if (!categorias.value.length) {
            form.idcategoria = 0;
        } else {
            form.idcategoria = props.terreno?.idcategoria ?? 1;
        }
    },
);
watch(
    () => form.idbarrio,
    async (newIdBarrio) => {
        if (newIdBarrio) {
            await cargarCuadras();
        }
    },
);

// Cargar proyectos al montar
onMounted(() => {
    cargarProyectos();
});

watch(
    () => props.terreno,
    (t) => {
        if (t) {
            form.idproyecto = t.idproyecto;
            form.idcategoria = t.idcategoria ?? 0;
            form.idbarrio = t.cuadra?.barrio?.id ?? 0;
            form.idcuadra = t.cuadra?.id ?? 0;
            form.numero_terreno = String(t.numero_terreno ?? '');
            form.ubicacion = t.ubicacion;
            form.superficie = t.superficie;
            form.precio_venta = t.precio_venta;
            form.cuota_inicial = t.cuota_inicial;
            form.cuota_mensual = t.cuota_mensual;
            form.estado = t.estado;
            form.condicion = t.condicion ?? true;
        }
    },
    { immediate: true },
);

function closeDrawer() {
    localVisible.value = false;
    emit('update:visible', false);
}

const loading = ref(false);

async function actualizarTerreno() {
    if (!props.terreno) return;

    // Validaciones
    if (!form.idcategoria) {
        notificacion.tipo = 'error';
        notificacion.mensaje =
            'Debe seleccionar una categoría antes de actualizar';
        notificacion.visible = true;
        return;
    }

    if (!form.idbarrio) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'Debe seleccionar un barrio antes de actualizar';
        notificacion.visible = true;
        return;
    }

    if (!form.idcuadra) {
        notificacion.tipo = 'error';
        notificacion.mensaje =
            'Debe seleccionar una cuadra antes de actualizar';
        notificacion.visible = true;
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.put(`/terrenos/${props.terreno.id}`, form);
        notificacion.tipo = 'success';
        notificacion.mensaje = 'Terreno actualizado correctamente';
        notificacion.visible = true;

        emit('updated', data);
        closeDrawer();
    } catch (err) {
        notificacion.tipo = 'error';
        notificacion.mensaje = 'Error al actualizar terreno';
        notificacion.visible = true;
    } finally {
        loading.value = false;
    }
}
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
                        Editar Terreno
                    </h3>
                    <button
                        @click="closeDrawer"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                    >
                        ✕
                    </button>
                </div>

                <div class="flex-1 space-y-4">
                    <label class="block font-medium">Proyecto</label>
                    <div class="relative">
                        <select
                            v-model="form.idproyecto"
                            class="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-8 text-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option disabled value="">
                                Selecciona un proyecto
                            </option>
                            <option
                                v-for="p in proyectos"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.nombre }}
                            </option>
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>

                    <label class="mt-2 block font-medium">Categoría</label>
                    <select
                        v-model="form.idcategoria"
                        class="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-8 text-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option disabled value="">
                            Selecciona una categoría
                        </option>
                        <option
                            v-for="c in categorias"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.nombre }}
                        </option>
                    </select>

                    <!-- N° Terreno -->
                    <label class="block font-medium">N° Terreno</label>
                    <input
                        v-model="form.numero_terreno"
                        type="number"
                        min="1"
                        class="w-full rounded border px-3 py-2"
                    />

                    <!-- Barrio -->
                    <label class="block font-medium">Barrio</label>
                    <select
                        v-model="form.idbarrio"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option disabled value="0">Selecciona un barrio</option>
                        <option
                            v-for="barrio in barrios"
                            :key="barrio.id"
                            :value="barrio.id"
                        >
                            {{ barrio.nombre }}
                        </option>
                    </select>

                    <!-- Cuadra -->
                    <label class="block font-medium">Cuadra</label>
                    <select
                        v-model="form.idcuadra"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option disabled value="0">
                            Selecciona una cuadra
                        </option>
                        <option
                            v-for="cuadra in cuadras"
                            :key="cuadra.id"
                            :value="cuadra.id"
                        >
                            {{ cuadra.nombre }}
                        </option>
                    </select>

                    <label class="block font-medium">Ubicación</label>
                    <input
                        v-model="form.ubicacion"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Superficie</label>
                    <input
                        type="text"
                        v-model="form.superficie"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Precio de venta</label>
                    <input
                        type="number"
                        v-model.number="form.precio_venta"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Cuota inicial</label>
                    <input
                        type="number"
                        v-model.number="form.cuota_inicial"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Cuota mensual</label>
                    <input
                        type="number"
                        v-model.number="form.cuota_mensual"
                        class="w-full rounded border px-3 py-2"
                    />

                    <label class="block font-medium">Estado</label>
                    <select
                        v-model="form.estado"
                        class="block w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-8 text-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="0">Disponible</option>
                        <option value="1">Reservado</option>
                        <option value="2">Vendido</option>
                    </select>

                    <div class="mt-2 flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="condicion"
                            v-model="form.condicion"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="ml-2">{{
                            form.condicion ? 'Activo' : 'Inactivo'
                        }}</span>
                    </div>
                </div>

                <button
                    @click="actualizarTerreno"
                    :disabled="loading"
                    class="mt-4 w-full rounded bg-blue-600 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ loading ? 'Guardando...' : 'Actualizar Terreno' }}
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
