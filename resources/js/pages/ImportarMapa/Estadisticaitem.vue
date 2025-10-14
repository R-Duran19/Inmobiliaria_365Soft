<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
  titulo: string;
  total: number;
  conPoligono: number;
}>();

const porcentaje = computed(() => {
  if (props.total === 0) return 0;
  return Math.round((props.conPoligono / props.total) * 100);
});

const colorClase = computed(() => {
  const p = porcentaje.value;
  if (p === 0) return 'bg-red-500';
  if (p < 50) return 'bg-orange-500';
  if (p < 100) return 'bg-yellow-500';
  return 'bg-green-500';
});
</script>

<template>
  <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ titulo }}</span>
      <Badge variant="outline" class="text-xs">
        {{ total }}
      </Badge>
    </div>
    
    <div class="space-y-2">
      <div class="flex items-center justify-between text-xs">
        <span class="text-gray-600 dark:text-gray-400">Con polígono:</span>
        <span class="font-medium text-gray-900 dark:text-gray-100">
          {{ conPoligono }}
        </span>
      </div>
      
      <!-- Barra de progreso -->
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div 
          :class="['h-2 rounded-full transition-all', colorClase]"
          :style="{ width: porcentaje + '%' }"
        />
      </div>
      
      <div class="text-right text-xs font-medium text-gray-600 dark:text-gray-400">
        {{ porcentaje }}%
      </div>
    </div>
  </div>
</template>