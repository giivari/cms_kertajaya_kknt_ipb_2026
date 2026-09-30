import L from 'leaflet';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

const DEFAULT_CENTER = [-2.548926, 118.0148634];
const DEFAULT_ZOOM = 5;

function validCoordinates(latitude, longitude) {
    const lat = Number(latitude);
    const lng = Number(longitude);

    return Number.isFinite(lat)
        && Number.isFinite(lng)
        && lat >= -90
        && lat <= 90
        && lng >= -180
        && lng <= 180;
}

function normalizedZoom(value) {
    const zoom = Number(value);

    return Number.isFinite(zoom) && zoom >= 1 && zoom <= 19 ? zoom : 15;
}

function mount(element, options) {
    if (element.__villageLocationPicker) {
        element.__villageLocationPicker.sync(options.latitude, options.longitude, options.zoom);

        return element.__villageLocationPicker;
    }

    const hasInitialCoordinates = validCoordinates(options.latitude, options.longitude);
    const initialCoordinates = hasInitialCoordinates
        ? [Number(options.latitude), Number(options.longitude)]
        : DEFAULT_CENTER;
    const map = L.map(element, { scrollWheelZoom: false })
        .setView(initialCoordinates, hasInitialCoordinates ? normalizedZoom(options.zoom) : DEFAULT_ZOOM);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    let marker = null;

    const emitCoordinates = (coordinates) => {
        options.onChange(
            Number(coordinates.lat).toFixed(7),
            Number(coordinates.lng).toFixed(7),
        );
    };

    const placeMarker = (coordinates, shouldEmit = false) => {
        if (! marker) {
            marker = L.marker(coordinates, { draggable: true }).addTo(map);
            marker.on('dragend', () => emitCoordinates(marker.getLatLng()));
        } else {
            marker.setLatLng(coordinates);
        }

        if (shouldEmit) emitCoordinates(coordinates);
    };

    if (hasInitialCoordinates) placeMarker(initialCoordinates);

    map.on('click', (event) => placeMarker(event.latlng, true));

    const handle = {
        sync(latitude, longitude, zoom) {
            if (! validCoordinates(latitude, longitude)) return;

            const coordinates = [Number(latitude), Number(longitude)];
            placeMarker(coordinates);
            map.setView(coordinates, normalizedZoom(zoom));
        },
        destroy() {
            map.remove();
            delete element.__villageLocationPicker;
        },
    };

    element.__villageLocationPicker = handle;
    requestAnimationFrame(() => map.invalidateSize());

    return handle;
}

window.VillageLocationPicker = { mount };
