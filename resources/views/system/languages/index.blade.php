<div class="row-fluid">
    {!! $this->widgets['toolbar']->render() !!}

    {!! $this->makePartial('updates/search', ['itemType' => 'language']) !!}

    <div class="card shadow-sm m-3">
        {!! $this->widgets['list_filter']->render() !!}

        {!! $this->widgets['list']->render() !!}
    </div>
</div>
