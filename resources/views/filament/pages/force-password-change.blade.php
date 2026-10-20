<x-filament-panels::page>
    <form wire:submit="save" class="fi-form grid gap-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                {{ __('provisioning::provisioning.force_change.submit') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
