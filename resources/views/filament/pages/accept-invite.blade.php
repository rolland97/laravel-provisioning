<x-filament-panels::page.simple>
    <form wire:submit="save" class="fi-form grid gap-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            {{ __('provisioning::provisioning.invite.submit') }}
        </x-filament::button>
    </form>
</x-filament-panels::page.simple>
