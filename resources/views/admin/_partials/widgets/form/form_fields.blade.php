@foreach ($fields as $field)
    {!! $self->makePartial('form/field_container', ['field' => $field]) !!}
@endforeach
