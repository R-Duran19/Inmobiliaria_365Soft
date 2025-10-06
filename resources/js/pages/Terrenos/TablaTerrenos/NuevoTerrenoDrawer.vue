<script setup lang="ts">
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';

interface Proyecto {
    id: number;
    nombre: string;
}

interface Categoria {
    id: number;
    nombre: string;
}

const props = defineProps<{
    visible: boolean;
    proyectos: Proyecto[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'created', formData: any): void;
}>();

const categorias = ref<Categoria[]>([]);
const proyectos = ref<Proyecto[]>([]);
const loading = ref(false);

const form = reactive({
    idproyecto: '0' as string | number,
    categoria: '' as string,
    ubicacion: '' as string,
    superficie: null as number | null,
    precio_venta: null as number | null,
    cuota_inicial: null as number | null,
    cuota_mensual: null as number | null,
    estado: 0,
    condicion: true,
});

onMounted(() => {
    cargarProyectos();
    cargarCategorias();
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

async function cargarCategorias() {
    try {
        const { data } = await axios.get(
            `categorias_terrenos/proyecto/${form.idproyecto}`,
        );

        if (data.success && data.categorias?.length) {
            categorias.value = data.categorias;
        } else {
            categorias.value = [{ id: 0, nombre: 'Sin Categorías' }];
        }

        console.log('Datos:', data);
    } catch (err) {
        console.error('Error cargando categorías:', err);
        categorias.value = [{ id: 0, nombre: 'Sin Categorías' }];
    }
}

watch(() => form.idproyecto, cargarCategorias);

async function guardarTerreno() {
    emit('created', { ...form });
}
</script>

<template>
    <div
        v-if="visible"
        class="fixed inset-0 z-50 flex items-center justify-end bg-black/30"
    >
        <div
            class="h-full w-full max-w-md overflow-y-auto bg-white p-6 shadow-lg dark:bg-gray-900"
        >
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Nuevo Terreno</h3>
                <button
                    @click="emit('close')"
                    class="text-gray-500 hover:text-gray-700"
                >
                    ✖
                </button>
            </div>

            
            <div class="space-y-4">
                
                <div class="mb-4">
                    <label class="mb-1 block font-medium">Proyecto</label>
                    <div class="relative">
                        <select
                            id="proyecto"
                            v-model="form.idproyecto"
                            class="focus:ring-opacity-50 block w-full appearance-none rounded-md border border-gray-300 bg-white px-4 py-2 pr-8 text-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
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
                </div>

                
                <div>
                    <label class="mb-1 block font-medium">Categoría</label>
                    <select
                        v-model="form.categoria"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    >
                        <option disabled value="">
                            Selecciona una categoría
                        </option>
                        <option
                            v-for="c in categorias"
                            :key="c.id"
                            :value="c.nombre"
                        >
                            {{ c.nombre }}
                        </option>
                    </select>
                </div>

                
                <div>
                    <label class="mb-1 block font-medium">Ubicación</label>
                    <input
                        v-model="form.ubicacion"
                        type="text"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    />
                </div>

                
                <div>
                    <label class="mb-1 block font-medium"
                        >Superficie (m²)</label
                    >
                    <input
                        v-model.number="form.superficie"
                        type="number"
                        min="0"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    />
                </div>

                
                <div>
                    <label class="mb-1 block font-medium"
                        >Precio de venta</label
                    >
                    <input
                        v-model.number="form.precio_venta"
                        type="number"
                        step="0.01"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    />
                </div>

                
                <div>
                    <label class="mb-1 block font-medium">Cuota inicial</label>
                    <input
                        v-model.number="form.cuota_inicial"
                        type="number"
                        step="0.01"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    />
                </div>

                
                <div>
                    <label class="mb-1 block font-medium">Cuota mensual</label>
                    <input
                        v-model.number="form.cuota_mensual"
                        type="number"
                        step="0.01"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    />
                </div>

                
                <div>
                    <label class="mb-1 block font-medium">Estado</label>
                    <select
                        v-model="form.estado"
                        class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800"
                    >
                        <option value="0">Disponible</option>
                        <option value="1">Reservado</option>
                        <option value="2">Vendido</option>
                    </select>
                </div>
            </div>

            
            <div class="mt-2 flex items-center gap-2">
                <input
                    type="checkbox"
                    id="condicion"
                    v-model="form.condicion"
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600"
                />
                <label for="condicion" class="font-medium">Activo</label>
            </div>

            
            <div class="mt-6 flex justify-end gap-2">
                <button
                    @click="emit('close')"
                    class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400"
                >
                    Cancelar
                </button>
                <button
                    @click="guardarTerreno"
                    :disabled="loading"
                    class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    {{ loading ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </div>
    </div>
</template>
