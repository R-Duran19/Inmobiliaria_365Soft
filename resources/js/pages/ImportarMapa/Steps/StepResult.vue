<script setup lang="ts">
import { computed } from 'vue';

interface Result {
    summary?: {
        barrios: { creados: number; actualizados: number; omitidos: number };
        cuadras: { creadas: number; actualizadas: number; omitidas: number };
        terrenos: { creados: number; actualizados: number; omitidos: number };
    };
    message?: string;
}

const props = defineProps<{
    result: Result | null;
}>();

const emit = defineEmits<{
    reset: [];
}>();

const summary = computed(() => props.result?.summary || null);

const totalCreated = computed(() => {
    if (!summary.value) return 0;
    return (
        (summary.value.barrios?.creados || 0) +
        (summary.value.cuadras?.creadas || 0) +
        (summary.value.terrenos?.creados || 0)
    );
});

const totalUpdated = computed(() => {
    if (!summary.value) return 0;
    return (
        (summary.value.barrios?.actualizados || 0) +
        (summary.value.cuadras?.actualizadas || 0) +
        (summary.value.terrenos?.actualizados || 0)
    );
});

const totalSkipped = computed(() => {
    if (!summary.value) return 0;
    return (
        (summary.value.barrios?.omitidos || 0) +
        (summary.value.cuadras?.omitidas || 0) +
        (summary.value.terrenos?.omitidos || 0)
    );
});

const totalProcessed = computed(() => {
    return totalCreated.value + totalUpdated.value + totalSkipped.value;
});
</script>

