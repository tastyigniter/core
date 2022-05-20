<div
    class="widget-container"
>
    <div
        id="{{ $self->getId('container-list') }}"
        class="widget-list row {{ !$self->canManage ?: 'add-delete' }}"
        data-container-widget
    >
        {!! $self->makePartial('widget_list') !!}
    </div>
</div>
