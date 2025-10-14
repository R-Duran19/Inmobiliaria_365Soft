<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  file: File;
  uploading: boolean;
}>();

const emit = defineEmits<{
  remove: [];
}>();

const fileSize = computed(() => {
  const bytes = props.file.size;
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
});
</script>

<template>
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ file.name }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ fileSize }}
          </p>
        </div>
      </div>
      <Button
        variant="ghost"
        size="sm"
        @click="emit('remove')"
        :disabled="uploading"
      >
        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </Button>
    </div>
  </div>
</template>