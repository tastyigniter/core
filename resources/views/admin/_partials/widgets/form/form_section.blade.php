@if ($tabs->suppressTabs)

    <div
        id="{{ $self->getId($tabs->section.'-tabs') }}"
        class="{{ $tabs->cssClass }}">
        <div class="form-fields">
            {!! $self->makePartial('form/form_fields', ['fields' => $tabs]) !!}
        </div>
    </div>

@else

    <div
        id="{{ $self->getId($tabs->section.'-tabs') }}"
        class="{{ $tabs->section }}-tabs {{ $tabs->cssClass }}"
        data-control="form-tabs"
        data-store-name="{{ $cookieKey }}">
        {!! $self->makePartial('form/form_tabs', ['tabs' => $tabs]) !!}
    </div>

@endif
