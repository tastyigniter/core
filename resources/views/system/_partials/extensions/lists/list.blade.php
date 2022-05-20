{!! form_open(current_url(),
    [
        'id' => 'list-form',
        'role' => 'form',
        'method' => 'POST',
    ]
) !!}

<div class="list-extensions pt-3 px-3 pb-0">
    @if (count($records))
        {!! $self->makePartial('lists/list_body') !!}
    @else
        <div class="card bg-light border-none">
            <div class="card-body p-3">{{ $emptyMessage }}</div>
        </div>
    @endif
</div>

{!! form_close() !!}

{!! $self->makePartial('lists/list_pagination') !!}

{!! $self->makePartial('updates/recommended', ['itemType' => 'extension']) !!}
