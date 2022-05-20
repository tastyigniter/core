<input type="hidden" data-media-type="current-folder" value="{{ $currentFolder }}"/>

@if ($items)
    {!! $self->makePartial('mediamanager/list_grid') !!}
@else
    <p>@lang('igniter::admin.text_empty')</p>
@endif
