<aside class="page-builder-pages mb-3" aria-label="@lang('igniter::main.builder.text_pages')">
    @if($canAddPages)
        <button
            type="button"
            class="btn btn-light w-100 mb-2"
            data-toggle="record-editor"
            data-handler="{{ $this->getEventHandler('onLoadPageName') }}"
            data-record-id=""
        ><i class="fa fa-plus"></i>&nbsp;@lang('igniter::main.builder.button_new_page')</button>
    @endif
    <div class="card shadow-sm">
        <nav class="list-group list-group-flush" tabindex="0" data-page-builder-pages>
            @foreach($pages as $code => $page)
                @php($isCurrent = $code === $pageCode)
                <div @class(['page-builder-page list-group-item list-group-item-action position-relative p-0', 'active' => $isCurrent])>
                    <a
                        class="d-block px-3 py-2 text-reset text-decoration-none stretched-link"
                        @if($isCurrent) aria-current="page" @endif
                        href="{{ admin_url(strtr($this->pageUrl, ['{theme}' => $theme->getName(), '{page}' => $code])) }}"
                    >
                        <span class="d-block text-truncate">@lang($page['label'])</span>
                        <small @class(['d-block text-truncate', 'text-muted' => !$isCurrent])>{{ $code }}</small>
                    </a>
                    @unless($this->previewMode)
                        <div class="page-builder-page-actions btn-group btn-group-sm position-absolute top-50 end-0 translate-middle-y me-2">
                            <button
                                type="button"
                                class="btn btn-light"
                                title="@lang('igniter::main.builder.button_page_settings')"
                                aria-label="@lang('igniter::main.builder.button_page_settings'): @lang($page['label'])"
                                data-toggle="record-editor"
                                data-handler="{{ $this->getEventHandler('onLoadPageSettings') }}"
                                data-record-id="{{ $code }}"
                            ><i class="fa fa-gear"></i></button>
                            @if($canEditPages && $page['custom'])
                                <button
                                    type="button"
                                    class="btn btn-light"
                                    title="@lang('igniter::main.builder.button_rename_page')"
                                    aria-label="@lang('igniter::main.builder.button_rename_page'): @lang($page['label'])"
                                    data-toggle="record-editor"
                                    data-handler="{{ $this->getEventHandler('onLoadPageName') }}"
                                    data-record-id="{{ $code }}"
                                ><i class="fa fa-pencil"></i></button>
                                <button
                                    type="button"
                                    class="btn btn-light text-danger"
                                    title="@lang('igniter::main.builder.button_delete_page')"
                                    aria-label="@lang('igniter::main.builder.button_delete_page'): @lang($page['label'])"
                                    data-request="{{ $this->getEventHandler('onDeletePage') }}"
                                    data-request-data="pageCode: '{{ $code }}'"
                                    data-request-confirm="@lang('igniter::main.builder.confirm_delete_page')"
                                ><i class="fa fa-trash"></i></button>
                            @endif
                        </div>
                    @endunless
                </div>
            @endforeach
        </nav>
    </div>
    <script>
        // Keep the current page in view when the list is scrolled
        document.querySelector('[data-page-builder-pages] [aria-current="page"]')
            ?.scrollIntoView({block: 'nearest'});
    </script>
</aside>
