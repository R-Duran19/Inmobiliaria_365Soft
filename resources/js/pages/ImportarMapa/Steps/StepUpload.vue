<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';
import DropZone from '../DropZone.vue';
import FilePreview from '../FilePreview.vue';

const props = defineProps<{
  file: File | null;
  geojson: any;
  analysis: any;
}>();

const emit = defineEmits<{
  'update:file': [file: File | null];
  'update:geojson': [geojson: any];
  'update:analysis': [analysis: any];
  'next': [];
}>();

const analyzing = ref(false);
const error = ref<string | null>(null);

const handleFileSelect = async (selectedFile: File) => {
  emit('update:file', selectedFile);
  emit('update:geojson', null);
  emit('update:analysis', null);
  error.value = null;

  try {
    analyzing.value = true;
    const fileContent = await selectedFile.text();
    const geojsonData = JSON.parse(fileContent);
    
    emit('update:geojson', geojsonData);

    // Analizar el archivo
    const response = await axios.post('/api/import/analyze', {
      geojson: geojsonData,
    });

    if (response.data.success) {
      emit('update:analysis', response.data.analysis);
      
      // Auto-avanzar si no hay errores
      if (response.data.analysis.valid) {
        setTimeout(() => emit('next'), 500);
      }
    }
  } catch (err: any) {
    console.error('Error al analizar:', err);
    error.value = err.response?.data?.message || 'Error al procesar el archivo';
  } finally {
    analyzing.value = false;
  }
};

const removeFile = () => {
  emit('update:file', null);
  emit('update:geojson', null);
  emit('update:analysis', null);
  error.value = null;
};
const exampleGeoJSON = {
  type: "FeatureCollection",
  features: [
    { type: "Feature", properties: { PROYECTO: "UV 001" }, geometry: "{...}" },
    { type: "Feature", properties: { "UV 001": "MZ 007" }, geometry: "{...}" },
    { type: "Feature", properties: { "UV 001": "MZ 008" }, geometry: "{...}" },
    { type: "Feature", properties: { "MZ 007": "001" }, geometry: "{...}" },
    { type: "Feature", properties: { "MZ 007": "002" }, geometry: "{...}" },
    { type: "Feature", properties: { "MZ 008": "001" }, geometry: "{...}" },
  ]
};
</script>

<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <i class="pi pi-upload text-6xl text-blue-500 mb-4"></i>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-2">
        Cargar Archivo GeoJSON
      </h3>
      <p class="text-gray-600 dark:text-gray-400">
        Selecciona un archivo .json o .geojson para comenzar la importación
      </p>
    </div>

    <!-- DropZone -->
    <DropZone 
      v-if="!file"
      @file-selected="handleFileSelect" 
    />

    <!-- File Preview -->
    <FilePreview 
      v-if="file && !analyzing"
      :file="file"
      :uploading="false"
      @remove="removeFile"
    />

    <!-- Analyzing Spinner -->
    <div v-if="analyzing" class="flex flex-col items-center justify-center py-12">
      <ProgressSpinner 
        style="width: 50px; height: 50px"
        strokeWidth="4"
        animationDuration="1s"
      />
      <p class="mt-4 text-gray-600 dark:text-gray-400">
        Analizando archivo...
      </p>
    </div>

    <!-- Error Message -->
    <Message 
      v-if="error" 
      severity="error" 
      :closable="false"
    >
      {{ error }}
    </Message>

    <!-- Analysis Errors -->
    <Message 
      v-if="analysis && !analysis.valid" 
      severity="error"
      :closable="false"
    >
      <div class="space-y-2">
        <p class="font-semibold">Se encontraron errores en el archivo:</p>
        <ul class="list-disc list-inside space-y-1">
          <li v-for="(err, idx) in analysis.errors" :key="idx" class="text-sm">
            {{ err }}
          </li>
        </ul>
      </div>
    </Message>

    <!-- Quick Analysis Preview -->
    <div 
      v-if="analysis && analysis.valid" 
      class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
    >
      <div class="flex items-center gap-2 mb-3">
        <i class="pi pi-check-circle text-green-600 text-xl"></i>
        <h4 class="font-semibold text-green-800 dark:text-green-200">
          Archivo válido
        </h4>
      </div>
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div class="text-center">
          <div class="text-2xl font-bold text-green-700 dark:text-green-300">
            {{ analysis.barrios.count }}
          </div>
          <div class="text-gray-600 dark:text-gray-400">Barrios</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-700 dark:text-green-300">
            {{ analysis.cuadras.count }}
          </div>
          <div class="text-gray-600 dark:text-gray-400">Cuadras</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-700 dark:text-green-300">
            {{ analysis.terrenos.count }}
          </div>
          <div class="text-gray-600 dark:text-gray-400">Terrenos</div>
        </div>
      </div>
    </div>
  </div>
</template>