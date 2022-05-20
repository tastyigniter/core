<div
    class="media-manager"
    data-control="media-manager"
    data-alias="{{ $self->alias }}"
    data-max-upload-size="{{ $maxUploadSize }}"
    data-allowed-extensions='@json($allowedExtensions)'
    data-select-mode="{{ $selectMode }}"
    data-unique-id="{{ $self->getId() }}"
>
    <div id="{{ $self->getId('toolbar') }}" class="media-toolbar">
        {!! $self->makePartial('mediamanager/toolbar') !!}
    </div>

    <div id="notification"></div>

    <div class="media-container">
        <div class="row no-gutters">
            <div
                class="col-9 border-right wrap-none wrap-left"
                data-control="media-list"
            >
                <div id="{{ $self->getId('breadcrumb') }}" class="media-breadcrumb border-bottom">
                    {!! $self->makePartial('mediamanager/breadcrumb') !!}
                </div>

                <div id="{{ $self->getId('item-list') }}" class="media-list-container">
                    @if ($self->getSetting('uploads'))
                        {!! $self->makePartial('mediamanager/uploader') !!}
                    @endif

                    {!! $self->makePartial('mediamanager/item_list') !!}
                </div>
            </div>
            <div class="col-3">
                {!! $self->makePartial('mediamanager/sidebar') !!}
            </div>
        </div>
    </div>

    <div
        id="{{ $self->getId('statusbar') }}"
        data-control="media-statusbar">
        {!! $self->makePartial('mediamanager/statusbar') !!}
    </div>
    {!! $self->makePartial('mediamanager/forms') !!}
</div>

<div id="previewBox" style="display:none;"></div>
