<div class="form-row">
    <div class="col-md-2 pb-3 pb-md-0">
        <select
            id="{{ $field->getId('type') }}"
            name="{{ $field->getName() }}[type]"
            class="form-select"
            data-template-control="choose-type"
            data-request="{{ $self->getEventHandler('onChooseFile') }}"
            data-progress-indicator="@lang('igniter::admin.text_loading')"
        >
            @foreach ($templateTypes as $value => $label)
                <option
                    value="{{ $value }}"
                    {!! $value == $selectedTemplateType ? 'selected="selected"' : '' !!}
                >@lang($label)</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-10">
        <div class="input-group">
            <div class="control-selectlist flex-grow-1">
                <select
                    data-control="selectlist"
                    id="{{ $field->getId('file') }}"
                    name="{{ $field->getName() }}[file]"
                    data-template-control="choose-file"
                    data-request="{{ $self->getEventHandler('onChooseFile') }}"
                    data-progress-indicator="@lang('igniter::admin.text_loading')"
                >
                    @if ($self->placeholder)
                        <option
                            value=""
                        >{{ sprintf(lang($self->placeholder), strtolower($selectedTypeLabel)) }}</option>
                    @endif
                    @foreach ($fieldOptions as $value => $option)
                        @php if (!is_array($option)) $option = [$option]; @endphp
                        <option
                            {!! $value == $selectedTemplateFile ? 'selected="selected"' : '' !!}
                            @isset($option[1]) data-{{ strpos($option[1], '.') ? 'image' : 'icon' }}="{{ $option[1] }}" @endisset
                            value="{{ $value }}"
                        >{{ is_lang_key($option[0]) ? lang($option[0]) : $option[0] }}</option>
                    @endforeach
                </select>
            </div>
            <button
                type="button"
                class="btn btn-outline-default"
                data-bs-toggle="modal"
                data-bs-target="#{{ $self->getId('modal') }}"
                data-modal-title="{{ sprintf(lang($self->addLabel), $selectedTypeLabel) }}"
                data-modal-source-action="new"
                data-modal-source-name=""
            ><i class="fa fa-plus"></i>&nbsp;&nbsp;{{ sprintf(lang($self->addLabel), $selectedTypeLabel) }}
            </button>
            @if (!empty($selectedTemplateFile))
                <button
                    type="button"
                    class="btn btn-outline-default"
                    data-bs-toggle="modal"
                    data-bs-target="#{{ $self->getId('modal') }}"
                    data-modal-title="{{ sprintf(lang($self->editLabel), $selectedTypeLabel) }}"
                    data-modal-source-action="rename"
                    data-modal-source-name="{{ $selectedTemplateFile }}"
                ><i class="fa fa-pencil"></i>&nbsp;&nbsp;{{ sprintf(lang($self->editLabel), $selectedTypeLabel) }}
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    title="{{ sprintf(lang($self->deleteLabel), $selectedTypeLabel) }}"
                    data-request="{{ $self->getEventHandler('onManageSource') }}"
                    data-request-data="action: 'delete'"
                    data-request-confirm="@lang('igniter::admin.alert_warning_confirm')"
                    data-progress-indicator="@lang('igniter::admin.text_deleting')"
                ><i class="fa fa-trash"></i></button>
            @endif
        </div>
    </div>
</div>
