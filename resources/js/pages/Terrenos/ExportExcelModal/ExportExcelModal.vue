<script setup lang="ts">
import { ref, watch, defineProps, reactive } from 'vue'
import axios from 'axios'
import NotificacionToast from '@/components/ui/notificacionToast/NotificacionToast.vue';
import { Proyecto } from '@/types/Proyecto';

const props = defineProps<{
  visible: boolean;
  proyectos: Proyecto[]; 
}>();

const proyectoSeleccionado = ref(0)
const visible = ref(props.visible)

const notificacion = reactive({
  visible: false,
  tipo: 'success' as 'success' | 'error',
  mensaje: ''
})

function mostrarNotificacion(tipo: 'success' | 'error', mensaje: string) {
  notificacion.tipo = tipo
  notificacion.mensaje = mensaje
  notificacion.visible = true
}

watch(
  () => props.visible,
  v => (visible.value = v)
)

function cancelar() {
  visible.value = false
  proyectoSeleccionado.value = 0
}

async function exportar() {
  if (!proyectoSeleccionado.value) {
    mostrarNotificacion('error', 'Debe seleccionar un proyecto.')
    return
  }

  const proyecto = props.proyectos.find(p => p.id === proyectoSeleccionado.value)
  if (!proyecto) {
    mostrarNotificacion('error', 'Proyecto no válido.')
    return
  }

  try {
    const response = await axios.get(`terrenos/export/${proyecto.id}`, {
      responseType: 'blob'
    })

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `terrenos_${proyecto.nombre}.xlsx`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    mostrarNotificacion(
      'success',
      `Exportación del proyecto "${proyecto.nombre}" completada correctamente.`
    )

    cancelar()
  } catch (error) {
    console.error('Error al exportar terrenos:', error)
    mostrarNotificacion('error', 'Error al exportar los terrenos.')
  }
}
</script>

<template>
  <!-- Modal -->
  <div
    v-if="visible"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div
      class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-96 p-6 relative"
    >
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
        Exportar Proyecto
      </h2>

      <div class="mb-4">
        <label
          for="proyecto"
          class="block text-gray-700 dark:text-gray-300 font-medium mb-2"
        >
          Seleccione un proyecto:
        </label>
        <select
          v-model="proyectoSeleccionado"
          id="proyecto"
          class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 outline-none"
        >
          <option disabled value="">-- Seleccione un proyecto</option>
          <option v-for="p in proyectos" :key="p.id" :value="p.id">
            {{ p.nombre }}
          </option>
        </select>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button
          @click="cancelar"
          class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium transition"
        >
          Cancelar
        </button>
        <button
          @click="exportar"
          class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium transition flex items-center gap-2"
        >
          <i class="pi pi-file-excel"></i>
          Exportar
        </button>
      </div>
    </div>
  </div>

  <!-- ✅ Componente de notificación siempre disponible -->
  <NotificacionToast
    v-model="notificacion.visible"
    :type="notificacion.tipo"
    :message="notificacion.mensaje"
  />
</template>
