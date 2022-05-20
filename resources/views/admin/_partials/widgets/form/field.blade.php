@unless ($field->hidden)
    @unless ($self->showFieldLabels($field))
        {!! $self->renderFieldElement($field) !!}
    @else
        @if ($field->label)
            <label for="{{ $field->getId() }}" class="form-label">@lang($field->label)</label>
        @endif

        @if ($field->comment && $field->commentPosition == 'above')
            <p class="help-block before-field">
                @if ($field->commentHtml) {!! lang($field->comment) !!} @else @lang($field->comment) @endif
            </p>
        @endif

        {!! $self->renderFieldElement($field) !!}

        @if ($field->comment && $field->commentPosition == 'below')
            <p class="help-block">
                @if ($field->commentHtml) {!! lang($field->comment) !!} @else @lang($field->comment) @endif
            </p>
        @endif

    @endunless
@endunless
