<div class="row-fluid">
    {!! form_open(current_url(),
        [
            'id'     => 'list-form',
            'role'   => 'form',
            'method' => 'POST',
        ]
    ) !!}

    <div class="container-fluid">
        {!! $self->makePartial('lists/list_body') !!}
    </div>

    {!! form_close() !!}

    {!! $self->makePartial('lists/list_pagination') !!}

    {!! $self->makePartial('igniter.system::updates/recommended', ['itemType' => 'theme']) !!}
</div>
