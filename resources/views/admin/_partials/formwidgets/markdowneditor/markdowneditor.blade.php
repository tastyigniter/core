@if ($self->previewMode)
    <div class="form-control">{!! $value !!}</div>
@else
    <div
        id="{{ $self->getId() }}"
        class="field-markdowneditor size-{{ $size }}"
        data-control="markdowneditor"
    >
        <textarea
            name="{{ $name }}"
            id="{{ $self->getId('textarea') }}"
            rows="20"
            class="form-control"
            {!! $self->previewMode ? 'disabled="disabled"' : '' !!}
        >{!! $value !!}</textarea>
    </div>
@endif
