<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const toast = useToast();

const props = defineProps<{
  search: string;
  categoriasFiltradasLength: number;
}>();

const emit = defineEmits(['update:search', 'new']);
const abrirDialogoImportar = ref(false);
const archivoSeleccionado = ref<File | null>(null);

const descargarPlantilla = () => {
  window.location.href = '/categorias/plantilla';
};

const manejarArchivoSeleccionado = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    archivoSeleccionado.value = target.files[0];
  }
};

type ToastSeverity = 'success' | 'info' | 'warn' | 'error';

const mostrarToast = (severity: ToastSeverity, message: string) => {
  toast.add({
    severity: severity,
    summary: severity === 'success' ? 'Éxito' : severity === 'error' ? 'Error' : 'Aviso',
    detail: message,
    life: 3000,
  });
};

const importarCategorias = async () => {
  if (!archivoSeleccionado.value) {
    mostrarToast('error', 'Selecciona un archivo para importar.');
    return;
  }
  const formData = new FormData();
  formData.append('archivo', archivoSeleccionado.value);
  try {
    const response = await axios.post('/categorias/importar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      },
    });
    if (response.data.success) {
      mostrarToast('success', response.data.message);
      if (response.data.errores && response.data.errores.length > 0) {
        response.data.errores.forEach((error: string) => {
          mostrarToast('warn', error);
        });
      }
      abrirDialogoImportar.value = false;
      setTimeout(() => {
        window.location.reload();
      }, 5000);
    } else {
      mostrarToast('error', response.data.message);
    }
  } catch (error) {
    console.error(error);
    mostrarToast('error', 'Error al importar categorías. Revisa la consola para más detalles.');
  }
};

const exportarCategorias = async () => {
  try {
    const response = await axios.get('/categorias/exportar', {
      responseType: 'blob', // Importante para descargar el archivo
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'categorias_exportadas.xlsx');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error(error);
    mostrarToast('error', 'Error al exportar categorías.');
  }
};

</script>

<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mx-6 my-4">
    <div class="w-full sm:flex-grow">
      <Input
        :model-value="props.search"
        @update:model-value="$emit('update:search', $event)"
        placeholder="Buscar categorías..."
        class="w-full max-w-3xl dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ props.categoriasFiltradasLength || 0 }} categorías encontradas
      </p>
    </div>

    <div class="flex gap-2 w-full sm:w-auto justify-end flex-col sm:flex-row">
  <!-- Contenido aquí -->
</div>

    <div class="flex gap-2 w-full justify-end flex-col sm:flex-row">
      <Button
        variant="default"
        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white flex items-center gap-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:text-white"
        @click="$emit('new')"
      >
        <i class="pi pi-plus"></i>
        <span>Nueva Categoría</span>
      </Button>
      <Button
        variant="outline"
        class="w-full sm:w-auto bg-green-700 hover:bg-green-800 text-white flex items-center gap-2 border-green-700 hover:border-green-800 dark:bg-green-700 dark:hover:bg-green-800 dark:text-white dark:border-green-700 dark:hover:border-green-800"
        @click="abrirDialogoImportar = true"
      >
        <i class="pi pi-file-excel"></i>
        <span>Importar</span>
      </Button>
      <Button
        variant="outline"
        class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white flex items-center gap-2 border-green-600 hover:border-green-700 dark:bg-green-600 dark:hover:bg-green-700 dark:text-white dark:border-green-600 dark:hover:border-green-700"
        @click="exportarCategorias"
      >
        <i class="pi pi-file-export"></i>
        <span>Exportar</span>
      </Button>

      <!-- Diálogo para importar -->
      <Dialog v-model:open="abrirDialogoImportar">
        <DialogContent class="sm:max-w-[500px] dark:bg-gray-900 dark:text-gray-100">
          <DialogHeader>
            <DialogTitle>Importar Categorías</DialogTitle>
          </DialogHeader>
          <div class="p-4 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">
              Descarga la plantilla para llenar los datos de las categorías:
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
              @click="importarCategorias"
            >
              Importar
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </div>
</template>
