<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import { type BreadcrumbItem } from '@/types';

// 📍 Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentos', href: '/documentos' },
];

// 📁 Archivos seleccionados
const archivos = ref<FileList | null>(null);

// 🔔 Notificación
const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

// 📤 Función para subir archivo(s)
async function subirArchivos() {
    if (!archivos.value || archivos.value.length === 0) {
        mostrarNotificacion('error', 'Debe seleccionar al menos un archivo.');
        return;
    }

    const formData = new FormData();
    for (let i = 0; i < archivos.value.length; i++) {
        formData.append('archivos', archivos.value[i]);
    }

    try {
        await axios.post('/documentos/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        });

    mostrarNotificacion('success', 'Archivo(s) subido(s) correctamente.');
    archivos.value = null;
    } catch (error) {
        console.error('Error al subir archivos:', error);
        mostrarNotificacion('error', 'Error al subir los archivos.');
    }
}

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6">
        <h1 class="text-2xl font-semibold">Subir Documentos</h1>

        <div class="bg-white shadow rounded-2xl p-6 space-y-4">
            <label class="block text-sm font-medium text-gray-700"
            >Seleccione archivo(s):</label
        >
        <input
            type="file"
            multiple
            @change="(e) => (archivos = (e.target as HTMLInputElement).files)"
            class="border border-gray-300 rounded-lg p-2 w-full"
        />

        <button
            @click="subirArchivos"
            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
        >
            Subir
        </button>
        </div>
    </div>

    <!-- 🔔 Toast de notificación -->
    <NotificacionToast
        v-model="notificacion.visible"
        :type="notificacion.tipo"
        :message="notificacion.mensaje"
    />
    </AppLayout>
</template>

<style scoped>
h1 {
    color: #ffffff;
}
</style>
