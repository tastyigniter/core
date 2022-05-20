<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! $self->widgets['toolbar']->render() !!}

        {!! $self->makePartial('updates/search', ['itemType' => 'extension']) !!}

        {!! $self->widgets['list_filter']->render() !!}

        {!! $self->widgets['list']->render() !!}
    </div>
</x-igniter.admin::layout>
