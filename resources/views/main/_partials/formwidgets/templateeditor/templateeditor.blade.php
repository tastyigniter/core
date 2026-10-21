@unless($this->previewMode)
    <div
        id="{{ $this->getId() }}"
        data-control="template-editor"
        data-alias="{{ $this->alias }}"
    >
        {!! $this->makePartial('templateeditor/modal') !!}

        <div
            id="{{ $this->getId('container') }}"
        >
            {!! $this->makePartial('templateeditor/container') !!}
        </div>
    </div>
@else
    <div class="card shadow-sm border-warning text-warning">
        <div class="card-body">
            <b>@lang($this->model->getTheme()?->child === false ? 'igniter::system.themes.alert_theme_locked_no_child' : 'igniter::system.themes.alert_theme_locked')</b>
        </div>
    </div>
@endunless
