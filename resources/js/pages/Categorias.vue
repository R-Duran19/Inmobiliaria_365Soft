<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import CategoriasHeader from '@/pages/Categorias/CategoriasHeader.vue';
import CategoriasTable from '@/pages/Categorias/CategoriasTable.vue';
import CategoriasFormDialog from '@/pages/Categorias/CategoriasFormDialog.vue';
import { ref, onMounted, computed } from 'vue';
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

const filtro = ref('');
const loading = ref(false);
const showDialog = ref(false);
const categoriaActual = ref<{
  id: number;
  nombre: string;
  idproyecto: number | null;
  estado: boolean;
  color: string; 
  proyecto?: { id: number; nombre: string };
} | null>(null);

const categoriasFiltradas = computed(() => {
  if (!filtro.value) return categoriasData.value;
  return categoriasData.value.filter(c =>
    c.nombre.toLowerCase().includes(filtro.value.toLowerCase())
  );
});

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

const handleEdit = (categoria: {
  id: number;
  nombre: string;
  idproyecto: number | null;
  estado: boolean;
  color: string; 
  proyecto?: { id: number; nombre: string };
}) => {
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
onMounted(() => cargarCategorias());
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Categorías de Terrenos" />
    <div class="py-6">
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
        @save="cargarCategorias"
      />
    </div>
  </AppLayout>
</template>