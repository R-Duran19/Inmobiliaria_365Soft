<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
  fileSelected: [file: File];
}>();

const dragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const validationError = ref<string | null>(null);

const validateFile = (file: File): boolean => {
  validationError.value = null;
  
  const validExtensions = ['.json', '.geojson'];
  const fileName = file.name.toLowerCase();
  const isValid = validExtensions.some(ext => fileName.endsWith(ext));

  if (!isValid) {
    validationError.value = 'Solo se permiten archivos .json o .geojson';
    return false;
  }

  return true;
};

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const selectedFile = target.files?.[0];
  
  if (selectedFile && validateFile(selectedFile)) {
    emit('fileSelected', selectedFile);
  }
};

const handleDrop = (event: DragEvent) => {
  dragOver.value = false;
  const droppedFile = event.dataTransfer?.files[0];
  
  if (droppedFile && validateFile(droppedFile)) {
    emit('fileSelected', droppedFile);
  }
};
</script>

<template>
  <div>
    <div
      @drop.prevent="handleDrop"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      :class="[
        'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
        dragOver 
          ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
          : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
      ]"
    >
      <div class="space-y-4">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          stroke="currentColor"
          fill="none"
          viewBox="0 0 48 48"
        >
          <path
            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        
        <div>
          <label
            for="file-upload"
            class="cursor-pointer text-blue-600 dark:text-blue-400 hover:text-blue-500"
          >
            <span class="font-medium">Haz clic para seleccionar</span>
            <span class="text-gray-500 dark:text-gray-400"> o arrastra aquí</span>
          </label>
          <input
            id="file-upload"
            ref="fileInputRef"
            type="file"
            class="hidden"
            accept=".json,.geojson"
            @change="handleFileSelect"
          />
        </div>
        
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Archivos .json o .geojson
        </p>
      </div>
    </div>

    <p v-if="validationError" class="mt-2 text-sm text-red-600 dark:text-red-400">
      {{ validationError }}
    </p>
  </div>
</template>