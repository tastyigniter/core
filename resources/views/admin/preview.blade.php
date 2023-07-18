<div class="row-fluid">
    {!! form_open([
        'id'     => 'preview-form',
        'role'   => 'form',
    ]) !!}

    {!! $this->renderFormToolbar() !!}

    <div class="card shadow-sm mx-3">
        {!! $this->renderForm(['preview' => true]) !!}
    </div>

    {!! form_close() !!}
</div>