<template>
    <div class="space-y-8 p-6">
        
        <div class="relative mb-10 text-center">
            
            <div class="absolute inset-0 flex items-center justify-center">
                <div
                    class="h-32 w-32 animate-ping rounded-full bg-green-100 opacity-20 dark:bg-green-900/30"
                ></div>
            </div>

            
            <div class="relative inline-block">
                <div
                    class="mx-auto mb-6 flex h-24 w-24 animate-bounce items-center justify-center rounded-full bg-gradient-to-br from-green-400 to-emerald-600 shadow-2xl"
                >
                    <i class="pi pi-check text-5xl text-white"></i>
                </div>
            </div>

            <h3
                class="mb-3 bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-4xl font-extrabold text-transparent"
            >
                ¡Importación Completada!
            </h3>
            <p class="text-lg font-medium text-gray-600 dark:text-gray-400">
                Los datos se han importado exitosamente
            </p>

            
            <div class="mt-4 flex items-center justify-center gap-2">
                <div
                    class="h-1 w-12 rounded bg-gradient-to-r from-transparent to-green-500"
                ></div>
                <div class="h-1 w-12 rounded bg-green-500"></div>
                <div
                    class="h-1 w-12 rounded bg-gradient-to-l from-transparent to-green-500"
                ></div>
            </div>
        </div>

        
        <div
            class="relative overflow-hidden rounded-2xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 shadow-lg dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
        >
            <div
                class="absolute top-0 right-0 -mt-20 -mr-20 h-40 w-40 rounded-full bg-green-300 opacity-20 dark:bg-green-700"
            ></div>
            <div class="relative flex items-start gap-4 p-6">
                <div
                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-500 shadow-lg"
                >
                    <i class="pi pi-check-circle text-2xl text-white"></i>
                </div>
                <div class="flex-1">
                    <p
                        class="text-xl font-bold text-green-800 dark:text-green-200"
                    >
                        Proceso exitoso
                    </p>
                    <p class="mt-1 text-green-700 dark:text-green-300">
                        {{ result?.message }}
                    </p>
                </div>
            </div>
        </div>

        
        <div v-if="summary" class="grid grid-cols-1 gap-6 md:grid-cols-3">
            
            <div
                class="group relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800"
            >
                <div
                    class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-green-400 to-emerald-500"
                ></div>
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <span
                            class="text-sm font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                            >Creados</span
                        >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 shadow-lg transition-transform group-hover:scale-110"
                        >
                            <i class="pi pi-plus text-xl text-white"></i>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-green-600 to-emerald-600 bg-clip-text text-5xl font-black text-transparent"
                    >
                        {{ totalCreated }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Registros nuevos
                    </div>
                </div>
            </div>

            
            <div
                class="group relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800"
            >
                <div
                    class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-blue-400 to-indigo-500"
                ></div>
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <span
                            class="text-sm font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                            >Actualizados</span
                        >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 shadow-lg transition-transform group-hover:scale-110"
                        >
                            <i class="pi pi-refresh text-xl text-white"></i>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-blue-600 to-indigo-600 bg-clip-text text-5xl font-black text-transparent"
                    >
                        {{ totalUpdated }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Registros modificados
                    </div>
                </div>
            </div>

            
            <div
                class="group relative transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:bg-gray-800"
            >
                <div
                    class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-gray-400 to-gray-500"
                ></div>
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <span
                            class="text-sm font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-400"
                            >Omitidos</span
                        >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-gray-400 to-gray-500 shadow-lg transition-transform group-hover:scale-110"
                        >
                            <i class="pi pi-minus text-xl text-white"></i>
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-gray-600 to-gray-700 bg-clip-text text-5xl font-black text-transparent"
                    >
                        {{ totalSkipped }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Sin cambios
                    </div>
                </div>
            </div>
        </div>

        
        <div
            v-if="summary"
            class="overflow-hidden rounded-2xl border-0 bg-white shadow-2xl dark:bg-gray-800"
        >
            
            <div
                class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6"
            >
                <h4
                    class="flex items-center gap-3 text-2xl font-bold text-white"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/20"
                    >
                        <i class="pi pi-th text-xl"></i>
                    </div>
                    Detalles por Tipo
                </h4>
                <p class="mt-2 text-sm text-indigo-100">
                    Resumen detallado de la importación
                </p>
            </div>

            
            <div class="space-y-8 p-6">
                
                <div class="relative">
                    <div class="mb-5 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 shadow-lg"
                        >
                            <i class="pi pi-map text-xl text-white"></i>
                        </div>
                        <div>
                            <h5
                                class="text-xl font-bold text-gray-800 dark:text-gray-200"
                            >
                                Barrios
                            </h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Zonas geográficas
                            </p>
                        </div>
                    </div>
                    <div class="ml-2 grid grid-cols-3 gap-4">
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-5 transition-all hover:shadow-lg dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
                        >
                            <div class="text-3xl font-black text-green-600">
                                {{ summary.barrios?.creados || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Creados
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-5 transition-all hover:shadow-lg dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20"
                        >
                            <div class="text-3xl font-black text-blue-600">
                                {{ summary.barrios?.actualizados || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Actualizados
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 p-5 transition-all hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-900"
                        >
                            <div class="text-3xl font-black text-gray-600">
                                {{ summary.barrios?.omitidos || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Omitidos
                            </div>
                        </div>
                    </div>
                </div>

                
                <div
                    class="my-6 border-t border-gray-200 dark:border-gray-700"
                ></div>

                
                <div class="relative">
                    <div class="mb-5 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-400 to-red-500 shadow-lg"
                        >
                            <i class="pi pi-th-large text-xl text-white"></i>
                        </div>
                        <div>
                            <h5
                                class="text-xl font-bold text-gray-800 dark:text-gray-200"
                            >
                                Cuadras
                            </h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Divisiones de terreno
                            </p>
                        </div>
                    </div>
                    <div class="ml-2 grid grid-cols-3 gap-4">
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-5 transition-all hover:shadow-lg dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
                        >
                            <div class="text-3xl font-black text-green-600">
                                {{ summary.cuadras?.creadas || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Creadas
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-5 transition-all hover:shadow-lg dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20"
                        >
                            <div class="text-3xl font-black text-blue-600">
                                {{ summary.cuadras?.actualizadas || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Actualizadas
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 p-5 transition-all hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-900"
                        >
                            <div class="text-3xl font-black text-gray-600">
                                {{ summary.cuadras?.omitidas || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Omitidas
                            </div>
                        </div>
                    </div>
                </div>

                
                <div
                    class="my-6 border-t border-gray-200 dark:border-gray-700"
                ></div>

                
                <div class="relative">
                    <div class="mb-5 flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-400 to-pink-500 shadow-lg"
                        >
                            <i class="pi pi-home text-xl text-white"></i>
                        </div>
                        <div>
                            <h5
                                class="text-xl font-bold text-gray-800 dark:text-gray-200"
                            >
                                Terrenos
                            </h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Propiedades individuales
                            </p>
                        </div>
                    </div>
                    <div class="ml-2 grid grid-cols-3 gap-4">
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-5 transition-all hover:shadow-lg dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20"
                        >
                            <div class="text-3xl font-black text-green-600">
                                {{ summary.terrenos?.creados || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Creados
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-5 transition-all hover:shadow-lg dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20"
                        >
                            <div class="text-3xl font-black text-blue-600">
                                {{ summary.terrenos?.actualizados || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Actualizados
                            </div>
                        </div>
                        <div
                            class="group relative overflow-hidden rounded-xl border-2 border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100 p-5 transition-all hover:shadow-lg dark:border-gray-700 dark:from-gray-800 dark:to-gray-900"
                        >
                            <div class="text-3xl font-black text-gray-600">
                                {{ summary.terrenos?.omitidos || 0 }}
                            </div>
                            <div
                                class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400"
                            >
                                Omitidos
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 shadow-2xl"
        >
            <div class="absolute inset-0 bg-black/10"></div>
            <div
                class="absolute top-0 right-0 -mt-32 -mr-32 h-64 w-64 rounded-full bg-white/10"
            ></div>
            <div
                class="absolute bottom-0 left-0 -mb-32 -ml-32 h-64 w-64 rounded-full bg-white/10"
            ></div>

            <div class="relative p-8">
                <div class="mb-6 text-center">
                    <h4 class="mb-2 text-2xl font-bold text-white">
                        Resumen Total
                    </h4>
                    <p class="text-indigo-100">
                        Estadísticas completas del proceso
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <div
                        class="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm"
                    >
                        <div class="mb-2 text-5xl font-black text-white">
                            {{ totalProcessed }}
                        </div>
                        <div class="text-sm font-semibold text-indigo-100">
                            Procesados
                        </div>
                    </div>
                    <div
                        class="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm"
                    >
                        <div class="mb-2 text-5xl font-black text-white">
                            {{ totalCreated }}
                        </div>
                        <div class="text-sm font-semibold text-green-200">
                            Creados
                        </div>
                    </div>
                    <div
                        class="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm"
                    >
                        <div class="mb-2 text-5xl font-black text-white">
                            {{ totalUpdated }}
                        </div>
                        <div class="text-sm font-semibold text-blue-200">
                            Actualizados
                        </div>
                    </div>
                    <div
                        class="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm"
                    >
                        <div class="mb-2 text-5xl font-black text-white">
                            {{ totalSkipped }}
                        </div>
                        <div class="text-sm font-semibold text-gray-200">
                            Omitidos
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="mt-8 text-center">
            <button
                @click="emit('reset')"
                class="inline-flex transform cursor-pointer items-center gap-2 rounded-xl border-0 bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-4 text-lg font-bold text-white shadow-lg transition-all hover:scale-105 hover:shadow-2xl"
            >
                <i class="pi pi-refresh"></i>
                Realizar Nueva Importación
            </button>
        </div>
    </div>
</template>
