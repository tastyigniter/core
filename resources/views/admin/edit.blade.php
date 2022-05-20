<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! form_open([
            'id'     => 'edit-form',
            'role'   => 'form',
            'method' => 'PATCH',
        ]) !!}

        {!! $self->renderForm() !!}

        {!! form_close() !!}
    </div>
</x-igniter.admin::layout>
