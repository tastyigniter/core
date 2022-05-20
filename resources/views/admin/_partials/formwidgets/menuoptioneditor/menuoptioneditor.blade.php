<div
    id="{{ $self->getId() }}"
    class="field-menu-option-editor"
    data-control="menu-option-editor"
    data-alias="{{ $self->alias }}"
>
    <div
        id="{{ $self->getId('toolbar') }}"
        class="mb-3"
    >
        {!! $self->makePartial('menuoptioneditor/toolbar') !!}
    </div>

    <div
        id="{{ $self->getId('items') }}"
        role="tablist"
        aria-multiselectable="true">
        {!! $self->makePartial('menuoptioneditor/items') !!}
    </div>
</div>
