<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import axios from 'axios';
import { type BreadcrumbItem } from '@/types';

const props = defineProps({
    terrenoId: Number,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentos', href: '/documentos' },
];

const archivos = ref<FileList | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);

const notificacion = reactive({
    visible: false,
    tipo: 'success' as 'success' | 'error',
    mensaje: '',
});

const confirmModal = reactive({
    visible: false,
    documento: null as any,
});

const infoModal = reactive({
    visible: false,
    documento: null as any,
});

const documentos = ref<any[]>([]);

async function cargarDocumentos() {
    try {
        const res = await axios.get(`/documentos/list/${props.terrenoId}`);
        documentos.value = res.data;
    } catch (error) {
        console.error('Error al cargar documentos:', error);
        mostrarNotificacion('error', 'Error al cargar los documentos.');
    }
}

onMounted(() => {
    cargarDocumentos();
});

async function subirArchivos() {
    if (!archivos.value || archivos.value.length === 0) {
        mostrarNotificacion('error', 'Debe seleccionar al menos un archivo.');
        return;
    }

    const formData = new FormData();
    for (let i = 0; i < archivos.value.length; i++) {
        formData.append('archivos[]', archivos.value[i]);
    }

    formData.append('terreno_id', props.terrenoId?.toString() || '');

    isUploading.value = true;

    try {
        await axios.post('/documentos/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        mostrarNotificacion('success', 'Archivo(s) subido(s) correctamente.');
        archivos.value = null;
        
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
        
        await cargarDocumentos();
    } catch (error) {
        console.error('Error al subir archivos:', error);
        mostrarNotificacion('error', 'Error al subir los archivos.');
    } finally {
        isUploading.value = false;
    }
}

function abrirConfirmacion(doc: any) {
    confirmModal.documento = doc;
    confirmModal.visible = true;
}

function cancelarEliminacion() {
    confirmModal.visible = false;
    confirmModal.documento = null;
}

async function confirmarEliminacion() {
    if (!confirmModal.documento) return;

    try {
        await axios.delete(`/documentos/${confirmModal.documento.id}`);
        mostrarNotificacion('success', 'Documento eliminado correctamente.');
        await cargarDocumentos();
    } catch (error) {
        console.error('Error al eliminar documento:', error);
        mostrarNotificacion('error', 'Error al eliminar el documento.');
    } finally {
        confirmModal.visible = false;
        confirmModal.documento = null;
    }
}

function abrirInfoModal(doc: any) {
    infoModal.documento = doc;
    infoModal.visible = true;
}

function cerrarInfoModal() {
    infoModal.visible = false;
    infoModal.documento = null;
}

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
    notificacion.tipo = tipo;
    notificacion.mensaje = mensaje;
    notificacion.visible = true;
    
    setTimeout(() => {
        notificacion.visible = false;
    }, 3000);
}

function getFileExtension(filename: string) {
    return filename.split('.').pop()?.toLowerCase() || '';
}

function isImage(filename: string) {
    const ext = getFileExtension(filename);
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
}

function getFileIcon(filename: string) {
    const ext = getFileExtension(filename);
    const icons: Record<string, string> = {
        pdf: '📄',
        doc: '📝',
        docx: '📝',
        xls: '📊',
        xlsx: '📊',
        txt: '📃',
        zip: '🗜️',
        rar: '🗜️',
    };
    return icons[ext] || '📎';
}

