<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Moneda } from '@/types/Moneda';
import { onMounted, ref, watch, computed } from 'vue';

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
const visible = defineModel<boolean>({ default: false });

const props = defineProps<{
    moneda?: Moneda | null;
}>();

const emit = defineEmits<{
    (e: 'guardar', moneda: Partial<Moneda>): void;
}>();

const form = ref({
    nombre: '',
    abreviacion: '',
    pais: '',
    tipo_cambio: 0,
    activo: true,
});

const cargando = ref(false);


const paisesFiltrados = computed(() => {
    if (!busqueda.value) return [];
    return paises.value.filter((pais) =>
        pais.toLowerCase().includes(busqueda.value.toLowerCase())
    );
});


function seleccionarPais(pais: string) {
    form.value.pais = pais;
    busqueda.value = '';
}

function limpiarFormulario() {
    form.value = {
        nombre: '',
        abreviacion: '',
        pais: '',
        tipo_cambio: 0,
        activo: true,
    };
}

watch(
    () => props.moneda,
    (nuevaMoneda) => {
        if (nuevaMoneda) {
            form.value = {
                nombre: nuevaMoneda.nombre ?? '',
                abreviacion: nuevaMoneda.abreviacion ?? '',
                pais: nuevaMoneda.pais ?? '',
                tipo_cambio: nuevaMoneda.tipo_cambio ?? 0,
                activo: nuevaMoneda.activo ?? true,
            };
        } else {
            limpiarFormulario();
        }
    },
    { immediate: true },
);

function cerrarDialog() {
    visible.value = false;
    limpiarFormulario();
}

async function guardarMoneda() {
    if (!form.value.nombre.trim())
        return alert('El nombre de la moneda es requerido');
    if (!form.value.abreviacion.trim())
        return alert('El código/abreviación es requerido');
    if (!form.value.pais.trim())
        return alert('El país es requerido');
    if (form.value.tipo_cambio <= 0)
        return alert('El tipo de cambio debe ser mayor a 0');

    cargando.value = true;
    try {
        emit('guardar', {
            ...form.value,
            ...(props.moneda?.id && { id: props.moneda.id }),
        });
        cerrarDialog();
    } catch (error) {
        console.error('Error al guardar moneda:', error);
        alert('Error al guardar la moneda');
    } finally {
        cargando.value = false;
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
   
    <Dialog :open="visible" @update:open="(val) => (visible = val)">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>
                    {{ moneda ? 'Editar Moneda' : 'Registrar Nueva Moneda' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        moneda
                            ? 'Modifica los datos de la moneda existente'
                            : 'Completa la información de la nueva moneda'
                    }}
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="nombre">Nombre de la Moneda *</Label>
                    <Input
                        id="nombre"
                        v-model="form.nombre"
                        placeholder="Ej: Boliviano"
                        :disabled="cargando"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="abreviacion">Código/Abreviación *</Label>
                    <Input
                        id="abreviacion"
                        v-model="form.abreviacion"
                        placeholder="Ej: Bs, USD, EUR"
                        maxlength="10"
                        :disabled="cargando"
                    />
                </div>

                <div class="grid gap-2 relative">
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
                        class="absolute top-full mt-1 w-full rounded-md border border-gray-300 bg-gray-900 shadow z-10 max-h-40 overflow-y-auto space-y-1"
                    >
                        <div
                            v-for="pais in paisesFiltrados"
                            :key="pais"
                            @click="seleccionarPais(pais)"
                            class="px-3 py-2 cursor-pointer hover:bg-gray-500"
                        >
                            {{ pais }}
                        </div>
                    </div>
                    
                    <p v-if="form.pais" class="text-sm text-gray-500">
                        País seleccionado: <strong>{{ form.pais }}</strong>
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="tipo_cambio">Tipo de Cambio *</Label>
                    <Input
                        id="tipo_cambio"
                        v-model.number="form.tipo_cambio"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Ej: 6.96"
                        :disabled="cargando"
                    />
                    <p class="text-xs text-gray-500">
                        Valor de conversión respecto a la moneda base
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="activo"
                        v-model="form.activo"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        :disabled="cargando"
                    />
                    <Label for="activo" class="cursor-pointer">Moneda activa</Label>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="cerrarDialog" :disabled="cargando">
                    Cancelar
                </Button>
                <Button @click="guardarMoneda" :disabled="cargando">
                    <span v-if="cargando">Guardando...</span>
                    <span v-else>{{ moneda ? 'Actualizar' : 'Registrar' }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
