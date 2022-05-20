@if ($outsideTabs->hasFields())
    {!! $self->makePartial('form/form_section', ['tabs' => $outsideTabs]) !!}
@endif

@if ($primaryTabs->hasFields())
    {!! $self->makePartial('form/form_section', ['tabs' => $primaryTabs]) !!}
@endif

