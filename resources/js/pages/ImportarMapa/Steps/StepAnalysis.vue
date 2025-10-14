<script setup lang="ts">
import { computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Panel from 'primevue/panel';

const props = defineProps<{
  analysis: any;
}>();

const emit = defineEmits<{
  'next': [];
}>();

const importTypeInfo = computed(() => {
  const types: Record<string, any> = {
    complete: {
      label: 'Importación Completa',
      description: 'Proyecto con barrios, cuadras y terrenos',
      severity: 'success',
      icon: 'pi-check-circle',
    },
    barrios_only: {
      label: 'Solo Barrios',
      description: 'Se importarán únicamente barrios',
      severity: 'info',
      icon: 'pi-map',
    },
    cuadras_terrenos: {
      label: 'Cuadras con Terrenos',
      description: 'Se importarán cuadras y terrenos',
      severity: 'info',
      icon: 'pi-th-large',
    },
    cuadras_only: {
      label: 'Solo Cuadras',
      description: 'Se importarán únicamente cuadras',
      severity: 'info',
      icon: 'pi-th-large',
    },
    terrenos_only: {
      label: 'Solo Terrenos',
      description: 'Se importarán únicamente terrenos',
      severity: 'warning',
      icon: 'pi-stop',
    },
  };

  return types[props.analysis?.import_type] || {
    label: 'Desconocido',
    description: 'Tipo de importación no identificado',
    severity: 'danger',
    icon: 'pi-exclamation-triangle',
  };
});
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <i class="pi pi-chart-bar text-6xl text-blue-500 mb-4"></i>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        Análisis del Archivo
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Revisa los detalles detectados en tu archivo GeoJSON
      </p>
    </div>

    <!-- Import Type Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
      <div class="flex items-center gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-full p-4 shadow-md">
          <i :class="['pi', importTypeInfo.icon, 'text-3xl text-blue-600']"></i>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <h4 class="text-xl font-bold text-gray-800 dark:text-gray-200">
              {{ importTypeInfo.label }}
            </h4>
            <Tag :severity="importTypeInfo.severity" :value="analysis.import_type.toUpperCase()" />
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            {{ importTypeInfo.description }}
          </p>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Barrios -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <i class="pi pi-map text-3xl text-purple-500"></i>
          <Tag :value="analysis.barrios.count" severity="success" class="text-lg px-3 py-1" />
        </div>
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">
          Barrios
        </h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Unidades vecinales detectadas
        </p>
      </div>

      <!-- Cuadras -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <i class="pi pi-th-large text-3xl text-blue-500"></i>
          <Tag :value="analysis.cuadras.count" severity="info" class="text-lg px-3 py-1" />
        </div>
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">
          Cuadras
        </h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Manzanas detectadas
        </p>
      </div>

      <!-- Terrenos -->
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <i class="pi pi-stop text-3xl text-green-500"></i>
          <Tag :value="analysis.terrenos.count" severity="warning" class="text-lg px-3 py-1" />
        </div>
        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">
          Terrenos
        </h4>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Lotes detectados
        </p>
      </div>
    </div>

    <!-- Detailed Tables -->
    <div class="space-y-4">
      <!-- Barrios Table -->
      <Panel v-if="analysis.barrios.count > 0" header="Barrios Detectados" toggleable>
        <DataTable 
          :value="analysis.barrios.items" 
          :rows="5" 
          :paginator="analysis.barrios.count > 5"
          class="text-sm"
        >
          <Column field="nombre" header="Nombre" sortable>
            <template #body="{ data }">
              <Tag :value="data.nombre" severity="info" />
            </template>
          </Column>
          <Column field="index" header="Índice en archivo" sortable />
        </DataTable>
      </Panel>

      <!-- Cuadras Table -->
      <Panel v-if="analysis.cuadras.count > 0" header="Cuadras Detectadas" toggleable>
        <DataTable 
          :value="analysis.cuadras.items" 
          :rows="5" 
          :paginator="analysis.cuadras.count > 5"
          class="text-sm"
        >
          <Column field="nombre" header="Nombre" sortable>
            <template #body="{ data }">
              <Tag :value="data.nombre" severity="success" />
            </template>
          </Column>
          <Column field="barrio_ref" header="Barrio relacionado" sortable>
            <template #body="{ data }">
              <Tag v-if="data.barrio_ref" :value="data.barrio_ref" severity="info" />
              <span v-else class="text-gray-400">Sin relación</span>
            </template>
          </Column>
          <Column field="index" header="Índice" sortable />
        </DataTable>
      </Panel>

      <!-- Terrenos Table -->
      <Panel v-if="analysis.terrenos.count > 0" header="Terrenos Detectados" toggleable>
        <DataTable 
          :value="analysis.terrenos.items" 
          :rows="5" 
          :paginator="analysis.terrenos.count > 5"
          class="text-sm"
        >
          <Column field="numero" header="Número" sortable>
            <template #body="{ data }">
              <Tag :value="`Lote ${data.numero}`" severity="warning" />
            </template>
          </Column>
          <Column field="cuadra_ref" header="Cuadra relacionada" sortable>
            <template #body="{ data }">
              <Tag v-if="data.cuadra_ref" :value="data.cuadra_ref" severity="success" />
              <span v-else class="text-gray-400">Sin relación</span>
            </template>
          </Column>
          <Column field="index" header="Índice" sortable />
        </DataTable>
      </Panel>
    </div>
  </div>
</template>