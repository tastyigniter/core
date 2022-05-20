@foreach ($self->widgets as $widgetAlias => $widgetInfo)
    {!! $self->makePartial('widget_item', [
        'widgetAlias' => $widgetAlias,
        'widget'      => $widgetInfo['widget'],
        'priority'    => $widgetInfo['priority'],
    ]) !!}
@endforeach
