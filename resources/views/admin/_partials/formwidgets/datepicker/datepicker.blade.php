@if ($self->previewMode)
    <div class="form-control-static">{{ $value ? $value->format($formatAlias) : null }}</div>
@else

    <div
        id="{{ $self->getId() }}"
        class="control-datepicker"
    >
        {!! $self->makePartial('datepicker/picker_'.$mode) !!}
    </div>

@endif
