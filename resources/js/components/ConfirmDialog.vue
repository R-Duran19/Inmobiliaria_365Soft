<script setup lang="ts">
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertTriangle, Info } from 'lucide-vue-next';

interface Props {
  open: boolean;
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'default' | 'destructive';
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  variant: 'default',
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'confirm': [];
}>();

const internalOpen = ref(props.open);

watch(() => props.open, (newVal) => {
  internalOpen.value = newVal;
});

watch(internalOpen, (newVal) => {
  emit('update:open', newVal);
});

const handleConfirm = () => {
  emit('confirm');
  internalOpen.value = false;
};

const handleCancel = () => {
  internalOpen.value = false;
};
</script>

<template>
  <Dialog v-model:open="internalOpen">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          <Alert :variant="variant" class="mt-3">
            <AlertTriangle v-if="variant === 'destructive'" class="h-4 w-4" />
            <Info v-else class="h-4 w-4" />
            <AlertTitle>{{ variant === 'destructive' ? 'Atención' : 'Información' }}</AlertTitle>
            <AlertDescription>
              {{ description }}
            </AlertDescription>
          </Alert>
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button 
          variant="outline" 
          @click="handleCancel"
        >
          {{ cancelText }}
        </Button>
        <Button 
          :variant="variant === 'destructive' ? 'destructive' : 'default'"
          @click="handleConfirm"
        >
          {{ confirmText }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>