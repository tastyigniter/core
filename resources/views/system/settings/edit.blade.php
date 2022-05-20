<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! form_open(current_url(),
            [
                'id'     => 'edit-form',
                'role'   => 'form',
                'method' => 'PATCH',
            ]
        ) !!}

        {!! $self->toolbarWidget->render() !!}
        {!! $self->formWidget->render() !!}

        {!! form_close() !!}
    </div>
</x-igniter.admin::layout>
