<div class="media-finder">
    <div class="grid">
        @if ($self->previewMode)
            <a>
                <div class="img-cover">
                    <img src="{{ $self->getMediaThumb($mediaItem) }}" class="img-responsive">
                </div>
            </a>
        @else
            @if (is_null($mediaItem))
                <a class="find-button blank-cover">
                    <i class="fa fa-plus"></i>
                </a>
            @else
                <i class="find-remove-button fa fa-times-circle" title="@lang('igniter::admin.text_remove')"></i>
                <div class="icon-container">
                    <span data-find-name>{{ $self->getMediaName($mediaItem) }}</span>
                </div>
                <a class="{{ $useAttachment ? 'find-config-button' : '' }}">
                    <div class="img-cover">
                        @if(($mediaFileType = $self->getMediaFileType($mediaItem)) === 'image')
                            <img
                                data-find-image
                                src="{{ $self->getMediaThumb($mediaItem) }}"
                                class="img-responsive"
                                alt=""
                            />
                        @else
                            <div class="media-icon">
                                <i
                                    data-find-file
                                    class="fa fa-{{ $mediaFileType }} fa-3x text-muted mb-2"
                                ></i>
                            </div>
                        @endif
                    </div>
                </a>
            @endif
            <input
                type="hidden"
                {!! (!is_null($mediaItem) && !$useAttachment) ? 'name="'.$fieldName.'"' : '' !!}
                value="{{ $self->getMediaPath($mediaItem) }}"
                data-find-value
            />
            <input
                type="hidden"
                value="{{ $self->getMediaIdentifier($mediaItem) }}"
                data-find-identifier
            />
        @endif
    </div>
</div>
