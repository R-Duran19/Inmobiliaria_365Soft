<script setup lang="ts">
import Tag from 'primevue/tag';
import { computed } from 'vue';

const props = defineProps<{
    analysis: any;
}>();

const emit = defineEmits<{
    next: [];
}>();

const importTypeInfo = computed(() => {
    const types: Record<string, any> = {
        complete: {
            label: 'Importación Completa',
            description: 'Proyecto con barrios, cuadras y terrenos',
            severity: 'success',
            icon: 'pi-check-circle',
        },
        barrios: {
            label: 'Solo Barrios',
            description: 'Se importarán únicamente barrios',
            severity: 'info',
            icon: 'pi-map',
        },
        cuadras_terrenos: {
            label: 'Cuadras con Terrenos',
            description: 'Se importarán cuadras y terrenos',
            severity: 'info',
            icon: 'pi-th-large',
        },
        cuadras: {
            label: 'Solo Cuadras',
            description: 'Se importarán únicamente cuadras',
            severity: 'info',
            icon: 'pi-th-large',
        },
        terrenos: {
            label: 'Solo Terrenos',
            description: 'Se importarán únicamente terrenos',
            severity: 'warning',
            icon: 'pi-stop',
        },
    };

    return (
        types[props.analysis?.import_type?.toLowerCase()] || {
            label: 'Desconocido',
            description: 'Tipo de importación no identificado',
            severity: 'danger',
            icon: 'pi-exclamation-triangle',
        }
    );
});
</script>

