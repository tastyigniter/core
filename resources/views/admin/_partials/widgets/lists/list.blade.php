{!! form_open([
    'id' => 'list-form',
    'role' => 'form',
    'method' => 'POST',
]) !!}

<div
    id="{{ $self->getId() }}"
    class="list-table table-responsive"
>
    <table
        id="{{ $self->getId('table') }}"
        class="table table-hover mb-0 border-bottom"
    >
        <thead>
        @if ($showCheckboxes)
            {!! $self->makePartial('lists/list_actions') !!}
        @endif
        {!! $self->makePartial('lists/list_head') !!}
        </thead>
        <tbody>
        @if(count($records))
            {!! $self->makePartial('lists/list_body') !!}
        @else
            <tr>
                <td colspan="99" class="text-center">{{ $emptyMessage }}</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>

{!! form_close() !!}

{!! $self->makePartial('lists/list_pagination') !!}

@if ($showSetup)
    {!! $self->makePartial('lists/list_setup') !!}
@endif
