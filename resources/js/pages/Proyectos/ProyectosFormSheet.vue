<script setup lang="ts">
import { ref } from 'vue';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import ProyectosForm from './ProyectosForm.vue';

const open = ref(false);

const handleSave = () => {
  open.value = false;
};

const handleCancel = () => {
  open.value = false;
};
</script>

<template>
  <Sheet v-model:open="open">
    <SheetTrigger as-child>
      <slot></slot>
    </SheetTrigger>
    <SheetContent class="w-[90%] sm:w-[500px] md:w-[540px] p-6 dark:bg-gray-800">
      <SheetHeader class="mb-4">
        <SheetTitle>Registro de Nuevo Proyecto</SheetTitle>
      </SheetHeader>
      <div class="py-2">
        <ProyectosForm @save="handleSave" @cancel="handleCancel" />
      </div>
    </SheetContent>
  </Sheet>
</template>
