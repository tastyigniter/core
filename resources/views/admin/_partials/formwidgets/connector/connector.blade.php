<div
    id="{{ $self->getId('items-container') }}"
    class="field-connector"
    data-control="connector"
    data-alias="{{ $self->alias }}"
    data-sortable-container="#{{ $self->getId('items') }}"
    data-sortable-handle=".{{ $self->getId('items') }}-handle"
    data-editable="{{ ($self->previewMode || !$self->editable) ? 'false' : 'true' }}"
>
    <div
        id="{{ $self->getId('items') }}"
        role="tablist"
        aria-multiselectable="true">
        {!! $self->makePartial('connector/connector_items') !!}
    </div>
</div>
