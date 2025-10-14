<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import DropZone from './DropZone.vue';
import FilePreview from './FilePreview.vue';
import UploadResult from './UploadResult.vue';
import UploadError from './UploadError.vue';

const props = defineProps<{
  proyectoId: number;
}>();

const emit = defineEmits<{
  success: [result: any];
  error: [error: any];
}>();

const file = ref<File | null>(null);
const uploading = ref(false);
const uploadResult = ref<any>(null);
const errorMessage = ref<string | null>(null);

const handleFileSelect = (selectedFile: File) => {
  file.value = selectedFile;
  errorMessage.value = null;
  uploadResult.value = null;
};

const removeFile = () => {
  file.value = null;
  uploadResult.value = null;
  errorMessage.value = null;
};

const uploadFile = async () => {
  if (!file.value) return;

  uploading.value = true;
  errorMessage.value = null;
  uploadResult.value = null;

  try {
    const fileContent = await file.value.text();
    const geojson = JSON.parse(fileContent);

    const response = await axios.post('/import/geojson', {
      idproyecto: props.proyectoId,
      geojson: geojson,
    });

    uploadResult.value = response.data;
    emit('success', response.data);
    
    setTimeout(() => {
      removeFile();
    }, 3000);

  } catch (error: any) {
    console.error('Error al importar:', error);
    
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors;
      errorMessage.value = Array.isArray(errors) 
        ? errors.join('\n') 
        : Object.values(errors).flat().join('\n');
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else if (error.response?.data?.error) {
      errorMessage.value = error.response.data.error;
    } else {
      errorMessage.value = 'Error al procesar el archivo';
    }
    
    emit('error', errorMessage.value);
  } finally {
    uploading.value = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <DropZone 
      v-if="!file"
      @file-selected="handleFileSelect" 
    />

    <FilePreview 
      v-if="file"
      :file="file"
      :uploading="uploading"
      @remove="removeFile"
    />

    <Button
      v-if="file"
      @click="uploadFile"
      :disabled="uploading"
      class="w-full"
      size="lg"
    >
      <svg v-if="uploading" class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
      {{ uploading ? 'Importando...' : 'Importar GeoJSON' }}
    </Button>

    <UploadResult 
      v-if="uploadResult?.success"
      :result="uploadResult"
    />

    <UploadError 
      v-if="errorMessage"
      :error-message="errorMessage"
    />
  </div>
</template>