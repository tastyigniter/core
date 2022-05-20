@if (count($components))
    @foreach ($components as $component)
        {!! $self->makePartial('component', [
            'component' => $component,
        ]) !!}
    @endforeach
@endif
