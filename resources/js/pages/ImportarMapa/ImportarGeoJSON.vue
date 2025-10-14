<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import ImportHeader from './ImportHeader.vue';
import ProyectoInfo from './ProyectoInfo.vue';
import ImportInstrucciones from './ImportInstrucciones.vue';
import ImportGeoJsonUploader from './ImportGeoJsonUploader.vue';

interface Proyecto {
  id: number;
  nombre: string;
  descripcion: string;
  fecha_lanzamiento: string | null;
  numero_lotes: number | null;
  ubicacion: string | null;
  fotografia: string | null;
  estado: boolean;
}

const props = defineProps<{
  proyecto: Proyecto;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Importar Mapa', href: '/importarmapa' },
  { title: props.proyecto.nombre, href: '#' },
];

const handleSuccess = (result: any) => {
  console.log('Importación exitosa:', result);
  // Opcional: redirigir después de 3 segundos
  setTimeout(() => {
    router.visit('/importarmapa');
  }, 3000);
};

const handleError = (error: any) => {
  console.error('Error en importación:', error);
};

const volverAProyectos = () => {
  router.visit('/importarmapa');
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Importar GeoJSON - ${proyecto.nombre}`" />
    
    <div class="py-6">
      <ImportHeader
        :proyecto-nombre="proyecto.nombre"
        @volver="volverAProyectos"
      />

      <Card class="dark:bg-gray-800 dark:border-gray-700">
        <CardHeader>
          <CardTitle class="text-xl dark:text-gray-100">
            Importar archivo GeoJSON
          </CardTitle>
          <CardDescription class="dark:text-gray-400">
            Sube un archivo GeoJSON para importar barrios, cuadras y terrenos al proyecto
          </CardDescription>
        </CardHeader>
        
        <CardContent class="space-y-6">
          <ProyectoInfo :proyecto="proyecto" />
          
          <ImportInstrucciones />

          <ImportGeoJsonUploader
            :proyecto-id="proyecto.id"
            @success="handleSuccess"
            @error="handleError"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>