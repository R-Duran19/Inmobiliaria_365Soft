<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits(['save', 'cancel']);

const form = useForm({
  nombre: '',
  descripcion: '',
  estado: true,
});

const handleSubmit = () => {
  form.post('/proyectos', {
    onSuccess: () => {
      emit('save');
      form.reset();
    },
  });
};

const handleCancel = () => {
  emit('cancel');
  form.reset();
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
    <!-- Campo: Nombre del Proyecto -->
    <div class="space-y-2">
      <Label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Proyecto</Label>
      <Input
        id="nombre"
        v-model="form.nombre"
        placeholder="Ej: Desarrollo de Software"
        required
        class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
    </div>

    <!-- Campo: Descripción -->
    <div class="space-y-2">
      <Label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</Label>
      <textarea
        id="descripcion"
        v-model="form.descripcion"
        placeholder="Detalles del proyecto..."
        rows="5"
        class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
    </div>

    <!-- Campo: Estado -->
    <div class="space-y-2">
      <Label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</Label>
      <select
        id="estado"
        v-model="form.estado"
        required
        class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      >
        <option :value="true">Activo</option>
        <option :value="false">Inactivo</option>
      </select>
    </div>

    <!-- Botones: Guardar y Cancelar -->
    <div class="flex justify-end gap-3 pt-2">
      <Button
        type="button"
        variant="outline"
        @click="handleCancel"
        class="dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
      >
        Cancelar
      </Button>
      <Button type="submit" variant="default" :disabled="form.processing">
        {{ form.processing ? 'Guardando...' : 'Guardar' }}
      </Button>
    </div>
  </form>
</template>
