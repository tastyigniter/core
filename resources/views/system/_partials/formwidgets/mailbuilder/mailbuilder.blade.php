<div
    id="{{ $this->getId() }}"
    class="mail-builder{{ $this->previewMode ? ' is-preview' : '' }}"
    data-control="mail-builder"
>
    <input
        type="hidden"
        name="{{ $field->getName() }}"
        value="{{ json_encode($blocks) }}"
        data-mail-builder-input
    />
    <script type="application/json" data-mail-builder-config>@json($editorConfig)</script>

    <div data-mail-builder-notices></div>

    <div class="d-flex flex-wrap align-items-center gap-2 py-2" role="toolbar" aria-label="@lang('igniter::system.mail_builder.js_toolbar')">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-light" data-mail-builder-action="undo" title="@lang('igniter::system.mail_builder.js_undo')" aria-label="@lang('igniter::system.mail_builder.js_undo')">
                <i class="fa fa-rotate-left"></i>
            </button>
            <button type="button" class="btn btn-light" data-mail-builder-action="redo" title="@lang('igniter::system.mail_builder.js_redo')" aria-label="@lang('igniter::system.mail_builder.js_redo')">
                <i class="fa fa-rotate-right"></i>
            </button>
        </div>
        <span class="small text-warning-emphasis" data-mail-builder-dirty hidden>
            <i class="fa fa-circle fa-2xs me-1"></i>@lang('igniter::system.mail_builder.js_unsaved_changes')
        </span>
        <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group" role="group" data-mail-builder-views>
                <button type="button" class="btn btn-light active" data-mail-builder-view="desktop" title="@lang('igniter::system.mail_builder.js_desktop')" aria-label="@lang('igniter::system.mail_builder.js_desktop')">
                    <i class="fa fa-desktop"></i>
                </button>
                <button type="button" class="btn btn-light" data-mail-builder-view="mobile" title="@lang('igniter::system.mail_builder.js_mobile')" aria-label="@lang('igniter::system.mail_builder.js_mobile')">
                    <i class="fa fa-mobile-screen"></i>
                </button>
                <button type="button" class="btn btn-light" data-mail-builder-view="text" title="@lang('igniter::system.mail_builder.js_plain_text')" aria-label="@lang('igniter::system.mail_builder.js_plain_text')">
                    <i class="fa fa-file-lines"></i>
                </button>
            </div>
            @unless($this->previewMode)
                <div class="input-group w-auto">
                    <input type="email" class="form-control" data-mail-builder-test-email aria-label="@lang('igniter::system.mail_builder.js_test_email')" placeholder="@lang('igniter::system.mail_builder.js_test_email')"/>
                    <button type="button" class="btn btn-light" data-mail-builder-action="send-test">
                        <i class="fa fa-paper-plane"></i> @lang('igniter::system.mail_builder.js_send_test')
                    </button>
                </div>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-request="onSave"
                    data-progress-indicator="@lang('igniter::admin.text_saving')"
                    data-mail-builder-publish
                >@lang('igniter::system.mail_builder.button_publish')</button>
            @endunless
        </div>
    </div>

    <div class="mail-builder-body">
        <aside class="mail-builder-panel border rounded bg-body px-3 pb-3">
            <ul class="nav nav-tabs nav-fill bg-body sticky-top pt-2 mx-n3 mb-3" role="tablist">
                <li class="nav-item"><button type="button" class="nav-link active" data-mail-builder-side-tab="blocks">@lang('igniter::system.mail_builder.js_add_block')</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-mail-builder-side-tab="variables">@lang('igniter::system.mail_builder.js_variables')</button></li>
            </ul>
            <div data-mail-builder-side-panel="blocks">
                <p class="form-text mt-0">@lang('igniter::system.mail_builder.js_add_block_hint')</p>
                <div class="row row-cols-2 g-1" data-mail-builder-palette></div>
            </div>
            <div data-mail-builder-side-panel="variables" data-mail-builder-variables hidden></div>
        </aside>

        <div class="bg-body-tertiary border rounded p-3 overflow-hidden" data-mail-builder-canvas>
            @if($this->mode === 'template')
                <div class="mail-builder-paper d-flex flex-wrap align-items-center gap-2 mb-3 px-3 py-2 bg-body border rounded">
                    <label class="form-label small text-body-secondary mb-0" for="{{ $this->getId('subject') }}">@lang('igniter::system.mail_templates.label_subject')</label>
                    <input
                        type="text"
                        id="{{ $this->getId('subject') }}"
                        class="form-control fw-semibold flex-grow-1 w-auto"
                        maxlength="255"
                        placeholder="@lang('igniter::system.mail_builder.js_subject_placeholder')"
                        data-mail-builder-subject
                        @if($this->previewMode) readonly @endif
                    />
                    <div class="w-100 small text-body-secondary" data-mail-builder-subject-preview hidden></div>
                </div>
            @endif
            <div class="mail-builder-paper mail-builder-frame-wrap position-relative" data-mail-builder-frame-wrap>
                <iframe
                    class="d-block w-100 border-0 bg-white"
                    title="@lang('igniter::system.mail_builder.js_desktop')"
                    sandbox="allow-same-origin"
                    data-mail-builder-frame
                ></iframe>
            </div>
            <pre class="mail-builder-paper mail-builder-text p-3 bg-body border small" data-mail-builder-text hidden></pre>
            <div class="mail-builder-paper mt-3 small text-danger" data-mail-builder-error hidden></div>
        </div>

        <aside class="mail-builder-panel d-flex flex-column border rounded bg-body">
            <div class="flex-grow-1 p-3" data-mail-builder-inspector></div>
            @unless($this->previewMode)
                <div class="border-top p-3 bg-body sticky-bottom">
                    <button type="button" class="btn btn-light w-100" data-mail-builder-action="theme">
                        <i class="fa fa-palette me-1"></i>@lang('igniter::system.mail_builder.js_theme')
                    </button>
                </div>
            @endunless
        </aside>
    </div>

    <div class="modal fade" tabindex="-1" aria-labelledby="{{ $this->getId('theme-title') }}" aria-hidden="true" data-mail-builder-theme-modal>
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $this->getId('theme-title') }}">@lang('igniter::system.mail_builder.js_theme')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('igniter::system.mail_builder.js_cancel')"></button>
                </div>
                <div class="modal-body" data-mail-builder-theme></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-mail-builder-action="reset-theme">@lang('igniter::system.mail_builder.js_reset_theme')</button>
                    <button type="button" class="btn btn-primary" data-mail-builder-action="save-theme">@lang('igniter::system.mail_builder.js_save_theme')</button>
                </div>
            </div>
        </div>
    </div>
</div>
