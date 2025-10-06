<script setup lang="ts">
import type { Terreno } from '@/types/terrenos';
import { defineEmits, defineProps, ref, watch } from 'vue';

const props = defineProps<{
    visible: boolean;
    terreno: Terreno | null;
}>();

const emit = defineEmits<{
    (e: 'update:visible', val: boolean): void;
}>();

const localVisible = ref(props.visible);

watch(
    () => props.visible,
    (val) => {
        localVisible.value = val;
    },
);

function closeDrawer() {
    localVisible.value = false;
    emit('update:visible', false);
}
</script>

<template>
    <transition name="drawer">
        <div
            v-if="localVisible"
            class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-sm"
        >
            <div
                class="flex h-full w-96 transform flex-col overflow-auto bg-white p-6 shadow-2xl transition-transform dark:bg-gray-900"
            >
                
                <div
                    class="mb-6 flex items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700"
                >
                    <h3
                        class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Costos del Terreno
                    </h3>
                    <button
                        class="text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300"
                        @click="closeDrawer"
                    >
                        ✕
                    </button>
                </div>

                
                <div
                    v-if="terreno"
                    class="space-y-4 text-gray-800 dark:text-gray-200"
                >
                    <div class="flex justify-between">
                        <span class="font-medium">Proyecto:</span>
                        <span>{{ terreno.proyecto.nombre }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Ubicación:</span>
                        <span>{{ terreno.ubicacion }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Precio de venta:</span>
                        <span
                            >${{ terreno.precio_venta.toLocaleString() }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Cuota inicial:</span>
                        <span
                            >${{ terreno.cuota_inicial.toLocaleString() }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Cuota mensual:</span>
                        <span
                            >${{ terreno.cuota_mensual.toLocaleString() }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="font-medium">Estado</span>
                        <span>
                            {{
                                Number(terreno.estado) === 0
                                    ? 'Disponible'
                                    :  Number(terreno.estado) === 1
                                      ? 'Reservado'
                                      :  Number(terreno.estado) === 2
                                        ? 'Vendido'
                                        : 'Desconocido'
                            }}
                        </span>
                    </div>
                </div>

                
                <div
                    class="mt-auto border-t border-gray-200 pt-4 dark:border-gray-700"
                >
                    <button
                        @click="closeDrawer"
                        class="w-full rounded bg-blue-600 py-2 text-white transition-colors hover:bg-blue-700"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
    transition:
        transform 0.3s ease,
        opacity 0.3s ease;
}
.drawer-enter-from,
.drawer-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
.drawer-enter-to,
.drawer-leave-from {
    transform: translateX(0%);
    opacity: 1;
}
</style>
