@php
    use App\Filament\Support\AdminTable;

    $badge = AdminTable::filterBadge($action->getBadge());
@endphp

<x-filament::icon-button
    :attributes="\Filament\Support\prepare_inherited_attributes($action->getExtraAttributeBag())->class(['fi-ac-icon-btn-action'])"
    :badge="$badge"
    :badge-color="$action->getBadgeColor($badge)"
    :color="$action->getColor()"
    :disabled="$action->isDisabled()"
    :icon="$action->getIcon()"
    :icon-size="$action->getIconSize()"
    :label="$action->getLabel()"
    :loading-indicator="false"
    :size="$action->getSize()"
    :tooltip="$action->getTooltip()"
/>
