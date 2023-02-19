@if ($formModel->referrer && count($formModel->referrer))
    <div class="form-control-static">
        <ul class="list-unstyled">
            @foreach($formModel->referrer as $referrer)
                <li>{{ $referrer }}</li>
            @endforeach
        </ul>
    </div>
@else
    <div class="form-control-static">@lang('igniter::system.request_logs.text_empty_referrer')</div>
@endif
