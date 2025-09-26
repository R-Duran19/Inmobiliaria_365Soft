<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { categorias } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categorias',
        href: categorias().url,
    },
];
</script>

<template>
     <AppLayout :breadcrumbs="breadcrumbs">
        categorias
     </AppLayout>
</template>