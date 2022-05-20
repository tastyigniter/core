<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! $self->widgets['toolbar']->render() !!}

        {!! $self->makePartial('updates/search', ['itemType' => 'language']) !!}

        {!! $self->widgets['list_filter']->render() !!}

        {!! $self->widgets['list']->render() !!}
    </div>
</x-igniter.admin::layout>
