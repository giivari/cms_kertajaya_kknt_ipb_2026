@php
    $state = is_array($getState()) ? $getState() : [];
    $results = $getSearchResults();
    $message = $state['message'] ?? null;
    $messageType = $state['message_type'] ?? null;
    $selected = $state['selected'] ?? null;
    $searchAction = $getAction('searchLocations');
    $selectAction = $getAction('selectLocationResult');
    $latitudeStatePath = $getLatitudeStatePath();
    $longitudeStatePath = $getLongitudeStatePath();
    $zoomStatePath = $getZoomStatePath();
    $mapId = 'location-picker-'.str_replace(['.', ':'], '-', $getKey());
@endphp

@assets
    @vite('resources/js/admin-location-picker.js')
@endassets

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-5">
        <div>
            <label for="{{ $mapId }}-search" class="mb-2 block text-sm font-medium text-gray-950 dark:text-white">
                Cari Lokasi
            </label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="fi-input-wrp flex-1">
                    <input
                        id="{{ $mapId }}-search"
                        type="search"
                        wire:model="{{ $getStatePath() }}.query"
                        maxlength="160"
                        autocomplete="off"
                        placeholder="Nama tempat atau alamat..."
                        class="fi-input block w-full border-0 bg-transparent px-3 py-2 text-base text-gray-950 outline-none dark:text-white sm:text-sm"
                    >
                </div>
                {{ $searchAction }}
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Pencarian dijalankan hanya saat tombol Cari dipilih. Jika layanan tidak tersedia, gunakan koordinat manual.
            </p>
        </div>

        @if ($message)
            <div
                role="status"
                @class([
                    'rounded-xl border px-3 py-2 text-sm',
                    'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300' => $messageType === 'error',
                    'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-300' => $messageType === 'empty',
                    'border-success-200 bg-success-50 text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300' => in_array($messageType, ['success', 'selected'], true),
                ])
            >
                {{ $message }}
            </div>
        @endif

        @if ($results !== [])
            <fieldset>
                <legend class="mb-2 text-sm font-medium text-gray-950 dark:text-white">Hasil Pencarian</legend>
                <ul class="space-y-2">
                    @foreach ($results as $index => $result)
                        @php
                            $resultAction = (clone $selectAction)(['result' => $index]);
                            $resultAction
                                ->label($result['display_name'])
                                ->extraAttributes([
                                    'class' => 'w-full justify-start text-left',
                                    'aria-pressed' => $selected === $index ? 'true' : 'false',
                                ], merge: true);
                        @endphp
                        <li>{{ $resultAction }}</li>
                    @endforeach
                </ul>
            </fieldset>
        @endif

        <div
            x-data="{
                latitude: $wire.entangle({{ \Illuminate\Support\Js::from($latitudeStatePath) }}).live,
                longitude: $wire.entangle({{ \Illuminate\Support\Js::from($longitudeStatePath) }}).live,
                zoom: {{ $zoomStatePath ? '$wire.entangle('.\Illuminate\Support\Js::from($zoomStatePath).').live' : '15' }},
                picker: null,
                startTimer: null,
                isDestroyed: false,
                init() {
                    const start = () => {
                        if (this.isDestroyed) return;
                        if (! window.VillageLocationPicker) {
                            this.startTimer = setTimeout(start, 40);
                            return;
                        }

                        this.picker = window.VillageLocationPicker.mount(this.$refs.map, {
                            latitude: this.latitude,
                            longitude: this.longitude,
                            zoom: this.zoom,
                            onChange: (latitude, longitude) => {
                                this.latitude = latitude;
                                this.longitude = longitude;
                            },
                        });

                        this.$watch('latitude', () => this.syncPicker());
                        this.$watch('longitude', () => this.syncPicker());
                        this.$watch('zoom', () => this.syncPicker());
                    };

                    start();
                },
                syncPicker() {
                    this.picker?.sync(this.latitude, this.longitude, this.zoom);
                },
                destroy() {
                    this.isDestroyed = true;
                    clearTimeout(this.startTimer);
                    this.picker?.destroy();
                },
            }"
            class="space-y-2"
        >
            <p class="text-sm font-medium text-gray-950 dark:text-white">Peta</p>
            <div
                x-ref="map"
                wire:ignore
                class="h-80 w-full overflow-hidden rounded-xl border border-gray-300 bg-gray-100 dark:border-gray-700 dark:bg-gray-900"
                aria-label="Pilih titik lokasi pada peta"
            ></div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Klik peta atau geser penanda untuk menyesuaikan titik.
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300" aria-live="polite">
                <span class="font-medium">Koordinat terpilih:</span>
                <span x-show="latitude !== null && latitude !== '' && longitude !== null && longitude !== ''">
                    <span x-text="latitude"></span>, <span x-text="longitude"></span>
                </span>
                <span x-show="latitude === null || latitude === '' || longitude === null || longitude === ''">
                    belum dipilih
                </span>
            </p>
        </div>
    </div>
</x-dynamic-component>
