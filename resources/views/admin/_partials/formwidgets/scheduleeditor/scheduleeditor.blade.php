<div
    id="{{ $self->getId('items-container') }}"
    class="field-scheduleeditor"
    data-control="scheduleeditor"
    data-alias="{{ $self->alias }}"
>
    <div
        id="{{ $self->getId('schedules') }}"
        role="tablist"
        aria-multiselectable="true"
    >
        {!! $self->makePartial('scheduleeditor/schedules') !!}
    </div>
</div>
