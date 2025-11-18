<script setup lang="ts">
import Loading from '@/components/ui/Loading/Loading.vue';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    visible: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'cliente-seleccionado', data: any): void;
    (e: 'nuevo-cliente', data: any): void;
}>();

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const loading = ref(false);
const opcionSeleccionada = ref<'cliente' | 'nuevo' | null>(null);
const numeroDocumento = ref('');
const buscando = ref(false);
const clienteEncontrado = ref<any>(null);
const nuevoCliente = ref({
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    telefono: '',
    telefono_referencia: '',
    direccion: '',
    ci: '',
    codigo_carnet: '',
});
const abreviacionesDepartamentos = [
    'SC',
    'LP',
    'CB',
    'CH',
    'PT',
    'BN',
    'OR',
    'TJ',
    'PD',
    'EX',
];

const confirmarEnvio = computed(() => {
    return Object.values(nuevoCliente.value).every((v) => v !== '');
});

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}

const mostrarBusqueda = computed(() => opcionSeleccionada.value === 'cliente');
const botonAceptarHabilitado = computed(() => {
    if (opcionSeleccionada.value === 'nuevo' && confirmarEnvio.value)
        return true;
    if (opcionSeleccionada.value === 'cliente' && clienteEncontrado.value)
        return true;
    return false;
});

function seleccionarOpcion(opcion: 'cliente' | 'nuevo') {
    opcionSeleccionada.value = opcion;
    if (opcion === 'nuevo') {
        numeroDocumento.value = '';
        clienteEncontrado.value = null;
    }
}

async function buscarCliente() {
    if (!numeroDocumento.value.trim()) {
        alert('Por favor, ingrese un número de documento');
        return;
    }

    buscando.value = true;

    try {
        loading.value = true;

        const response = await axios.get(`/clientes/${numeroDocumento.value}`);
        clienteEncontrado.value = response.data.cliente;
        buscando.value = false;
    } catch (error) {
        console.error('Error al buscar cliente:', error);
        mostrarNotificacion(
            'error',
            'No se encontró el cliente con ese documento',
        );
        clienteEncontrado.value = null;
        buscando.value = false;
    } finally {
        loading.value = false;
    }
}

function cancelar() {
    opcionSeleccionada.value = null;
    numeroDocumento.value = '';
    clienteEncontrado.value = null;
    emit('update:visible', false);
}

function aceptar() {
    if (!botonAceptarHabilitado.value) return;

    if (opcionSeleccionada.value === 'cliente') {
        emit('cliente-seleccionado', clienteEncontrado.value);
    } else {
        emit('nuevo-cliente', nuevoCliente.value);
        console.log('nuevo cliente');
    }

    // Resetear el formulario
    opcionSeleccionada.value = null;
    numeroDocumento.value = '';
    clienteEncontrado.value = null;
}
</script>

