<div class="row-fluid">
    {!! form_open(current_url(),
        [
            'id'     => 'edit-form',
            'role'   => 'form',
            'method' => 'PATCH',
        ]
    ) !!}

    {!! $this->toolbarWidget->render() !!}
    <div class="card shadow-sm mx-3">
        {!! $this->formWidget->render() !!}
    </div>
    {!! form_close() !!}
</div>
