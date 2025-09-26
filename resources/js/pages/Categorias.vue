<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { categorias } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categorias',
        href: categorias().url,
    },
];

const toast = useToast();
const categoriasData = ref<Array<any>>([]);
const loading = ref(false);
const showDialog = ref(false);

const form = reactive({ id: null, nombre: '', idproyecto: null, estado: true });
const filtro = ref('');

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
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar categorías' });
  } finally {
    loading.value = false;
  }
};

const guardarCategoria = async () => {
  try {
    if (form.id) {
      await axios.put(`/categorias_terrenos/${form.id}`, form);
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Categoría actualizada' });
    } else {
      await axios.post('/categorias_terrenos', form);
      toast.add({ severity: 'success', summary: 'Creado', detail: 'Categoría creada' });
    }
    showDialog.value = false;
    cargarCategorias();
  } catch (error) {
    console.error(error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo guardar categoría' });
  }
};

const editarCategoria = (categoria: any) => {
  form.id = categoria.id;
  form.nombre = categoria.nombre;
  form.idproyecto = categoria.idproyecto;
  form.estado = categoria.estado;
  showDialog.value = true;
};

const toggleEstado = async (categoria: any) => {
  try {
    await axios.patch(
      `/categorias_terrenos/${categoria.estado ? 'desactivar' : 'activar'}/${categoria.id}`
    );
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: `Categoría ${categoria.estado ? 'desactivada' : 'activada'}`,
    });
    cargarCategorias();
  } catch (error) {
    console.error(error);
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cambiar estado' });
  }
};

const nuevaCategoria = () => {
  form.id = null;
  form.nombre = '';
  form.idproyecto = null;
  form.estado = true;
  showDialog.value = true;
};

const exportar = () => {
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

// Templates para columnas
const accionesTemplate = (row: any) => {
  return h('div', { class: 'flex gap-2' }, [
    h('Button', { icon: 'pi pi-pencil', class: 'p-button-sm', onClick: () => editarCategoria(row) }),
    h('Button', {
      icon: row.estado ? 'pi pi-times' : 'pi pi-check',
      class: ['p-button-sm', row.estado ? 'p-button-danger' : 'p-button-success'],
      onClick: () => toggleEstado(row),
    }),
  ]);
};

const estadoTemplate = (row: any) => (row.estado ? 'Activo' : 'Inactivo');
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Categorías de Terrenos" />

    <div class="flex justify-between items-center mb-4">
      <InputText v-model="filtro" placeholder="Buscar por nombre" />
      <div class="flex gap-2">
        <Button label="Exportar" icon="pi pi-file" @click="exportar" />
        <Button label="Nuevo" icon="pi pi-plus" @click="nuevaCategoria" />
      </div>
    </div>

    <DataTable :value="categoriasFiltradas" :loading="loading" striped-rows>
      <Column header="Acciones" :body="accionesTemplate" />
      <Column field="nombre" header="Nombre" />
      <Column field="proyecto.nombre" header="Proyecto" />
      <Column header="Estado" :body="estadoTemplate" />
    </DataTable>

    <Dialog v-model:visible="showDialog" header="Categoría" modal>
      <div class="flex flex-col gap-3">
        <InputText v-model="form.nombre" placeholder="Nombre" />
        <InputText v-model="form.idproyecto" placeholder="ID Proyecto" />
        <Button label="Guardar" icon="pi pi-check" @click="guardarCategoria" />
      </div>
    </Dialog>

    <Toast />
  </AppLayout>
</template>