<div class="row-fluid">
    {!! form_open([
        'id'     => 'edit-form',
        'role'   => 'form',
        'method' => 'PATCH',
    ]) !!}

    {!! $this->renderFormToolbar() !!}

    <div class="card shadow-sm mx-3">
        {!! $this->renderForm([], true) !!}
    </div>

    {!! form_close() !!}
</div>
