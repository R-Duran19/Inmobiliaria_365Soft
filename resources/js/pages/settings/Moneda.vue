<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import MonedaTable from '../Moneda/MonedaTable.vue';
import { onMounted, ref, Ref } from 'vue';
import { Moneda } from '@/types/Moneda';
import axios from 'axios';
import FormMonedas from '../Moneda/FormMonedas.vue';

const monedas: Ref<Moneda[]> = ref([]);
const mostrarFormulario = ref(false);
const monedaEditar = ref<Moneda | null>(null);


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

function mostrarForm(){
  mostrarFormulario.value = !mostrarFormulario.value;
}

async function registrarMoneda(monedaData: Partial<Moneda>) {
    try {
        const { data } = await axios.post('/monedas', monedaData);
        monedas.value.push(data.moneda); 
        mostrarFormulario.value = false; 
    } catch (error) {
        console.error('Error al registrar la moneda:', error);
    }
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

            <MonedaTable :monedas="monedas" />
            <FormMonedas
                v-model="mostrarFormulario"
                :moneda="monedaEditar"
                @guardar="registrarMoneda"
            />
        </SettingsLayout>
    </AppLayout>
</template>
