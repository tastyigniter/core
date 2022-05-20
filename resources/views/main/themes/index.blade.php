<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">
        {!! $self->widgets['toolbar']->render() !!}

        {!! $self->makePartial('igniter.system::updates/search', ['itemType' => 'theme']) !!}

        {!! $self->widgets['list']->render() !!}
    </div>
</x-igniter.admin::layout>
