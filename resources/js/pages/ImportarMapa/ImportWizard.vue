<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { computed, ref } from 'vue';
import StepAnalysis from './Steps/StepAnalysis.vue';
import StepConfirm from './Steps/StepConfirm.vue';
import StepConflicts from './Steps/StepConflicts.vue';
import StepMapping from './Steps/StepMapping.vue';
import StepResult from './Steps/StepResult.vue';
import StepUpload from './Steps/StepUpload.vue';

interface WizardData {
    file: File | null;
    geojson: any;
    analysis: any;
    mapping: any;
    conflicts: any;
    conflictResolution: 'overwrite_all' | 'skip_all' | 'ask';
    conflictDecisions: Record<number, 'overwrite' | 'skip'>;
    result: any;
}

const activeStep = ref(0);
const wizardData = ref<WizardData>({
    file: null,
    geojson: null,
    analysis: null,
    mapping: null,
    conflicts: null,
    conflictResolution: 'ask',
    conflictDecisions: {},
    result: null,
});

const steps = ref([
    { label: 'Cargar Archivo', icon: 'pi pi-upload' },
    { label: 'Análisis', icon: 'pi pi-search' },
    { label: 'Asignación', icon: 'pi pi-sitemap' },
    { label: 'Conflictos', icon: 'pi pi-exclamation-triangle' },
    { label: 'Confirmar', icon: 'pi pi-check-circle' },
    { label: 'Resultado', icon: 'pi pi-flag' },
]);

const canGoNext = computed(() => {
    switch (activeStep.value) {
        case 0:
            return (
                wizardData.value.file !== null &&
                wizardData.value.analysis !== null
            );
        case 1:
            return wizardData.value.analysis?.valid === true;
        case 2:
            return wizardData.value.mapping !== null;
        case 3:
            return wizardData.value.conflicts !== null;
        case 4:
            return true;
        default:
            return false;
    }
});

const canGoBack = computed(() => {
    return activeStep.value > 0 && activeStep.value < 5;
});

const nextStep = () => {
    if (canGoNext.value && activeStep.value < steps.value.length - 1) {
        activeStep.value++;
    }
};

const prevStep = () => {
    if (canGoBack.value) {
        activeStep.value--;
    }
};

const resetWizard = () => {
    activeStep.value = 0;
    wizardData.value = {
        file: null,
        geojson: null,
        analysis: null,
        mapping: null,
        conflicts: null,
        conflictResolution: 'ask',
        conflictDecisions: {},
        result: null,
    };
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[{ title: 'Importar Mapa', href: '/importarmapa' }]"
    >
        <Head title="Importar GeoJSON - Wizard" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2
                    class="text-xl font-semibold text-gray-800 dark:text-gray-200"
                >
                    Asistente de Importación GeoJSON
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Steps Navigation -->
                <!-- Steps Navigation -->
                <div
                    class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between text-center py-3">
                        <div
                            v-for="(step, index) in steps"
                            :key="index"
                            class="group relative flex-1 transition-all duration-300"
                        >
                            <!-- Línea de conexión entre pasos -->
                            <div
                                v-if="index < steps.length - 1"
                                class="absolute top-1/2 right-0 z-0 h-[2px] w-full translate-x-1/2 bg-gray-200 dark:bg-gray-600"
                            ></div>

                            <!-- Círculo del paso -->
                            <div
                                class="relative z-10 mx-auto flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300"
                                :class="[
                                    activeStep === index
                                        ? 'border-blue-500 bg-blue-100 text-blue-700 dark:border-blue-400 dark:bg-blue-900'
                                        : activeStep > index
                                          ? 'border-green-500 bg-green-100 text-green-700 dark:border-green-400 dark:bg-green-900'
                                          : 'border-gray-300 bg-gray-50 text-gray-400 dark:border-gray-600 dark:bg-gray-700',
                                ]"
                            >
                                <span class="text-sm font-semibold">{{
                                    index + 1
                                }}</span>
                            </div>

                            <!-- Etiqueta del paso -->
                            <div
                                class="mt-2 text-sm font-medium transition-all duration-300"
                                :class="[
                                    activeStep === index
                                        ? 'text-blue-600 dark:text-blue-400'
                                        : activeStep > index
                                          ? 'text-green-600 dark:text-green-400'
                                          : 'text-gray-500 dark:text-gray-400',
                                ]"
                            >
                                {{ step.label }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Content -->
                <!-- Step Content -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg transition-all duration-300 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="min-h-[500px] p-6">
                        <!-- Step 0: Upload -->
                        <StepUpload
                            v-if="activeStep === 0"
                            v-model:file="wizardData.file"
                            v-model:geojson="wizardData.geojson"
                            v-model:analysis="wizardData.analysis"
                            @next="nextStep"
                        />

                        <!-- Step 1: Analysis -->
                        <StepAnalysis
                            v-if="activeStep === 1"
                            :analysis="wizardData.analysis"
                            @next="nextStep"
                        />

                        <!-- Step 2: Mapping -->
                        <StepMapping
                            v-if="activeStep === 2"
                            :analysis="wizardData.analysis"
                            v-model:mapping="wizardData.mapping"
                            @next="nextStep"
                        />

                        <!-- Step 3: Conflicts -->
                        <StepConflicts
                            v-if="activeStep === 3"
                            :geojson="wizardData.geojson"
                            :mapping="wizardData.mapping"
                            v-model:conflicts="wizardData.conflicts"
                            v-model:resolution="wizardData.conflictResolution"
                            v-model:decisions="wizardData.conflictDecisions"
                            @next="nextStep"
                        />

                        <!-- Step 4: Confirm -->
                        <StepConfirm
                            v-if="activeStep === 4"
                            :wizard-data="wizardData"
                            @execute="
                                (result: WizardData['result']) => {
                                    wizardData.result = result;
                                    nextStep();
                                }
                            "
                        />

                        <!-- Step 5: Result -->
                        <StepResult
                            v-if="activeStep === 5"
                            :result="wizardData.result"
                            @reset="resetWizard"
                        />
                    </div>

                    <!-- Navigation Buttons -->
                    <div
                        class="flex items-center justify-between rounded-b-2xl border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div
                            class="rounded-xl border-2 border-blue-800 !bg-none p-2 text-white"
                        >
                            <Button
                                label="Anterior"
                                icon="pi pi-arrow-left"
                                @click="prevStep"
                                :disabled="!canGoBack"
                                outlined
                                class="!bg-transparent !text-blue-800 hover:!bg-blue-800/10 !border-none"
                            />
                        </div>

                        <div
                            class="!text-white-500 !dark:text-white-400 text-sm"
                        >
                            Paso {{ activeStep + 1 }} de {{ steps.length }}
                        </div>

                        <div
                            class="rounded-xl !bg-blue-800 p-2 hover:!bg-blue-700"
                        >
                            <Button
                                v-if="activeStep < 4"
                                label="Siguiente"
                                icon="pi pi-arrow-right"
                                iconPos="right"
                                @click="nextStep"
                                :disabled="!canGoNext"
                                class="!bg-blue-800 !text-white hover:!bg-blue-700"
                            />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
