<script setup lang="ts">
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import * as pdfjsLib from 'pdfjs-dist';

pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
  'pdfjs-dist/build/pdf.worker.min.js',
  import.meta.url
).toString();

const props = defineProps({
    terrenoId: Number,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Visualizar Documentos', href: `/documentos/visualizar/${props.terrenoId}` },
];

const documentos = ref<any[]>([]);
const nombreProyecto = ref<string>('');
const ubicacionTerreno = ref<string>('');

function isHTMLElement(el: unknown): el is HTMLElement {
    return el instanceof HTMLElement;
}

async function cargarDocumentos() {
    try {
        const res = await axios.get(`/documentos/list/${props.terrenoId}`);
        documentos.value = res.data;
        const terrenoRes = await axios.get(`/api/terrenos/${props.terrenoId}`);
        nombreProyecto.value = terrenoRes.data.proyecto?.nombre || 'Sin proyecto';
        ubicacionTerreno.value = terrenoRes.data.ubicacion || 'Sin ubicación';
    } catch (error) {
        console.error('Error al cargar documentos:', error);
    }
}

function limpiarNombreArchivo(nombre: string): string {
    let nombreLimpio = nombre.replace(/^\d+_/, '');
    nombreLimpio = nombreLimpio.replace(/\.[^/.]+$/, '');
    return nombreLimpio;
}

function esImagen(nombreArchivo: string): boolean {
    const extensionesImagen = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    const extension = nombreArchivo.split('.').pop()?.toLowerCase();
    return extensionesImagen.includes(extension || '');
}

function esPDF(nombreArchivo: string): boolean {
    return nombreArchivo.toLowerCase().endsWith('.pdf');
}

function esDOCX(nombreArchivo: string): boolean {
    return nombreArchivo.toLowerCase().endsWith('.docx');
}

function obtenerExtension(nombreArchivo: string): string {
    return nombreArchivo.split('.').pop()?.toUpperCase() || '';
}

function obtenerFechaLegible(fecha: string): string {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function setPdfContainer(el: unknown, docId: string, doc: any) {
    if (isHTMLElement(el)) {
        renderizarPDF(`/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`, el);
    }
}

async function renderizarPDF(url: string, container: HTMLElement) {
    try {
        const loadingTask = pdfjsLib.getDocument(url);
        const pdf = await loadingTask.promise;
        const page = await pdf.getPage(1);
        const viewport = page.getViewport({ scale: 0.50 });
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d')!;
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        await page.render({
            canvasContext: context,
            viewport: viewport,
        } as any);
        container.innerHTML = '';
        container.appendChild(canvas);
    } catch (error) {
        console.error('Error al renderizar PDF:', error);
        container.innerHTML = '<p class="text-red-500 text-xs">Error al cargar el PDF</p>';
    }
}

function descargarDocumento(nombreDocumento: string) {
    const url = `/documentos_clientes/${props.terrenoId}/${nombreDocumento}`;
    const link = document.createElement('a');
    link.href = url;
    link.download = nombreDocumento;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

onMounted(() => {
    cargarDocumentos();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">
                Documentos del Terreno - ({{ nombreProyecto }} | {{ ubicacionTerreno }})
            </h1>
            <div v-if="documentos.length === 0" class="text-center py-12">
                <p class="text-gray-500 text-lg">Aún no se subieron documentos</p>
            </div>
            <!-- Lista de documentos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="doc in documentos"
                    :key="doc.id"
                    class="border rounded-lg p-4 hover:shadow-lg transition-shadow flex flex-col h-[330px]"
                >
                    <!-- Icono según el tipo de archivo -->
                    <div class="flex justify-center mb-4 h-16">
                        <svg
                            v-if="esImagen(doc.nombre_documento)"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-16 w-16 text-yellow-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <svg
                            v-else-if="esPDF(doc.nombre_documento)"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-16 w-16 text-red-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-16 w-16 text-blue-600 mt-custom"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <!-- Vista previa para imágenes -->
                    <div v-if="esImagen(doc.nombre_documento)" class="flex justify-center items-center mb-1 h-40 overflow-hidden">
                        <img
                            :src="`/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`"
                            :alt="doc.nombre_documento"
                            class="w-full h-full object-contain rounded"
                        />
                    </div>
                    <!-- Vista previa para PDF -->
                    <div
                        v-else-if="esPDF(doc.nombre_documento)"
                        class="flex justify-center items-center mb-2 h-30  overflow-hidden border rounded-lg"
                    >
                        <div :ref="(el) => setPdfContainer(el, doc.id, doc)" class="w-full h-full"></div>
                    </div>
                    <!-- Vista previa no disponible-->
                    <div
                        v-else
                        class="w-full mb-2 h-30 flex justify-center items-center border rounded-lg bg-gray-100"
                    >
                        <p class="text-gray-500 text-xs">Vista previa no disponible</p>
                    </div>

                    <!-- Nombre del documento -->
                    <div class="flex-grow flex items-center justify-center">
                        <p class="text-sm font-medium text-center px-2">
                            {{ limpiarNombreArchivo(doc.nombre_documento) }}
                        </p>
                    </div>

                    <!-- Metadatos -->
                    <p class="text-xs text-gray-500 text-center mb-3">
                        Formato: .{{ obtenerExtension(doc.nombre_documento) }} |
                        Subido: {{ obtenerFechaLegible(doc.created_at) }}
                    </p>

                    <!-- Botón de descarga -->
                    <button
                        @click="descargarDocumento(doc.nombre_documento)"
                        class="mt-auto bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 text-xs"
                    >
                        Descargar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>