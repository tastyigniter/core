<div class="row-fluid">
    {!! $this->renderListToolbar() !!}
    <div class="card shadow-sm mx-3">
        {!! $this->renderListFilter() !!}
        {!! $this->renderList(null, true) !!}
    </div>
</div>
