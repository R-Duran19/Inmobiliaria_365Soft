<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DataView from 'primevue/dataview';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface Proyecto {
  id: number;
  nombre: string;
  descripcion: string;
  fecha_lanzamiento: string;
  numero_lotes: number;
  ubicacion: string;
  fotografia: string | null;
  total_terrenos: number;
  terrenos_disponibles: number;
  terrenos_vendidos: number;
  terrenos_reservados: number;
}

const proyectos = ref<Proyecto[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Cargar proyectos desde la API
const cargarProyectos = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axios.get('/api/proyectos-list');
    proyectos.value = response.data;
  } catch (err: any) {
    console.error('Error al cargar proyectos:', err);
    error.value = 'Error al cargar los proyectos';
  } finally {
    loading.value = false;
  }
};

// Ir al mapa del proyecto
const verMapa = (proyectoId: number) => {
  router.visit(`/mapa/${proyectoId}`);
};

// Formatear fecha
const formatearFecha = (fecha: string) => {
  if (!fecha) return 'N/A';
  const date = new Date(fecha);
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Obtener severidad del tag según disponibilidad
const getSeveridadDisponibilidad = (proyecto: Proyecto) => {
  const porcentaje = (proyecto.terrenos_disponibles / proyecto.total_terrenos) * 100;
  if (porcentaje > 50) return 'success';
  if (porcentaje > 20) return 'warning';
  return 'danger';
};

onMounted(() => {
  cargarProyectos();
});
</script>

<template>
  <Head title="Proyectos" />

  <AppLayout>
    <div class="p-6 transition-colors duration-300">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Proyectos Inmobiliarios</h1>
        <p class="text-gray-600 dark:text-gray-400">
          Selecciona un proyecto para ver el mapa de terrenos
        </p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="text-center">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-500 mb-4"></i>
          <p class="text-gray-600 dark:text-gray-400">Cargando proyectos...</p>
        </div>
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6"
      >
        <div class="flex items-center">
          <i class="pi pi-exclamation-triangle text-red-500 mr-2"></i>
          <p class="text-red-700 dark:text-red-300">{{ error }}</p>
        </div>
      </div>

      <!-- DataView -->
      <DataView v-else :value="proyectos" layout="grid">
        <template #grid="slotProps">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              v-for="proyecto in slotProps.items"
              :key="proyecto.id"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
                     rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden"
            >
              <!-- Imagen -->
              <div class="relative h-48 bg-gray-100 dark:bg-gray-700">
                <img
                  v-if="proyecto.fotografia"
                  :src="proyecto.fotografia"
                  :alt="proyecto.nombre"
                  class="w-full h-full object-cover"
                />
                <div
                  v-else
                  class="w-full h-full flex items-center justify-center bg-gradient-to-br 
                         from-blue-100 to-blue-200 dark:from-blue-900 dark:to-blue-800"
                >
                  <i class="pi pi-image text-6xl text-blue-300 dark:text-blue-500"></i>
                </div>

                <!-- Tag -->
                <Tag
                  :value="`${proyecto.terrenos_disponibles} disponibles`"
                  :severity="getSeveridadDisponibilidad(proyecto)"
                  class="absolute top-4 right-4"
                />
              </div>

              <!-- Contenido -->
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  {{ proyecto.nombre }}
                </h3>

                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                  {{ proyecto.descripcion || 'Sin descripción' }}
                </p>

                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-map-marker text-blue-500 mr-2"></i>
                    <span>{{ proyecto.ubicacion }}</span>
                  </div>
                  <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-calendar text-blue-500 mr-2"></i>
                    <span>{{ formatearFecha(proyecto.fecha_lanzamiento) }}</span>
                  </div>
                  <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <i class="pi pi-th-large text-blue-500 mr-2"></i>
                    <span>{{ proyecto.numero_lotes }} lotes totales</span>
                  </div>
                </div>

                <!-- Estadísticas -->
                <div
                  class="grid grid-cols-3 gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 
                         rounded-lg transition-colors"
                >
                  <div class="text-center">
                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                      {{ proyecto.terrenos_disponibles }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Disponibles</div>
                  </div>
                  <div class="text-center">
                    <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                      {{ proyecto.terrenos_reservados }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Reservados</div>
                  </div>
                  <div class="text-center">
                    <div class="text-lg font-bold text-red-600 dark:text-red-400">
                      {{ proyecto.terrenos_vendidos }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Vendidos</div>
                  </div>
                </div>

                <!-- Botón -->
                <Button
                  label="Ver Mapa"
                  icon="pi pi-map"
                  @click="verMapa(proyecto.id)"
                  class="w-full"
                  severity="primary"
                />
              </div>
            </div>
          </div>
        </template>

        <!-- Estado vacío -->
        <template #empty>
          <div class="text-center py-12">
            <i class="pi pi-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 text-lg">
              No hay proyectos disponibles
            </p>
          </div>
        </template>
      </DataView>
    </div>
  </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
