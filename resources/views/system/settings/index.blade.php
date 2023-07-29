<div class="container-fluid pt-4">
    @foreach($settings as $item => $categories)
        @continue(!count($categories))
        @unless($item == 'core')
            <h5 class="mb-2 px-3">{{ ucwords($item) }}</h5>
        @endunless

        <div class="card shadow-sm p-4 mb-3">
            <div class="row gy-4">
                @foreach($categories as $key => $category)
                    <div class="col-lg-4">
                        <a
                            class="text-reset d-block h-100"
                            href="{{ $category->url }}"
                            role="button"
                        >
                            <div class="card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <div class="pr-3">
                                        @if ($item == 'core' && count(array_get($settingItemErrors, $category->code, [])))
                                            <i
                                                class="text-danger fa fa-exclamation-triangle fa-fw"
                                                title="@lang('igniter::system.settings.alert_settings_errors')"
                                            ></i>
                                        @elseif ($category->icon)
                                            <i class="text-muted {{ $category->icon }} fa-fw"></i>
                                        @else
                                            <i class="text-muted fa fa-puzzle-piece fa-fw"></i>
                                        @endif
                                </div>
                                <div class="">
                                    <h5>@lang($category->label)</h5>
                                    <p class="no-margin text-muted">{!! $category->description ? lang($category->description) : '' !!}</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
            </div>
        </div>
    @endforeach
</div>
