<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { terrenos as terrenosRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Terrenos',
    href: terrenosRoute().url,
  },
];

interface Terreno {
  id: number;
  idproyecto: string;
  ubicacion: string;
  categoria: string;
  superficie: number;
  precio_venta: number;
  estado: number;
}

const terrenos = ref<Terreno[]>([]);

const cargarTerrenos = async () => {
  try {
    const response = await axios.get('/terrenos');
    terrenos.value = response.data;
  } catch (error) {
    console.error('Error al cargar terrenos:', error);
  }
};

onMounted(() => {
  cargarTerrenos();
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div>
      <h2>Lista de Terrenos</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Proyecto</th>
            <th>Ubicación</th>
            <th>Categoría</th>
            <th>Superficie</th>
            <th>Precio Venta</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in terrenos" :key="t.id">
            <td>{{ t.id }}</td>
            <td>{{ t.idproyecto }}</td>
            <td>{{ t.ubicacion }}</td>
            <td>{{ t.categoria }}</td>
            <td>{{ t.superficie }}</td>
            <td>{{ t.precio_venta }}</td>
            <td>
              <span v-if="t.estado === 0">Disponible</span>
              <span v-else-if="t.estado === 1">Reservado</span>
              <span v-else-if="t.estado === 2">Vendido</span>
              <span v-else>{{ t.estado }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<style scoped>
table {
  width: 100%;
  border-collapse: collapse;
}
table,
th,
td {
  border: 1px solid black;
  padding: 8px;
  text-align: left;
}
</style>
