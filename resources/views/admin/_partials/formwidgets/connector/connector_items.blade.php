@forelse ($fieldItems as $fieldItem)
    {!! $self->makePartial('connector/connector_item', [
        'item' => $fieldItem,
        'index' => $loop->iteration,
    ]) !!}
@empty
    @lang($emptyMessage)
@endforelse
