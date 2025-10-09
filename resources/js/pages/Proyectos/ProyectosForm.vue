<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref } from 'vue';

const emit = defineEmits(['save', 'cancel']);
let barrios_send: { idproyecto: number; barrios: string[] } | null = null;

const cantidadesCuadras = ref<number[]>([]);

let barriosRegistradosIds: number[] = []; 

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const form = useForm({
    nombre: '',
    descripcion: '',
    fecha_lanzamiento: undefined as string | undefined,
    numero_lotes: undefined as number | undefined,
    ubicacion: undefined as string | undefined,
    fotografia: undefined as File | string | undefined,
});

const uvInicio = ref(0);
const uvFin = ref(0);

const formBarrios = useForm({
    idproyecto: 0 as number,
    barrios: [] as string[],
});

const formCuadras = useForm({
    idbarrio: 0 as number,
    cuadras: [] as string[],
});

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}

const getUltimoIdProyectos = async (): Promise<{
    UltimoProyecto: number;
} | null> => {
    try {
        const response = await axios.get(`/proyectos/ultimoId`);
        console.log('REVISAR AQUI ', response)
        console.log('REVISAR AQUI ', response.data)
        console.log('REVISAR AQUI ', response.data.UltimoProyecto)
        return response.data;
    } catch (err) {
        console.error('Error al cargar último id:', err);
        return null;
    }
};

const getUltimoNombreCuadra = async (): Promise<string> => {
    try {
        const response = await axios.get('/cuadras/UltNombreCuadra');
        if (response.data.nombre == 1) {
            response.data.nombre = 'MZ 000';
        }
        return response.data?.nombre;
    } catch (err) {
        console.error('Error al obtener el último nombre de cuadra:', err);
        return 'MZ 000';
    }
};

const generarUVs = async () => {
    const inicio = uvInicio.value;
    const fin = uvFin.value;

    if (isNaN(inicio) || isNaN(fin) || inicio > fin) {
        alert('⚠️ El rango no es válido.');
        return;
    }

    const respuesta = await getUltimoIdProyectos();
    const ultimoIdProyecto = respuesta?.UltimoProyecto ?? 0;
    console.log('MOSTRANSO ID ', ultimoIdProyecto);

    const barrios: string[] = [];
    for (let i = inicio; i <= fin; i++) {
        const codigo = `UV ${i.toString().padStart(3, '0')}`;
        barrios.push(codigo);
    }

    formBarrios.barrios = barrios;
    formCuadras.cuadras = new Array(barrios.length).fill('');

    console.log('✅ Barrios generados:', JSON.stringify(barrios_send, null, 2));
};

const handleCancel = () => {
    emit('cancel');
    form.reset();
};

const formatearMZ = (valor: string) => {
    return `MZ ${valor.padStart(3, '0')}`;
};
const handleSubmit = () => {
    console.log('proororooro ', form);
    form.post('/proyectos', {
        onSuccess: () => {
            form.reset();
        },
    });
};

async function guardarBarrios() {
    try {
        const response = await axios.post('/barrios/postBarrios', {
            idproyecto: formBarrios.idproyecto,
            barrios: formBarrios.barrios,
        });
        console.log('Barrios registrados fasfdsafsad', response.data.barrios);
        
        if (response.data.barrios && Array.isArray(response.data.barrios)) {
            barriosRegistradosIds = [
                ...barriosRegistradosIds,
                ...response.data.barrios,
            ];
        }
        console.log('Barrios registrados correctamente', response.data);
        return response.data;
    } catch (err) {
        console.error('Error desconocido', err);
        throw err;
    }
}






const generarCuadrasSecuenciales = async (idsBarriosRegistrados: number[]) => {
    const respuestaUltima = await getUltimoNombreCuadra();
    const ultimoNombre = respuestaUltima;


    
    const resultado: { idbarrio: number; cuadras: string[] }[] = [];

    idsBarriosRegistrados.forEach((idBarrio, index) => {
        const cantidad = Number(cantidadesCuadras.value[index]) || 0;
        const cuadrasBarrio: string[] = [];
        let contador = 0;

        for (let i = 0; i < cantidad; i++) {
            contador++;
            const codigo = `MZ ${contador.toString().padStart(3, '0')}`;
            cuadrasBarrio.push(codigo);
        }

        
        resultado.push({
            idbarrio: idBarrio, 
            cuadras: cuadrasBarrio,
        });
    });

    console.log('Resultado generado ✅', resultado);
    return resultado;
};

