<div
    id="{{ $self->getId() }}"
    class="mediafinder {{ $mode }}-mode{{ $isMulti ? ' is-multi' : '' }}{{ $value ? ' is-populated' : '' }}"
    data-control="mediafinder"
    data-alias="{{ $self->alias }}"
    data-mode="{{ $mode }}"
    data-choose-button-text="{{ $chooseButtonText }}"
    data-use-attachment="{{ $useAttachment }}"
>
    {!! $self->makePartial('mediafinder/image') !!}

    @if ($useAttachment)
        <script type="text/template" data-config-modal-template>
            <div class="modal-dialog">
                <div id="{{ $self->getId('config-modal-content') }}">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="progress-indicator">
                                <span class="ti-loading spinner-border fa-3x fa-fw"></span>
                                @lang('igniter::admin.text_loading')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </script>
    @endif
</div>
