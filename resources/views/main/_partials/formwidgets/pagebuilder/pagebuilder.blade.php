<div
    id="{{ $this->getId() }}"
    class="page-builder{{ $this->previewMode ? ' is-preview' : '' }}"
    data-control="page-builder"
>
    <input
        type="hidden"
        name="{{ $field->getName() }}"
        value="{{ json_encode($sections) }}"
        data-page-builder-input
    />
    <script type="application/json" data-page-builder-config>@json($editorConfig)</script>

    <div class="page-builder-sections" data-page-builder-sections></div>

    <div class="page-builder-empty text-center text-muted p-5" data-page-builder-empty hidden>
        <i class="fa fa-table-columns fa-2x mb-3 d-block"></i>
        @lang('igniter::main.builder.text_empty_page')
    </div>

    @unless($this->previewMode)
        <div class="page-builder-add-section card card-body mt-3">
            <div class="fw-bold mb-2">@lang('igniter::main.builder.button_add_section')</div>
            <div class="page-builder-layout-picker">
                @foreach($layouts as $layout)
                    <button
                        type="button"
                        class="page-builder-layout-option"
                        title="{{ $layout['label'] }}"
                        aria-label="{{ $layout['label'] }}"
                        data-page-builder-add-section="{{ $layout['code'] }}"
                    >
                        <span class="page-builder-layout-preview">
                            @foreach($layout['spans'] as $span)
                                <span style="flex-grow: {{ $span ?? 1 }}"></span>
                            @endforeach
                        </span>
                        <span class="page-builder-layout-label">{{ $layout['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endunless

    <template data-page-builder-template="section">
        <div class="page-builder-section card mb-3" data-section>
            <div class="page-builder-section-header card-header d-flex align-items-center gap-2">
                <span
                    class="page-builder-handle"
                    title="@lang('igniter::main.builder.text_drag_section')"
                    data-section-handle
                ><i class="fa fa-grip-vertical"></i></span>
                <select
                    class="form-select form-select-sm w-auto"
                    aria-label="@lang('igniter::main.builder.label_layout')"
                    data-section-layout
                >
                    @foreach($layouts as $layout)
                        <option value="{{ $layout['code'] }}">{{ $layout['label'] }}</option>
                    @endforeach
                </select>
                <span class="small text-muted text-truncate" data-section-summary></span>
                <div class="ms-auto d-flex gap-1">
                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        title="@lang('igniter::main.builder.text_section_settings')"
                        data-section-action="settings"
                    ><i class="fa fa-gear"></i></button>
                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        title="@lang('igniter::main.builder.button_duplicate')"
                        data-section-action="duplicate"
                    ><i class="fa fa-clone"></i></button>
                    <button
                        type="button"
                        class="btn btn-light btn-sm text-danger"
                        title="@lang('igniter::main.builder.button_remove_section')"
                        data-section-action="remove"
                    ><i class="fa fa-trash"></i></button>
                </div>
            </div>
            <div class="page-builder-columns card-body" data-section-columns></div>
        </div>
    </template>

    <template data-page-builder-template="column">
        <div class="page-builder-column" data-column>
            <div class="page-builder-blocks" data-column-blocks></div>
            <div class="dropdown page-builder-add-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary w-100"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                ><i class="fa fa-plus"></i>&nbsp;@lang('igniter::main.builder.button_add_block')</button>
                <ul class="dropdown-menu page-builder-block-menu" data-column-block-menu></ul>
            </div>
        </div>
    </template>

    <template data-page-builder-template="block">
        <div class="page-builder-block" data-block>
            <span
                class="page-builder-handle"
                title="@lang('igniter::main.builder.text_drag_block')"
                data-block-handle
            ><i class="fa fa-grip-vertical"></i></span>
            <i class="page-builder-block-icon" data-block-icon></i>
            <div class="page-builder-block-text">
                <div class="page-builder-block-name" data-block-name></div>
                <div class="page-builder-block-summary" data-block-summary></div>
            </div>
            <span class="badge text-bg-light" data-block-required hidden>@lang('igniter::main.builder.text_required')</span>
            <button
                type="button"
                class="btn btn-light btn-sm"
                title="@lang('igniter::main.builder.button_edit_block')"
                data-block-action="edit"
            ><i class="fa fa-pencil"></i></button>
            <button
                type="button"
                class="btn btn-light btn-sm text-danger"
                title="@lang('igniter::main.builder.button_remove_block')"
                data-block-action="remove"
            ><i class="fa fa-xmark"></i></button>
        </div>
    </template>

    <div class="modal fade" tabindex="-1" aria-hidden="true" data-page-builder-modal>
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content" data-page-builder-modal-content></div>
        </div>
    </div>
</div>
