@if (!$self->hasCenter())
    <div class="text-warning fw-500 rounded">
        <b>@lang('igniter::admin.locations.alert_missing_map_center')</b>
    </div>
@elseif(!$self->isConfigured())
    <div class="text-warning fw-500 rounded">
        <b>@lang('igniter::admin.locations.alert_missing_map_config')</b>
    </div>
@else
    <div
        data-control="map-view"
        data-map-height="{{ $mapHeight }}"
        data-map-zoom="{{ $mapZoom }}"
        data-map-center='@json($mapCenter)'
        data-map-shape-selector="{{ $shapeSelector }}"
        data-map-editable-shape="{{ !$previewMode }}"
    >
        <div class="map-view"></div>
    </div>
@endif
