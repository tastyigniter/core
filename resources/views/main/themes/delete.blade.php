{!! form_open([
    'id' => 'edit-form',
    'role' => 'form',
    'method' => 'DELETE',
]) !!}

<input type="hidden" name="_handler" value="onDelete">
<div class="toolbar">
    <div class="toolbar-action">
        <button
            type="submit"
            class="btn btn-danger"
            data-request="onDelete"
        >@lang('igniter::system.themes.button_yes_delete')</button>
        <a class="btn btn-default" href="{{ admin_url('themes') }}">
            @lang('igniter::system.themes.button_return_to_list')
        </a>
    </div>
</div>

<div class="form-fields flex-column">
    @php
        $deleteAction = !empty($themeData)
            ? lang('igniter::system.themes.text_files_data')
            : lang('igniter::system.themes.text_files');
    @endphp
    <p>{!! sprintf(lang('igniter::system.themes.alert_delete_warning'), $deleteAction, $themeObj->label) !!}</p>
    <p>{{ sprintf(lang('igniter::system.themes.alert_delete_confirm'), $deleteAction) }}</p>

    @if ($themeData)
        <div class="form-group span-full">
            <label
                for="input-delete-data"
                class="form-label"
            >@lang('igniter::system.themes.label_delete_data')</label>
            <br>
            <div id="input-delete-data">
                <input
                    type="hidden"
                    name="delete_data"
                    value="0"
                >
                <div class="form-check form-switch">
                    <input
                        type="checkbox"
                        name="delete_data"
                        id="delete-data"
                        class="form-check-input"
                        value="1"
                    />
                    <label
                        class="form-check-label"
                        for="delete-data"
                    >@lang('igniter::admin.text_no')/@lang('igniter::admin.text_yes')</label>
                </div>
            </div>
        </div>
    @endif
</div>
{!! form_close() !!}
