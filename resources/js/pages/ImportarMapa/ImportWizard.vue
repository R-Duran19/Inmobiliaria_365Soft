<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import Steps from 'primevue/steps';
import Card from 'primevue/card';
import Button from 'primevue/button';
import StepUpload from './Steps/StepUpload.vue';
import StepAnalysis from './Steps/StepAnalysis.vue';
import StepMapping from './Steps/StepMapping.vue';
import StepConflicts from './Steps/StepConflicts.vue';
import StepConfirm from './Steps/StepConfirm.vue';
import StepResult from './Steps/StepResult.vue';
import AppLayout from '@/layouts/AppLayout.vue';


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
    case 0: return wizardData.value.file !== null && wizardData.value.analysis !== null;
    case 1: return wizardData.value.analysis?.valid === true;
    case 2: return wizardData.value.mapping !== null;
    case 3: return wizardData.value.conflicts !== null;
    case 4: return true;
    default: return false;
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
    <AppLayout :breadcrumbs="[{ title: 'Importar Mapa', href: '/importarmapa' }]">
  <Head title="Importar GeoJSON - Wizard" />


    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
          Asistente de Importación GeoJSON
        </h2>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Steps Navigation -->
        <Card class="mb-6">
          <template #content>
            <Steps 
              :model="steps" 
              :activeStep="activeStep"
              :readonly="false"
              class="mb-4"
            />
          </template>
        </Card>

        <!-- Step Content -->
        <Card>
          <template #content>
            <div class="min-h-[500px]">
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
                @execute="(result: WizardData['result']) => { wizardData.result = result; nextStep(); }"
              />

              <!-- Step 5: Result -->
              <StepResult
                v-if="activeStep === 5"
                :result="wizardData.result"
                @reset="resetWizard"
              />
            </div>
          </template>

          <!-- Navigation Buttons -->
          <template #footer>
            <div class="flex justify-between items-center pt-4 border-t">
              <Button
                label="Anterior"
                icon="pi pi-arrow-left"
                @click="prevStep"
                :disabled="!canGoBack"
                severity="secondary"
                outlined
              />

              <div class="text-sm text-gray-500">
                Paso {{ activeStep + 1 }} de {{ steps.length }}
              </div>

              <Button
                v-if="activeStep < 4"
                label="Siguiente"
                icon="pi pi-arrow-right"
                iconPos="right"
                @click="nextStep"
                :disabled="!canGoNext"
              />

              <Button
                v-if="activeStep === 5"
                label="Nueva Importación"
                icon="pi pi-refresh"
                @click="resetWizard"
                severity="success"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>