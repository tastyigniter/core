<div
    id="{{ $this->getId() }}"
    class="field-translationseditor"
    data-control="translationseditor"
    data-alias="{{ $this->alias }}"
>
    <h5
        class="fw-600"
    >{{ sprintf(lang('igniter::system.languages.text_locale_strings'), $translatedProgress, $totalStrings) }}</h5>
    <span class="help-block">@lang('igniter::system.languages.help_locale_strings')</span>
    <div
        id="{{ $this->getId('items') }}"
        class="table-responsive mt-3"
    >
        <table class="table mb-0">
            <thead>
            <tr>
                <th width="1"></th>
                <th>{{ sprintf(lang('igniter::system.languages.column_language'), $this->model->name) }}</th>
                <th width="45%">@lang('igniter::system.languages.column_variable')</th>
            </tr>
            </thead>
            <tbody>
            @if ($field->options && $field->options->count())
                @foreach($field->options as $key => $value)
                    <tr>
                        <td>
                            <a
                                role="button"
                                class="btn btn-link"
                                data-control="edit-translation"
                                data-input-name="{{ $field->getName() }}[{{ $key }}]"
                                data-source="{{ $value['source'] }}"
                                data-translation='{!! $value['translation'] !!}'
                            ><i class="fa fa-pencil"></i></a>
                        </td>
                        <td
                            data-control="edit-translation"
                            data-input-name="{{ $field->getName() }}[{{ $key }}]"
                            data-source="{{ $value['source'] }}"
                            data-translation="{{$value['translation']}}"
                        >
                            <div data-toggle="translation-preview">
                                <p class="mb-1">{{ $value['translation'] ?: '--' }}</p>
                            </div>
                            <div data-toggle="translation-input"></div>
                        </td>
                        <td>
                            <p class="mb-1">{{ $value['source'] }}</p>
                            <span class="text-muted small">{{ $key }}</span>
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td class="border-bottom-0" colspan="999">
                        <div class="d-flex justify-content-end pt-3">
                            {!! $field->options->render() !!}
                        </div>
                    </td>
                </tr>
            @else
                <tr>
                    <td colspan="99" class="text-center">@lang('igniter::system.languages.text_empty_translations')
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
