<div class="media-sidebar">
    <div data-media-preview-container></div>
</div>

<script type="text/template" data-media-single-selection-template>
    <div class="sidebar-preview-placeholder-container">
        <div class="sidebar-preview-toolbar">
            <button
                type="button"
                class="btn btn-sm btn-light text-danger"
                title="@lang('igniter::main.media_manager.button_cancel')"
                data-media-control="cancel-selection">
                <i class="fa fa-times"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light"
                title="@lang('igniter::main.media_manager.button_rename')"
                data-media-control="rename-item"
                data-swal-title="@lang('igniter::main.media_manager.text_file_name')"
                {{ !$this->getSetting('enable_rename') ? 'disabled' : '' }}>
                <i class="fa fa-pencil"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light"
                title="@lang('igniter::main.media_manager.button_move')"
                data-media-control="move-item"
                data-swal-title="@lang('igniter::main.media_manager.text_move_destination_folder')"
                {{ !$this->getSetting('enable_move') ? 'disabled' : '' }}>
                <i class="fa fa-folder-open"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light"
                title="@lang('igniter::main.media_manager.button_copy')"
                data-media-control="copy-item"
                data-swal-title="@lang('igniter::main.media_manager.text_copy_destination_folder')"
                {{ !$this->getSetting('enable_copy') ? 'disabled' : '' }}>
                <i class="fa fa-clipboard"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light text-danger"
                title="@lang('igniter::main.media_manager.button_delete')"
                data-media-control="delete-item"
                data-swal-confirm="@lang('igniter::admin.alert_warning_confirm')"
                {{ !$this->getSetting('enable_delete') ? 'disabled' : '' }}>
                <i class="fa fa-trash"></i>
            </button>
        </div>
        <div class="sidebar-preview-placeholder">
            <div data-media-preview-placeholder></div>
        </div>
        <div class="sidebar-preview-info">
            <p>{name}</p>
        </div>
        <div class="sidebar-preview-meta">
            <p><span class="small text-muted">@lang('igniter::main.media_manager.label_dimension') </span>{dimension}
            </p>
            <p><span class="small text-muted">@lang('igniter::main.media_manager.label_size') </span>{size}</p>
            <p><span class="small text-muted">URL </span><a href="{url}" target="_blank">Click here</a></p>
            <p><span class="small text-muted">@lang('igniter::main.media_manager.label_modified_date') </span>{modified}
            </p>
        </div>
        @if($chooseButton)
            <div class="sidebar-choose-btn">
                <button
                    class="btn btn-primary btn-block"
                    data-control="media-choose">
                    @lang($chooseButtonText)
                </button>
            </div>
        @endif
    </div>
</script>

<script type="text/template" data-media-multi-selection-template>
    <div class="sidebar-preview-placeholder-container">
        <div class="sidebar-preview-toolbar">
            <button
                type="button"
                class="btn btn-sm btn-light text-danger"
                title="@lang('igniter::main.media_manager.button_cancel')"
                data-media-control="cancel-selection">
                <i class="fa fa-times"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light"
                title="@lang('igniter::main.media_manager.button_move')"
                data-media-control="move-item"
                {{ !$this->getSetting('enable_move') ? 'disabled' : '' }}>
                <i class="fa fa-folder-open"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light"
                title="@lang('igniter::main.media_manager.button_copy')"
                data-media-control="copy-item"
                {{ !$this->getSetting('enable_copy') ? 'disabled' : '' }}>
                <i class="fa fa-clipboard"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-light text-danger"
                title="@lang('igniter::main.media_manager.button_delete')"
                data-media-control="delete-item"
                {{ !$this->getSetting('enable_delete') ? 'disabled' : '' }}>
                <i class="fa fa-trash"></i>
            </button>
        </div>
        <div class="sidebar-preview-placeholder">
            <i class="fa fa-clone fa-4x"></i>
        </div>
        <div class="sidebar-preview-info">
            <p class="fa-2x" data-media-total-size>{total}</p>
            <p><span class="text-muted small">@lang('igniter::main.media_manager.text_items_selected')</span></p>
        </div>
        @if($chooseButton)
            <div class="sidebar-choose-btn">
                <button
                    class="btn btn-primary btn-block"
                    data-control="media-choose"
                >@lang($chooseButtonText)</button>
            </div>
        @endif
    </div>
</script>

<script type="text/template" data-media-no-selection-template>
    <div></div>
</script>

<script type="text/template" data-media-image-selection-template>
    <img class="img-responsive" src="{src}">
</script>

<script type="text/template" data-media-video-selection-template>
    <div class="ratio ratio-1x1">
        <video src="{src}" controls>
            <div class="p-3">Your browser doesn't support HTML5 video.</div>
        </video>
    </div>
</script>

<script type="text/template" data-media-audio-selection-template>
    <audio src="{src}" controls>
        <div class="p-3">Your browser doesn't support HTML5 audio.</div>
    </audio>
</script>

<script type="text/template" data-media-file-selection-template>
    <div class="media-icon">
        <i class="fa fa-4x fa-{fileType}"></i>
    </div>
</script>
