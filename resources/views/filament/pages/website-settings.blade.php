<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 flex flex-wrap gap-3">
            {{ $this->previewAction }}
            <x-filament::button type="submit" wire:target="save">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
