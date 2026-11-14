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
                        class="page-builder-layout-option d-flex flex-column gap-1 p-2 border rounded bg-body text-start"
                        title="{{ $layout['label'] }}"
                        aria-label="{{ $layout['label'] }}"
                        data-page-builder-add-section="{{ $layout['code'] }}"
                    >
                        <span class="page-builder-layout-preview d-flex">
                            @foreach($layout['spans'] as $span)
                                <span style="flex-grow: {{ $span ?? 1 }}"></span>
                            @endforeach
                        </span>
                        <span class="small lh-sm text-body-secondary">{{ $layout['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endunless

    <template data-page-builder-template="section">
        <div class="page-builder-section position-relative rounded" data-section>
            <div class="page-builder-section-toolbar d-flex align-items-center gap-1 p-1 rounded-top bg-primary shadow-sm">
                <span
                    class="page-builder-handle px-1 text-body-secondary"
                    title="@lang('igniter::main.builder.text_drag_section')"
                    data-section-handle
                ><i class="fa fa-grip-vertical text-white"></i></span>
                <select
                    class="form-select form-select-sm w-auto"
                    aria-label="@lang('igniter::main.builder.label_layout')"
                    data-section-layout
                >
                    @foreach($layouts as $layout)
                        <option value="{{ $layout['code'] }}">{{ $layout['label'] }}</option>
                    @endforeach
                </select>
                <span class="small text-muted text-truncate px-1" data-section-summary></span>
                <button
                    type="button"
                    class="btn btn-light btn-sm"
                    title="@lang('igniter::main.builder.text_section_settings')"
                    aria-label="@lang('igniter::main.builder.text_section_settings')"
                    data-section-action="settings"
                ><i class="fa fa-gear"></i></button>
                <button
                    type="button"
                    class="btn btn-light btn-sm"
                    title="@lang('igniter::main.builder.button_duplicate')"
                    aria-label="@lang('igniter::main.builder.button_duplicate')"
                    data-section-action="duplicate"
                ><i class="fa fa-clone"></i></button>
                <button
                    type="button"
                    class="btn btn-light btn-sm text-danger"
                    title="@lang('igniter::main.builder.button_remove_section')"
                    aria-label="@lang('igniter::main.builder.button_remove_section')"
                    data-section-action="remove"
                ><i class="fa fa-trash"></i></button>
            </div>
            <div class="page-builder-columns" data-section-columns></div>
        </div>
    </template>

    <template data-page-builder-template="column">
        <div class="page-builder-column d-flex flex-column gap-2 p-2 border rounded bg-body-tertiary" data-column>
            <div class="page-builder-blocks d-flex flex-column gap-2" data-column-blocks></div>
            <div class="dropdown page-builder-add-block">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-light text-dark border w-100"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                ><i class="fa fa-plus"></i>&nbsp;@lang('igniter::main.builder.button_add_block')</button>
                <div class="dropdown-menu page-builder-block-picker p-0">
                    <div class="p-2 border-bottom">
                        <input
                            type="search"
                            class="form-control form-control-sm"
                            placeholder="@lang('igniter::main.builder.text_search_blocks')"
                            aria-label="@lang('igniter::main.builder.text_search_blocks')"
                            autocomplete="off"
                            data-column-block-search
                        />
                    </div>
                    <ul class="list-unstyled mb-0 py-1 overflow-y-auto" style="max-height: 20rem" data-column-block-menu></ul>
                    <p class="small text-muted px-3 py-2 mb-0" data-column-block-empty hidden>@lang('igniter::main.builder.text_no_blocks_found')</p>
                </div>
            </div>
        </div>
    </template>

    <template data-page-builder-template="block">
        <div class="page-builder-block d-flex align-items-center gap-2 p-2 border rounded bg-body" data-block>
            <span
                class="page-builder-handle px-1 text-body-secondary"
                title="@lang('igniter::main.builder.text_drag_block')"
                data-block-handle
            ><i class="fa fa-grip-vertical"></i></span>
            <i class="fa-fw text-body-secondary" data-block-icon></i>
            <div class="flex-grow-1 overflow-hidden">
                <div class="fw-semibold text-truncate" data-block-name></div>
                <div class="small text-body-secondary text-truncate" data-block-summary></div>
            </div>
            <span class="badge text-bg-light" data-block-required hidden>@lang('igniter::main.builder.text_required')</span>
            <button
                type="button"
                class="page-builder-block-remove btn btn-light btn-sm text-danger"
                title="@lang('igniter::main.builder.button_remove_block')"
                aria-label="@lang('igniter::main.builder.button_remove_block')"
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
