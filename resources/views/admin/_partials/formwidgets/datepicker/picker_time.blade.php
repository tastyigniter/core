<div
    class="input-group"
    data-control="clockpicker"
    data-autoclose="true">
    <input
        type="text"
        name="{{ $field->getName() }}"
        id="{{ $self->getId('time') }}"
        class="form-control"
        autocomplete="off"
        value="{{ $value ? $value->format($timeFormat) : null }}"
        {!! $field->getAttributes() !!}
        @if ($self->previewMode) readonly="readonly" @endif
    />
    <span class="input-group-text"><i class="fa fa-clock-o"></i></span>
</div>
