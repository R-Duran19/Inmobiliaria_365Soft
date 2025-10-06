<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import Mapa from '@/components/Mapa.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Control',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Mapa" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-1">
            <Mapa />
        </div>
    </AppLayout>
</template>