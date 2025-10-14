<script setup lang="ts">
import { computed } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Divider from 'primevue/divider';

interface Result {
  summary?: {
    barrios: { creados: number; actualizados: number; omitidos: number };
    cuadras: { creadas: number; actualizadas: number; omitidas: number };
    terrenos: { creados: number; actualizados: number; omitidos: number };
  };
  message?: string;
}

const props = defineProps<{
  result: Result | null;
}>();

const emit = defineEmits<{
  reset: [];
}>();

const summary = computed(() => props.result?.summary || null);

const totalCreated = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.creados || 0) +
    (summary.value.cuadras?.creadas || 0) +
    (summary.value.terrenos?.creados || 0)
  );
});

const totalUpdated = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.actualizados || 0) +
    (summary.value.cuadras?.actualizadas || 0) +
    (summary.value.terrenos?.actualizados || 0)
  );
});

const totalSkipped = computed(() => {
  if (!summary.value) return 0;
  return (
    (summary.value.barrios?.omitidos || 0) +
    (summary.value.cuadras?.omitidas || 0) +
    (summary.value.terrenos?.omitidos || 0)
  );
});

const totalProcessed = computed(() => {
  return totalCreated.value + totalUpdated.value + totalSkipped.value;
});
</script>

<template>
  <div class="space-y-6">
    <!-- Success Header -->
    <div class="text-center mb-8">
      <i class="pi pi-flag text-6xl text-green-500 mb-4 block animate-bounce"></i>
      <h3 class="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        ¡Importación Completada!
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Los datos se han importado exitosamente
      </p>
    </div>

    <!-- Success Message -->
    <Message severity="success" :closable="false">
      <div class="flex items-start gap-2">
        <i class="pi pi-check-circle mt-1 text-xl"></i>
        <div>
          <p class="font-semibold text-lg">Proceso exitoso</p>
          <p class="text-sm mt-1">{{ result?.message }}</p>
        </div>
      </div>
    </Message>

    <!-- Summary Grid -->
    <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Created -->
      <Card class="border-l-4 border-l-green-500">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-gray-400 text-sm">Creados</span>
              <i class="pi pi-plus text-green-500 text-xl"></i>
            </div>
            <div class="text-4xl font-bold text-green-600">
              {{ totalCreated }}
            </div>
          </div>
        </template>
      </Card>

      <!-- Updated -->
      <Card class="border-l-4 border-l-blue-500">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-gray-400 text-sm">Actualizados</span>
              <i class="pi pi-refresh text-blue-500 text-xl"></i>
            </div>
            <div class="text-4xl font-bold text-blue-600">
              {{ totalUpdated }}
            </div>
          </div>
        </template>
      </Card>

      <!-- Skipped -->
      <Card class="border-l-4 border-l-gray-500">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-gray-400 text-sm">Omitidos</span>
              <i class="pi pi-times text-gray-500 text-xl"></i>
            </div>
            <div class="text-4xl font-bold text-gray-600">
              {{ totalSkipped }}
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Detailed Results -->
    <Card v-if="summary">
      <template #header>
        <div
          class="px-6 py-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800">
          <h4
            class="font-semibold text-indigo-800 dark:text-indigo-200 flex items-center gap-2">
            <i class="pi pi-th"></i>
            Detalles por Tipo
          </h4>
        </div>
      </template>

      <template #content>
        <div class="space-y-6">
          <!-- Barrios -->
          <div>
            <h5
              class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
              <i class="pi pi-map text-green-600"></i>
              Barrios
            </h5>
            <div class="grid grid-cols-3 gap-3 ml-6">
              <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                <div class="text-2xl font-bold text-green-600">
                  {{ summary.barrios?.creados || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Creados</div>
              </div>
              <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                <div class="text-2xl font-bold text-blue-600">
                  {{ summary.barrios?.actualizados || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Actualizados</div>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-2xl font-bold text-gray-600">
                  {{ summary.barrios?.omitidos || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Omitidos</div>
              </div>
            </div>
          </div>

          <Divider />

          <!-- Cuadras -->
          <div>
            <h5
              class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
              <i class="pi pi-square text-orange-600"></i>
              Cuadras
            </h5>
            <div class="grid grid-cols-3 gap-3 ml-6">
              <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                <div class="text-2xl font-bold text-green-600">
                  {{ summary.cuadras?.creadas || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Creadas</div>
              </div>
              <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                <div class="text-2xl font-bold text-blue-600">
                  {{ summary.cuadras?.actualizadas || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Actualizadas</div>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-2xl font-bold text-gray-600">
                  {{ summary.cuadras?.omitidas || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Omitidas</div>
              </div>
            </div>
          </div>

          <Divider />

          <!-- Terrenos -->
          <div>
            <h5
              class="font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
              <i class="pi pi-home text-purple-600"></i>
              Terrenos
            </h5>
            <div class="grid grid-cols-3 gap-3 ml-6">
              <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                <div class="text-2xl font-bold text-green-600">
                  {{ summary.terrenos?.creados || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Creados</div>
              </div>
              <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                <div class="text-2xl font-bold text-blue-600">
                  {{ summary.terrenos?.actualizados || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Actualizados</div>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-2xl font-bold text-gray-600">
                  {{ summary.terrenos?.omitidos || 0 }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Omitidos</div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Total Summary -->
    <Card class="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
          <div>
            <div class="text-3xl font-bold text-green-600">{{ totalProcessed }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Elementos Procesados</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-green-600">{{ totalCreated }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Creados</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-blue-600">{{ totalUpdated }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Actualizados</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-gray-600">{{ totalSkipped }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Omitidos</div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Reset Button -->
    <div class="text-center mt-6">
      <Button
        label="Volver a importar"
        icon="pi pi-refresh"
        class="p-button-outlined p-button-primary"
        @click="emit('reset')"
      />
    </div>
  </div>
</template>
