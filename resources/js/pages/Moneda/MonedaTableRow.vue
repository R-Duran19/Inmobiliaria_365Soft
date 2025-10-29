<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Moneda } from '@/types/Moneda';

import axios from 'axios';
import { ref } from 'vue';
import EditarMoneda from './EditarMoneda.vue';

const props = defineProps<{ moneda: Moneda }>();

const emit = defineEmits(['updated', 'deleted', 'costos', 'refresh']);

const camposTerreno = [
    { key: 'nombre', label: 'Moneda' },
    { key: 'abreviacion', label: 'Código' },
    { key: 'pais', label: 'País' },
    { key: 'tipo_cambio', label: 'Tipo cambio' },
    { key: 'updated_at', label: 'Fecha actualización' },
    { key: 'activo', label: 'Estado' },
];

const editarVisible = ref(false);

const abrirSubidaDocumento = () => {
    // const id = props.terreno.id;
    // window.open(`/documentos/${id}`, '_blank');
};

const abrirVisualizarDocumentos = () => {
    // const id = props.terreno.id;
    // window.open(`/documentos/visualizar/${id}`);
};

const confirmarEliminar = () => {
    emit('deleted', props.moneda.id);
};

function obtenerValor(obj: any, ruta: string) {
    return ruta.split('.').reduce((acc, key) => acc?.[key], obj);
}

