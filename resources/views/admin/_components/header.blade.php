@if(AdminAuth::isLogged())
    <nav {{ $attributes->merge(['class' => 'navbar navbar-top navbar-expand'])}}>
        <div class="container-fluid">
            @if($previousUrl = AdminMenu::getPreviousUrl())
                <a
                    class="btn shadow-none border-none pe-0"
                    href="{{$previousUrl}}"
                ><i class="fa fa-angle-left fs-4 align-bottom"></i></a>
            @endif
            <h4 class="page-title px-2 mb-0">
                <span>{!! Template::getHeading() !!}</span>
            </h4>
            <div class="navbar navbar-right py-2 pe-lg-0">
                <button
                    type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navSidebar"
                    aria-controls="navSidebar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="fa fa-bars"></span>
                </button>

                {{ $slot }}
            </div>
        </div>
    </nav>
@endif
