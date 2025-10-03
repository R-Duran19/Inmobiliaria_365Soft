<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const search = ref('');

const props = defineProps<{
  search: string;
  categoriasFiltradasLength: number;
}>();

const emit = defineEmits(['update:search', 'new', 'export']);
</script>

<template>
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mx-6 my-4">
    <div class="w-full sm:flex-grow">
      <Input
        :model-value="props.search"
        @update:model-value="$emit('update:search', $event)"
        placeholder="Buscar categorías..."
        class="w-full max-w-3xl dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100"
      />
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ props.categoriasFiltradasLength || 0 }} categorías encontradas
      </p>
    </div>
    <div class="flex gap-2 w-full sm:w-auto justify-end">
      <Button
        variant="default"
        class="w-full sm:w-auto"
        @click="$emit('new')"
      >
        Nuevo
      </Button>
      <Button
        variant="outline"
        class="w-full sm:w-auto dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700"
        @click="$emit('export')"
      >
        Exportar
      </Button>
    </div>
  </div>
</template>
