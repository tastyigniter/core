<div
    data-control="components"
    data-alias="{{ $this->alias }}"
    data-remove-handler="{{ $this->getEventHandler('onRemoveComponent') }}"
    data-sortable-container=".components-container"
>
    <div class="components list-group list-group-flush d-flex">
        <div
            id="{{ $this->getId('container') }}"
            class="components-container"
        >
            {!! $this->makePartial('container', ['components' => $components]) !!}
        </div>
        <div
            class="list-group-item list-group-item-action border components-item components-picker"
            data-component-control="load"
            data-component-context="create"
        >
            <i class="fa fa-plus"></i>
            <span class="text-muted">@lang($this->prompt)</span>
        </div>

    </div>
</div>
