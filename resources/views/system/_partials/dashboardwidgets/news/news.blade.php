<div class="dashboard-widget widget-news">
    <h6 class="widget-title">@lang('igniter::admin.dashboard.text_news')</h6>
    <div class="row">
        <div class="list-group list-group-flush w-100">
            @forelse($newsFeed as $feed)
                <a class="list-group-item" target="_blank" href="{{ $feed['link'] }}?ref=tastyigniter-dashboard">
                    <b class="d-block text-truncate">{{ $feed['title'] }}</b>
                    <span class="text-muted d-block text-truncate">{{ strip_tags($feed['description']) }}</span>
                </a>
            @empty
                <div class="mt-3">
                    <p class="text-danger">
                        @lang('igniter::admin.dashboard.error_rss')
                        <a href="javascript:location.reload();">
                            @lang('igniter::admin.text_reload')
                        </a>.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
