<div
    id="{{ $field->getId('container') }}"
    class="input-group"
>
    <input
        type="text"
        id="{{ $field->getId() }}"
        value="{{ $value }}"
        placeholder="{{ $field->placeholder }}"
        class="form-control"
        autocomplete="off"
        pattern="-?\d+(\.\d+)?"
        maxlength="255"
        disabled
        {!! $field->getAttributes() !!}
    />

    <a
        class="btn btn-outline-default {{ $previewMode ? 'disabled' : '' }}"
        data-toggle="record-editor"
        data-handler="{{ $self->getEventHandler('onLoadRecord') }}"
    >@lang('igniter::admin.stocks.button_manage_stock')</a>
    <a
        class="btn btn-outline-default {{ $previewMode ? 'disabled' : '' }}"
        data-toggle="record-editor"
        data-handler="{{ $self->getEventHandler('onLoadHistory') }}"
    >@lang('igniter::admin.stocks.button_stock_history')</a>
</div>
