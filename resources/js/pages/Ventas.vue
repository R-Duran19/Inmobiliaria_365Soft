<script setup lang="ts">
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import Loading from '@/components/ui/Loading/Loading.vue';
import ModalVenta from './Ventas/ModalVenta.vue';
import ConfirmacionModal from '@/components/ui/confirmacionModal/ConfirmacionModal.vue';
import { usePage } from '@inertiajs/vue3';

interface Cliente {
    id: number;
    nombre: string;
    apellido_paterno: string;
    apellido_materno: string;
    telefono: string;
    telefono_referencia: string;
    direccion: string;
    ci: string;
    codigo_carnet: string;
}


const props = defineProps<{
    terreno: {
        id: number;
        cuota_inicial: number;
        cuota_mensual: number;
        precio_venta: number;
        ubicacion: string;
        proyecto: {
            nombre: string;
            id: number;
        };
    };
}>();
const page = usePage();
const simuladoPlanPagos = ref(false);
const loading = ref(false);
const headerTablaPlanPago = [
    { label: 'N° Cuota' },
    { label: 'Fecha' },
    { label: 'Cuota' },
    { label: 'Saldo' },
];
const clienteSeleccionado = ref<Cliente | null>(null);
const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const infoItems = [
    {
        label: 'Proyecto',
        valor: props.terreno.proyecto.nombre,
        icon: 'pi-folder',
        color: 'text-indigo-500',
        darkColor: 'dark:text-indigo-400',
    },
    {
        label: 'Ubicación',
        valor: props.terreno.ubicacion,
        icon: 'pi-map-marker',
        color: 'text-green-500',
        darkColor: 'dark:text-green-400',
    },
    {
        label: 'Precio referencial (USD)',
        valor: props.terreno.precio_venta,
        icon: 'pi-money-bill',
        color: 'text-yellow-500',
        darkColor: 'dark:text-yellow-400',
    },
];

const plazos_descuentos = ref([
    {
        plazo: '12',
        descuento: '31.5',
    },
    {
        plazo: '24',
        descuento: '28',
    },
    {
        plazo: '36',
        descuento: '24.5',
    },
    {
        plazo: '48',
        descuento: '21',
    },
    {
        plazo: '60',
        descuento: '17.5',
    },
    {
        plazo: '72',
        descuento: '14',
    },
    {
        plazo: '84',
        descuento: '10.5',
    },
    {
        plazo: '96',
        descuento: '7',
    },
    {
        plazo: '108',
        descuento: '3.5',
    },
    {
        plazo: '120',
        descuento: '0',
    },
]);
const estadoDialogos = reactive({
    confirmacionVisible: false,
});

const mostrarModal = ref(false);
const continuarCompra = ref(false);
const vendiendo = ref(false);
const precioVenta = ref(props.terreno.precio_venta * 0.5);
const tipoCompra = ref('En cuotas');
const cuotaInicial = ref(props.terreno.cuota_inicial);
const fechaPrimerPago = ref('');
const plazoSeleccionado = ref('');
const nuevoCliente = ref<Cliente>({
  id: 0,
  nombre: '',
  apellido_paterno: '',
  apellido_materno: '',
  telefono: '',
  telefono_referencia: '',
  direccion: '',
  ci: '',
  codigo_carnet: ''
});


const descuento = computed(() => {
    if (tipoCompra.value == "Pago inmediato") {
        return 50.00;
    } else {
        const plazo = plazos_descuentos.value.find(
            (p) => p.plazo === plazoSeleccionado.value,
        );
        return plazo ? parseFloat(plazo.descuento) : 0; 
    }
});


function aumentarMes(fecha: Date): Date {
    const nuevaFecha = new Date(fecha);
    nuevaFecha.setMonth(nuevaFecha.getMonth() + 1);
    return nuevaFecha;
}

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}

