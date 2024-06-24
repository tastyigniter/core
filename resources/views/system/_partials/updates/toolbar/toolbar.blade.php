@php
    $lastChecked = isset($updates['last_checked_at'])
        ? time_elapsed($updates['last_checked_at'])
        : lang('igniter::admin.text_never');
@endphp
<div
    id="{{ $toolbarId }}"
    class="toolbar btn-toolbar {{ $cssClasses }}"
>
    <div class="toolbar-action">
        <div class="progress-indicator-container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    @if (isset($updates['items']) && $updates['items']->isNotEmpty())
                        <button
                            type="button"
                            class="btn btn-primary pull-left mr-2 ml-0"
                            data-control="apply-updates"
                        >@lang('igniter::system.updates.button_update')</button>
                    @endif
                    <button
                        type="button"
                        class="btn btn-success"
                        data-request="onCheckUpdates"
                        data-progress-indicator="@lang('igniter::system.updates.text_checking_updates')"
                    >@lang('igniter::system.updates.button_check')</button>
                    <button
                        type="button"
                        class="btn btn-default"
                        data-bs-target="#carte-modal"
                        data-bs-toggle="modal"
                    >@lang(array_get($carteInfo, 'id') ? 'igniter::system.updates.button_carte' : 'igniter::system.updates.button_attach_carte')</button>
                </div>
                <div>
                    @lang('igniter::system.version'): <b>{{$igniterVersion}}</b>
                    &nbsp;&nbsp;-&nbsp;&nbsp;
                    @lang('igniter::system.updates.text_last_checked'): <b>{{$lastChecked}}</b>
                </div>
            </div>
        </div>
    </div>
</div>
