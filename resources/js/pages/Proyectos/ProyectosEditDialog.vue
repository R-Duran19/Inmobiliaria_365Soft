<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
  proyecto: {
    id: number;
    nombre: string;
    descripcion: string;
    fecha_lanzamiento: string | null;
    numero_lotes: number | null;
    ubicacion: string | null;
    fotografia: string | null;
    estado: boolean;
  };
  open: boolean;
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
  nombre: props.proyecto.nombre,
  descripcion: props.proyecto.descripcion,
  fecha_lanzamiento: props.proyecto.fecha_lanzamiento || undefined,
  numero_lotes: props.proyecto.numero_lotes || undefined,
  ubicacion: props.proyecto.ubicacion || undefined,
  fotografia: undefined as File | string | undefined,
});

const previewImage = ref<string | null>(null);
const selectedFile = ref<File | null>(null);

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    selectedFile.value = file;
    form.fotografia = file;
    
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const displayImage = computed(() => {
  if (previewImage.value) {
    return previewImage.value;
  }
  if (props.proyecto.fotografia) {
    return `/${props.proyecto.fotografia}`;
  }
  return null;
});

const handleSubmit = () => {
  form.post(`/proyectos/${props.proyecto.id}`, {
    forceFormData: true,
    onSuccess: () => {
      emit('update:open', false);
      previewImage.value = null;
      selectedFile.value = null;
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
    }
  });
};

const handleCancel = () => {
  emit('update:open', false);
  previewImage.value = null;
  selectedFile.value = null;
  form.reset();
};

const handleDialogChange = (isOpen: boolean) => {
  if (!isOpen) {
    previewImage.value = null;
    selectedFile.value = null;
  }
  emit('update:open', isOpen);
};
</script>

<template>
  <Dialog :open="open" @update:open="handleDialogChange">
    <DialogContent class="sm:max-w-[600px] max-h-[80vh] overflow-y-auto dark:bg-gray-900 dark:text-gray-100">
      <DialogHeader>
        <DialogTitle>Editar Proyecto</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
        
        <div class="space-y-2">
          <Label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Proyecto *</Label>
          <Input
            id="nombre"
            v-model="form.nombre"
            placeholder="Ej: Desarrollo de Software"
            required
            class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</Label>
          <textarea
            id="descripcion"
            v-model="form.descripcion"
            placeholder="Detalles del proyecto..."
            rows="3"
            class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="fecha_lanzamiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Lanzamiento</Label>
          <Input
            id="fecha_lanzamiento"
            v-model="form.fecha_lanzamiento"
            type="date"
            class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="numero_lotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Lotes</Label>
          <Input
            id="numero_lotes"
            v-model.number="form.numero_lotes"
            type="number"
            class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="ubicacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación</Label>
          <Input
            id="ubicacion"
            v-model="form.ubicacion"
            placeholder="Ej: Calle Principal 123"
            class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
        </div>
        
        <div class="space-y-2">
          <Label for="fotografia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fotografía</Label>
          <Input
            id="fotografia"
            type="file"
            accept="image/*"
            @change="handleFileChange"
            class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
          />
          
          <div v-if="displayImage" class="mt-4 flex flex-col items-center gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ previewImage ? 'Nueva imagen seleccionada:' : 'Imagen actual:' }}
            </p>
            <div class="relative">
              <img 
                :src="displayImage" 
                alt="Vista previa" 
                class="w-64 h-64 object-cover rounded-lg shadow-md border-2 border-gray-200 dark:border-gray-700"
              >

              <span 
                v-if="previewImage" 
                class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow"
              >
                Nueva
              </span>
            </div>
          </div>
          
          <!-- Mensaje sin imagen-->
          <div v-else class="mt-4 flex justify-center">
            <div class="text-center p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-sm text-gray-500 dark:text-gray-400">No hay imagen</p>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="handleCancel" class="dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700">
            Cancelar
          </Button>
          <Button type="submit" variant="default" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : 'Guardar' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>