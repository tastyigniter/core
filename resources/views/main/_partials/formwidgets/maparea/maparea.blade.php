<div
    id="{{ $self->getId() }}"
    data-control="map-area"
    data-alias="{{ $self->alias }}"
    data-remove-handler="{{ $self->getEventHandler('onDeleteArea') }}"
    data-sortable-container="#{{ $self->getId('areas') }}"
    data-sortable-handle=".{{ $self->getId('areas') }}-handle"
>
    <div class="map-area-container my-3" id="{{ $self->getId('items') }}">
        {!! $self->makePartial('maparea/areas') !!}
    </div>

    <div
        id="{{ $self->getId('toolbar') }}"
        class="map-area-toolbar"
    >
        {!! $self->makePartial('maparea/toolbar') !!}
    </div>
</div>
