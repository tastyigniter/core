<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! form_open([
            'id'     => 'preview-form',
            'role'   => 'form',
        ]) !!}

        {!! $self->renderForm(['preview' => true]) !!}

        {!! form_close() !!}
    </div>
</x-igniter.admin::layout>
