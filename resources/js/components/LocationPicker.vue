<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

interface Coordinates {
    latitude: number;
    longitude: number;
}

const props = withDefaults(defineProps<{
    latitude?: number | string | null;
    longitude?: number | string | null;
    height?: string;
}>(), {
    latitude: null,
    longitude: null,
    height: '320px',
});

const emit = defineEmits<{
    (event: 'update:coordinates', value: Coordinates | null): void;
}>();

const mapEl = ref<HTMLElement | null>(null);
const locating = ref(false);
const locationError = ref('');

let map: L.Map | null = null;
let marker: L.Marker | null = null;

const defaultCenter: [number, number] = [-6.2, 106.816666];

const parseCoordinate = (value: number | string | null | undefined, min: number, max: number) => {
    if (value === null || value === undefined || value === '') return null;

    const parsed = Number(value);

    return Number.isFinite(parsed) && parsed >= min && parsed <= max ? parsed : null;
};

const coordinates = computed<Coordinates | null>(() => {
    const latitude = parseCoordinate(props.latitude, -90, 90);
    const longitude = parseCoordinate(props.longitude, -180, 180);

    return latitude === null || longitude === null ? null : { latitude, longitude };
});

const formatted = computed(() => {
    if (!coordinates.value) return 'Belum dipilih';

    return `${coordinates.value.latitude.toFixed(6)}, ${coordinates.value.longitude.toFixed(6)}`;
});

const pinIcon = L.divIcon({
    className: 'location-pin',
    html: '<span class="location-pin-dot"></span>',
    iconSize: [28, 36],
    iconAnchor: [14, 34],
});

const rounded = (value: number) => Number(value.toFixed(6));

const ensureMarker = (latitude: number, longitude: number) => {
    if (!map) return;

    if (marker) {
        marker.setLatLng([latitude, longitude]);
        return;
    }

    marker = L.marker([latitude, longitude], {
        icon: pinIcon,
        draggable: true,
        keyboard: true,
        title: 'Lokasi warehouse',
    })
        .addTo(map)
        .on('dragend', () => {
            const position = marker?.getLatLng();
            if (!position) return;

            emit('update:coordinates', {
                latitude: rounded(position.lat),
                longitude: rounded(position.lng),
            });
        });
};

const removeMarker = () => {
    marker?.remove();
    marker = null;
};

const selectLocation = (latitude: number, longitude: number, zoom = 16) => {
    ensureMarker(latitude, longitude);
    map?.setView([latitude, longitude], Math.max(map.getZoom(), zoom));
    emit('update:coordinates', {
        latitude: rounded(latitude),
        longitude: rounded(longitude),
    });
};

const initializeMap = () => {
    if (!mapEl.value || map) return;

    const initial = coordinates.value;

    map = L.map(mapEl.value, {
        center: initial ? [initial.latitude, initial.longitude] : defaultCenter,
        zoom: initial ? 16 : 11,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>',
    }).addTo(map);

    map.on('click', (event: L.LeafletMouseEvent) => {
        selectLocation(event.latlng.lat, event.latlng.lng);
    });

    L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map);

    if (initial) {
        ensureMarker(initial.latitude, initial.longitude);
    }
};

const clearLocation = () => {
    removeMarker();
    locationError.value = '';
    emit('update:coordinates', null);
};

const locateMe = () => {
    if (!navigator.geolocation) {
        locationError.value = 'Browser tidak mendukung geolokasi.';
        return;
    }

    locating.value = true;
    locationError.value = '';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            selectLocation(position.coords.latitude, position.coords.longitude, 17);
            locating.value = false;
        },
        () => {
            locationError.value = 'Lokasi tidak dapat diakses. Pilih manual lewat peta.';
            locating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

watch(coordinates, (value) => {
    if (!map) return;

    if (!value) {
        removeMarker();
        return;
    }

    ensureMarker(value.latitude, value.longitude);
});

onMounted(() => {
    nextTick(initializeMap);
});

onBeforeUnmount(() => {
    marker?.remove();
    marker = null;
    map?.remove();
    map = null;
});
</script>

<template>
    <div class="location-picker">
        <div class="location-toolbar">
            <div>
                <strong>Pilih lokasi di peta</strong>
                <span>Klik peta atau geser pin untuk mengisi latitude dan longitude.</span>
            </div>
            <code>{{ formatted }}</code>
        </div>

        <div ref="mapEl" class="location-map" :style="{ height: props.height }" />

        <div class="location-actions">
            <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="locating" @click="locateMe">
                <i class="mdi mdi-crosshairs-gps me-1"></i>
                {{ locating ? 'Mencari lokasi...' : 'Gunakan lokasi saya' }}
            </button>
            <button class="btn btn-sm btn-outline-secondary" type="button" @click="clearLocation">
                <i class="mdi mdi-map-marker-off-outline me-1"></i>
                Hapus pin
            </button>
            <span v-if="locationError" class="location-error">{{ locationError }}</span>
        </div>
    </div>
</template>

<style scoped>
.location-picker {
    overflow: hidden;
    border: 1px solid var(--app-border);
    border-radius: 0.8rem;
    background: var(--app-surface);
}

.location-toolbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 0.9rem;
    border-bottom: 1px solid var(--app-border);
}

.location-toolbar strong {
    display: block;
    margin-bottom: 0.15rem;
    color: var(--app-ink);
    font-size: 0.85rem;
}

.location-toolbar span {
    display: block;
    color: var(--app-muted);
    font-size: 0.78rem;
}

.location-toolbar code {
    flex: 0 0 auto;
    color: var(--app-accent-ink);
    font-size: 0.78rem;
    white-space: nowrap;
}

.location-map {
    width: 100%;
    background: var(--app-surface-3);
}

.location-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 0.9rem;
    border-top: 1px solid var(--app-border);
}

.location-error {
    color: var(--app-danger-ink);
    font-size: 0.78rem;
}

:deep(.leaflet-container) {
    font-family: inherit;
    background: var(--app-surface-3);
}

:deep(.location-pin) {
    background: transparent;
    border: 0;
}

:deep(.location-pin-dot) {
    display: block;
    width: 18px;
    height: 18px;
    margin: 7px auto 0;
    border: 3px solid #fff;
    border-radius: 50%;
    background: var(--app-accent);
    box-shadow: 0 0 0 3px rgba(59, 76, 240, 0.22), 0 4px 12px rgba(15, 23, 42, 0.35);
}
</style>