const datos = computed(() => ({
    tipoCompra: tipoCompra.value,
    cuotaInicial: parseFloat(cuotaInicial.value.toString()),
    plazo: parseFloat(plazoSeleccionado.value.toString()),
    descuento: descuento.value, 
    precioReferencial: parseFloat(props.terreno.precio_venta.toString()),
    fechaPrimerPago: fechaPrimerPago.value,
}));



const precio_total = computed(
    () =>
        datos.value.precioReferencial -
        datos.value.precioReferencial * (datos.value.descuento / 100),
);
const precio_a_pagar = computed(
    () => precio_total.value - datos.value.cuotaInicial,
);
const precio_mes = computed(() => precio_a_pagar.value / datos.value.plazo);
const descuento_total = computed(() =>
    Number(
        datos.value.precioReferencial * (datos.value.descuento / 100)
    ).toFixed(2),
);

const pagos = computed(() => {
    const arr = [];
    let fechaActual = new Date(datos.value.fechaPrimerPago);
    let total_plan_pago = precio_a_pagar.value;
    let saldo;
    for (let i = 0; i < datos.value.plazo; i++) {
        total_plan_pago -= precio_mes.value;
        arr.push({
            numero: i + 1,
            fecha: new Date(fechaActual).toISOString().split('T')[0],
            cuota: Number(precio_mes.value.toFixed(2)),
            saldo: Number(total_plan_pago.toFixed(2)),
        });
        fechaActual = aumentarMes(fechaActual);
    }
    return arr;
});

function mostrarTabla() {
    simuladoPlanPagos.value = !simuladoPlanPagos.value;
    continuarCompra.value = true;
    console.log('pagos', pagos);
}

const precios = computed(() => [
    { titulo: 'Precio de lista', precio: props.terreno.precio_venta },
    { titulo: `Descuento (${descuento.value}%): `, precio: descuento_total },
    {
        titulo: 'Precio de venta: ',
        precio: Number(precio_total.value).toFixed(2),
    },
    { titulo: 'Cuota inicial: ', precio: cuotaInicial },
    {
        titulo: 'Total plan de pago: ',
        precio: Number(precio_a_pagar.value).toFixed(2),
    },
]);

const formValido = computed(() => {
    return (
        cuotaInicial.value > 0 &&
        plazoSeleccionado.value !== '' &&
        fechaPrimerPago.value !== ''
    );
});

function verificarTipoCompra() {
    if (tipoCompra.value === 'Pago inmediato') {
        vendiendo.value = true;
        plazoSeleccionado.value = '';
        fechaPrimerPago.value = '';
        continuarCompra.value = true;
    } else {
        vendiendo.value = false;
        continuarCompra.value = false;
    }
}

function handleClienteSeleccionado(cliente: Cliente) {
    console.log('Cliente seleccionado:', cliente);
    
    
    
    if (cliente.id) {
        nuevoCliente.value.id = cliente.id;
    }
    
    
    estadoDialogos.confirmacionVisible = true;
    
    
    mostrarModal.value = false;
}



async function handleNuevoCliente(cli: any) {
    console.log('Creando nuevo cliente', cli);

    try {
        loading.value = true;
        const response = await axios.post('/clientes/', cli);
        nuevoCliente.value = response.data.cliente;
    } catch (error) {
        console.error('Error al buscar cliente:', error);
        mostrarNotificacion('error', 'No se pudo añadir al cliente.');
    } finally {
        loading.value = false;
        mostrarNotificacion('success', 'Cliente añadido correctamente.');
        estadoDialogos.confirmacionVisible = true;
    }
    
    
    mostrarModal.value = false;
}

function round(num: number): number {
    return Math.round(num * 100) / 100;
}


