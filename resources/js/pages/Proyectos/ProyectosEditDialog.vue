<script setup lang="ts">
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
    estado: boolean;
  };
  open: boolean;
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
  nombre: props.proyecto.nombre,
  descripcion: props.proyecto.descripcion,
  estado: props.proyecto.estado,
});

const handleSubmit = () => {
  form.put(`/proyectos/${props.proyecto.id}`, {
    onSuccess: () => {
      emit('update:open', false); // Cierra el diálogo
      window.location.reload(); // Recarga la página
    },
  });
};

const handleCancel = () => {
  emit('update:open', false);
  form.reset();
};
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px] dark:bg-gray-900 dark:text-gray-100" :show-close="false">
      <!-- Contenido del diálogo -->
      <DialogHeader>
        <DialogTitle>Editar Proyecto</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
        <!-- Campos del formulario -->
        <div class="space-y-2">
          <Label for="nombre">Nombre del Proyecto</Label>
          <Input id="nombre" v-model="form.nombre" required />
        </div>
        <div class="space-y-2">
          <Label for="descripcion">Descripción</Label>
          <textarea id="descripcion" v-model="form.descripcion" rows="3" class="w-full p-2 border rounded" />
        </div>
        <div class="space-y-2">
        <Label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</Label>
        <select
            id="estado"
            v-model="form.estado"
            required
            class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
        >
            <option :value="true" class="dark:bg-gray-800 dark:text-gray-100">Activo</option>
            <option :value="false" class="dark:bg-gray-800 dark:text-gray-100">Inactivo</option>
        </select>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="handleCancel">Cancelar</Button>
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : 'Guardar' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
