<div
    data-control="components"
    data-alias="{{ $self->alias }}"
    data-remove-handler="{{ $self->getEventHandler('onRemoveComponent') }}"
    data-sortable-container=".components-container"
>
    <div class="components d-flex">
        <div class="components-item components-picker">
            <div
                class="component btn btn-light p-4 h-100"
                data-component-control="load"
                data-component-context="create"
            >
                <b><i class="fa fa-plus"></i></b>
                <p class="text-muted mb-0">@lang($self->prompt)</p>
            </div>
        </div>

        <div
            id="{{ $self->getId('container') }}"
            class="components-container"
        >
            {!! $self->makePartial('container', ['components' => $components]) !!}
        </div>
    </div>
</div>