async function guardarDatosVenta() {
    try {
        loading.value = true;

        const datosVenta = {
            idusuario: Number(page.props.auth?.user?.id),
            idcliente: Number(nuevoCliente.value.id),
            idterreno: Number(props.terreno.id),

            
            precio_lista: round(props.terreno.precio_venta),
            descuento: round(descuento.value)/100,
            precio_venta: round(precio_total.value),
            cuota_inicial: round(datos.value.cuotaInicial),
            total_plan_pago: round(precio_a_pagar.value),
        };

        console.log("Enviando datos:", datosVenta, {
            tipos: {
                lista: typeof datosVenta.precio_lista,
                descuento: typeof datosVenta.descuento,
                venta: typeof datosVenta.precio_venta,
                cuota: typeof datosVenta.cuota_inicial,
                total: typeof datosVenta.total_plan_pago,
            }
        });

        await axios.post('/ventas/', datosVenta);
        mostrarNotificacion('success', 'Venta registrada correctamente.');
    } catch (error) {
        console.error('Error al registrar venta:', error);
        mostrarNotificacion('error', 'No se pudo registrar la venta.');
    } finally {
        loading.value = false;
        estadoDialogos.confirmacionVisible = false;
    }
}








</script>

<template>
    <div class="flex w-full flex-col gap-5 overflow-x-hidden p-4">
        <ModalVenta
            v-model:visible="mostrarModal"
            @cliente-seleccionado="handleClienteSeleccionado"
            @nuevo-cliente="handleNuevoCliente"
        />
        <NotificacionToast
            v-model="notificacion.visible"
            :type="notificacion.tipo"
            :message="notificacion.mensaje"
        />

        <ConfirmacionModal
            v-model="estadoDialogos.confirmacionVisible"
            title="Confirmar venta"
            message="¿Estás seguro de que deseas registrar venta?"
            @confirm="guardarDatosVenta"
        />
        <div
            v-if="loading"
            class="absolute inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        >
            <Loading />
        </div>

        <div class="flex w-full flex-col gap-2 sm:px-4">
            <div
                class="flex flex-col items-start justify-between gap-3 rounded-2xl border-2 border-green-500 p-4 lg:flex-row lg:items-center dark:border-green-700 dark:bg-gray-800"
            >
                <div
                    v-for="(item, index) in infoItems"
                    :key="index"
                    class="flex flex-col items-start gap-1 py-2 text-gray-900 sm:flex-row sm:items-center dark:text-gray-100"
                >
                    <i
                        :class="[
                            'pi',
                            item.icon,
                            'mr-2',
                            item.color,
                            item.darkColor,
                        ]"
                    />
                    <span class="font-medium">{{ item.label }}:</span>
                    <span>{{ item.valor }}</span>
                </div>

                <button
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-green-700 p-3 transition sm:w-auto"
                    :class="
                        formValido
                            ? 'cursor-pointer bg-green-700 hover:bg-green-600'
                            : 'cursor-not-allowed bg-gray-400 opacity-60'
                    "
                    :disabled="!formValido"
                    @click="mostrarTabla"
                    v-if="!continuarCompra"
                >
                    <i class="pi pi-money-bill text-white" />
                    <span class="text-white">Simular plan de Pagos</span>
                </button>
                <button
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-green-700 bg-green-700 p-3 transition hover:bg-green-600 sm:w-auto"
                    @click="mostrarModal = true"
                    v-if="continuarCompra"
                >
                    <span class="text-white">Siguiente</span>
                    <i class="pi pi-arrow-right text-white" />
                </button>
            </div>

            <div
                class="mt-3 flex w-full flex-col rounded-2xl border-2 border-green-500 bg-white p-4 dark:border-green-700 dark:bg-gray-800"
            >
                <div class="flex gap-4">
                    <div class="mb-4 flex-1">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Tipo de compra</label
                        >
                        <select
                            v-model="tipoCompra"
                            class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            @change="verificarTipoCompra"
                        >
                            <option>En cuotas</option>
                            <option>Pago inmediato</option>
                        </select>
                    </div>

                    <div v-if="vendiendo" class="flex-1">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Descuento (%)</label
                        >
                        <input
                            type="number"
                            :value="'50'"
                            :disabled="true"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-3 text-gray-900 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <div v-if="vendiendo" class="flex-1">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Precio de venta</label
                        >
                        <input
                            type="number"
                            :value="Number(precioVenta).toFixed(2)"
                            :disabled="true"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-3 text-gray-900 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-4 sm:grid sm:grid-cols-4">
                    <div v-if="!vendiendo">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Cuota inicial (USD) *</label
                        >
                        <input
                            type="number"
                            placeholder="50,00"
                            v-model="cuotaInicial"
                            class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <div v-if="!vendiendo">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Plazo (Meses) *</label
                        >
                        <select
                            v-model="plazoSeleccionado"
                            class="w-full rounded-lg border border-gray-300 bg-white p-3 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option disabled value="">
                                Seleccione un plazo
                            </option>
                            <option
                                v-for="(value, index) in plazos_descuentos"
                                :key="index"
                                :value="value.plazo"
                            >
                                {{ value.plazo }}
                            </option>
                        </select>
                    </div>

                    <div v-if="!vendiendo">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Descuento (%)</label
                        >
                        <input
                            type="number"
                            :value="descuento"
                            class="w-full rounded-lg border border-gray-300 bg-gray-100 p-3 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>

                    <div v-if="!vendiendo">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Fecha 1er. Pago <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="fechaPrimerPago"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 shadow-sm transition-all duration-200 hover:border-gray-400 focus:border-white focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:border-gray-500"
                        />
                    </div>
                </div>
            </div>
            <div
                v-if="simuladoPlanPagos"
                class="mt-3 rounded-2xl border-2 border-green-500 bg-white p-4 text-start sm:grid sm:grid-cols-3 dark:border-green-700 dark:bg-gray-800"
            >
                <div
                    v-for="(value, index) in precios"
                    :key="index"
                    class="flex items-center gap-3 border-b border-gray-200 py-3 last:border-b-0 sm:justify-evenly dark:border-gray-700"
                >
                    <div class="flex items-center gap-2">
                        <i
                            class="pi pi-dollar text-green-600 dark:text-green-400"
                        />
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >{{ value.titulo }}</span
                        >
                    </div>
                    <span
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                        >${{ value.precio }}</span
                    >
                </div>
            </div>
        </div>

        <div v-if="simuladoPlanPagos" class="w-full sm:p-4 sm:pt-0">
            <div
                class="rounded-2xl border-2 border-green-500 p-3 dark:border-green-700 dark:bg-gray-800"
            >
                <h3
                    class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-100"
                >
                    Plan de Pagos
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="border-b border-gray-200 dark:border-gray-700"
                            >
                                <th
                                    class="pb-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400"
                                    v-for="(
                                        value, index
                                    ) in headerTablaPlanPago"
                                    :key="index"
                                >
                                    {{ value.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="overflow-auto-x h-full">
                            <tr
                                v-for="(item, index) in pagos"
                                :key="index"
                                class="border-b border-gray-100 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                            >
                                <td
                                    class="py-3 text-center text-gray-900 dark:text-gray-100"
                                >
                                    {{ index + 1 }}
                                </td>
                                <td
                                    class="py-3 text-center text-gray-700 dark:text-gray-300"
                                >
                                    {{ item.fecha }}
                                </td>
                                <td
                                    class="py-3 text-center text-gray-900 dark:text-gray-100"
                                >
                                    ${{ item.cuota }}
                                </td>
                                <td
                                    class="py-3 text-center text-gray-900 dark:text-gray-100"
                                >
                                    ${{ item.saldo }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="!pagos || pagos.length === 0"
                    class="py-8 text-center text-gray-500 dark:text-gray-400"
                >
                    <i class="pi pi-info-circle mb-2 text-3xl"></i>
                    <p>No hay datos para mostrar</p>
                </div>
            </div>
        </div>
    </div>
</template>
