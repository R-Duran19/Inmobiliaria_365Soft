<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import RadioButton from 'primevue/radiobutton';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

interface Conflict {
  index: number;
  nombre?: string;
  numero?: string;
  id: number;
  tiene_poligono: boolean;
}

interface Conflicts {
  barrios: Conflict[];
  cuadras: Conflict[];
  terrenos: Conflict[];
}

const props = defineProps<{
  geojson: any;
  mapping: any;
  conflicts: Conflicts | null;
  resolution: 'overwrite_all' | 'skip_all' | 'ask';
  decisions: Record<number, 'overwrite' | 'skip'>;
}>();

const emit = defineEmits<{
  'update:conflicts': [conflicts: Conflicts];
  'update:resolution': [resolution: 'overwrite_all' | 'skip_all' | 'ask'];
  'update:decisions': [decisions: Record<number, 'overwrite' | 'skip'>];
  'next': [];
}>();

const loading = ref(false);
const error = ref<string | null>(null);
const localResolution = ref<'overwrite_all' | 'skip_all' | 'ask'>(props.resolution);
const localDecisions = ref<Record<number, 'overwrite' | 'skip'>>({ ...props.decisions });

// Cargar conflictos
const loadConflicts = async () => {
  loading.value = true;
  try {
    const response = await axios.post('/api/import/conflicts', {
      geojson: props.geojson,
      mapping: props.mapping,
    });
    if (response.data.success) {
      emit('update:conflicts', response.data.conflicts);
    }
  } catch (err: any) {
    error.value = 'Error al detectar conflictos';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const allConflicts = computed(() => {
  if (!props.conflicts) return [];
  return [
    ...props.conflicts.barrios.map(c => ({ ...c, type: 'barrio', label: c.nombre })),
    ...props.conflicts.cuadras.map(c => ({ ...c, type: 'cuadra', label: c.nombre })),
    ...props.conflicts.terrenos.map(c => ({ ...c, type: 'terreno', label: `LT ${c.numero}` })),
  ];
});

const totalConflicts = computed(() => allConflicts.value.length);
const hasConflicts = computed(() => totalConflicts.value > 0);

const typeLabel = (type: string) => {
  const labels: Record<string, string> = {
    barrio: 'Barrio',
    cuadra: 'Cuadra',
    terreno: 'Terreno',
  };
  return labels[type] || type;
};

const typeIcon = (type: string) => {
  const icons: Record<string, string> = {
    barrio: 'pi-map',
    cuadra: 'pi-square',
    terreno: 'pi-home',
  };
  return `pi ${icons[type] || 'pi-circle'}`;
};

const getDecision = (index: number) => {
  return localDecisions.value[index] || null;
};

const setDecision = (index: number, decision: 'overwrite' | 'skip') => {
  localDecisions.value[index] = decision;
  emit('update:decisions', localDecisions.value);
};

const resolveAll = (action: 'overwrite' | 'skip') => {
  allConflicts.value.forEach(conflict => {
    localDecisions.value[conflict.index] = action;
  });
  emit('update:decisions', localDecisions.value);
};

onMounted(() => {
  loadConflicts();
});
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4 block"></i>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        Gestión de Conflictos
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Se encontraron elementos que ya existen en la base de datos
      </p>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <ProgressSpinner 
        style="width: 50px; height: 50px"
        strokeWidth="4"
        animationDuration="1s"
      />
    </div>

    <div v-else class="space-y-6">
      <!-- No conflicts -->
      <Message 
        v-if="!hasConflicts"
        severity="success"
        :closable="false"
      >
        <div class="flex items-center gap-2">
          <i class="pi pi-check-circle"></i>
          <div>
            <p class="font-semibold">Sin conflictos detectados</p>
            <p class="text-sm">Todos los elementos a importar son nuevos</p>
          </div>
        </div>
      </Message>

      <!-- Has conflicts -->
      <div v-else class="space-y-6">
        <!-- Resolution Strategy -->
        <Card>
          <template #header>
            <div class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
              <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 flex items-center gap-2">
                <i class="pi pi-cog"></i>
                Estrategia de Resolución
              </h4>
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <!-- Overwrite All -->
              <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                   @click="localResolution = 'overwrite_all'; emit('update:resolution', 'overwrite_all')">
                <RadioButton 
                  v-model="localResolution" 
                  value="overwrite_all"
                />
                <div>
                  <p class="font-medium text-gray-800 dark:text-gray-200">Reemplazar Todos</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    Sobrescribir todos los elementos que ya existen
                  </p>
                </div>
              </div>

              <!-- Skip All -->
              <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                   @click="localResolution = 'skip_all'; emit('update:resolution', 'skip_all')">
                <RadioButton 
                  v-model="localResolution" 
                  value="skip_all"
                />
                <div>
                  <p class="font-medium text-gray-800 dark:text-gray-200">Omitir Todos</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    No importar elementos que ya existen
                  </p>
                </div>
              </div>

              <!-- Ask for Each -->
              <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                   @click="localResolution = 'ask'; emit('update:resolution', 'ask')">
                <RadioButton 
                  v-model="localResolution" 
                  value="ask"
                />
                <div>
                  <p class="font-medium text-gray-800 dark:text-gray-200">Decidir por Cada Uno</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400">
                    Elegir para cada elemento conflictivo
                  </p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Conflicts Table -->
        <Card v-if="localResolution === 'ask'">
          <template #header>
            <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
              <div class="flex items-center justify-between">
                <h4 class="font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2">
                  <i class="pi pi-list"></i>
                  Conflictos Detectados ({{ totalConflicts }})
                </h4>
                <div class="flex gap-2">
                  <Button
                    label="Reemplazar Todos"
                    icon="pi pi-check"
                    size="small"
                    severity="warning"
                    @click="resolveAll('overwrite')"
                  />
                  <Button
                    label="Omitir Todos"
                    icon="pi pi-times"
                    size="small"
                    severity="secondary"
                    @click="resolveAll('skip')"
                  />
                </div>
              </div>
            </div>
          </template>
          <template #content>
            <DataTable
              :value="allConflicts"
              stripedRows
              responsiveLayout="scroll"
              class="text-sm"
            >
              <Column field="type" header="Tipo" style="width: 100px">
                <template #body="{ data }">
                  <Tag 
                    :value="typeLabel(data.type)"
                    :icon="typeIcon(data.type)"
                    class="capitalize"
                    :severity="data.type === 'barrio' ? 'info' : data.type === 'cuadra' ? 'warning' : 'success'"
                  />
                </template>
              </Column>

              <Column field="label" header="Elemento" style="width: auto">
                <template #body="{ data }">
                  <span class="font-medium">{{ data.label }}</span>
                </template>
              </Column>

              <Column header="Polígono" style="width: 100px" class="text-center">
                <template #body="{ data }">
                  <Tag 
                    :value="data.tiene_poligono ? 'Sí' : 'No'"
                    :severity="data.tiene_poligono ? 'warning' : 'info'"
                  />
                </template>
              </Column>

              <Column header="Acción" style="width: 200px">
                <template #body="{ data }">
                  <div class="flex gap-2">
                    <Button
                      label="Reemplazar"
                      icon="pi pi-refresh"
                      size="small"
                      severity="warning"
                      :outlined="getDecision(data.index) !== 'overwrite'"
                      @click="setDecision(data.index, 'overwrite')"
                    />
                    <Button
                      label="Omitir"
                      icon="pi pi-times"
                      size="small"
                      severity="secondary"
                      :outlined="getDecision(data.index) !== 'skip'"
                      @click="setDecision(data.index, 'skip')"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Summary -->
        <Message 
          v-if="localResolution === 'ask' && totalConflicts > 0"
          severity="info"
          :closable="false"
        >
          <p class="text-sm">
            Debes decidir qué hacer con cada conflicto antes de continuar
          </p>
        </Message>

        <Message 
          v-if="localResolution !== 'ask'"
          severity="info"
          :closable="false"
        >
          <div class="text-sm">
            <p v-if="localResolution === 'overwrite_all'" class="font-medium">
              ✓ Se reemplazarán los {{ totalConflicts }} elementos existentes
            </p>
            <p v-if="localResolution === 'skip_all'" class="font-medium">
              ✓ Se omitirán los {{ totalConflicts }} elementos existentes
            </p>
          </div>
        </Message>
      </div>
    </div>
  </div>
</template>