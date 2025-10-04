<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits(['save', 'cancel']);

const form = useForm({
  nombre: '',
  descripcion: '',
  fecha_lanzamiento: undefined as string | undefined,
  numero_lotes: undefined as number | undefined,
  ubicacion: undefined as string | undefined,
  fotografia: undefined as File | string | undefined,
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
  <!-- Contenedor principal con scroll -->
  <div class="max-h-[70vh] overflow-y-auto p-2">
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Campo: Nombre del Proyecto -->
      <div class="space-y-2">
        <Label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Proyecto *</Label>
        <Input
          id="nombre"
          v-model="form.nombre"
          placeholder="Ej: Proyecto casas nueva"
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

      <!-- Campo: Fecha de Lanzamiento -->
      <div class="space-y-2">
        <Label for="fecha_lanzamiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Lanzamiento</Label>
        <Input
          id="fecha_lanzamiento"
          v-model="form.fecha_lanzamiento"
          type="date"
          class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
        />
      </div>

      <!-- Campo: Número de Lotes -->
      <div class="space-y-2">
        <Label for="numero_lotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Lotes</Label>
        <Input
          id="numero_lotes"
          v-model.number="form.numero_lotes"
          type="number"
          class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
        />
      </div>

      <!-- Campo: Ubicación -->
      <div class="space-y-2">
        <Label for="ubicacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación</Label>
        <Input
          id="ubicacion"
          v-model="form.ubicacion"
          placeholder="Ej: Calle Principal 123"
          class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
        />
      </div>

      <!-- Campo: Fotografía -->
      <div class="space-y-2">
        <Label for="fotografia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fotografía</Label>
        <Input
          id="fotografia"
          type="file"
          @input="form.fotografia = $event.target.files[0]"
          class="w-full dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
        />
      </div>
    </form>

    <!-- Contenedor fijo para los botones -->
    <div class="sticky bottom-0 bg-white dark:bg-gray-800 py-4 px-2 border-t">
      <div class="flex justify-end gap-3">
        <Button
          type="button"
          variant="outline"
          @click="() => {
            handleCancel();
            emit('cancel');
          }"
          class="dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
        >
          Cancelar
        </Button>
        <Button
          type="button"
          variant="default"
          @click="() => {
            handleSubmit();
            emit('save');
          }"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Guardando...' : 'Guardar' }}
        </Button>
      </div>
    </div>
  </div>
</template>
