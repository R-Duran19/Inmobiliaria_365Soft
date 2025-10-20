<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Loading from '@/components/ui/Loading/Loading.vue';

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
      
      <div class="mb-10 relative">
        
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-2xl -z-10 transform -rotate-1"></div>
        
        <div class="relative p-8 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-xl overflow-hidden">
          
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
          
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                <i class="pi pi-building text-3xl text-white"></i>
              </div>
              <h1 class="text-4xl font-black text-white">Proyectos Inmobiliarios</h1>
            </div>
            <p class="text-purple-100 text-lg ml-16">
              Selecciona un proyecto para ver el mapa de terrenos disponibles
            </p>
          </div>
        </div>
      </div>

      
      
      <Loading v-if="loading"></Loading>
      

      
      <div
        v-else-if="error"
        class="relative overflow-hidden bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-2 border-red-200 dark:border-red-800 rounded-2xl p-6 mb-6 shadow-lg"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-red-200 dark:bg-red-800 rounded-full -mr-16 -mt-16 opacity-20"></div>
        <div class="relative flex items-center gap-4">
          <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-red-500 flex items-center justify-center shadow-lg">
            <i class="pi pi-exclamation-triangle text-white text-2xl"></i>
          </div>
          <div>
            <p class="font-bold text-lg text-red-800 dark:text-red-200 mb-1">Error al cargar</p>
            <p class="text-red-700 dark:text-red-300">{{ error }}</p>
          </div>
        </div>
      </div>

      
      <div v-else>
        <div v-if="proyectos && proyectos.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
          <div
            v-for="proyecto in proyectos"
            :key="proyecto.id"
            class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2 border border-gray-100 dark:border-gray-700"
          >
            
            <div class="relative h-56 bg-gray-100 dark:bg-gray-700 overflow-hidden">
              <img
                v-if="proyecto.fotografia"
                :src="proyecto.fotografia"
                :alt="proyecto.nombre"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              />
              <div
                v-else
                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600 relative overflow-hidden"
              >
                
                <div class="absolute inset-0 opacity-20">
                  <div class="absolute top-0 left-0 w-full h-full" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.1) 10px, rgba(255,255,255,.1) 20px);"></div>
                </div>
                <i class="pi pi-building text-7xl text-white/80 relative z-10"></i>
              </div>

              
              <div class="absolute top-4 right-4">
                <div 
                  :class="[
                    'px-4 py-2 rounded-xl font-bold text-sm shadow-lg backdrop-blur-sm',
                    getSeveridadDisponibilidad(proyecto) === 'success' ? 'bg-green-500/90 text-white' :
                    getSeveridadDisponibilidad(proyecto) === 'warning' ? 'bg-orange-500/90 text-white' :
                    'bg-red-500/90 text-white'
                  ]"
                >
                  <i class="pi pi-home mr-1"></i>
                  {{ proyecto.terrenos_disponibles }} disponibles
                </div>
              </div>

              
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            
            <div class="p-6">
              
              <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                {{ proyecto.nombre }}
              </h3>

              
              <p class="text-gray-600 dark:text-gray-400 text-sm mb-5 line-clamp-2 leading-relaxed">
                {{ proyecto.descripcion || 'Sin descripción disponible' }}
              </p>

              
              <div class="space-y-3 mb-5">
                <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 group/item hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-md group-hover/item:scale-110 transition-transform">
                    <i class="pi pi-map-marker text-white text-xs"></i>
                  </div>
                  <span class="font-medium">{{ proyecto.ubicacion }}</span>
                </div>
                
                <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 group/item hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center flex-shrink-0 shadow-md group-hover/item:scale-110 transition-transform">
                    <i class="pi pi-calendar text-white text-xs"></i>
                  </div>
                  <span class="font-medium">{{ formatearFecha(proyecto.fecha_lanzamiento) }}</span>
                </div>
                
                <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 group/item hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-md group-hover/item:scale-110 transition-transform">
                    <i class="pi pi-th-large text-white text-xs"></i>
                  </div>
                  <span class="font-medium">{{ proyecto.numero_lotes }} lotes totales</span>
                </div>
              </div>

              
              <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="relative overflow-hidden p-3 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border-2 border-green-200 dark:border-green-800 text-center hover:shadow-md transition-all">
                  <div class="text-2xl font-black text-green-600 dark:text-green-400 mb-1">
                    {{ proyecto.terrenos_disponibles }}
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase tracking-wide">
                    Disponibles
                  </div>
                </div>
                
                <div class="relative overflow-hidden p-3 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl border-2 border-orange-200 dark:border-orange-800 text-center hover:shadow-md transition-all">
                  <div class="text-2xl font-black text-orange-600 dark:text-orange-400 mb-1">
                    {{ proyecto.terrenos_reservados }}
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase tracking-wide">
                    Reservados
                  </div>
                </div>
                
                <div class="relative overflow-hidden p-3 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl border-2 border-red-200 dark:border-red-800 text-center hover:shadow-md transition-all">
                  <div class="text-2xl font-black text-red-600 dark:text-red-400 mb-1">
                    {{ proyecto.terrenos_vendidos }}
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase tracking-wide">
                    Vendidos
                  </div>
                </div>
              </div>

              
              <button
                @click="verMapa(proyecto.id)"
                class="w-full py-4 px-6 rounded-xl font-bold text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center gap-2 group/btn"
              >
                <i class="pi pi-map text-lg group-hover/btn:animate-pulse"></i>
                <span>Ver Mapa Interactivo</span>
              </button>
            </div>

            
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-purple-400/20 to-pink-600/20 rounded-bl-full transform translate-x-10 -translate-y-10 group-hover:translate-x-8 group-hover:-translate-y-8 transition-transform duration-300"></div>
          </div>
        </div>

        
        <div v-else class="text-center py-20">
          <div class="relative inline-block mb-6">
            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 flex items-center justify-center shadow-lg">
              <i class="pi pi-inbox text-6xl text-gray-400 dark:text-gray-500"></i>
            </div>
            <div class="absolute -bottom-2 -right-2 w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center shadow-lg">
              <i class="pi pi-plus text-white text-xl"></i>
            </div>
          </div>
          <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">
            No hay proyectos disponibles
          </h3>
          <p class="text-gray-500 dark:text-gray-400">
            Comienza agregando tu primer proyecto inmobiliario
          </p>
        </div>
      </div>
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

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.group-hover\/btn:hover .pi-map {
  animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>