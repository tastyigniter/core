<div
    id="{{ $self->getId() }}"
    class="calendar-widget"
    data-control="calendar"
    data-alias="{{ $self->alias }}"
    data-aspect-ratio="{{ $aspectRatio }}"
    data-editable="{{ $editable ? 'true' : 'false' }}"
    data-day-max-event-rows="{{ $eventLimit }}"
    data-initial-date="{{ $defaultDate }}"
    data-locale={{ setting('default_language') }}
>

    @if($editable)
        <script type="text/template" data-calendar-popover-template>
            {!! $self->renderPopoverPartial() !!}
        </script>
    @endif
</div>