<template>
    <div class="space-y-6">
        <div class="mb-8 text-center">
            <i class="pi pi-chart-bar mb-4 text-6xl text-blue-500"></i>
            <h3
                class="mb-2 text-2xl font-bold text-gray-800 dark:text-gray-200"
            >
                Análisis del Archivo
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Revisa los detalles detectados en tu archivo GeoJSON
            </p>
        </div>

        
        <div
            class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20"
        >
            <div class="flex items-center gap-4">
                <div
                    class="rounded-full bg-white p-4 shadow-md dark:bg-gray-800"
                >
                    <i
                        :class="[
                            'pi',
                            importTypeInfo.icon,
                            'text-3xl text-blue-600',
                        ]"
                    ></i>
                </div>
                <div class="flex-1">
                    <div class="mb-1 flex items-center gap-2">
                        <h4
                            class="text-xl font-bold text-gray-800 dark:text-gray-200"
                        >
                            {{ importTypeInfo.label }}
                        </h4>
                        <Tag
                            :severity="importTypeInfo.severity"
                            :value="analysis.import_type.toUpperCase()"
                            class="!rounded !p-1 !text-blue-800"
                        />
                    </div>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ importTypeInfo.description }}
                    </p>
                </div>
            </div>
        </div>

        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            
            <div
                class="rounded-2xl border-2 border-purple-200 bg-transparent p-6 transition-all duration-300 hover:border-purple-400 dark:border-purple-600"
            >
                <div class="mb-4 flex items-center justify-between">
                    <i class="pi pi-map text-4xl text-purple-500"></i>
                    <Tag
                        :value="analysis.barrios.count"
                        severity="success"
                        class="!bg-transparent px-3 py-1 text-lg"
                    />
                </div>
                <h4
                    class="mb-1 text-xl font-semibold text-purple-700 dark:text-purple-300"
                >
                    Barrios
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Unidades vecinales detectadas
                </p>
            </div>

            
            <div
                class="rounded-2xl border-2 border-blue-200 bg-transparent p-6 transition-all duration-300 hover:border-blue-400 dark:border-blue-600"
            >
                <div class="mb-4 flex items-center justify-between">
                    <i class="pi pi-th-large text-4xl text-blue-500"></i>
                    <Tag
                        :value="analysis.cuadras.count"
                        severity="info"
                        class="!bg-transparent px-3 py-1 text-lg"
                    />
                </div>
                <h4
                    class="mb-1 text-xl font-semibold text-blue-700 dark:text-blue-300"
                >
                    Cuadras
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Manzanas detectadas
                </p>
            </div>

            
            <div
                class="rounded-2xl border-2 border-green-200 bg-transparent p-6 transition-all duration-300 hover:border-green-400 dark:border-green-600"
            >
                <div class="mb-4 flex items-center justify-between">
                    <i class="pi pi-stop text-4xl text-green-500"></i>
                    <Tag
                        :value="analysis.terrenos.count"
                        severity="warning"
                        class="px-3 py-1 text-lg"
                    />
                </div>
                <h4
                    class="mb-1 text-xl font-semibold text-green-700 dark:text-green-300"
                >
                    Terrenos
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Lotes detectados
                </p>
            </div>
        </div>

        
        <div class="space-y-10">
            

            <div
                v-if="analysis.barrios.count > 0"
                class="overflow-hidden rounded-2xl dark:border dark:border-purple-800 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-lg transition-all duration-500 hover:shadow-2xl dark:from-blue-900/30 dark:to-indigo-900/30"
            >
                <div
                    class="flex items-center gap-4 border-b border-purple-100 bg-purple-50 p-6 dark:border-purple-800 dark:bg-purple-900"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                            />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-xl font-bold text-gray-900 dark:text-white"
                        >
                            Barrios Detectados
                        </h3>
                        <p
                            class="text-sm font-medium text-purple-600 dark:text-purple-400"
                        >
                            {{ analysis.barrios.count }}
                            {{
                                analysis.barrios.count === 1
                                    ? 'barrio encontrado'
                                    : 'barrios encontrados'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-b-3xl bg-white dark:bg-gray-800"
                >
                    <div
                        v-if="analysis.barrios.count > 0"
                        class="overflow-hidden bg-gradient-to-br from-blue-50 to-blue-100 shadow-md transition-all duration-500 hover:shadow-xl dark:from-blue-900/30 dark:to-blue-800/30"
                    >
                        

                        
                        <div class="!dark:border-none overflow-x-auto">
                            <table
                                class="!dark:border-none min-w-full text-left text-sm text-gray-600 dark:text-gray-300"
                            >
                                <thead
                                    class="bg-purple-100 text-purple-900 dark:border-none dark:bg-gray-900 dark:text-purple-200"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Nombre
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Índice en archivo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(data, i) in analysis.barrios
                                            .items"
                                        :key="i"
                                        class="dark:hover:bg-purple -900/30 border-t border-gray-200 bg-white transition-colors hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-full border border-purple-400 px-4 py-1 font-semibold text-purple-600 dark:text-purple-400"
                                            >
                                                {{ data.nombre }}
                                            </span>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-lg bg-gray-100 px-3 py-1 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                #{{ data.index }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>            
            
            <div
                v-if="analysis.cuadras.count > 0"
                class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-50 to-blue-100 shadow-lg transition-all duration-500 hover:shadow-2xl dark:border dark:border-blue-500 dark:from-blue-900/30 dark:to-blue-800/30"
            >
                <div class="flex items-center gap-4 p-6">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M3 15h18M9 3v18M15 3v18" />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-xl font-bold text-gray-900 dark:text-white"
                        >
                            Cuadras Detectadas
                        </h3>
                        <p
                            class="text-sm font-medium text-blue-600 dark:text-blue-400"
                        >
                            {{ analysis.cuadras.count }}
                            {{
                                analysis.cuadras.count === 1
                                    ? 'cuadra encontrada'
                                    : 'cuadras encontradas'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-b-3xl bg-white dark:bg-gray-800"
                >
                    <div
                        v-if="analysis.cuadras.count > 0"
                        class="rounded-B-3xl overflow-hidden bg-gradient-to-br from-blue-50 to-blue-100 shadow-md transition-all duration-500 hover:shadow-xl dark:bg-gray-800"
                    >
                        

                        
                        <div class="overflow-x-auto">
                            <table
                                class="dark:text-gray-30 0 min-w-full text-left text-sm text-gray-600"
                            >
                                <thead
                                    class="bg-blue-200 text-blue-900 dark:bg-gray-900 dark:text-blue-200"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Nombre
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Barrio relacionado
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Índice
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(data, i) in analysis.cuadras
                                            .items"
                                        :key="i"
                                        class="bg-white transition-colors hover:bg-blue-50 dark:bg-gray-900 dark:hover:bg-blue-900/30"
                                    >
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-full px-4 py-1 font-semibold text-blue-600 border border-blue-600 dark:text-blue-400"
                                            >
                                                {{ data.nombre }}
                                            </span>
                                        </td>

                                        
                                        <td class="px-6 py-4">
                                            <span
                                                v-if="data.barrio_ref"
                                                class="rounded-full px-4 py-1 font-semibold text-purple-500"
                                            >
                                                {{ data.barrio_ref }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-xs text-gray-400 italic dark:text-gray-500"
                                            >
                                                Sin relación
                                            </span>
                                        </td>

                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-lg bg-gray-100 px-3 py-1 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                #{{ data.index }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            
            <div
                v-if="analysis.terrenos.count > 0"
                class="overflow-hidden rounded-3xl bg-gradient-to-br from-green-50 to-green-100 shadow-lg transition-all duration-500 hover:shadow-2xl  dark:from-green-900/30 dark:to-green-800/30
                dark:border dark:border-green-500"
            >
                <div
                    class="flex items-center gap-4  p-6 "
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-green-500 to-green-600 text-white shadow-lg"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                            />
                            <circle cx="12" cy="7" r="4" />
                            <path d="M12 2v5M12 12v10" />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-xl font-bold text-green-700 dark:text-white"
                        >
                            Terrenos Detectados
                        </h3>
                        <p
                            class="text-sm font-medium text-green-600 dark:text-green-400"
                        >
                            {{ analysis.terrenos.count }}
                            {{
                                analysis.terrenos.count === 1
                                    ? 'terreno encontrado'
                                    : 'terrenos encontrados'
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-b-3xl bg-white dark:bg-gray-800"
                >
                    <div
                        v-if="analysis.terrenos.count > 0"
                        class="overflow-hidden  bg-gradient-to-br from-blue-50 to-blue-100 shadow-md transition-all duration-500 hover:shadow-xl dark:from-blue-900/30 dark:to-blue-800/30"
                    >
                        

                        
                        <div class="overflow-x-auto">
                            <table
                                class="min-w-full text-left text-sm text-gray-600 dark:text-gray-300"
                            >
                                <thead
                                    class="bg-green-200 text-green-700 dark:bg-gray-900 dark:text-green-200"
                                >
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold "
                                        >
                                            Número
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Cuadra relacionada
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 font-semibold"
                                        >
                                            Índice
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(data, i) in analysis.terrenos
                                            .items"
                                        :key="i"
                                        class=" bg-white transition-colors hover:bg-green-50 dark:hover:bg-green-900/30
                                        dark:bg-gray-900"
                                    >
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-full border border-green-400 px-4 py-1 font-semibold text-green-600 dark:text-green-400"
                                            >
                                                Lote {{ data.numero }}
                                            </span>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                v-if="data.cuadra_ref"
                                                class="rounded-full border border-blue-400 px-4 py-1 font-semibold text-blue-600 dark:text-blue-400"
                                            >
                                                {{ data.cuadra_ref }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-xs text-gray-400 italic dark:text-gray-500"
                                            >
                                                Sin relación
                                            </span>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <span
                                                class="rounded-lg bg-gray-100 px-3 py-1 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                #{{ data.index }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
