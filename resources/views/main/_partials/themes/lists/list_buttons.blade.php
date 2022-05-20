{!! $self->makePartial('lists/list_button', ['record' => $theme, 'column' => $self->getColumn('source')]) !!}

@if ($theme->getTheme()->isActive() && $theme->getTheme()->hasCustomData())
    {!! $self->makePartial('lists/list_button', ['record' => $theme, 'column' => $self->getColumn('edit')]) !!}
@endif

{!! $self->makePartial('lists/list_button', ['record' => $theme, 'column' => $self->getColumn('default')]) !!}

{!! $self->makePartial('lists/list_button', ['record' => $theme, 'column' => $self->getColumn('delete')]) !!}