const guardarCuadrasSecuenciales = async (idsBarriosRegistrados: number[]) => {
    try {
        const data = await generarCuadrasSecuenciales(idsBarriosRegistrados);
        console.log('datos finales ', data);
        await axios.post('/cuadras/postCuadras', data);
        console.log('✅ Cuadras registradas correctamente');
    } catch (error) {
        console.error('❌ Error al registrar cuadras:', error);
    }
};



async function guardarDatos() {
    await handleSubmit();
    const respuesta = await getUltimoIdProyectos();
    console.log('ididididid ', respuesta)
    const ultimoIdProyecto = respuesta?.UltimoProyecto ?? 0;
    formBarrios.idproyecto = ultimoIdProyecto;

    const responseBarrios = await guardarBarrios();

    const idsBarriosRegistrados = responseBarrios.barrios;
    console.log('XDDDDD ', idsBarriosRegistrados);

    await guardarCuadrasSecuenciales(idsBarriosRegistrados);

    emit('save');
}


</script>

<template>
    
    <div class="max-h-[70vh] overflow-y-auto p-2">
        <form @submit.prevent="handleSubmit" class="space-y-6">
            
            <div class="space-y-2">
                <Label
                    for="nombre"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Nombre del Proyecto *</Label
                >
                <Input
                    id="nombre"
                    v-model="form.nombre"
                    placeholder="Ej: Proyecto casas nueva"
                    required
                    class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>

            
            <div class="space-y-2">
                <Label
                    for="descripcion"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Descripción</Label
                >
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    placeholder="Detalles del proyecto..."
                    rows="5"
                    class="w-full rounded-md border p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>

            
            <div class="space-y-4">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label
                            for="uvInicio"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            UV Inicio
                        </Label>
                        <Input
                            id="uvInicio"
                            v-model="uvInicio"
                            type="number"
                            class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>

                    <div>
                        <Label
                            for="uvFin"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            UV Fin
                        </Label>
                        <Input
                            id="uvFin"
                            v-model="uvFin"
                            type="number"
                            class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        />
                    </div>
                </div>

                
                <div
                    class="sticky bottom-0 border-t bg-white px-2 dark:bg-gray-800"
                >
                    <div class="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="default"
                            @click="generarUVs"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing ? 'Guardando...' : 'Generar UVs'
                            }}
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="formBarrios.barrios.length" class="mt-4 space-y-2">
                <p class="text-sm font-semibold">
                    Registrar cuadras para cada barrio:
                </p>

                <div
                    v-for="(barrio, index) in formBarrios.barrios"
                    :key="index"
                    class="flex items-center gap-3"
                >
                    <span class="w-32 font-medium">
                        {{ barrio.replace('UV', 'Barrio') }}
                    </span>

                    <Input
                        type="number"
                        v-model="cantidadesCuadras[index]"
                        placeholder="Registrar cuadras"
                        class="w-28"
                    />
                </div>
            </div>

            
            <div class="space-y-2">
                <Label
                    for="fecha_lanzamiento"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Fecha de Lanzamiento</Label
                >
                <Input
                    id="fecha_lanzamiento"
                    v-model="form.fecha_lanzamiento"
                    type="date"
                    class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>

            
            <div class="space-y-2">
                <Label
                    for="numero_lotes"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Número de Lotes</Label
                >
                <Input
                    id="numero_lotes"
                    v-model.number="form.numero_lotes"
                    type="number"
                    class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>

            
            <div class="space-y-2">
                <Label
                    for="ubicacion"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Ubicación</Label
                >
                <Input
                    id="ubicacion"
                    v-model="form.ubicacion"
                    placeholder="Ej: Calle Principal 123"
                    class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>

            
            <div class="space-y-2">
                <Label
                    for="fotografia"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Fotografía</Label
                >
                <Input
                    id="fotografia"
                    type="file"
                    @input="form.fotografia = $event.target.files[0]"
                    class="w-full dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>
        </form>

        
        <div
            class="sticky bottom-0 border-t bg-white px-2 py-4 dark:bg-gray-800"
        >
            <div class="flex justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    @click="
                        () => {
                            handleCancel();
                            emit('cancel');
                        }
                    "
                    class="dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                >
                    Cancelar
                </Button>
                <Button
                    type="button"
                    variant="default"
                    @click="
                        () => {
                            guardarDatos();
                            emit('save');
                        }
                    "
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
                </Button>
            </div>
        </div>

    </div>
</template>
