<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { proyectos } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProyectosHeader from '@/components/proyectos/ProyectosHeader.vue';
import ProyectosTable from '@/components/proyectos/ProyectosTable.vue';

defineProps<{
  proyectos: Array<{
    id: number;
    nombre: string;
    descripcion: string;
    estado: boolean;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Proyectos', href: proyectos().url },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Proyectos" />
    <div class="py-6">
      <ProyectosHeader />
      <ProyectosTable :proyectos="proyectos" />
    </div>
  </AppLayout>
</template>