function tieneInformacion(doc: any) {
    return doc.estado_ocr === 'procesado' && doc.datos_extraidos && Object.keys(doc.datos_extraidos).length > 0;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                        📁 Documentos del Terreno
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Gestiona y organiza los documentos asociados a este terreno
                    </p>
                </div>

                <!-- Upload Section -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <span class="text-2xl">⬆️</span>
                        Subir Nuevos Documentos
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="relative">
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                @change="(e) => (archivos = (e.target as HTMLInputElement).files)"
                                class="block w-full text-sm text-gray-600 dark:text-gray-400
                                    file:mr-4 file:py-3 file:px-6
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    dark:file:bg-blue-900/30 dark:file:text-blue-400
                                    hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50
                                    cursor-pointer border-2 border-dashed border-gray-300 dark:border-gray-600
                                    rounded-xl p-4 hover:border-blue-400 dark:hover:border-blue-500 
                                    transition-colors bg-gray-50 dark:bg-gray-900"
                            />
                        </div>

                        <button
                            @click="subirArchivos"
                            :disabled="isUploading"
                            class="bg-gradient-to-r from-green-500 to-green-600 text-white 
                                px-6 py-3 rounded-xl font-semibold
                                hover:from-green-600 hover:to-green-700 
                                disabled:opacity-50 disabled:cursor-not-allowed
                                transition-all duration-200 shadow-md hover:shadow-lg
                                flex items-center gap-2"
                        >
                            <span v-if="!isUploading">✓ Subir Archivos</span>
                            <span v-else>⏳ Subiendo...</span>
                        </button>
                    </div>
                </div>

                <!-- Documents Grid -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <span class="text-2xl">📚</span>
                        Documentos Almacenados
                        <span class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ documentos.length }} archivo(s)
                        </span>
                    </h2>

                    <!-- Empty State -->
                    <div
                        v-if="documentos.length === 0"
                        class="text-center py-16 text-gray-400 dark:text-gray-500"
                    >
                        <div class="text-6xl mb-4">📭</div>
                        <p class="text-lg">No hay documentos subidos todavía</p>
                        <p class="text-sm mt-2">Sube tu primer documento usando el formulario superior</p>
                    </div>

                    <!-- Documents Grid -->
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
                    >
                        <div
                            v-for="doc in documentos"
                            :key="doc.id"
                            class="group relative bg-gray-50 dark:bg-gray-700/50
                                rounded-xl overflow-hidden shadow-md hover:shadow-xl 
                                transition-all duration-300 border border-gray-200 dark:border-gray-600
                                hover:scale-105"
                        >
                            <!-- Image/Icon Preview -->
                            <div class="relative h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="isImage(doc.nombre_documento)"
                                    :src="`/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`"
                                    :alt="doc.nombre_documento"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="text-6xl"
                                >
                                    {{ getFileIcon(doc.nombre_documento) }}
                                </div>

                                <!-- Botones de acción overlay -->
                                <div class="absolute top-2 right-2 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <!-- Botón Ver Información -->
                                    <button
                                        v-if="tieneInformacion(doc)"
                                        @click="abrirInfoModal(doc)"
                                        class="bg-blue-500 text-white rounded-full p-2 hover:bg-blue-600 shadow-lg"
                                        title="Ver información extraída"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <!-- Botón Eliminar -->
                                    <button
                                        @click="abrirConfirmacion(doc)"
                                        class="bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg"
                                        title="Eliminar documento"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Badge de estado OCR -->
                                <div v-if="doc.estado_ocr === 'procesado' && tieneInformacion(doc)" 
                                     class="absolute bottom-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow-lg">
                                    ✓ Escaneado
                                </div>
                                <div v-else-if="doc.estado_ocr === 'error'" 
                                     class="absolute bottom-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full shadow-lg">
                                    ✕ Error OCR
                                </div>
                            </div>

                            <!-- Document Info -->
                            <div class="p-4 bg-white dark:bg-gray-800">
                                <p
                                    class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate"
                                    :title="doc.nombre_documento"
                                >
                                    {{ doc.nombre_documento }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ new Date(doc.created_at).toLocaleDateString('es-ES') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Información Extraída -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-90"
        >
            <div
                v-if="infoModal.visible"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click="cerrarInfoModal"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                <!-- Modal -->
                <div
                    @click.stop
                    class="relative z-10 w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform max-h-[90vh] overflow-y-auto"
                >
                    <!-- Cabecera -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4">
                            <svg
                                class="w-8 h-8 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white">
                            Información Extraída
                        </h3>
                        <p class="text-white/80 text-sm mt-2">{{ infoModal.documento?.nombre_documento }}</p>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <div v-if="infoModal.documento?.datos_extraidos" class="space-y-4">
                            <!-- Tipo de documento -->
                            <div v-if="infoModal.documento.datos_extraidos.tipo_documento" 
                                 class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Tipo de Documento</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ infoModal.documento.datos_extraidos.tipo_documento }}
                                </p>
                            </div>

                            <!-- Grid de datos -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="infoModal.documento.datos_extraidos.numero_documento" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Número de Documento</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.numero_documento }}
                                    </p>
                                </div>

                                <div v-if="infoModal.documento.datos_extraidos.fecha" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.fecha }}
                                    </p>
                                </div>

                                <div v-if="infoModal.documento.datos_extraidos.codigo_catastral" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Código Catastral</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.codigo_catastral }}
                                    </p>
                                </div>

                                <div v-if="infoModal.documento.datos_extraidos.matricula" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Matrícula</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.matricula }}
                                    </p>
                                </div>

                                <div v-if="infoModal.documento.datos_extraidos.superficie" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Superficie</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.superficie }} m²
                                    </p>
                                </div>

                                <div v-if="infoModal.documento.datos_extraidos.propietario" 
                                     class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Propietario</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ infoModal.documento.datos_extraidos.propietario }}
                                    </p>
                                </div>
                            </div>

                            <!-- Ubicación (ocupa todo el ancho) -->
                            <div v-if="infoModal.documento.datos_extraidos.ubicacion" 
                                 class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ubicación</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ infoModal.documento.datos_extraidos.ubicacion }}
                                </p>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500">
                            No se pudo extraer información de este documento
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 pt-0">
                        <button
                            @click="cerrarInfoModal"
                            class="w-full px-6 py-3 bg-gray-100 dark:bg-gray-700 
                                text-gray-700 dark:text-gray-300 rounded-xl font-semibold
                                hover:bg-gray-200 dark:hover:bg-gray-600 
                                transition-colors duration-200"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Modal de Confirmación de Eliminación -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-90"
        >
            <div
                v-if="confirmModal.visible"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click="cancelarEliminacion"
            >
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                <div
                    @click.stop
                    class="relative z-10 w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden transform"
                >
                    <div class="bg-gradient-to-br from-red-500 to-red-600 p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4">
                            <svg
                                class="w-8 h-8 text-red-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white">
                            ¿Eliminar documento?
                        </h3>
                    </div>

                    <div class="p-6">
                        <p class="text-gray-600 dark:text-gray-300 text-center mb-2">
                            ¿Está seguro de eliminar el documento?
                        </p>
                        <p class="text-gray-800 dark:text-gray-100 font-semibold text-center mb-6 break-words">
                            "{{ confirmModal.documento?.nombre_documento }}"
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                            Esta acción no se puede deshacer
                        </p>
                    </div>

                    <div class="flex gap-3 p-6 pt-0">
                        <button
                            @click="cancelarEliminacion"
                            class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 
                                text-gray-700 dark:text-gray-300 rounded-xl font-semibold
                                hover:bg-gray-200 dark:hover:bg-gray-600 
                                transition-colors duration-200"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="confirmarEliminacion"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 
                                text-white rounded-xl font-semibold
                                hover:from-red-600 hover:to-red-700 
                                transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Notificación Custom -->
        <Transition
            enter-active-class="transition ease-out duration-300 transform"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition ease-in duration-200 transform"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div
                v-if="notificacion.visible"
                class="fixed top-6 right-6 z-[9999] max-w-md w-auto min-w-[320px]"
            >
                <div
                    :class="[
                        'rounded-xl shadow-2xl text-white overflow-hidden',
                        notificacion.tipo === 'success' 
                            ? 'bg-gradient-to-r from-green-500 to-green-600' 
                            : 'bg-gradient-to-r from-red-500 to-red-600'
                    ]"
                >
                    <div class="flex items-start p-4 gap-3">
                        <div
                            :class="[
                                'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-xl font-bold shadow-lg',
                                notificacion.tipo === 'success' ? 'bg-green-600' : 'bg-red-600'
                            ]"
                        >
                            {{ notificacion.tipo === 'success' ? '✓' : '✕' }}
                        </div>

                        <div class="flex-1 pt-0.5">
                            <h3 class="font-semibold text-base mb-1">
                                {{ notificacion.tipo === 'success' ? 'Éxito' : 'Error' }}
                            </h3>
                            <p class="text-sm text-white/95 leading-relaxed">
                                {{ notificacion.mensaje }}
                            </p>
                        </div>

                        <button
                            @click="notificacion.visible = false"
                            class="flex-shrink-0 text-white/70 hover:text-white transition-colors rounded-lg p-1 hover:bg-white/10"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="h-1 bg-white/20">
                        <div
                            class="h-full bg-white/50 notification-progress"
                        ></div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group:hover {
    animation: fadeIn 0.3s ease-out;
}

@keyframes notificationProgress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.notification-progress {
    animation: notificationProgress 3s linear forwards;
}
</style>