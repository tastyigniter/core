@if ($mapAreas)
    <div
        id="{{ $self->getId('areas') }}"
        class="map-areas"
        aria-multiselectable="true"
        data-control="areas"
    >
        @foreach ($mapAreas as $index => $mapArea)
            {!! $self->makePartial('maparea/area', ['index' => $index, 'area' => $mapArea]) !!}
        @endforeach
    </div>
@else
    <div class="card shadow-sm bg-light border-warning text-warning">
        <div class="card-body">
            <b>@lang('igniter::admin.locations.alert_delivery_area')</b>
        </div>
    </div>
@endif
