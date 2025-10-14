<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Summary {
  barrios: { creados: number; omitidos: number };
  cuadras: { creadas: number; omitidas: number };
  terrenos: { creados: number; omitidos: number };
}

defineProps<{
  result: {
    message: string;
    summary: Summary;
  };
}>();
</script>

<template>
  <Alert class="bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
    <AlertDescription class="ml-3">
      <p class="font-medium text-green-800 dark:text-green-200">{{ result.message }}</p>
      <div class="mt-3 grid grid-cols-3 gap-4">
        <div class="text-center">
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ result.summary.barrios.creados }}
          </p>
          <p class="text-xs text-green-700 dark:text-green-300">Barrios creados</p>
          <p v-if="result.summary.barrios.omitidos" class="text-xs text-gray-500 dark:text-gray-400">
            ({{ result.summary.barrios.omitidos }} omitidos)
          </p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ result.summary.cuadras.creadas }}
          </p>
          <p class="text-xs text-green-700 dark:text-green-300">Cuadras creadas</p>
          <p v-if="result.summary.cuadras.omitidas" class="text-xs text-gray-500 dark:text-gray-400">
            ({{ result.summary.cuadras.omitidas }} omitidas)
          </p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ result.summary.terrenos.creados }}
          </p>
          <p class="text-xs text-green-700 dark:text-green-300">Terrenos creados</p>
          <p v-if="result.summary.terrenos.omitidos" class="text-xs text-gray-500 dark:text-gray-400">
            ({{ result.summary.terrenos.omitidos }} omitidos)
          </p>
        </div>
      </div>
    </AlertDescription>
  </Alert>
</template>