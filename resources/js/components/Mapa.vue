<script setup lang="ts">
import { onMounted, ref, onUnmounted } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapContainer = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;

onMounted(() => {
    if (!mapContainer.value) return;

    // Crear el mapa centrado en La Paz, Bolivia
    map = L.map(mapContainer.value).setView([-17.5, -66.15], 10);

    // Agregar capa de tiles (puedes elegir entre varias opciones)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
});

onUnmounted(() => {
    // Limpiar el mapa cuando el componente se destruya
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <div ref="mapContainer" class="w-full h-[600px] rounded-lg shadow-lg"></div>
</template>

<style scoped>
/* Aseguramos que Leaflet tenga el estilo correcto */
:deep(.leaflet-container) {
    font-family: inherit;
    z-index: 0;
}
</style>