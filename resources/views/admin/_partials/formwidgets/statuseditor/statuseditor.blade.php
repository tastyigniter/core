<div
    id="{{ $self->getId() }}"
    class="control-statuseditor"
    data-control="status-editor"
    data-alias="{{ $self->alias }}"
>
    {!! $self->makePartial('statuseditor/info') !!}
</div>
