<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
  open: boolean;
  idproyecto?: number | null;
  categoria?: {
    id: number;
    nombre: string;
    color: string;
    idproyecto: number | null;
    estado: boolean;
    proyecto?: { id: number; nombre: string };
  } | null;
}>();

const emit = defineEmits(['update:open', 'save']);

const form = ref({
  id: null as number | null,
  nombre: '',
  idproyecto: null as number | null,
  estado: true,
  color: '#000000',
});

const proyectos = ref<Array<{ id: number; nombre: string }>>([]);
const isSaving = ref(false); // 👈 Controla si está guardando

const cargarProyectos = async () => {
  try {
    const response = await axios.get('/categorias_terrenos/proyectos');
    proyectos.value = response.data.data;
  } catch (error) {
    console.error(error);
  }
};

watch(() => props.open, (isOpen) => {
  if (isOpen && props.categoria) {
    form.value = {
      ...props.categoria,
      color: props.categoria.color || '#000000'
    };
  } else if (isOpen) {
    form.value = {
      id: null,
      nombre: '',
      idproyecto: props.idproyecto || null,
      estado: true,
      color: '#000000'
    };
  }
});

const handleSubmit = async () => {
  if (isSaving.value) return; // 👈 Previene doble clic
  isSaving.value = true;

  try {
    if (!form.value.nombre.trim()) {
      alert('El nombre es requerido');
      return;
    }
    if (!form.value.idproyecto) {
      alert('El proyecto es requerido');
      return;
    }

    if (!form.value.id) {
      form.value.estado = true;
      await axios.post('/categorias_terrenos', form.value);
    } else {
      await axios.put(`/categorias_terrenos/${form.value.id}`, form.value);
    }

    emit('save');
    emit('update:open', false);
  } catch (error) {
    console.error(error);
    alert('Error al guardar la categoría');
  } finally {
    isSaving.value = false; // 👈 Habilita de nuevo el botón
  }
};

onMounted(cargarProyectos);
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px] dark:bg-gray-900 dark:text-gray-100">
      <DialogHeader>
        <DialogTitle>{{ form.id ? 'Editar Categoría' : 'Nueva Categoría' }}</DialogTitle>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6 px-2">
        <div class="space-y-2">
          <Label for="nombre">Nombre de la Categoría *</Label>
          <Input id="nombre" v-model="form.nombre" required />
        </div>

        <div class="space-y-2">
          <Label for="color">Color *</Label>
          <Input
            id="color"
            type="color"
            v-model="form.color"
            required
            class="w-full h-10 p-1 border rounded-md dark:bg-gray-800 dark:border-gray-700"
          />
        </div>

        <div class="space-y-2">
          <Label for="idproyecto">Proyecto *</Label>
          <select
            id="idproyecto"
            v-model="form.idproyecto"
            :disabled="!!props.idproyecto"
            class="w-full p-2 border rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 disabled:opacity-70 disabled:cursor-not-allowed"
            required
          >
            <option value="" disabled selected>Selecciona un proyecto</option>
            <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
              {{ proyecto.nombre }}
            </option>
          </select>

          <p v-if="props.idproyecto" class="text-sm text-gray-500 dark:text-gray-400">
            Proyecto asignado:
            <strong>{{ proyectos.find(p => p.id === props.idproyecto)?.nombre || 'Cargando...' }}</strong>
          </p>
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            :disabled="isSaving"
            @click="emit('update:open', false)"
          >
            Cancelar
          </Button>
          <Button type="submit" :disabled="isSaving">
            {{ isSaving ? 'Guardando...' : 'Guardar' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
