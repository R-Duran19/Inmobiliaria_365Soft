<script setup lang="ts">
import Loading from '@/components/ui/Loading/Loading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface Proyecto {
    id: number;
    nombre: string;
    descripcion: string;
    fecha_lanzamiento: string;
    numero_lotes: number;
    ubicacion: string;
    fotografia: string | null;
    total_terrenos: number;
    terrenos_disponibles: number;
    terrenos_vendidos: number;
    terrenos_reservados: number;
}

const proyectos = ref<Proyecto[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Cargar proyectos desde la API
const cargarProyectos = async () => {
    try {
        loading.value = true;
        error.value = null;

        const response = await axios.get('/api/proyectos-list');
        proyectos.value = response.data;
    } catch (err: any) {
        console.error('Error al cargar proyectos:', err);
        error.value = 'Error al cargar los proyectos';
    } finally {
        loading.value = false;
    }
};

// Ir al mapa del proyecto
const verMapa = (proyectoId: number) => {
    router.visit(`/mapa/${proyectoId}`);
};

// Formatear fecha
const formatearFecha = (fecha: string) => {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

// Obtener severidad del tag según disponibilidad
const getSeveridadDisponibilidad = (proyecto: Proyecto) => {
    const porcentaje =
        (proyecto.terrenos_disponibles / proyecto.total_terrenos) * 100;
    if (porcentaje > 50) return 'success';
    if (porcentaje > 20) return 'warning';
    return 'danger';
};

onMounted(() => {
    cargarProyectos();
});
</script>

<template>
    <Head title="Proyectos" />

    <AppLayout>
        <div class="p-6 transition-colors duration-300">
            <div class="relative mb-10">
                <div
                    class="absolute inset-0 -z-10 -rotate-1 transform rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10"
                ></div>

                <div
                    class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-xl"
                >
                    <div
                        class="absolute top-0 right-0 -mt-32 -mr-32 h-64 w-64 rounded-full bg-white/10"
                    ></div>
                    <div
                        class="absolute bottom-0 left-0 -mb-24 -ml-24 h-48 w-48 rounded-full bg-white/5"
                    ></div>

                    <div class="relative z-10">
                        <div class="mb-3 flex items-center gap-3">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg backdrop-blur-sm"
                            >
                                <i
                                    class="pi pi-building text-3xl text-white"
                                ></i>
                            </div>
                            <h1 class="text-4xl font-black text-white">
                                Proyectos Inmobiliarios
                            </h1>
                        </div>
                        <p class="ml-16 text-lg text-purple-100">
                            Selecciona un proyecto para ver el mapa de terrenos
                            disponibles
                        </p>
                    </div>
                </div>
            </div>

            <Loading v-if="loading"></Loading>

            <div
                v-else-if="error"
                class="relative mb-6 overflow-hidden rounded-2xl border-2 border-red-200 bg-gradient-to-r from-red-50 to-orange-50 p-6 shadow-lg dark:border-red-800 dark:from-red-900/20 dark:to-orange-900/20"
            >
                <div
                    class="absolute top-0 right-0 -mt-16 -mr-16 h-32 w-32 rounded-full bg-red-200 opacity-20 dark:bg-red-800"
                ></div>
                <div class="relative flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-red-500 shadow-lg"
                    >
                        <i
                            class="pi pi-exclamation-triangle text-2xl text-white"
                        ></i>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-lg font-bold text-red-800 dark:text-red-200"
                        >
                            Error al cargar
                        </p>
                        <p class="text-red-700 dark:text-red-300">
                            {{ error }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else>
                <div
                    v-if="proyectos && proyectos.length > 0"
                    class="grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="proyecto in proyectos"
                        :key="proyecto.id"
                        class="group relative transform overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div
                            class="relative h-56 overflow-hidden bg-gray-100 dark:bg-gray-700"
                        >
                            <img
                                v-if="proyecto.fotografia"
                                :src="proyecto.fotografia"
                                :alt="proyecto.nombre"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                            />
                            <div
                                v-else
                                class="relative flex h-full w-full items-center justify-center overflow-hidden bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600"
                            >
                                <div class="absolute inset-0 opacity-20">
                                    <div
                                        class="absolute top-0 left-0 h-full w-full"
                                        style="
                                            background-image: repeating-linear-gradient(
                                                45deg,
                                                transparent,
                                                transparent 10px,
                                                rgba(255, 255, 255, 0.1) 10px,
                                                rgba(255, 255, 255, 0.1) 20px
                                            );
                                        "
                                    ></div>
                                </div>
                                <i
                                    class="pi pi-building relative z-10 text-7xl text-white/80"
                                ></i>
                            </div>

                            <div class="absolute top-4 right-4">
                                <div
                                    :class="[
                                        'rounded-xl px-4 py-2 text-sm font-bold shadow-lg backdrop-blur-sm',
                                        getSeveridadDisponibilidad(proyecto) ===
                                        'success'
                                            ? 'bg-green-500/90 text-white'
                                            : getSeveridadDisponibilidad(
                                                    proyecto,
                                                ) === 'warning'
                                              ? 'bg-orange-500/90 text-white'
                                              : 'bg-red-500/90 text-white',
                                    ]"
                                >
                                    <i class="pi pi-home mr-1"></i>
                                    {{
                                        proyecto.terrenos_disponibles
                                    }}
                                    disponibles
                                </div>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            ></div>
                        </div>

                        <div class="p-6">
                            <h3
                                class="mb-3 text-2xl font-black text-gray-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                            >
                                {{ proyecto.nombre }}
                            </h3>

                            <p
                                class="mb-5 line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400"
                            >
                                {{
                                    proyecto.descripcion ||
                                    'Sin descripción disponible'
                                }}
                            </p>

                            <div class="mb-5 space-y-3">
                                <div
                                    class="group/item flex items-center gap-3 text-sm text-gray-700 transition-colors hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 shadow-md transition-transform group-hover/item:scale-110"
                                    >
                                        <i
                                            class="pi pi-map-marker text-xs text-white"
                                        ></i>
                                    </div>
                                    <span class="font-medium">
                                        {{
                                            proyecto.ubicacion
                                                ? proyecto.ubicacion
                                                : 'N/A'
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="group/item flex items-center gap-3 text-sm text-gray-700 transition-colors hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-400 to-indigo-600 shadow-md transition-transform group-hover/item:scale-110"
                                    >
                                        <i
                                            class="pi pi-calendar text-xs text-white"
                                        ></i>
                                    </div>
                                    <span class="font-medium">{{
                                        formatearFecha(
                                            proyecto.fecha_lanzamiento,
                                        )
                                    }}</span>
                                </div>

                                <div
                                    class="group/item flex items-center gap-3 text-sm text-gray-700 transition-colors hover:text-purple-600 dark:text-gray-300 dark:hover:text-purple-400"
                                >
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 shadow-md transition-transform group-hover/item:scale-110"
                                    >
                                        <i
                                            class="pi pi-th-large text-xs text-white"
                                        ></i>
                                    </div>
                                    <span class="font-medium"
                                        >{{ proyecto.numero_lotes ?  proyecto.numero_lotes : '0'}} lotes
                                        totales</span
                                    >
                                </div>
                            </div>

                            <div class="mb-5 grid grid-cols-3 gap-3">
                                <div
                                    class="relative overflow-hidden rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-3 text-center transition-all hover:shadow-md dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
                                >
                                    <div
                                        class="mb-1 text-2xl font-black text-green-600 dark:text-green-400"
                                    >
                                        {{ proyecto.terrenos_disponibles }}
                                    </div>
                                    <div
                                        class="text-xs font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                                    >
                                        Disponibles
                                    </div>
                                </div>

                                <div
                                    class="relative overflow-hidden rounded-xl border-2 border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50 p-3 text-center transition-all hover:shadow-md dark:border-orange-800 dark:from-orange-900/20 dark:to-amber-900/20"
                                >
                                    <div
                                        class="mb-1 text-2xl font-black text-orange-600 dark:text-orange-400"
                                    >
                                        {{ proyecto.terrenos_reservados }}
                                    </div>
                                    <div
                                        class="text-xs font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                                    >
                                        Reservados
                                    </div>
                                </div>

                                <div
                                    class="relative overflow-hidden rounded-xl border-2 border-red-200 bg-gradient-to-br from-red-50 to-rose-50 p-3 text-center transition-all hover:shadow-md dark:border-red-800 dark:from-red-900/20 dark:to-rose-900/20"
                                >
                                    <div
                                        class="mb-1 text-2xl font-black text-red-600 dark:text-red-400"
                                    >
                                        {{ proyecto.terrenos_vendidos }}
                                    </div>
                                    <div
                                        class="text-xs font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                                    >
                                        Vendidos
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="verMapa(proyecto.id)"
                                class="group/btn flex w-full transform items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-4 font-bold text-white shadow-lg transition-all duration-300 hover:scale-105 hover:from-indigo-700 hover:via-purple-700 hover:to-pink-700 hover:shadow-xl"
                            >
                                <i
                                    class="pi pi-map text-lg group-hover/btn:animate-pulse"
                                ></i>
                                <span>Ver Mapa Interactivo</span>
                            </button>
                        </div>

                        <div
                            class="absolute top-0 right-0 h-20 w-20 translate-x-10 -translate-y-10 transform rounded-bl-full bg-gradient-to-br from-purple-400/20 to-pink-600/20 transition-transform duration-300 group-hover:translate-x-8 group-hover:-translate-y-8"
                        ></div>
                    </div>
                </div>

                <div v-else class="py-20 text-center">
                    <div class="relative mb-6 inline-block">
                        <div
                            class="flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-200 shadow-lg dark:from-gray-800 dark:to-gray-700"
                        >
                            <i
                                class="pi pi-inbox text-6xl text-gray-400 dark:text-gray-500"
                            ></i>
                        </div>
                        <div
                            class="absolute -right-2 -bottom-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-500 shadow-lg"
                        >
                            <i class="pi pi-plus text-xl text-white"></i>
                        </div>
                    </div>
                    <h3
                        class="mb-2 text-2xl font-bold text-gray-700 dark:text-gray-300"
                    >
                        No hay proyectos disponibles
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        Comienza agregando tu primer proyecto inmobiliario
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.group-hover\/btn:hover .pi-map {
    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