<template>
    <div
        v-if="visible"
        class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4 transition-opacity duration-300"
        @click.self="cancelar"
    >
        <NotificacionToast
            v-model="notificacion.visible"
            :type="notificacion.tipo"
            :message="notificacion.mensaje"
        />
        <div
            v-if="loading"
            class="absolute inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        >
            <Loading />
        </div>

        <div
            v-if="loading"
            class="absolute inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        >
            <Loading />
        </div>

        <div
            class="relative z-[100] max-h-[90vh] w-full max-w-2xl transform overflow-y-auto rounded-2xl bg-white shadow-2xl transition-all duration-300 dark:bg-gray-800"
            @click.stop
        >
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2
                            class="text-2xl font-semibold text-gray-900 dark:text-white"
                        >
                            Selección de Cliente
                        </h2>
                        <p
                            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                        >
                            Por favor, indique si ya es cliente o si desea
                            registrarse
                        </p>
                    </div>
                    <button
                        @click="cancelar"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <i class="pi pi-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div
                    @click="seleccionarOpcion('cliente')"
                    class="mb-4 cursor-pointer rounded-xl border-2 p-5 transition-all duration-200"
                    :class="
                        opcionSeleccionada === 'cliente'
                            ? 'border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-900/20'
                            : 'border-gray-300 hover:border-green-400 hover:bg-gray-50 dark:border-gray-600 dark:hover:border-green-500 dark:hover:bg-gray-700/50'
                    "
                >
                    <div class="flex items-center">
                        <input
                            type="radio"
                            name="tipo-cliente"
                            :checked="opcionSeleccionada === 'cliente'"
                            class="h-5 w-5 cursor-pointer text-green-600 focus:ring-green-500"
                            @click.stop="seleccionarOpcion('cliente')"
                        />
                        <label
                            class="ml-3 cursor-pointer text-lg font-medium text-gray-900 dark:text-white"
                        >
                            Soy cliente
                        </label>
                    </div>

                    <div v-if="mostrarBusqueda" class="animate-fadeIn mt-4">
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <input
                                    v-model="numeroDocumento"
                                    type="text"
                                    placeholder="Número de documento *"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-green-500"
                                    @keyup.enter="buscarCliente"
                                />
                            </div>
                            <button
                                @click="buscarCliente"
                                :disabled="buscando || !numeroDocumento.trim()"
                                class="flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 font-medium text-white transition-all hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <i
                                    :class="
                                        buscando
                                            ? 'pi pi-spin pi-spinner'
                                            : 'pi pi-search'
                                    "
                                ></i>
                                <span>{{
                                    buscando ? 'Buscando...' : 'Buscar'
                                }}</span>
                            </button>
                        </div>

                        <div v-if="clienteEncontrado" class="mt-3">
                            <button
                                type="button"
                                @click="aceptar"
                                class="w-full rounded-lg border border-gray-200 bg-white cursor-pointer p-4 shadow-sm transition-all duration-200 hover:border-green-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-green-600"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30"
                                    >
                                        <i
                                            class="pi pi-check text-green-600 dark:text-green-400"
                                        ></i>
                                    </div>
                                    <div class="flex-1 text-left">
                                        <p
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                        >
                                            Cliente encontrado
                                        </p>
                                        <p
                                            class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white"
                                        >
                                            {{ clienteEncontrado.nombre }}
                                        </p>
                                        <p
                                            class="text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            CI: {{ clienteEncontrado.ci }}
                                        </p>
                                    </div>
                                    <i
                                        class="pi pi-chevron-right text-gray-400 dark:text-gray-500"
                                    ></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div
                            class="w-full border-t border-gray-300 dark:border-gray-600"
                        ></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span
                            class="bg-white px-4 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                        >
                            O
                        </span>
                    </div>
                </div>

                <div
                    @click="seleccionarOpcion('nuevo')"
                    class="cursor-pointer rounded-xl border-2 p-5 transition-all duration-200"
                    :class="
                        opcionSeleccionada === 'nuevo'
                            ? 'border-green-500 bg-green-50 dark:border-green-600 dark:bg-green-900/20'
                            : 'border-gray-300 hover:border-green-400 hover:bg-gray-50 dark:border-gray-600 dark:hover:border-green-500 dark:hover:bg-gray-700/50'
                    "
                >
                    <div class="flex items-center">
                        <input
                            type="radio"
                            name="tipo-cliente"
                            :checked="opcionSeleccionada === 'nuevo'"
                            class="h-5 w-5 cursor-pointer text-green-600 focus:ring-green-500"
                            @click.stop="seleccionarOpcion('nuevo')"
                        />
                        <label
                            class="ml-3 cursor-pointer text-lg font-medium text-gray-900 dark:text-white"
                        >
                            Aún no soy cliente
                        </label>
                    </div>

                    <div
                        v-if="opcionSeleccionada === 'nuevo'"
                        class="animate-fadeIn mt-4"
                    >
                        <div class="flex flex-col gap-5">
                            <input
                                v-model="nuevoCliente.nombre"
                                type="text"
                                placeholder="Nombres *"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.apellido_paterno"
                                type="text"
                                placeholder="Primer apellido *"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.apellido_materno"
                                type="text"
                                placeholder="Segundo apellido"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.telefono"
                                type="text"
                                placeholder="Teléfono *"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.telefono_referencia"
                                type="text"
                                placeholder="Teléfono Referencia*"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.direccion"
                                type="text"
                                placeholder="Direccion*"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <input
                                v-model="nuevoCliente.ci"
                                type="text"
                                placeholder="Nro. Documento de identidad *"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <select
                                v-model="nuevoCliente.codigo_carnet"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option
                                    value=""
                                    disabled
                                    selected
                                    class="text-gray-500"
                                >
                                    Escoger código carnet
                                </option>
                                <option
                                    v-for="abreviacion in abreviacionesDepartamentos"
                                    :value="abreviacion"
                                    :key="abreviacion"
                                >
                                    {{ abreviacion }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex gap-3 border-t border-gray-200 p-6 dark:border-gray-700"
            >
                <button
                    @click="cancelar"
                    class="flex-1 rounded-lg border-2 border-gray-300 px-6 py-3 font-medium text-gray-700 transition-all hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Cancelar
                </button>
                <button
                    @click="aceptar"
                    :disabled="!botonAceptarHabilitado"
                    class="flex-1 rounded-lg bg-green-600 px-6 py-3 font-medium text-white transition-all hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}
</style>
