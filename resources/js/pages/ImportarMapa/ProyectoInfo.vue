<script setup lang="ts">
interface Proyecto {
  nombre: string;
  ubicacion: string | null;
  numero_lotes: number | null;
  estado: boolean;
}

defineProps<{
  proyecto: Proyecto;
}>();
</script>

<template>
  <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <span class="font-medium text-blue-900 dark:text-blue-200">Proyecto:</span>
        <span class="ml-2 text-blue-700 dark:text-blue-300">{{ proyecto.nombre }}</span>
      </div>
      <div>
        <span class="font-medium text-blue-900 dark:text-blue-200">Ubicación:</span>
        <span class="ml-2 text-blue-700 dark:text-blue-300">{{ proyecto.ubicacion || 'No especificada' }}</span>
      </div>
      <div>
        <span class="font-medium text-blue-900 dark:text-blue-200">Lotes:</span>
        <span class="ml-2 text-blue-700 dark:text-blue-300">{{ proyecto.numero_lotes || 0 }}</span>
      </div>
      <div>
        <span class="font-medium text-blue-900 dark:text-blue-200">Estado:</span>
        <span class="ml-2 text-blue-700 dark:text-blue-300">
          {{ proyecto.estado ? 'Activo' : 'Inactivo' }}
        </span>
      </div>
    </div>
  </div>
</template>