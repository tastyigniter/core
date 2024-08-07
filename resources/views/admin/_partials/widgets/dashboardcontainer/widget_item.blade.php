<div
    class="col col-sm-{{ $widget->getWidth() }} my-3"
>
    <div class="widget-item card {{ $widget->getCssClass() }} bg-white p-3 shadow-sm">
        <div class="widget-item-action">
            <a class="btn shadow-none handle pull-left"><i class="fa fa-arrows-alt"></i></a>
            <a
                class="btn shadow-none pull-right"
                data-control="remove-widget"
                aria-hidden="true"
            ><i class="fa fa-trash-alt text-danger"></i></a>
            <a
                class="btn shadow-none pull-right"
                data-control="edit-widget"
                data-bs-toggle="modal"
                data-bs-target="#{{ $widgetAlias }}-modal"
                data-handler="{{ $this->getEventHandler('onLoadUpdatePopup') }}"
            ><i class="fa fa-cog"></i></a>
        </div>

        <div id="{{ $widgetAlias }}">{!! $widget->render() !!}</div>

        <input type="hidden" data-widget-alias name="widgetAliases[]" value="{{ $widgetAlias }}"/>
        <input type="hidden" data-widget-priority name="widgetPriorities[]" value="{{ $widget->getPriority() }}"/>
    </div>

    <div
        class="modal slideInDown fade"
        id="{{ $widgetAlias }}-modal"
        tabindex="-1"
        role="dialog"
        aria-labelledby="{{ $widgetAlias }}-title"
        aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div
                id="{{ $widgetAlias }}-modal-content"
                class="modal-content"
            >
                {!! $this->makePartial('dashboardcontainer/widget_form', [
                    'widgetAlias' => $widgetAlias,
                    'widget' => $widget,
                    'widgetForm' => $this->getFormWidget($widgetAlias, $widget),
                ]) !!}
            </div>
        </div>
    </div>
</div>
