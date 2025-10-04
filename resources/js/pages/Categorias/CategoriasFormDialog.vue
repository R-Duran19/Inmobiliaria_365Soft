<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps<{
  open: boolean;
  categoria?: {
    id: number;
    nombre: string;
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
});

const proyectos = ref<Array<{ id: number; nombre: string }>>([]);

const cargarProyectos = async () => {
  try {
    const response = await axios.get('/categorias_terrenos/proyectos');
    proyectos.value = response.data.data;
    console.log(proyectos.value); 
  } catch (error) {
    console.error(error);
  }
};

watch(() => props.open, (isOpen) => {
  if (isOpen && props.categoria) {
    form.value = { ...props.categoria };
  } else if (isOpen) {
    form.value = { id: null, nombre: '', idproyecto: null, estado: true };
  }
});

const handleSubmit = async () => {
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
    }
    
    if (form.value.id) {
      await axios.put(`/categorias_terrenos/${form.value.id}`, form.value);
    } else {
      await axios.post('/categorias_terrenos', form.value);
    }
    
    emit('save');
    emit('update:open', false);
  } catch (error) {
    console.error(error);
    alert('Error al guardar la categoría');
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
          <Label for="idproyecto">Proyecto *</Label>
          <select
            id="idproyecto"
            v-model="form.idproyecto"
            class="w-full p-2 border rounded-md dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
            required
          >
            <option value="" disabled selected>Selecciona un proyecto</option>
            <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
              {{ proyecto.nombre }}
            </option>
          </select>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="emit('update:open', false)">
            Cancelar
          </Button>
          <Button type="submit">
            Guardar
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>