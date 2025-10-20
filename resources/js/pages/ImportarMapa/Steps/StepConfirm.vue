<script setup lang="ts">
import axios from 'axios';
import { computed, ref } from 'vue';

interface WizardData {
    geojson: any;
    analysis: any;
    mapping: any;
    conflicts: any;
    conflictResolution: 'overwrite_all' | 'skip_all' | 'ask';
    conflictDecisions: Record<number, 'overwrite' | 'skip'>;
}

const props = defineProps<{
    wizardData: WizardData;
}>();

const emit = defineEmits<{
    execute: [result: any];
}>();

const executing = ref(false);
const error = ref<string | null>(null);

const typeLabel = (type: string) => {
    const labels: Record<string, string> = {
        complete: 'Proyecto Completo',
        barrios_only: 'Solo Barrios',
        cuadras_terrenos: 'Cuadras con Terrenos',
        cuadras_only: 'Solo Cuadras',
        terrenos_only: 'Solo Terrenos',
    };
    return labels[type] || type;
};

const resolutionLabel = (resolution: string) => {
    const labels: Record<string, string> = {
        overwrite_all: 'Reemplazar todos los conflictos',
        skip_all: 'Omitir todos los conflictos',
        ask: 'Decidir caso por caso',
    };
    return labels[resolution] || resolution;
};

const getConflictCount = computed(() => {
    if (!props.wizardData.conflicts) return 0;
    const c = props.wizardData.conflicts;
    return (
        (c.barrios?.length || 0) +
        (c.cuadras?.length || 0) +
        (c.terrenos?.length || 0)
    );
});

