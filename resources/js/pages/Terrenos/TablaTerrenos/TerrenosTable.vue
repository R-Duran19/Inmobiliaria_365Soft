<script setup lang="ts">
import type { Terreno } from '@/types/terrenos';
import TerrenosTableRow from './TerrenosTableRow.vue';

const props = defineProps<{ terrenos: Terreno[] }>();
const emit = defineEmits(['updated', 'deleted', 'costos']);

const headerNames = [
    'Acciones',
    'Proyecto',
    'Ubicación',
    'Categoria',
    'Superficie',
    'Costos',
];
</script>

<template>
    <div
        class="mx-6 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800"
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
                <TerrenosTableRow
                    v-for="t in terrenos"
                    :key="t.id"
                    :terreno="t"
                    @updated="emit('updated', $event)"
                    @deleted="emit('deleted', $event)"
                    @costos="emit('costos', $event)"
                />
            </tbody>
        </table>
    </div>
</template>
