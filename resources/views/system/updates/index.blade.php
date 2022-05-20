<x-igniter.admin::layout :self="$self">
    <div class="row-fluid">

        {!! $self->widgets['toolbar']->render() !!}

        @if (!empty($updates['items']) || !empty($updates['ignoredItems']))
            <div id="updates">
                {!! $self->makePartial('updates/list') !!}
            </div>
        @else
            <div class="panel panel-light">
                <div class="panel-body" id="list-items">
                    <h5 class="text-w-400 mb-0">@lang('igniter::system.updates.text_no_updates')</h5>
                </div>
            </div>
        @endif
    </div>

    {!! $self->makePartial('updates/carte') !!}
</x-igniter.admin::layout>
