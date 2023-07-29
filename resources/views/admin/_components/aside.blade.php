@props(['navItems'])
@if(AdminAuth::isLogged())
    <aside {{ $attributes->merge(['class' => 'sidebar', 'role' => 'navigation'])}}>
        <div class="navbar-brand p-3 mb-3">
            <a class="logo" href="{{ admin_url('dashboard') }}">
                <i class="logo-svg">
                    <svg
                        class="align-top"
                        version="1.1" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 500 500" width="100%">
                        <g>
                            <defs>
                                <clipPath id="clip-path-id-viewbox-item-0">
                                    <rect x="0.0" y="0.0" width="2351.85" height="3016.6" />
                                </clipPath>
                            </defs>
                            <g
                                transform="translate(56.0 2.0) rotate(0.0 193.0 248.0) scale(0.16412611348512873 0.1644235231717828)">
                                <g clip-path="url(#clip-path-id-viewbox-item-0)" transform="translate(-0.0 -0.0)">
                                    <path
                                        d="M1385.35,2973.45c77.97,38.77,169.23,31.59,238.38-18.56c531.73-386.54,643.3-765.6,643.3-1015.46  c0-337.59-187-602.57-371.46-817.55c4.78,176.65-66.62,287.14-127.7,348.82c-67.21,67.82-136.96,93.12-137.56,93.42  c-0.9,0.3-1.79,0.15-2.39-0.45c-0.6-0.6-1.05-1.5-0.9-2.25c0.45-2.54,42.72-258.09,42.72-454.06  c0-402.41-192.98-677.87-354.73-838.06C1158.46,114.65,1000.44,34.71,966.39,18.39c14.49,27.55,67.81,136.68,67.81,255.1  c0,217.07-184.31,415.59-397.75,645.38c-260.34,280.4-555.33,598.08-555.33,1020.55c0,240.73,111.27,611.1,641.06,1008.27  c68.71,51.5,160.27,59.58,239.13,21.11c68.26-33.38,109.93-95.06,111.57-164.98l1.79-81.89c0.3-14.52-4.48-28.74-13.89-41.17  C838.53,2386.3,841.67,2307.4,846.6,2187.94c0.45-11.53,0.9-23.35,1.19-35.78c3.14-135.19,79.76-715.45,88.57-781.17  c0.6-4.49,5.08-7.78,10.31-7.78h18.67c2.99,0,5.97,1.2,7.92,3.14c1.64,1.65,2.54,3.59,2.54,5.84l-3.29,775.78  c0,6.29,2.84,12.58,8.07,17.37c6.87,6.44,16.58,9.58,26.89,8.98c3.58-0.3,7.17-0.9,11.05-1.8c9.86-2.54,16.73-9.73,17.18-17.81  l37.79-782.67c0.3-4.79,4.78-8.53,10.31-8.53h13.44c5.68,0,10.16,3.74,10.31,8.53c1.05,24.85,26.74,610.65,26.74,713.8  c0,61.23,11.65,83.24,21.36,90.87c5.97,4.64,10.75,3.59,10.9,3.44c0.15,0,0.45,0,0.6,0h14.34c0.15,0,0.45,0,0.6,0.15  c0.15,0,5.08,1.2,10.9-3.44c9.71-7.64,21.36-29.64,21.36-90.87c0-97.31,22.25-611.7,26.74-713.8c0.15-4.79,4.78-8.53,10.31-8.53  h13.44c5.53,0,10.16,3.74,10.31,8.53l37.79,782.67c0.45,8.08,7.32,15.27,17.18,17.81c3.73,1.05,7.47,1.65,11.05,1.95  c10.31,0.75,20.01-2.54,26.89-8.83c5.23-4.79,8.07-11.08,8.07-17.37l-3.29-775.63c0-2.1,0.9-4.19,2.39-5.84  c1.94-1.95,4.93-3.14,7.92-3.14h18.67c5.23,0,9.71,3.29,10.31,7.78c8.66,65.72,85.29,645.98,88.57,781.17  c0.3,12.43,0.75,24.25,1.2,35.78c4.93,119.47,8.07,198.36-214.18,492.98c-9.41,12.43-14.19,26.65-13.89,41.32l1.94,87.43  C1276.91,2878.68,1317.98,2940.06,1385.35,2973.45z"
                                        style="fill:#192957;"></path>
                                </g>
                            </g>
                        </g>
                    </svg>
                </i>
            </a>
        </div>
        <div class="">
            {{ $slot }}
        </div>
        <div id="navSidebar" class="nav-sidebar">
            <x-igniter.admin::nav
                id="side-nav-menu"
                class="nav flex-column"
            >
                @foreach($navItems as $code => $item)
                    @if(isset($item['child']) && empty($item['child']))
                        @continue;
                    @endif
                    <x-igniter.admin::nav.item
                        :code="$code"
                        class="nav-item"
                    >
                        <x-igniter.admin::nav.item-link
                            class="nav-link mb-1 {{ !empty($item['child']) ? 'has-arrow' : '' }} {{ $item['class'] ?? '' }}"
                            href="{{ $item['href'] ?? '#' }}"
                            target="{{ $item['target'] ?? '_self' }}"
                        >
                            <i class="fa {{ $item['icon'] }} fa-fw"></i><span>{{ $item['title'] }}</span>
                        </x-igniter.admin::nav.item-link>

                        @if($children = array_get($item, 'child', []))
                            @php($isActive = (bool)AdminMenu::isActiveNavItem($code))
                            <x-igniter.admin::nav
                                class="nav collapse {{ $isActive ? ' show' : '' }}"
                                aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                            >
                                @foreach($children as $childCode => $childItem)
                                    @if(isset($childItem['child']) && empty($childItem['child']))
                                        @continue;
                                    @endif
                                    <x-igniter.admin::nav.item
                                        :code="$childCode"
                                        class="nav-item w-100"
                                    >
                                        <x-igniter.admin::nav.item-link
                                            class="nav-link mb-1 {{ $childItem['class'] ?? '' }}"
                                            href="{{ $childItem['href'] ?? '#' }}"
                                            target="{{ $childItem['target'] ?? '_self' }}"
                                        >
                                            <span>{{ $childItem['title'] }}</span>
                                        </x-igniter.admin::nav.item-link>
                                    </x-igniter.admin::nav.item>
                                @endforeach
                            </x-igniter.admin::nav>
                        @endif
                    </x-igniter.admin::nav.item>
              @endforeach
            </x-igniter.admin::nav>
        </div>
    </aside>
@endif
