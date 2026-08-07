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

function popupContent(item) {
    const wrapper = document.createElement('div');
    wrapper.className = 'location-popup';

    if (item.dataset.image) {
        const image = document.createElement('img');
        image.src = item.dataset.image;
        image.alt = '';
        image.loading = 'lazy';
        image.className = 'location-popup__image';
        wrapper.append(image);
    }

    const category = document.createElement('p');
    category.className = 'location-popup__category';
    category.textContent = item.dataset.category;
    wrapper.append(category);

    const name = document.createElement('h3');
    name.className = 'location-popup__name';
    name.textContent = item.dataset.name;
    wrapper.append(name);

    if (item.dataset.summary) {
        const summary = document.createElement('p');
        summary.className = 'location-popup__summary';
        summary.textContent = item.dataset.summary;
        wrapper.append(summary);
    }

    const link = document.createElement('a');
    link.href = item.dataset.url;
    link.className = 'location-popup__link';
    link.textContent = 'Lihat detail';
    wrapper.append(link);

    const gmapsLink = document.createElement('a');
    gmapsLink.href = `https://www.google.com/maps/search/?api=1&query=${item.dataset.latitude},${item.dataset.longitude}`;
    gmapsLink.target = '_blank';
    gmapsLink.rel = 'noopener noreferrer';
    gmapsLink.className = 'location-popup__link';
    gmapsLink.style.display = 'block';
    gmapsLink.textContent = 'Buka Google Maps ↗';
    wrapper.append(gmapsLink);

    return wrapper;
}

function initializeLocationMap() {
    const mapElement = document.getElementById('locations-map');
    const items = [...document.querySelectorAll('.js-location-item')];

    if (! mapElement || items.length === 0 || mapElement.dataset.initialized === 'true') {
        return;
    }

    mapElement.dataset.initialized = 'true';

    const map = L.map(mapElement, {
        scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const markers = new Map();
    const bounds = [];

    items.forEach((item) => {
        const latitude = Number(item.dataset.latitude);
        const longitude = Number(item.dataset.longitude);

        if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
            return;
        }

        const marker = L.marker([latitude, longitude])
            .addTo(map)
            .bindPopup(popupContent(item));

        markers.set(item.dataset.locationId, marker);
        bounds.push([latitude, longitude]);

        const focusMarker = () => {
            map.setView([latitude, longitude], Math.max(map.getZoom(), 16), { animate: true });
            marker.openPopup();
        };

        item.addEventListener('click', (event) => {
            if (! event.target.closest('a')) {
                focusMarker();
            }
        });
        item.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                focusMarker();
            }
        });
    });

    if (bounds.length === 1) {
        map.setView(bounds[0], 16);
        markers.values().next().value.openPopup();
    } else {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
    }
}

document.addEventListener('DOMContentLoaded', initializeLocationMap);
