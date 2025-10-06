<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import ProyectosFormSheet from './ProyectosFormSheet.vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const search = ref('');

const abrirDialogoImportar = ref(false);
const archivoSeleccionado = ref<File | null>(null);

const descargarPlantilla = () => {
  window.location.href = '/proyectos/plantilla';
};

const manejarArchivoSeleccionado = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    archivoSeleccionado.value = target.files[0];
  }
};

const importarProyectos = async () => {
  if (!archivoSeleccionado.value) return;

  const formData = new FormData();
  formData.append('archivo', archivoSeleccionado.value);

  try {
    const response = await axios.post('/proyectos/importar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      },
    });

    if (response.data.success) {
      router.reload({ only: ['proyectos'] });
      abrirDialogoImportar.value = false;
    } else {
      alert(response.data.message);
    }
  } catch (error) {
    console.error(error);
    alert('Error al importar proyectos. Revisa la consola para más detalles.');
  }
};
</script>

<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mx-6 my-4">
    <div class="w-full sm:flex-grow">
      <Input
        v-model="search"
        placeholder="Buscar proyectos..."
        class="w-full max-w-3xl dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
    </div>
    <div class="flex gap-2 w-full sm:w-auto justify-end">
      <ProyectosFormSheet>
        <Button variant="default" class="w-full sm:w-auto">
          Agregar Proyecto
        </Button>
      </ProyectosFormSheet>
      <Button
        variant="outline"
        class="w-full sm:w-auto dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
      >
        Exportar
      </Button>
      <Button
        variant="outline"
        class="w-full sm:w-auto dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
        @click="abrirDialogoImportar = true"
      >
        Importar
      </Button>
    </div>

    <!-- Diálogo para importar -->
    <Dialog v-model:open="abrirDialogoImportar">
      <DialogContent class="sm:max-w-[500px] dark:bg-gray-900 dark:text-gray-100">
        <DialogHeader>
          <DialogTitle>Importar Proyectos</DialogTitle>
        </DialogHeader>
        <div class="p-4 space-y-4">
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Descarga la plantilla para llenar los datos de los proyectos:
          </p>
          <Button
            variant="outline"
            class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
            @click="descargarPlantilla"
          >
            Descargar Plantilla
          </Button>
          <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4">
            <Input
              type="file"
              accept=".xlsx"
              @change="manejarArchivoSeleccionado"
              class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
            />
          </div>
          <Button
            variant="default"
            class="w-full"
            :disabled="!archivoSeleccionado"
            @click="importarProyectos"
          >
            Importar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
