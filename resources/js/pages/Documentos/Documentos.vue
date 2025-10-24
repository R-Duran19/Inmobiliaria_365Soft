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

const tipoDocumento = ref('general');
const nextBtnRef = ref<HTMLDivElement | null>(null);
const prevBtnRef = ref<HTMLDivElement | null>(null);

const props = defineProps({
    terrenoId: Number,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Documentos', href: '/documentos' },
];

const archivos = ref<FileList | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isUploading = ref(false);
const archivosArray = ref<File[]>([]);
const previewUrls = ref<string[]>([]);
const errorValidacion = ref('');

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

// Función para agrupar documentos de cédula
const documentosAgrupados = computed(() => {
    const grupos: any[] = [];
    const cedulasAgrupadas = new Map();

    documentos.value.forEach((doc: any) => {
        // Detectar si es una cédula por múltiples indicadores
        const tieneNumeroCI = doc.datos_extraidos?.numero_cedula;
        const tieneNombres =
            doc.datos_extraidos?.primer_nombre ||
            doc.datos_extraidos?.apellido_paterno;
        const tieneFechaNacimiento = doc.datos_extraidos?.fecha_nacimiento;
        const tieneFechaEmision = doc.datos_extraidos?.fecha_emision;
        const tieneEstadoCivil = doc.datos_extraidos?.estado_civil;
        const tieneLugarNacimiento = doc.datos_extraidos?.lugar_nacimiento;

        // Es cédula si tiene al menos 2 de estos campos característicos
        const indicadoresCedula = [
            tieneNumeroCI,
            tieneNombres,
            tieneFechaNacimiento,
            tieneEstadoCivil,
            tieneLugarNacimiento,
            tieneFechaEmision,
        ].filter(Boolean).length;

        const esCedula = indicadoresCedula >= 2;

        if (esCedula) {
            // Determinar la clave de agrupación
            let key: string;

            if (tieneNumeroCI) {
                // Si tiene CI, agrupar por CI
                key = doc.datos_extraidos.numero_cedula;
            } else if (tieneNombres) {
                // Si no tiene CI pero tiene nombres, crear clave con nombres
                const nombreKey =
                    `${doc.datos_extraidos.primer_nombre || ''}_${doc.datos_extraidos.apellido_paterno || ''}`
                        .toLowerCase()
                        .trim();
                key = `cedula_${nombreKey}`;
            } else {
                // Fallback
                key = `cedula_${doc.id}`;
            }

            if (!cedulasAgrupadas.has(key)) {
                // Construir nombre_completo si existe
                const nombreCompleto = [
                    doc.datos_extraidos.primer_nombre,
                    doc.datos_extraidos.segundo_nombre,
                    doc.datos_extraidos.apellido_paterno,
                    doc.datos_extraidos.apellido_materno,
                ]
                    .filter(Boolean)
                    .join(' ');

                cedulasAgrupadas.set(key, {
                    id: doc.id,
                    tipo: 'cedula_agrupada',
                    imagenes: [],
                    datos_extraidos: {
                        ...doc.datos_extraidos,
                        nombre_completo: nombreCompleto || null,
                    },
                    created_at: doc.created_at,
                    estado_ocr: doc.estado_ocr,
                });
            } else {
                // Combinar datos_extraidos de ambas imágenes
                const grupoExistente = cedulasAgrupadas.get(key);

                // Construir nombre_completo si no existe
                if (!grupoExistente.datos_extraidos.nombre_completo) {
                    const nombreCompleto = [
                        doc.datos_extraidos.primer_nombre ||
                            grupoExistente.datos_extraidos.primer_nombre,
                        doc.datos_extraidos.segundo_nombre ||
                            grupoExistente.datos_extraidos.segundo_nombre,
                        doc.datos_extraidos.apellido_paterno ||
                            grupoExistente.datos_extraidos.apellido_paterno,
                        doc.datos_extraidos.apellido_materno ||
                            grupoExistente.datos_extraidos.apellido_materno,
                    ]
                        .filter(Boolean)
                        .join(' ');

                    grupoExistente.datos_extraidos.nombre_completo =
                        nombreCompleto || null;
                }

                // Combinar todos los datos
                grupoExistente.datos_extraidos = {
                    ...grupoExistente.datos_extraidos,
                    ...doc.datos_extraidos,
                    nombre_completo:
                        grupoExistente.datos_extraidos.nombre_completo,
                };
            }

            cedulasAgrupadas.get(key).imagenes.push({
                id: doc.id,
                nombre: doc.nombre_documento,
                url: `/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`,
            });
        } else {
            // Documentos normales
            grupos.push(doc);
        }
    });

    // Agregar las cédulas agrupadas
    cedulasAgrupadas.forEach((cedula) => {
        grupos.push(cedula);
    });

    return grupos;
});

// Función para verificar si es cédula agrupada
function esCedulaAgrupada(doc: any) {
    return doc?.tipo === 'cedula_agrupada';
}

// Función para obtener el título del documento
function getTituloDocumento(doc: any) {
    if (esCedulaAgrupada(doc)) {
        const nombre =
            doc.datos_extraidos?.nombre_completo || 'Cédula de Identidad';
        return `🪪 ${nombre}`;
    }
    return doc.nombre_documento;
}

// Función para eliminar cédula agrupada (eliminar todas las imágenes)
function eliminarCedulaAgrupada(cedula: any) {
    confirmModal.documento = cedula;
    confirmModal.visible = true;
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
const puedeSubir = computed(() => {
    if (archivosArray.value.length === 0) return false;

    if (tipoDocumento.value === 'cedula_identidad') {
        return archivosArray.value.length === 2;
    }

    return true;
});
onMounted(() => {
    cargarDocumentos();
    if (swiperRef.value && nextBtnRef.value && prevBtnRef.value) {
        swiperRef.value.params.navigation.nextEl = nextBtnRef.value;
        swiperRef.value.params.navigation.prevEl = prevBtnRef.value;
        swiperRef.value.navigation.init();
        swiperRef.value.navigation.update();
    }
});
function handleFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const files = input.files;

    if (!files) return;

    errorValidacion.value = '';

    // Para cédula de identidad, manejar especialmente
    if (tipoDocumento.value === 'cedula_identidad') {
        // Si ya tiene 2 imágenes, no permitir más
        if (archivosArray.value.length >= 2) {
            errorValidacion.value =
                'Solo puede subir 2 imágenes para Cédula de Identidad';
            input.value = '';
            return;
        }

        // Agregar las nuevas imágenes (máximo hasta completar 2)
        const nuevasImagenes = Array.from(files).slice(
            0,
            2 - archivosArray.value.length,
        );
        archivosArray.value.push(...nuevasImagenes);

        // Crear previews
        nuevasImagenes.forEach((file) => {
            const url = URL.createObjectURL(file);
            previewUrls.value.push(url);
        });

        // Validar que sean exactamente 2
        if (archivosArray.value.length < 2) {
            errorValidacion.value = `Seleccione ${2 - archivosArray.value.length} imagen(es) más`;
        }
    } else {
        // Para otros documentos, comportamiento normal
        archivosArray.value = Array.from(files);
    }

    // Limpiar el input para permitir seleccionar de nuevo
    input.value = '';
}

function eliminarImagen(index: number) {
    archivosArray.value.splice(index, 1);

    // Liberar URL del preview
    URL.revokeObjectURL(previewUrls.value[index]);
    previewUrls.value.splice(index, 1);

    errorValidacion.value = '';

    if (
        tipoDocumento.value === 'cedula_identidad' &&
        archivosArray.value.length < 2
    ) {
        errorValidacion.value = `Seleccione ${2 - archivosArray.value.length} imagen(es) más`;
    }
}

function limpiarArchivos() {
    archivosArray.value = [];
    previewUrls.value.forEach((url) => URL.revokeObjectURL(url));
    previewUrls.value = [];
    errorValidacion.value = '';

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}
async function subirArchivos() {
    // Validación especial para cédula
    if (tipoDocumento.value === 'cedula_identidad') {
        if (archivosArray.value.length !== 2) {
            errorValidacion.value =
                'Debe seleccionar exactamente 2 imágenes para Cédula de Identidad';
            mostrarNotificacion(
                'error',
                'Debe seleccionar exactamente 2 imágenes para Cédula de Identidad',
            );
            return;
        }
    } else {
        // Para otros documentos, usar archivos normal
        if (!archivos.value || archivos.value.length === 0) {
            if (archivosArray.value.length === 0) {
                mostrarNotificacion(
                    'error',
                    'Debe seleccionar al menos un archivo.',
                );
                return;
            }
        }
    }

    const formData = new FormData();

    // Si es cédula, usar archivosArray, sino usar archivos
    if (tipoDocumento.value === 'cedula_identidad') {
        archivosArray.value.forEach((file, index) => {
            formData.append('archivos[]', file);
        });
    } else {
        if (archivos.value) {
            for (let i = 0; i < archivos.value.length; i++) {
                formData.append('archivos[]', archivos.value[i]);
            }
        } else if (archivosArray.value.length > 0) {
            archivosArray.value.forEach((file) => {
                formData.append('archivos[]', file);
            });
        }
    }

    formData.append('terreno_id', props.terrenoId?.toString() || '');
    formData.append('tipo_documento', tipoDocumento.value);

    isUploading.value = true;

    try {
        await axios.post('/documentos/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        mostrarNotificacion('success', 'Archivo(s) subido(s) correctamente.');

        // Limpiar todo
        archivos.value = null;
        limpiarArchivos();

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
        if (esCedulaAgrupada(confirmModal.documento)) {
            // Eliminar todas las imágenes de la cédula
            for (const imagen of confirmModal.documento.imagenes) {
                await axios.delete(`/documentos/${imagen.id}`);
            }
            mostrarNotificacion('success', 'Cédula eliminada correctamente.');
        } else {
            // Eliminar documento normal
            await axios.delete(`/documentos/${confirmModal.documento.id}`);
            mostrarNotificacion(
                'success',
                'Documento eliminado correctamente.',
            );
        }

        await cargarDocumentos();
    } catch (error) {
        console.error('Error al eliminar:', error);
        mostrarNotificacion('error', 'Error al eliminar.');
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
    if (esCedulaAgrupada(doc)) {
        return doc.imagenes[0].url;
    }
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

function abrirImagen(url: string) {
    window.open(url, '_blank');
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
                        <!-- Selector de tipo de documento -->
                        <div>
                            <label
                                for="tipoDocumento"
                                class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >
                                📋 Tipo de Documento
                            </label>
                            <select
                                id="tipoDocumento"
                                v-model="tipoDocumento"
                                @change="limpiarArchivos"
                                class="block w-full rounded-xl border-2 border-gray-300 bg-white px-4 py-3 text-gray-800 transition-colors hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-blue-500"
                            >
                                <option value="general">📄 General</option>
                                <option value="folio_real">
                                    🏠 Folio Real
                                </option>
                                <option value="testimonio">
                                    📜 Testimonio de Propiedad
                                </option>
                                <option value="certificado_catastral">
                                    📊 Certificado Catastral
                                </option>
                                <option value="cedula_identidad">
                                    🪪 Cédula de Identidad
                                </option>
                            </select>
                        </div>

                        <!-- Instrucciones especiales para Cédula de Identidad -->
                        <div
                            v-if="tipoDocumento === 'cedula_identidad'"
                            class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20"
                        >
                            <p
                                class="mb-2 text-sm font-semibold text-blue-800 dark:text-blue-300"
                            >
                                📸 Para Cédula de Identidad debe subir 2
                                imágenes:
                            </p>
                            <ul
                                class="ml-4 space-y-1 text-sm text-blue-700 dark:text-blue-400"
                            >
                                <li>✓ Imagen 1: Frontal (con foto y número)</li>
                                <li>
                                    ✓ Imagen 2: Reverso (con datos personales)
                                </li>
                            </ul>
                            <p
                                class="mt-2 text-xs text-blue-600 dark:text-blue-500"
                            >
                                💡 Asegúrese de que las fotos sean claras y con
                                buena iluminación
                            </p>
                        </div>

                        <!-- Input de archivos -->
                        <div class="relative">
                            <input
                                ref="fileInputRef"
                                type="file"
                                :multiple="tipoDocumento !== 'cedula_identidad'"
                                accept="image/*"
                                @change="handleFileChange"
                                class="block w-full cursor-pointer rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 transition-colors file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-6 file:py-3 file:text-sm file:font-semibold file:text-blue-700 hover:border-blue-400 hover:file:bg-blue-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400 dark:file:bg-blue-900/30 dark:file:text-blue-400 dark:hover:border-blue-500 dark:hover:file:bg-blue-900/50"
                            />

                            <!-- Contador de archivos seleccionados -->
                            <div v-if="archivosArray.length > 0" class="mt-2">
                                <p
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ archivosArray.length }} archivo(s)
                                    seleccionado(s)
                                    <span
                                        v-if="
                                            tipoDocumento === 'cedula_identidad'
                                        "
                                        class="ml-2"
                                    >
                                        <span
                                            v-if="archivosArray.length === 2"
                                            class="text-green-600"
                                            >✓</span
                                        >
                                        <span v-else class="text-red-600"
                                            >(Se requieren 2 imágenes)</span
                                        >
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Preview de imágenes (solo para Cédula de Identidad) -->
                        <div
                            v-if="
                                tipoDocumento === 'cedula_identidad' &&
                                previewUrls.length > 0
                            "
                            class="grid grid-cols-2 gap-4"
                        >
                            <div
                                v-for="(url, index) in previewUrls"
                                :key="index"
                                class="relative overflow-hidden rounded-lg border-2 border-gray-300 dark:border-gray-600"
                            >
                                <img
                                    :src="url"
                                    :alt="`Imagen ${index + 1}`"
                                    class="h-40 w-full object-cover"
                                />
                                <div
                                    class="absolute top-2 left-2 rounded bg-blue-600 px-2 py-1 text-xs font-semibold text-white"
                                >
                                    {{ index === 0 ? 'Frontal' : 'Reverso' }}
                                </div>
                                <button
                                    @click="eliminarImagen(index)"
                                    class="absolute top-2 right-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 p-1 text-xs font-bold text-white hover:bg-red-600"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Validación de errores -->
                        <div
                            v-if="errorValidacion"
                            class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20"
                        >
                            <p
                                class="text-sm font-medium text-red-800 dark:text-red-300"
                            >
                                ⚠️ {{ errorValidacion }}
                            </p>
                        </div>

                        <!-- Botón de subir -->
                        <button
                            @click="subirArchivos"
                            :disabled="isUploading || !puedeSubir"
                            class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-green-500 to-green-600 px-6 py-3 font-semibold text-white shadow-md transition-all duration-200 hover:from-green-600 hover:to-green-700 hover:shadow-lg disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-500 disabled:opacity-50"
                        >
                            <span v-if="!isUploading">✓ Subir Archivos</span>
                            <span v-else>⏳ Subiendo...</span>
                        </button>
                    </div>
                </div>

                <div
                    v-if="documentosAgrupados.length"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                >
                    <h2
                        class="mb-4 flex items-center gap-2 text-lg font-semibold text-gray-800 dark:text-gray-100"
                    >
                        <span class="text-2xl">🎞️</span>
                        Vista en Carrusel
                        <span
                            class="ml-auto text-sm text-gray-500 dark:text-gray-400"
                            >{{ documentosAgrupados.length }} items</span
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
                            :slidesPerView="
                                Math.min(3, documentosAgrupados.length || 1)
                            "
                            :loop="documentosAgrupados.length > 3"
                            :coverflowEffect="{
                                rotate: 50,
                                stretch: 0,
                                depth: 100,
                                modifier: 1,
                                slideShadows: true,
                            }"
                        >
                            <SwiperSlide
                                v-for="doc in documentosAgrupados"
                                :key="doc.id"
                                class="relative"
                            >
                                <div
                                    class="relative flex h-94 w-full items-center justify-center overflow-hidden rounded-xl bg-gray-100 md:h-96 dark:bg-gray-800"
                                >
                                    <!-- Cédula agrupada -->
                                    <template v-if="esCedulaAgrupada(doc)">
                                        <img
                                            :src="doc.imagenes[0].url"
                                            :alt="getTituloDocumento(doc)"
                                            class="h-full w-full bg-gray-100 object-contain"
                                        />
                                        <div
                                            class="absolute top-2 left-2 rounded-full bg-blue-500 px-3 py-1 text-xs text-white shadow-lg"
                                        >
                                            {{ doc.imagenes.length }} imagen(es)
                                        </div>
                                    </template>
                                    <!-- Documentos normales -->
                                    <template v-else>
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
                                    </template>
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
                            {{ documentosAgrupados.length }} archivo(s)
                        </span>
                    </h2>
                    <div
                        v-if="documentosAgrupados.length === 0"
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
                            v-for="doc in documentosAgrupados"
                            :key="doc.id"
                            class="group relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50 shadow-md transition-all duration-300 hover:scale-105 hover:shadow-xl dark:border-gray-600 dark:bg-gray-700/50"
                        >
                            <div
                                class="relative flex h-48 items-center justify-center overflow-hidden bg-gray-100 dark:bg-gray-700"
                            >
                                <!-- Cédula agrupada -->
                                <template v-if="esCedulaAgrupada(doc)">
                                    <img
                                        :src="doc.imagenes[0].url"
                                        :alt="getTituloDocumento(doc)"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        class="absolute top-2 left-2 rounded-full bg-blue-500 px-3 py-1 text-xs font-semibold text-white shadow-lg"
                                    >
                                        {{ doc.imagenes.length }} imgs
                                    </div>
                                </template>
                                <!-- Documento normal -->
                                <template v-else>
                                    <img
                                        v-if="isImage(doc.nombre_documento)"
                                        :src="`/documentos_clientes/${props.terrenoId}/${doc.nombre_documento}`"
                                        :alt="doc.nombre_documento"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="text-6xl">
                                        {{ getFileIcon(doc.nombre_documento) }}
                                    </div>
                                </template>

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
                                        @click="
                                            esCedulaAgrupada(doc)
                                                ? eliminarCedulaAgrupada(doc)
                                                : abrirConfirmacion(doc)
                                        "
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
                                        v-if="!esCedulaAgrupada(doc)"
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
                                    :title="getTituloDocumento(doc)"
                                >
                                    {{ getTituloDocumento(doc) }}
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

        <!-- Modal de Información -->
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
                            {{
                                esCedulaAgrupada(infoModal.documento)
                                    ? getTituloDocumento(infoModal.documento)
                                    : infoModal.documento?.nombre_documento
                            }}
                        </p>
                    </div>

                    <!-- Galería de imágenes para cédulas agrupadas -->
                    <div
                        v-if="esCedulaAgrupada(infoModal.documento)"
                        class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <p
                            class="mb-3 text-center text-sm font-semibold text-gray-600 dark:text-gray-400"
                        >
                            📸 Imágenes de la cédula
                        </p>
                        <div class="flex justify-center gap-3">
                            <div
                                v-for="(imagen, index) in infoModal.documento
                                    .imagenes"
                                :key="imagen.id"
                                class="relative w-32 overflow-hidden rounded-lg border-2 border-gray-300 shadow-md dark:border-gray-600"
                            >
                                <img
                                    :src="imagen.url"
                                    :alt="`Imagen ${index + 1}`"
                                    class="h-full w-full cursor-pointer object-cover transition-opacity hover:opacity-80"
                                    @click="abrirImagen(imagen.url)"
                                />
                                <div
                                    class="absolute bottom-1 left-1 rounded bg-black/60 px-2 py-0.5 text-xs text-white"
                                >
                                    {{ index === 0 ? 'Frente' : 'Reverso' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div
                            v-if="infoModal.documento?.datos_extraidos"
                            class="space-y-4"
                        >
                            <!-- Folio Real -->
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

                            <!-- Testimonio -->
                            <div
                                v-if="
                                    infoModal.documento.datos_extraidos.serie &&
                                    !infoModal.documento.datos_extraidos
                                        .numero_cedula
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

                            <!-- Certificado Catastral -->
                            <div
                                v-if="
                                    infoModal.documento.datos_extraidos
                                        .numero &&
                                    !infoModal.documento.datos_extraidos
                                        .numero_cedula
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

                            <!-- Cédula de Identidad -->
                            <div
                                v-if="
                                    infoModal.documento.datos_extraidos
                                        .numero_cedula ||
                                    infoModal.documento.datos_extraidos
                                        .nombre_completo ||
                                    infoModal.documento.datos_extraidos
                                        .mrz_detected
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
                                        🪪 Cédula de Identidad
                                    </p>
                                </div>

                                <!-- Información principal -->
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .numero_cedula
                                        "
                                        class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                    >
                                        <p
                                            class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            Número de Cédula
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .numero_cedula
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .serie
                                        "
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
                                                infoModal.documento
                                                    .datos_extraidos.serie
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .seccion
                                        "
                                        class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                    >
                                        <p
                                            class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            Sección
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos.seccion
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .codigo_biometrico
                                        "
                                        class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                    >
                                        <p
                                            class="mb-1 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                            Código Biométrico
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .codigo_biometrico
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Nombre completo destacado -->
                                <div
                                    v-if="
                                        infoModal.documento.datos_extraidos
                                            .nombre_completo
                                    "
                                    class="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-6 dark:from-blue-900/20 dark:to-indigo-900/20"
                                >
                                    <p
                                        class="mb-2 text-sm font-semibold text-indigo-700 dark:text-indigo-400"
                                    >
                                        👤 Nombre Completo
                                    </p>
                                    <p
                                        class="text-2xl font-bold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            infoModal.documento.datos_extraidos
                                                .nombre_completo
                                        }}
                                    </p>
                                    <div
                                        class="mt-3 grid grid-cols-2 gap-3 text-sm md:grid-cols-4"
                                    >
                                        <div
                                            v-if="
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .primer_nombre
                                            "
                                        >
                                            <p
                                                class="text-gray-600 dark:text-gray-400"
                                            >
                                                Primer Nombre
                                            </p>
                                            <p
                                                class="font-medium text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .primer_nombre
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .segundo_nombre
                                            "
                                        >
                                            <p
                                                class="text-gray-600 dark:text-gray-400"
                                            >
                                                Segundo Nombre
                                            </p>
                                            <p
                                                class="font-medium text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .segundo_nombre
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .apellido_paterno
                                            "
                                        >
                                            <p
                                                class="text-gray-600 dark:text-gray-400"
                                            >
                                                Apellido Paterno
                                            </p>
                                            <p
                                                class="font-medium text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .apellido_paterno
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .apellido_materno
                                            "
                                        >
                                            <p
                                                class="text-gray-600 dark:text-gray-400"
                                            >
                                                Apellido Materno
                                            </p>
                                            <p
                                                class="font-medium text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .apellido_materno
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fechas -->
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-3"
                                >
                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .fecha_nacimiento
                                        "
                                        class="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20"
                                    >
                                        <p
                                            class="mb-1 text-sm text-blue-700 dark:text-blue-400"
                                        >
                                            🎂 Fecha de Nacimiento
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .fecha_nacimiento
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .fecha_emision
                                        "
                                        class="rounded-xl bg-green-50 p-4 dark:bg-green-900/20"
                                    >
                                        <p
                                            class="mb-1 text-sm text-green-700 dark:text-green-400"
                                        >
                                            📅 Fecha de Emisión
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .fecha_emision
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="
                                            infoModal.documento.datos_extraidos
                                                .fecha_expiracion
                                        "
                                        class="rounded-xl bg-orange-50 p-4 dark:bg-orange-900/20"
                                    >
                                        <p
                                            class="mb-1 text-sm text-orange-700 dark:text-orange-400"
                                        >
                                            ⏰ Fecha de Expiración
                                        </p>
                                        <p
                                            class="font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            {{
                                                infoModal.documento
                                                    .datos_extraidos
                                                    .fecha_expiracion
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Género -->
                                <div
                                    v-if="
                                        infoModal.documento.datos_extraidos
                                            .genero
                                    "
                                    class="rounded-xl bg-purple-50 p-4 dark:bg-purple-900/20"
                                >
                                    <p
                                        class="mb-1 text-sm text-purple-700 dark:text-purple-400"
                                    >
                                        ⚧ Género
                                    </p>
                                    <p
                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        {{
                                            infoModal.documento.datos_extraidos
                                                .genero
                                        }}
                                    </p>
                                </div>

                                <!-- Información personal (del reverso) -->
                                <div
                                    v-if="
                                        infoModal.documento.datos_extraidos
                                            .lugar_nacimiento ||
                                        infoModal.documento.datos_extraidos
                                            .domicilio ||
                                        infoModal.documento.datos_extraidos
                                            .ocupacion ||
                                        infoModal.documento.datos_extraidos
                                            .estado_civil
                                    "
                                    class="space-y-4"
                                >
                                    <div
                                        class="rounded-xl bg-purple-50 p-4 dark:bg-purple-900/20"
                                    >
                                        <p
                                            class="mb-2 text-sm font-semibold text-purple-700 dark:text-purple-400"
                                        >
                                            📋 Información Adicional
                                        </p>

                                        <div class="space-y-3">
                                            <div
                                                v-if="
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .lugar_nacimiento
                                                "
                                                class="rounded-lg bg-white p-3 dark:bg-gray-800"
                                            >
                                                <p
                                                    class="text-xs text-gray-600 dark:text-gray-400"
                                                >
                                                    Lugar de Nacimiento
                                                </p>
                                                <p
                                                    class="font-medium text-gray-900 dark:text-gray-100"
                                                >
                                                    {{
                                                        infoModal.documento
                                                            .datos_extraidos
                                                            .lugar_nacimiento
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                v-if="
                                                    infoModal.documento
                                                        .datos_extraidos
                                                        .domicilio
                                                "
                                                class="rounded-lg bg-white p-3 dark:bg-gray-800"
                                            >
                                                <p
                                                    class="text-xs text-gray-600 dark:text-gray-400"
                                                >
                                                    Domicilio
                                                </p>
                                                <p
                                                    class="font-medium text-gray-900 dark:text-gray-100"
                                                >
                                                    {{
                                                        infoModal.documento
                                                            .datos_extraidos
                                                            .domicilio
                                                    }}
                                                </p>
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div
                                                    v-if="
                                                        infoModal.documento
                                                            .datos_extraidos
                                                            .ocupacion
                                                    "
                                                    class="rounded-lg bg-white p-3 dark:bg-gray-800"
                                                >
                                                    <p
                                                        class="text-xs text-gray-600 dark:text-gray-400"
                                                    >
                                                        Ocupación
                                                    </p>
                                                    <p
                                                        class="font-medium text-gray-900 dark:text-gray-100"
                                                    >
                                                        {{
                                                            infoModal.documento
                                                                .datos_extraidos
                                                                .ocupacion
                                                        }}
                                                    </p>
                                                </div>

                                                <div
                                                    v-if="
                                                        infoModal.documento
                                                            .datos_extraidos
                                                            .estado_civil
                                                    "
                                                    class="rounded-lg bg-white p-3 dark:bg-gray-800"
                                                >
                                                    <p
                                                        class="text-xs text-gray-600 dark:text-gray-400"
                                                    >
                                                        Estado Civil
                                                    </p>
                                                    <p
                                                        class="font-medium text-gray-900 dark:text-gray-100"
                                                    >
                                                        {{
                                                            infoModal.documento
                                                                .datos_extraidos
                                                                .estado_civil
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indicador MRZ -->
                                <div
                                    v-if="
                                        infoModal.documento.datos_extraidos
                                            .mrz_detected
                                    "
                                    class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700"
                                >
                                    <p
                                        class="mb-2 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        ✓ Código MRZ detectado y procesado
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

        <!-- Modal de Confirmación -->
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
                            ¿Está seguro de eliminar
                            {{
                                esCedulaAgrupada(confirmModal.documento)
                                    ? 'esta cédula'
                                    : 'el documento'
                            }}?
                        </p>

                        <template
                            v-if="esCedulaAgrupada(confirmModal.documento)"
                        >
                            <div
                                class="mb-4 rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20"
                            >
                                <p
                                    class="text-center text-sm font-semibold text-yellow-700 dark:text-yellow-400"
                                >
                                    🪪
                                    {{
                                        confirmModal.documento?.datos_extraidos
                                            ?.nombre_completo ||
                                        'Cédula de Identidad'
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-center text-xs text-yellow-600 dark:text-yellow-500"
                                >
                                    Se eliminarán
                                    {{
                                        confirmModal.documento?.imagenes
                                            ?.length || 0
                                    }}
                                    imagen(es)
                                </p>
                            </div>
                        </template>
                        <template v-else>
                            <p
                                class="mb-6 text-center font-semibold break-words text-gray-800 dark:text-gray-100"
                            >
                                "{{ confirmModal.documento?.nombre_documento }}"
                            </p>
                        </template>

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

        <!-- Notificaciones -->
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
