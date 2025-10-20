<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import CategoriasHeader from '@/pages/Categorias/CategoriasHeader.vue';
import CategoriasTable from '@/pages/Categorias/CategoriasTable.vue';
import CategoriasFormDialog from '@/pages/Categorias/CategoriasFormDialog.vue';
import { ref, onMounted, computed, reactive } from 'vue';
import axios from 'axios';


const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Categorías de Terrenos', href: '/categorias-terrenos' },
];


const categoriasData = ref<Array<{
  id: number;
  nombre: string;
  idproyecto: number | null;
  estado: boolean;
  color: string;
  proyecto?: { id: number; nombre: string };
}>>([]);

const proyectos = ref<Array<{ id: number; nombre: string; ubicacion: string }>>([]);
const filtro = ref('');
const loading = ref(false);
const showDialog = ref(false);
const categoriaActual = ref<any>(null);


const mostrandoCategorias = ref(false);
const proyectoSeleccionado = ref<{ id: number; nombre: string } | null>(null);


const categoriasFiltradas = computed(() => {
  let base = categoriasData.value;
  if (proyectoSeleccionado.value)
    base = base.filter(c => c.idproyecto === proyectoSeleccionado.value?.id);
  if (!filtro.value) return base;
  return base.filter(c =>
    c.nombre.toLowerCase().includes(filtro.value.toLowerCase())
  );
});


const cargarProyectos = async () => {
  try {
    const { data } = await axios.get('/api/proyectos');
    proyectos.value = data;
  } catch (error) {
    console.error('Error al cargar proyectos:', error);
  }
};


const cargarCategorias = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/categorias_terrenos');
    categoriasData.value = data.data;
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
};


function seleccionarProyecto(id: number) {
  const proyecto = proyectos.value.find(p => p.id === id);
  if (!proyecto) return;
  proyectoSeleccionado.value = proyecto;
  mostrandoCategorias.value = true;
  
}


function volverAProyectos() {
  proyectoSeleccionado.value = null;
  mostrandoCategorias.value = false;
}


const handleEdit = (categoria: any) => {
  categoriaActual.value = categoria;
  showDialog.value = true;
};

const handleNew = () => {
  categoriaActual.value = null;
  showDialog.value = true;
};


const exportarCategorias = () => {
  let csv = 'Nombre,Proyecto,Estado\n';
  categoriasData.value.forEach(c => {
    csv += `${c.nombre},${c.proyecto?.nombre || ''},${c.estado ? 'Activo' : 'Inactivo'}\n`;
  });
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'categorias_terrenos.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};


onMounted(() => {
  cargarProyectos();
  cargarCategorias();
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Categorías de Terrenos" />

    <!-- 🔹 Mostrar PROYECTOS -->
    <div v-if="!mostrandoCategorias" class="mb-6 p-4">
      <h2 class="text-xl font-semibold mb-3 dark:text-white">Proyectos Disponibles</h2>
      <div class="flex flex-col rounded-lg shadow-md bg-white dark:bg-gray-800">
        <div
          v-for="(p, index) in proyectos"
          :key="p.id"
          @click="seleccionarProyecto(p.id)"
          class="flex items-center p-4 sm:p-5 gap-4 cursor-pointer transition-colors duration-150"
          :class="{
            'border-t border-gray-200 dark:border-gray-700': index !== 0,
            'bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-600': proyectoSeleccionado?.id === p.id,
            'hover:bg-gray-50 dark:hover:bg-gray-700': proyectoSeleccionado?.id !== p.id
          }"
        >
          <div class="w-12 h-12 flex-shrink-0 rounded-full bg-blue-100 dark:bg-blue-700 flex items-center justify-center">
            <i class="pi pi-briefcase text-blue-600 dark:text-blue-100 text-xl"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-lg font-bold truncate text-gray-900 dark:text-white mt-0.5">
              {{ p.nombre }}
            </div>
          </div>
          <div class="flex items-center gap-3">
            <i v-if="proyectoSeleccionado?.id === p.id" class="pi pi-check-circle text-green-600 text-2xl" title="Proyecto Seleccionado"></i>
          </div>
        </div>

        <div v-if="proyectos.length === 0" class="p-6 text-center text-gray-500 dark:text-gray-400">
          No hay proyectos cargados.
        </div>
      </div>
    </div>

    <!-- 🔹 Mostrar CATEGORÍAS del proyecto seleccionado -->
    <div v-else class="py-6">
      <div class="flex items-center justify-between mb-4 ml-6 mr-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
          Categorías del Proyecto: {{ proyectoSeleccionado?.nombre }}
        </h2>
        <button
          @click="volverAProyectos"
          class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white px-3 py-1.5 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
        >
          ← Volver a Proyectos
        </button>
      </div>

      <CategoriasHeader
        v-model:search="filtro"
        :categoriasFiltradasLength="categoriasFiltradas.length"
        @new="handleNew"
        @export="exportarCategorias"
      />

      <CategoriasTable
        :categorias="categoriasFiltradas"
        :loading="loading"
        @refresh="cargarCategorias"
        @edit="handleEdit"
      />

      <CategoriasFormDialog
        v-model:open="showDialog"
        :categoria="categoriaActual"
        :idproyecto="proyectoSeleccionado?.id" 
        @save="cargarCategorias"
      />
    </div>
  </AppLayout>
</template>
