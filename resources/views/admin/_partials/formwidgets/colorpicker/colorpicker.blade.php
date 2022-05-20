<div
    class="input-group control-colorpicker"
    data-control="colorpicker"
    data-swatches-colors='@json($availableColors)'
    data-use-alpha="{{$showAlpha ? 'true' : 'false'}}"
>
    <div class="component input-group-text"><i class="fa fa-square"></i></div>
    <input
        type="text"
        id="{{ $self->getId('input') }}"
        name="{{ $name }}"
        class="form-control"
        value="{{ $value }}"
        {!! ($self->disabled || $self->previewMode) ? 'disabled="disabled"' : '' !!}
        {!! ($self->readOnly) ? 'readonly="readonly"' : '' !!}
    />
</div>
