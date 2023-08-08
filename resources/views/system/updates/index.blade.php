<div class="row-fluid">

  {!! $this->widgets['toolbar']->render() !!}

  <div class="card shadow-sm mx-3">
    @if (isset($updates) && ($updates['items']->isNotEmpty() || $updates['ignoredItems']->isNotEmpty()))
      <div id="updates">
        {!! $this->makePartial('updates/list') !!}
      </div>
    @else
          <div class="card-body" id="list-items">
              <h5 class="text-w-400 mb-0">@lang('igniter::system.updates.text_no_updates')</h5>
      </div>
    @endif
  </div>
</div>

{!! $this->makePartial('updates/carte') !!}
