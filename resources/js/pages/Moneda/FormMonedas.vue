<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Moneda } from '@/types/Moneda';

const visible = defineModel<boolean>({ default: false });

const props = defineProps<{
  moneda?: Moneda | null;
}>();

const emit = defineEmits<{
  (e: 'guardar', moneda: Partial<Moneda>): void;
}>();

const form = ref({
  nombre: '',
  abreviacion: '',
  pais: '',
  tipo_cambio: 0,
  activo: true,
});

const cargando = ref(false);

const paises = [
  'Bolivia', 'Argentina', 'Brasil', 'Chile', 'Colombia', 'Ecuador', 'Estados Unidos',
  'México', 'Perú', 'Paraguay', 'Uruguay', 'Venezuela', 'España', 'Reino Unido', 'Japón', 'China'
];

function limpiarFormulario() {
  form.value = {
    nombre: '',
    abreviacion: '',
    pais: '',
    tipo_cambio: 0,
    activo: true,
  };
}

watch(
  () => props.moneda,
  (nuevaMoneda) => {
    if (nuevaMoneda) {
      form.value = {
        nombre: nuevaMoneda.nombre ?? '',
        abreviacion: nuevaMoneda.abreviacion ?? '',
        pais: nuevaMoneda.pais ?? '',
        tipo_cambio: nuevaMoneda.tipo_cambio ?? 0,
        activo: nuevaMoneda.activo ?? true,
      };
    } else {
      limpiarFormulario();
    }
  },
  { immediate: true }
);

function cerrarDialog() {
  visible.value = false;
  limpiarFormulario();
}

async function guardarMoneda() {
  if (!form.value.nombre.trim()) return alert('El nombre de la moneda es requerido');
  if (!form.value.abreviacion.trim()) return alert('El código/abreviación es requerido');
  if (!form.value.pais.trim()) return alert('El país es requerido');
  if (form.value.tipo_cambio <= 0) return alert('El tipo de cambio debe ser mayor a 0');

  cargando.value = true;

  try {
    emit('guardar', {
      ...form.value,
      ...(props.moneda?.id && { id: props.moneda.id }),
    });
    cerrarDialog();
  } catch (error) {
    console.error('Error al guardar moneda:', error);
    alert('Error al guardar la moneda');
  } finally {
    cargando.value = false;
  }
}
</script>

<template>
  <Dialog :open="visible" @update:open="(val) => (visible = val)">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>
          {{ moneda ? 'Editar Moneda' : 'Registrar Nueva Moneda' }}
        </DialogTitle>
        <DialogDescription>
          {{
            moneda
              ? 'Modifica los datos de la moneda existente'
              : 'Completa la información de la nueva moneda'
          }}
        </DialogDescription>
      </DialogHeader>

      <div class="grid gap-4 py-4">
        <!-- Nombre -->
        <div class="grid gap-2">
          <Label for="nombre">Nombre de la Moneda *</Label>
          <Input id="nombre" v-model="form.nombre" placeholder="Ej: Boliviano" :disabled="cargando" />
        </div>

        <!-- Abreviación -->
        <div class="grid gap-2">
          <Label for="abreviacion">Código/Abreviación *</Label>
          <Input
            id="abreviacion"
            v-model="form.abreviacion"
            placeholder="Ej: Bs, USD, EUR"
            maxlength="10"
            :disabled="cargando"
          />
        </div>

        <!-- País -->
        <div class="grid gap-2">
          <Label for="pais">País *</Label>
          <select
            id="pais"
            v-model="form.pais"
            :disabled="cargando"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="" disabled>Selecciona un país</option>
            <option v-for="pais in paises" :key="pais" :value="pais">{{ pais }}</option>
          </select>
        </div>

        <!-- Tipo de cambio -->
        <div class="grid gap-2">
          <Label for="tipo_cambio">Tipo de Cambio *</Label>
          <Input
            id="tipo_cambio"
            v-model.number="form.tipo_cambio"
            type="number"
            step="0.01"
            min="0"
            placeholder="Ej: 6.96"
            :disabled="cargando"
          />
          <p class="text-xs text-gray-500">Valor de conversión respecto a la moneda base</p>
        </div>

        <!-- Estado -->
        <div class="flex items-center gap-2">
          <input
            id="activo"
            v-model="form.activo"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            :disabled="cargando"
          />
          <Label for="activo" class="cursor-pointer">Moneda activa</Label>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="cerrarDialog" :disabled="cargando">Cancelar</Button>
        <Button @click="guardarMoneda" :disabled="cargando">
          <span v-if="cargando">Guardando...</span>
          <span v-else>{{ moneda ? 'Actualizar' : 'Registrar' }}</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
