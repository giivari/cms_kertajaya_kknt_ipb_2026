<?php

namespace App\Filament\Forms\Components;

use App\Exceptions\LocationSearchException;
use App\Services\LocationSearchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class LocationPicker extends Field
{
    protected string $view = 'filament.forms.components.location-picker';

    protected string $latitudePath = 'latitude';

    protected string $longitudePath = 'longitude';

    protected ?string $addressPath = null;

    protected ?string $zoomPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->default([
                'query' => '',
                'results' => [],
                'message' => null,
                'message_type' => null,
                'selected' => null,
            ])
            ->dehydrated(false)
            ->registerActions([
                fn (LocationPicker $component): Action => $component->getSearchAction(),
                fn (LocationPicker $component): Action => $component->getSelectResultAction(),
            ]);
    }

    public function latitudePath(string $path): static
    {
        $this->latitudePath = $path;

        return $this;
    }

    public function longitudePath(string $path): static
    {
        $this->longitudePath = $path;

        return $this;
    }

    public function addressPath(?string $path): static
    {
        $this->addressPath = $path;

        return $this;
    }

    public function zoomPath(?string $path): static
    {
        $this->zoomPath = $path;

        return $this;
    }

    public function getLatitudeStatePath(): string
    {
        return $this->resolveRelativeStatePath($this->latitudePath);
    }

    public function getLongitudeStatePath(): string
    {
        return $this->resolveRelativeStatePath($this->longitudePath);
    }

    public function getZoomStatePath(): ?string
    {
        return $this->zoomPath === null ? null : $this->resolveRelativeStatePath($this->zoomPath);
    }

    /** @return array<int, array{display_name: string, latitude: string, longitude: string}> */
    public function getSearchResults(): array
    {
        $state = $this->getState();

        return is_array($state) && is_array($state['results'] ?? null) ? $state['results'] : [];
    }

    public function getSearchAction(): Action
    {
        return Action::make('searchLocations')
            ->label('Cari')
            ->icon('heroicon-o-magnifying-glass')
            ->action(function (LocationPicker $component, LocationSearchService $searchService): void {
                $state = $component->normalizedState();

                try {
                    $results = $searchService->search((string) ($state['query'] ?? ''));
                    $state['results'] = $results;
                    $state['selected'] = null;
                    $state['message_type'] = $results === [] ? 'empty' : 'success';
                    $state['message'] = $results === []
                        ? 'Lokasi tidak ditemukan. Coba kata kunci lain atau gunakan koordinat manual.'
                        : count($results).' hasil ditemukan. Pilih lokasi yang sesuai.';
                } catch (ValidationException $exception) {
                    $state['results'] = [];
                    $state['selected'] = null;
                    $state['message_type'] = 'error';
                    $state['message'] = collect($exception->errors())->flatten()->first()
                        ?? 'Pencarian lokasi tidak valid.';
                } catch (LocationSearchException $exception) {
                    $state['results'] = [];
                    $state['selected'] = null;
                    $state['message_type'] = 'error';
                    $state['message'] = $exception->getMessage();
                }

                $component->state($state);
            });
    }

    public function getSelectResultAction(): Action
    {
        return Action::make('selectLocationResult')
            ->color('gray')
            ->outlined()
            ->action(function (array $arguments, LocationPicker $component, Set $set): void {
                $index = filter_var($arguments['result'] ?? null, FILTER_VALIDATE_INT);
                $state = $component->normalizedState();
                $result = $index === false ? null : ($state['results'][$index] ?? null);

                if (! is_array($result)
                    || ! is_numeric($result['latitude'] ?? null)
                    || ! is_numeric($result['longitude'] ?? null)
                    || ! is_string($result['display_name'] ?? null)) {
                    $state['message_type'] = 'error';
                    $state['message'] = 'Hasil lokasi tidak valid. Jalankan pencarian kembali.';
                    $component->state($state);

                    return;
                }

                $set($component->latitudePath, $result['latitude'], shouldCallUpdatedHooks: true);
                $set($component->longitudePath, $result['longitude'], shouldCallUpdatedHooks: true);
                if ($component->addressPath !== null) {
                    $set($component->addressPath, $result['display_name'], shouldCallUpdatedHooks: true);
                }

                $state['selected'] = $index;
                $state['message_type'] = 'selected';
                $state['message'] = 'Lokasi dipilih. Anda dapat menyesuaikan titik pada peta atau alamat secara manual.';
                $component->state($state);
            });
    }

    /** @return array<string, mixed> */
    private function normalizedState(): array
    {
        $state = $this->getState();

        return is_array($state) ? $state : [
            'query' => '',
            'results' => [],
            'message' => null,
            'message_type' => null,
            'selected' => null,
        ];
    }
}
