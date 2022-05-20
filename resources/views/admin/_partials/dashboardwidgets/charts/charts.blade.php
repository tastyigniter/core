<div class="dashboard-widget widget-charts">
    <h6 class="widget-title">@lang($self->property('title'))</h6>
    <div
        class="chart-container"
        data-control="chart"
        data-alias="{{ $self->alias }}"
        data-range-selector="#{{ $self->alias }}-daterange"
        data-range-parent-selector="#{{ $self->alias }}-daterange-parent"
        data-range-format="{{ $self->property('rangeFormat') }}"
    >
        <div
            id="{{ $self->alias }}-daterange-parent"
            class="chart-toolbar d-flex justify-content-end pb-3"
        >
            <button
                id="{{ $self->alias }}-daterange"
                class="btn btn-light btn-sm"
                data-control="daterange"
            >
                <i class="fa fa-calendar"></i>&nbsp;&nbsp;
                <span>@lang('igniter::admin.dashboard.text_select_range')</span>&nbsp;&nbsp;
                <i class="fa fa-caret-down"></i>
            </button>
        </div>
        <div class="chart-canvas">
            <canvas
                id="{{ $self->alias }}"
            ></canvas>
        </div>
    </div>
</div>
