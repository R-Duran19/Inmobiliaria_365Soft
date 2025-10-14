<script setup lang="ts">
const instrucciones = [
  'El archivo debe ser formato .json o .geojson',
  'Debe contener geometrías tipo Polygon o MultiPolygon',
  'Las propiedades pueden incluir: nombre, tipo, barrio, cuadra, superficie, etc.',
  'El sistema creará automáticamente barrios, cuadras y terrenos según la estructura'
];
</script>

<template>
  <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Instrucciones
    </h3>
    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
      <li 
        v-for="(instruccion, index) in instrucciones" 
        :key="index"
        class="flex items-start"
      >
        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span v-html="instruccion.replace(/\.(json|geojson|Polygon|MultiPolygon)/g, '<code class=\'px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs font-mono\'>.$1</code>')" />
      </li>
    </ul>
  </div>
</template>