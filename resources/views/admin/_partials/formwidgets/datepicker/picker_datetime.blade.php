<div class="input-group">
    <input
        type="text"
        id="{{ $self->getId('datetime') }}"
        class="form-control"
        autocomplete="off"
        value="{{ $value ? $value->format($formatAlias) : null }}"
        {!! $field->getAttributes() !!}
        @if ($self->previewMode) readonly="readonly" @endif
        data-control="datepicker"
        data-toggle="datetimepicker"
        data-target="#{{ $self->getId('datetime') }}"
        data-mode="{{ $self->mode }}"
        @if ($startDate) data-start-date="{{ $startDate }}" @endif
        @if ($endDate) data-end-date="{{ $endDate }}" @endif
        @if ($datesDisabled) data-dates-disabled="{{ $datesDisabled }}" @endif
        data-format="{{ $datePickerFormat }}"
    />
    <input
        type="hidden"
        name="{{ $field->getName() }}"
        value="{{ $value ? $value->format('Y-m-d H:i:s') : null }}"
        data-datepicker-value
    />
    <span class="input-group-text"><i class="fa fa-calendar-o"></i></span>
</div>
