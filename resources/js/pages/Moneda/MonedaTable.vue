<script setup lang="ts">

import { Moneda } from '@/types/Moneda';
import MonedaTableRow from '../Moneda/MonedaTableRow.vue';
import { onMounted } from 'vue';

const props = defineProps<{ monedas: Moneda[] }>();
// const emit = defineEmits(['updated', 'deleted', 'costos']);

const headerNames = [
    'Acciones',
    'Moneda',
    'Codigo',
    'País',
    'Tipo Cambio',
    'Fecha Actualización',
    'Estado',
];


onMounted(async () => {
    console.log('fdsfsadfdas fdffda ', props.monedas)
});



</script>

<template>
    <div
        class=" overflow-x-auto rounded-lg bg-white shadow-sm dark:bg-gray-800"
    >
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th
                        v-for="header in headerNames"
                        :key="header"
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-300"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>

            <tbody
                class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800"
            >
                <MonedaTableRow v-for="m in props.monedas"
                    :key="m.id"
                    :moneda="m"/>
            </tbody>
        </table>
    </div>
</template>
