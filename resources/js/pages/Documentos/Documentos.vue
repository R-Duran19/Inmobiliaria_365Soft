<script setup lang="ts">
import { type BreadcrumbItem } from '@/types';
import axios from 'axios';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import {
    Autoplay,
    EffectCoverflow,
    Navigation,
    Pagination,
} from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, onMounted, reactive, ref } from 'vue';
const nextBtnRef = ref<HTMLDivElement | null>(null);
const prevBtnRef = ref<HTMLDivElement | null>(null);
const slidesPerView = computed(() => Math.min(3, documentos.value.length || 1));
const enableLoop = computed(() => documentos.value.length > 3);
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
const swiperRef = ref<any>(null);
function onSwiperInit(swiperInstance: any) {
    swiperRef.value = swiperInstance;
}
async function cargarDocumentos() {
    try {
        const res = await axios.get(`/documentos/list/${props.terrenoId}`);
        documentos.value = res.data.map((doc: any) => {
            if (
                doc.datos_extraidos &&
                typeof doc.datos_extraidos === 'string'
            ) {
                doc.datos_extraidos = JSON.parse(doc.datos_extraidos);
            }
            return doc;
        });
        console.log('Documentos cargados:', documentos.value);
    } catch (error) {
        console.error('Error al cargar documentos:', error);
        mostrarNotificacion('error', 'Error al cargar los documentos.');
    }
}
onMounted(() => {
    cargarDocumentos();
    if (swiperRef.value && nextBtnRef.value && prevBtnRef.value) {
        swiperRef.value.params.navigation.nextEl = nextBtnRef.value;
        swiperRef.value.params.navigation.prevEl = prevBtnRef.value;
        swiperRef.value.navigation.init();
        swiperRef.value.navigation.update();
    }
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
    formData.append('tipo_documento', 'folio_real');
    formData.append('tipo_documento', 'testimonio');
    formData.append('tipo_documento', 'certificado_catastral');
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
function onSlideChange(swiper: any) {
    console.log('Índice real:', swiper.realIndex);
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
    return (
        doc.estado_ocr === 'procesado' &&
        doc.datos_extraidos &&
        Object.keys(doc.datos_extraidos).length > 0
    );
}
function getImageUrl(doc: any) {
    return `/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`;
}
function descargarDocumento(doc: any) {
    const url = `/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`;
    const link = document.createElement('a');
    link.href = url;
    link.download = doc.nombre_documento;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
function abrirDocumento(doc: any) {
    window.open(getImageUrl(doc), '_blank');
}
</script>
<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gray-50 p-6 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl space-y-6">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <h1
                        class="mb-2 text-3xl font-bold text-gray-800 dark:text-gray-100"
                    >
                        📁 Documentos del Terreno
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Gestiona y organiza los documentos asociados a este
                        terreno
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-xl font-semibold text-gray-800 dark:text-gray-100"
                    >
                        <span class="text-2xl">⬆️</span>
                        Subir Nuevos Documentos
                    </h2>
                    <div class="space-y-4">
                        <div class="relative">
                            <input
                                ref="fileInputRef"
                                type="file"
                                multiple
                                @change="
                                    (e) =>
                                        (archivos = (
                                            e.target as HTMLInputElement
                                        ).files)
                                "
                                class="block w-full cursor-pointer rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 transition-colors file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-6 file:py-3 file:text-sm file:font-semibold file:text-blue-700 hover:border-blue-400 hover:file:bg-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400 dark:file:bg-blue-900/30 dark:file:text-blue-400 dark:hover:border-blue-500 dark:hover:file:bg-blue-900/50"
                            />
                        </div>
                        <button
                            @click="subirArchivos"
                            :disabled="isUploading"
                            class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-green-500 to-green-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:from-green-600 hover:to-green-700 hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span v-if="!isUploading">✓ Subir Archivos</span>
                            <span v-else>⏳ Subiendo...</span>
                        </button>
                    </div>
                </div>
                <div
                    v-if="documentos.length"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-gray-100"
                    >
                        <span class="text-2xl">🎞️</span>
                        Vista en Carrusel
                        <span
                            class="ml-auto text-sm text-gray-500 dark:text-gray-400"
                            >{{ documentos.length }} items</span
                        >
                    </h2>
                    <div class="mx-auto max-w-4xl">
                        <swiper
                            :modules="[
                                Navigation,
                                Pagination,
                                Autoplay,
                                EffectCoverflow,
                            ]"
                            :navigation="{
                                nextEl: nextBtnRef,
                                prevEl: prevBtnRef,
                            }"
                            @slideChange="onSlideChange"
                            :pagination="{ clickable: true }"
                            :autoplay="{
                                delay: 5000,
                                disableOnInteraction: false,
                            }"
                            effect="coverflow"
                            :centeredSlides="true"
                            :slidesPerView="slidesPerView"
                            :loop="enableLoop"
                            :coverflowEffect="{
                                rotate: 50,
                                stretch: 0,
                                depth: 100,
                                modifier: 1,
                                slideShadows: true,
                            }"
                        >
                            <SwiperSlide
                                v-for="doc in documentos"
                                :key="doc.id"
                                class="relative"
                            >
                                <div
                                    class="relative flex h-94 w-full items-center justify-center overflow-hidden rounded-xl bg-gray-100 md:h-96 dark:bg-gray-800"
                                >
                                    <img
                                        v-if="isImage(doc.nombre_documento)"
                                        :src="getImageUrl(doc)"
                                        :alt="doc.nombre_documento"
                                        class="h-full w-full bg-gray-100 object-contain"
                                    />
                                    <div
                                        v-else
                                        class="flex flex-col items-center gap-3"
                                    >
                                        <div class="text-8xl select-none">
                                            {{
                                                getFileIcon(
                                                    doc.nombre_documento,
                                                )
                                            }}
                                        </div>
                                        <p
                                            class="px-4 text-center text-sm font-medium break-words"
                                        >
                                            {{ doc.nombre_documento }}
                                        </p>
                                        <button
                                            @click="abrirDocumento(doc)"
                                            class="mt-2 inline-block cursor-pointer rounded-full bg-white/90 px-3 py-1 font-medium text-gray-900 hover:bg-white"
                                        >
                                            Abrir documento
                                        </button>
                                    </div>
                                </div>
                            </SwiperSlide>
                            <div
                                ref="nextBtnRef"
                                class="swiper-button-next text-gray-800 dark:text-gray-100"
                            ></div>
                            <div
                                ref="prevBtnRef"
                                class="swiper-button-prev text-gray-800 dark:text-gray-100"
                            ></div>
                        </swiper>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-6 flex items-center gap-2 text-xl font-semibold text-gray-800 dark:text-gray-100"
                    >
                        <span class="text-2xl">📚</span>
                        Documentos Almacenados
                        <span
                            class="ml-auto text-sm font-normal text-gray-500 dark:text-gray-400"
                        >
                            {{ documentos.length }} archivo(s)
                        </span>
                    </h2>
                    <div
                        v-if="documentos.length === 0"
                        class="py-16 text-center text-gray-400 dark:text-gray-500"
                    >
                        <div class="mb-4 text-6xl">📭</div>
                        <p class="text-lg">No hay documentos subidos todavía</p>
                        <p class="mt-2 text-sm">
                            Sube tu primer documento usando el formulario
                            superior
                        </p>
                    </div>
                    <div
                        v-else
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
                    >
                        <div
                            v-for="doc in documentos"
                            :key="doc.id"
                            class="group relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-md transition-all duration-300 hover:scale-105 hover:shadow-xl dark:border-gray-600 dark:bg-gray-700/50"
                        >
                            <div
                                class="relative flex h-48 items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-700"
                            >
                                <img
                                    v-if="isImage(doc.nombre_documento)"
                                    :src="`/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`"
                                    :alt="doc.nombre_documento"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="text-6xl">
                                    {{ getFileIcon(doc.nombre_documento) }}
                                </div>
                                <div
                                    class="absolute top-2 right-2 flex gap-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                                >
                                    <button
                                        v-if="tieneInformacion(doc)"
                                        @click="abrirInfoModal(doc)"
                                        class="rounded-full bg-blue-500 p-2 text-white shadow-lg hover:bg-blue-600"
                                        title="Ver información extraída"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M10 12a2 2 0 100-4 2 2 0 000 4z"
                                            />
                                            <path
                                                fill-rule="evenodd"
                                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="abrirConfirmacion(doc)"
                                        class="rounded-full bg-red-500 p-2 text-white shadow-lg hover:bg-red-600"
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
                                    <button
                                        @click="descargarDocumento(doc)"
                                        class="flex items-center justify-center rounded-full bg-green-500 p-2 text-white shadow-lg hover:bg-green-600"
                                        title="Descargar documento"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div
                                    v-if="
                                        doc.estado_ocr === 'procesado' &&
                                        tieneInformacion(doc)
                                    "
                                    class="absolute bottom-2 left-2 rounded-full bg-green-500 px-2 py-1 text-xs text-white shadow-lg"
                                >
                                    ✓ Escaneado
                                </div>
                                <div
                                    v-else-if="doc.estado_ocr === 'error'"
                                    class="absolute bottom-2 left-2 rounded-full bg-red-500 px-2 py-1 text-xs text-white shadow-lg"
                                >
                                    ✕ Error OCR
                                </div>
                            </div>
                            <div class="bg-white p-4 dark:bg-gray-800">
                                <p
                                    class="truncate text-sm font-medium text-gray-800 dark:text-gray-200"
                                    :title="doc.nombre_documento"
                                >
                                    {{ doc.nombre_documento }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        new Date(
                                            doc.created_at,
                                        ).toLocaleDateString('es-ES')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                ></div>
                <div
                    @click.stop
                    class="relative z-10 max-h-[90vh] w-full max-w-2xl transform overflow-hidden overflow-y-auto rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
                >
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-center"
                    >
                        <div
                            class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-white"
                        >
                            <svg
                                class="h-8 w-8 text-blue-600"
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
                        <p class="mt-2 text-sm text-white/80">
                            {{ infoModal.documento?.nombre_documento }}
                        </p>
                    </div>
                    <div class="p-6">
                        <div
                            v-if="infoModal.documento?.datos_extraidos"
                            class="space-y-4"
                        >
                            <div
                                v-if="
                                    infoModal.documento.datos_extraidos
                                        .matricula ||
                                    infoModal.documento.datos_extraidos
                                        .codigo_barras
                                "
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20"
                                >
                                    <p
                                        class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Tipo de Documento
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Folio Real
                                    </p>
                                </div>
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .matricula
                                        "
                                        class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                    >
                                        <p
                                            class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            Matrícula
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos.matricula
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .codigo_barras
                                        "
                                        class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                    >
                                        <p
                                            class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            Código de Barras
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .codigo_barras
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="infoModal.documento.datos_extraidos.serie"
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20"
                                >
                                    <p
                                        class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Tipo de Documento
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Testimonio de Propiedad
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                >
                                    <p
                                        class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Serie
                                    </p>
                                    <p
                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            infoModal.documento.datos_extraidos
                                                .serie
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="
                                    infoModal.documento.datos_extraidos.numero
                                "
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20"
                                >
                                    <p
                                        class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Tipo de Documento
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Certificado Catastral
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                >
                                    <p
                                        class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Número
                                    </p>
                                    <p
                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            infoModal.documento.datos_extraidos
                                                .numero
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center text-gray-500">
                            No se pudo extraer información de este documento
                        </div>
                    </div>
                    <div class="p-6 pt-0">
                        <button
                            @click="cerrarInfoModal"
                            class="w-full rounded-xl bg-gray-100 px-6 py-3 font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
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
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                ></div>
                <div
                    @click.stop
                    class="relative z-10 w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
                >
                    <div
                        class="bg-gradient-to-br from-red-500 to-red-600 p-6 text-center"
                    >
                        <div
                            class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-white"
                        >
                            <svg
                                class="h-8 w-8 text-red-600"
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
                        <p
                            class="mb-2 text-center text-gray-600 dark:text-gray-300"
                        >
                            ¿Está seguro de eliminar el documento?
                        </p>
                        <p
                            class="mb-6 text-center font-semibold break-words text-gray-800 dark:text-gray-100"
                        >
                            "{{ confirmModal.documento?.nombre_documento }}"
                        </p>
                        <p
                            class="text-center text-sm text-gray-500 dark:text-gray-400"
                        >
                            Esta acción no se puede deshacer
                        </p>
                    </div>
                    <div class="flex gap-3 p-6 pt-0">
                        <button
                            @click="cancelarEliminacion"
                            class="flex-1 rounded-xl bg-gray-100 px-6 py-3 font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="confirmarEliminacion"
                            class="flex-1 rounded-xl bg-gradient-to-r from-red-500 to-red-600 px-6 py-3 font-semibold text-white shadow-lg transition-all duration-200 hover:from-red-600 hover:to-red-700 hover:shadow-xl"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
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
                class="fixed top-6 right-6 z-[9999] w-auto max-w-md min-w-[320px]"
            >
                <div
                    :class="[
                        'overflow-hidden rounded-xl text-white shadow-2xl',
                        notificacion.tipo === 'success'
                            ? 'bg-gradient-to-r from-green-500 to-green-600'
                            : 'bg-gradient-to-r from-red-500 to-red-600',
                    ]"
                >
                    <div class="flex items-start gap-3 p-4">
                        <div
                            :class="[
                                'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-xl font-bold shadow-lg',
                                notificacion.tipo === 'success'
                                    ? 'bg-green-600'
                                    : 'bg-red-600',
                            ]"
                        >
                            {{ notificacion.tipo === 'success' ? '✓' : '✕' }}
                        </div>
                        <div class="flex-1 pt-0.5">
                            <h3 class="mb-1 text-base font-semibold">
                                {{
                                    notificacion.tipo === 'success'
                                        ? 'Éxito'
                                        : 'Error'
                                }}
                            </h3>
                            <p class="text-sm leading-relaxed text-white/95">
                                {{ notificacion.mensaje }}
                            </p>
                        </div>
                        <button
                            @click="notificacion.visible = false"
                            class="flex-shrink-0 rounded-lg p-1 text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                        >
                            <svg
                                class="h-5 w-5"
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
                            class="notification-progress h-full bg-white/50"
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