function formatearFecha(fecha?: string | null): string {
    if (!fecha || fecha.startsWith('-000001')) {
        return 'Sin modificación';
    }
    try {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch {
        return 'Sin modificación';
    }
}
const toggleEstado = async () => {
    try {
        await axios.patch(`/monedas/${props.moneda.id}/activo`);
        emit('refresh');
    } catch (error) {
        console.error(error);
    }
};

const banderas: Record<string, string> = {
  "Albania": "al",
  "Algeria": "dz",
  "American Samoa": "as",
  "Andorra": "ad",
  "Anguilla": "ai",
  "Antigua and Barbuda": "ag",
  "Argentina": "ar",
  "Armenia": "am",
  "Aruba": "aw",
  "Australia": "au",
  "Austria": "at",
  "Azerbaijan": "az",
  "Bahamas": "bs",
  "Bahrain": "bh",
  "Bangladesh": "bd",
  "Barbados": "bb",
  "Belarus": "by",
  "Belgium": "be",
  "Bermuda": "bm",
  "Bhutan": "bt",
  "Bolivia (Plurinational State of)": "bo",
  "Bosnia and Herzegovina": "ba",
  "Botswana": "bw",
  "Brazil": "br",
  "British Virgin Islands": "vg",
  "Brunei Darussalam": "bn",
  "Bulgaria": "bg",
  "Burkina Faso": "bf",
  "Burundi": "bi",
  "Cabo Verde": "cv",
  "Cameroon": "cm",
  "Canada": "ca",
  "Cayman Islands": "ky",
  "Central African Republic": "cf",
  "Chad": "td",
  "Chile": "cl",
  "China": "cn",
  "China, Hong Kong SAR": "hk",
  "China, Macao SAR": "mo",
  "Colombia": "co",
  "Comoros": "km",
  "Congo": "cg",
  "Cook Islands": "ck",
  "Costa Rica": "cr",
  "Croatia": "hr",
  "Cuba": "cu",
  "Czech Republic": "cz",
  "Côte d'Ivoire": "ci",
  "Democratic People's Republic of Korea": "kp",
  "Denmark": "dk",
  "Dominica": "dm",
  "Dominican Republic": "do",
  "Ecuador": "ec",
  "Egypt": "eg",
  "El Salvador": "sv",
  "Equatorial Guinea": "gq",
  "Eritrea": "er",
  "Estonia": "ee",
  "Faeroe Islands": "fo",
  "Falkland Islands (Malvinas)": "fk",
  "Fiji": "fj",
  "Finland": "fi",
  "France": "fr",
  "French Guiana": "gf",
  "French Polynesia": "pf",
  "Gabon": "ga",
  "Gambia": "gm",
  "Georgia": "ge",
  "Germany": "de",
  "Ghana": "gh",
  "Gibraltar": "gi",
  "Greece": "gr",
  "Greenland": "gl",
  "Grenada": "gd",
  "Guadeloupe": "gp",
  "Guam": "gu",
  "Guatemala": "gt",
  "Guernsey": "gg",
  "Guinea": "gn",
  "Guinea-Bissau": "gw",
  "Guyana": "gy",
  "Holy See": "va",
  "Honduras": "hn",
  "Hungary": "hu",
  "Iceland": "is",
  "India": "in",
  "Indonesia": "id",
  "Iran (Islamic Republic of)": "ir",
  "Iraq": "iq",
  "Ireland": "ie",
  "Isle of Man": "im",
  "Israel": "il",
  "Italy": "it",
  "Jamaica": "jm",
  "Japan": "jp",
  "Jersey": "je",
  "Jordan": "jo",
  "Kazakhstan": "kz",
  "Kenya": "ke",
  "Kiribati": "ki",
  "Kuwait": "kw",
  "Kyrgyzstan": "kg",
  "Lao People's Democratic Republic": "la",
  "Latvia": "lv",
  "Lebanon": "lb",
  "Lesotho": "ls",
  "Liberia": "lr",
  "Liechtenstein": "li",
  "Lithuania": "lt",
  "Luxembourg": "lu",
  "Madagascar": "mg",
  "Malawi": "mw",
  "Malaysia": "my",
  "Maldives": "mv",
  "Malta": "mt",
  "Marshall Islands": "mh",
  "Martinique": "mq",
  "Mauritania": "mr",
  "Mauritius": "mu",
  "Mexico": "mx",
  "Micronesia (Federated States of)": "fm",
  "Monaco": "mc",
  "Mongolia": "mn",
  "Montenegro": "me",
  "Montserrat": "ms",
  "Mozambique": "mz",
  "Myanmar": "mm",
  "Namibia": "na",
  "Nauru": "nr",
  "Nepal": "np",
  "Netherlands": "nl",
  "New Caledonia": "nc",
  "New Zealand": "nz",
  "Nicaragua": "ni",
  "Niger": "ne",
  "Nigeria": "ng",
  "Niue": "nu",
  "Northern Mariana Islands": "mp",
  "Norway": "no",
  "Oman": "om",
  "Pakistan": "pk",
  "Palau": "pw",
  "Papua New Guinea": "pg",
  "Paraguay": "py",
  "Peru": "pe",
  "Philippines": "ph",
  "Pitcairn": "pn",
  "Poland": "pl",
  "Portugal": "pt",
  "Puerto Rico": "pr",
  "Qatar": "qa",
  "Republic of Korea": "kr",
  "Republic of Moldova": "md",
  "Republic of South Sudan": "ss",
  "Romania": "ro",
  "Russian Federation": "ru",
  "Rwanda": "rw",
  "Réunion": "re",
  "Saint Helena ex. dep.": "sh",
  "Saint Kitts and Nevis": "kn",
  "Saint Lucia": "lc",
  "Saint Pierre and Miquelon": "pm",
  "Saint Vincent and the Grenadines": "vc",
  "Samoa": "ws",
  "San Marino": "sm",
  "Sao Tome and Principe": "st",
  "Saudi Arabia": "sa",
  "Senegal": "sn",
  "Serbia": "rs",
  "Seychelles": "sc",
  "Sierra Leone": "sl",
  "Singapore": "sg",
  "Slovakia": "sk",
  "Slovenia": "si",
  "Solomon Islands": "sb",
  "South Africa": "za",
  "Spain": "es",
  "Sri Lanka": "lk",
  "State of Palestine": "ps",
  "Suriname": "sr",
  "Swaziland": "sz",
  "Sweden": "se",
  "Switzerland": "ch",
  "TFYR of Macedonia": "mk",
  "Tajikistan": "tj",
  "Thailand": "th",
  "Timor-Leste": "tl",
  "Tonga": "to",
  "Trinidad and Tobago": "tt",
  "Turkey": "tr",
  "Turkmenistan": "tm",
  "Turks and Caicos Islands": "tc",
  "Tuvalu": "tv",
  "Uganda": "ug",
  "Ukraine": "ua",
  "United Kingdom of Great Britain and Northern Ireland": "gb",
  "United Republic of Tanzania": "tz",
  "United States Virgin Islands": "vi",
  "United States of America": "us",
  "Uruguay": "uy",
  "Uzbekistan": "uz",
  "Vanuatu": "vu",
  "Venezuela (Bolivarian Republic of)": "ve",
  "Wallis and Futuna Islands": "wf",
  "Yemen": "ye",
  "Zambia": "zm",
  "Zimbabwe": "zw",
  "Åland Islands": "ax"
};


</script>

<template>
    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700">
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex gap-1">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="editarVisible = true"
                        >
                            <svg
                                xmlns="http://www/w3.org/2000/svg"
                                class="h-4 w-4 text-blue-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Editar</TooltipContent>
                </Tooltip>

                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="ghost" size="sm" @click="toggleEstado">
                            <svg
                                v-if="!moneda.activo"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-red-500 dark:text-red-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 text-green-500 dark:text-green-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>{{
                        moneda.activo ? 'Desactivar' : 'Activar'
                    }}</TooltipContent>
                </Tooltip>
            </div>
        </td>

        <td
            v-for="campo in camposTerreno"
            :key="campo.key"
            class="px-6 py-4 text-gray-900 dark:text-gray-100"
        >
            <template v-if="campo.key === 'nombre'">
                {{ props.moneda.nombre ?? '' }}
            </template>
            <template v-else-if="campo.key === 'abreviacion'">
                {{ props.moneda.abreviacion ?? '' }}
            </template>
            <template v-else-if="campo.key === 'pais'">
                <div class="flex items-center gap-2">
                    <img
                        v-if="banderas[props.moneda.pais]"
                        :src="`https://flagcdn.com/w20/${banderas[props.moneda.pais]}.png`"
                        :alt="props.moneda.pais"
                        class="h-5 w-5 rounded-sm border"
                    />
                    <span>{{ props.moneda.pais }}</span>
                </div>
            </template>

            <template v-else-if="campo.key === 'tipo_cambio'">
                {{ props.moneda.tipo_cambio ?? '' }}
            </template>
            <template v-else-if="campo.key === 'updated_at'">
                <span :class="[props.moneda.updated_at ? '' : 'text-gray-400']">
                    {{ formatearFecha(props.moneda.updated_at) }}
                </span>
            </template>

            <template v-else-if="campo.key === 'activo'">
                <span
                    :class="
                        props.moneda.activo
                            ? 'font-semibold text-green-600'
                            : 'font-semibold text-red-900'
                    "
                >
                    {{ props.moneda.activo ? 'Activo' : 'Inactivo' }}
                </span>
            </template>
        </td>
    </tr>
    <tr v-if="props.moneda == null">
        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
            No hay registros de monedas.
        </td>
    </tr>

    <EditarMoneda
        :visible="editarVisible"
        :moneda="moneda"
        @update:visible="editarVisible = $event"
        @updated="emit('updated', $event)"
    />
</template>
