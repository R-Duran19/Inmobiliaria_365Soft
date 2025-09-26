import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';

// --- PrimeVue ---
import 'primeflex/primeflex.css'; // Flex/Grid utilities (opcional)
import 'primeicons/primeicons.css'; // Iconos
import PrimeVue from 'primevue/config';

// --- Puedes importar componentes globalmente si quieres ---
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import ConfirmDialog from 'primevue/confirmdialog';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressSpinner from 'primevue/progressspinner';
import RadioButton from 'primevue/radiobutton';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue);

        // Registrar componentes globales de PrimeVue (opcional)
        vueApp.component('Button', Button);
        vueApp.component('DataTable', DataTable);
        vueApp.component('Column', Column);
        vueApp.component('Dialog', Dialog);
        vueApp.component('InputText', InputText);
        vueApp.component('InputNumber', InputNumber);
        vueApp.component('Dropdown', Dropdown);
        vueApp.component('Checkbox', Checkbox);
        vueApp.component('RadioButton', RadioButton);
        vueApp.component('Toast', Toast);
        vueApp.component('ConfirmDialog', ConfirmDialog);
        vueApp.component('ProgressSpinner', ProgressSpinner);
        vueApp.use(ToastService);


        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Esto mantiene el light/dark mode
initializeTheme();
