<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import Divider from 'primevue/divider';

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
  'execute': [result: any];
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
  return (c.barrios?.length || 0) + (c.cuadras?.length || 0) + (c.terrenos?.length || 0);
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
      error.value = response.data.message || 'Error durante la importación';
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Error al ejecutar la importación';
    console.error(err);
  } finally {
    executing.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <i class="pi pi-check-circle text-5xl text-green-500 mb-4 block"></i>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        Revisar Importación
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Revisa los detalles antes de ejecutar la importación
      </p>
    </div>

    <div v-if="error" class="mb-6">
      <Message 
        severity="error"
        :closable="true"
        @close="error = null"
      >
        {{ error }}
      </Message>
    </div>

    <div class="space-y-4">
      <!-- Tipo de Importación -->
      <Card>
        <template #header>
          <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
            <h4 class="font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2">
              <i class="pi pi-info-circle"></i>
              Tipo de Importación
            </h4>
          </div>
        </template>
        <template #content>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded">
              <span class="text-gray-700 dark:text-gray-300">Tipo detectado:</span>
              <span class="font-semibold text-blue-600 dark:text-blue-400">
                {{ typeLabel(wizardData.analysis?.import_type) }}
              </span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Cantidad de Elementos -->
      <Card>
        <template #header>
          <div class="px-6 py-4 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800">
            <h4 class="font-semibold text-green-800 dark:text-green-200 flex items-center gap-2">
              <i class="pi pi-th"></i>
              Elementos a Importar
            </h4>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-4 gap-4">
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
              <div class="text-2xl font-bold text-blue-600">
                {{ wizardData.analysis?.barrios?.count || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                Barrios
              </div>
            </div>
            <div class="text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded">
              <div class="text-2xl font-bold text-orange-600">
                {{ wizardData.analysis?.cuadras?.count || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                Cuadras
              </div>
            </div>
            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded">
              <div class="text-2xl font-bold text-purple-600">
                {{ wizardData.analysis?.terrenos?.count || 0 }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                Terrenos
              </div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded">
              <div class="text-2xl font-bold text-gray-600 dark:text-gray-300">
                {{ (wizardData.analysis?.barrios?.count || 0) + 
                   (wizardData.analysis?.cuadras?.count || 0) + 
                   (wizardData.analysis?.terrenos?.count || 0) }}
              </div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                Total
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Asignación -->
      <Card>
        <template #header>
          <div class="px-6 py-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800">
            <h4 class="font-semibold text-indigo-800 dark:text-indigo-200 flex items-center gap-2">
              <i class="pi pi-sitemap"></i>
              Asignación de Entidades
            </h4>
          </div>
        </template>
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-800">
              <span class="text-gray-700 dark:text-gray-300">Proyecto:</span>
              <span class="font-medium text-gray-800 dark:text-gray-200">
                ID {{ wizardData.mapping?.idproyecto }}
              </span>
            </div>
            <div v-if="wizardData.mapping?.idbarrio" class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-800">
              <span class="text-gray-700 dark:text-gray-300">Barrio:</span>
              <span class="font-medium text-gray-800 dark:text-gray-200">
                ID {{ wizardData.mapping?.idbarrio }}
              </span>
            </div>
            <div v-if="wizardData.mapping?.idcuadra" class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-800">
              <span class="text-gray-700 dark:text-gray-300">Cuadra:</span>
              <span class="font-medium text-gray-800 dark:text-gray-200">
                ID {{ wizardData.mapping?.idcuadra }}
              </span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Conflictos -->
      <Card>
        <template #header>
          <div class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
            <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
              <i class="pi pi-exclamation-triangle"></i>
              Gestión de Conflictos
            </h4>
          </div>
        </template>
        <template #content>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded">
              <span class="text-gray-700 dark:text-gray-300">Conflictos detectados:</span>
              <span class="font-semibold text-yellow-600">
                {{ getConflictCount }}
              </span>
            </div>
            <Divider />
            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
              <span class="text-gray-700 dark:text-gray-300">Estrategia:</span>
              <span class="font-semibold text-blue-600 dark:text-blue-400">
                {{ resolutionLabel(wizardData.conflictResolution) }}
              </span>
            </div>
          </div>
        </template>
      </Card>

      <!-- Warning if conflicts with ask strategy -->
      <Message 
        v-if="wizardData.conflictResolution === 'ask' && getConflictCount > 0"
        severity="warning"
        :closable="false"
      >
        <div class="flex items-start gap-2">
          <i class="pi pi-exclamation-circle mt-1"></i>
          <div>
            <p class="font-semibold">Decisiones pendientes</p>
            <p class="text-sm">
              Debes marcar qué hacer con cada uno de los {{ getConflictCount }} conflictos
            </p>
          </div>
        </div>
      </Message>

      <!-- Ready to import -->
      <Message 
        v-else
        severity="success"
        :closable="false"
      >
        <div class="flex items-start gap-2">
          <i class="pi pi-check-circle mt-1"></i>
          <div>
            <p class="font-semibold">Listo para importar</p>
            <p class="text-sm">
              Todos los parámetros están configurados correctamente
            </p>
          </div>
        </div>
      </Message>
    </div>

    <!-- Loading State -->
    <div v-if="executing" class="flex flex-col items-center justify-center py-12">
      <ProgressSpinner 
        style="width: 50px; height: 50px"
        strokeWidth="4"
        animationDuration="1s"
      />
      <p class="mt-4 text-gray-600 dark:text-gray-400">
        Ejecutando importación...
      </p>
    </div>

    <!-- Execute Button -->
    <div v-if="!executing" class="flex justify-center pt-4">
      <Button
        label="Ejecutar Importación"
        icon="pi pi-check"
        size="large"
        @click="executeImport"
        severity="success"
      />
    </div>
  </div>
</template>

        <!-- :disabled="wizardData.conflictResolution === 'ask' && getConflictCount > 0" -->