const executeImport = async () => {
    executing.value = true;
    error.value = null;

    try {
        const response = await axios.post('/api/import/execute', {
            geojson: props.wizardData.geojson,
            mapping: props.wizardData.mapping,
            conflictResolution: props.wizardData.conflictResolution,
            conflictDecisions: props.wizardData.conflictDecisions,
        });

        if (response.data.success) {
            emit('execute', response.data);
        } else {
            error.value =
                response.data.message || 'Error durante la importación';
        }
    } catch (err: any) {
        error.value =
            err.response?.data?.message || 'Error al ejecutar la importación';
        console.error(err);
    } finally {
        executing.value = false;
    }
};
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-8 p-6">
        <div class="mb-10 text-center">
            <div
                class="mb-5 inline-flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-green-800 to-green-900 shadow-green-500/30"
            >
                <i class="pi pi-check-circle text-4xl text-white"></i>
            </div>
            <h3
                class="mb-2 text-3xl font-bold tracking-tight text-green-600 dark:text-gray-100"
            >
                Revisar Importación
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Revisa los detalles antes de ejecutar la importación
            </p>
        </div>

        <div
            v-if="error"
            class="relative mb-6 rounded-xl border border-red-300 bg-red-100 px-4 py-3 text-red-700"
        >
            <div class="flex items-start justify-between">
                <p>{{ error }}</p>
                <button
                    @click="error = null"
                    class="font-bold text-red-700 hover:text-red-900"
                >
                    ×
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div
                class="rounded-2xl border border-blue-200 shadow-sm transition-all duration-300 hover:shadow-md dark:border-blue-800"
            >
                <div
                    class="rounded-t-2xl bg-blue-100 px-6 py-3 dark:bg-blue-900/30"
                >
                    <h4
                        class="flex items-center gap-2 text-lg font-semibold text-blue-800 dark:text-blue-200"
                    >
                        <i class="pi pi-info-circle"></i> Tipo de Importación
                    </h4>
                </div>
                <div
                    class="flex items-center justify-between rounded-b-2xl bg-gray-50 p-4 dark:bg-gray-800"
                >
                    <span class="text-gray-700 dark:text-gray-300"
                        >Tipo detectado:</span
                    >
                    <span
                        class="font-semibold text-blue-600 dark:text-blue-400"
                    >
                        {{ typeLabel(wizardData.analysis?.import_type) }}
                    </span>
                </div>
            </div>

            <div
                class="rounded-2xl border border-green-200 shadow-sm transition-all duration-300 hover:shadow-md dark:border-green-800"
            >
                <div
                    class="rounded-t-2xl bg-green-100 px-6 py-3 dark:bg-green-900/30"
                >
                    <h4
                        class="flex items-center gap-2 text-lg font-semibold text-green-800 dark:text-green-200"
                    >
                        <i class="pi pi-th"></i> Elementos a Importar
                    </h4>
                </div>
                <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4">
                    <div
                        class="rounded-xl bg-blue-50 p-4 text-center dark:bg-blue-900/20"
                    >
                        <div class="text-3xl font-extrabold text-blue-600">
                            {{ wizardData.analysis?.barrios?.count || 0 }}
                        </div>
                        <p
                            class="mt-1 text-xs tracking-wide text-gray-600 uppercase dark:text-gray-400"
                        >
                            Barrios
                        </p>
                    </div>
                    <div
                        class="rounded-xl bg-orange-50 p-4 text-center dark:bg-orange-900/20"
                    >
                        <div class="text-3xl font-extrabold text-orange-600">
                            {{ wizardData.analysis?.cuadras?.count || 0 }}
                        </div>
                        <p
                            class="mt-1 text-xs tracking-wide text-gray-600 uppercase dark:text-gray-400"
                        >
                            Cuadras
                        </p>
                    </div>
                    <div
                        class="rounded-xl bg-purple-50 p-4 text-center dark:bg-purple-900/20"
                    >
                        <div class="text-3xl font-extrabold text-purple-600">
                            {{ wizardData.analysis?.terrenos?.count || 0 }}
                        </div>
                        <p
                            class="mt-1 text-xs tracking-wide text-gray-600 uppercase dark:text-gray-400"
                        >
                            Terrenos
                        </p>
                    </div>
                    <div
                        class="rounded-xl bg-gray-100 p-4 text-center dark:bg-gray-800"
                    >
                        <div
                            class="text-3xl font-extrabold text-gray-700 dark:text-gray-200"
                        >
                            {{
                                (wizardData.analysis?.barrios?.count || 0) +
                                (wizardData.analysis?.cuadras?.count || 0) +
                                (wizardData.analysis?.terrenos?.count || 0)
                            }}
                        </div>
                        <p
                            class="mt-1 text-xs tracking-wide text-gray-600 uppercase dark:text-gray-400"
                        >
                            Total
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-indigo-200 shadow-sm transition-all duration-300 hover:shadow-md dark:border-indigo-800"
            >
                <div
                    class="rounded-t-2xl bg-indigo-100 px-6 py-3 dark:bg-indigo-900/30"
                >
                    <h4
                        class="flex items-center gap-2 text-lg font-semibold text-indigo-800 dark:text-indigo-200"
                    >
                        <i class="pi pi-sitemap"></i> Asignación de Entidades
                    </h4>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <span class="text-gray-700 dark:text-gray-300"
                            >Proyecto:</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >ID {{ wizardData.mapping?.idproyecto }}</span
                        >
                    </div>
                    <div
                        v-if="wizardData.mapping?.idbarrio"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <span class="text-gray-700 dark:text-gray-300"
                            >Barrio:</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >ID {{ wizardData.mapping?.idbarrio }}</span
                        >
                    </div>
                    <div
                        v-if="wizardData.mapping?.idcuadra"
                        class="flex items-center justify-between rounded-b-xl p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <span class="text-gray-700 dark:text-gray-300"
                            >Cuadra:</span
                        >
                        <span
                            class="font-medium text-gray-900 dark:text-gray-100"
                            >ID {{ wizardData.mapping?.idcuadra }}</span
                        >
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-yellow-200 shadow-sm transition-all duration-300 hover:shadow-md dark:border-yellow-800"
            >
                <div
                    class="rounded-t-2xl bg-yellow-100 px-6 py-3 dark:bg-yellow-900/30"
                >
                    <h4
                        class="flex items-center gap-2 text-lg font-semibold text-yellow-800 dark:text-yellow-200"
                    >
                        <i class="pi pi-exclamation-triangle"></i> Gestión de
                        Conflictos
                    </h4>
                </div>
                <div class="space-y-4 p-4">
                    <div
                        class="flex items-center justify-between rounded-xl bg-gray-50 p-3 dark:bg-gray-800"
                    >
                        <span class="text-gray-700 dark:text-gray-300"
                            >Conflictos detectados:</span
                        >
                        <span
                            class="font-semibold text-yellow-600 dark:text-yellow-400"
                            >{{ getConflictCount }}</span
                        >
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div
                        class="flex items-center justify-between rounded-xl bg-blue-50 p-3 dark:bg-blue-900/20"
                    >
                        <span class="text-gray-700 dark:text-gray-300"
                            >Estrategia:</span
                        >
                        <span
                            class="font-semibold text-blue-600 dark:text-blue-400"
                        >
                            {{ resolutionLabel(wizardData.conflictResolution) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>


        <div
            v-if="executing"
            class="flex flex-col items-center justify-center py-10"
        >
            <div
                class="h-12 w-12 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"
            ></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Ejecutando importación...
            </p>
        </div>

        <div v-if="!executing" class="flex justify-center pt-6">
            <button
                @click="executeImport"
                class="flex items-center gap-2 rounded-xl bg-green-900 px-8 py-3 text-lg font-semibold text-white shadow-md transition-all hover:bg-green-700 hover:shadow-lg"
            >
                <i class="pi pi-check"></i> Ejecutar Importación
            </button>
        </div>
    </div>
</template>
