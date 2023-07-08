@props(['navItems'])
@if(AdminAuth::isLogged())
    <aside {{ $attributes->merge(['class' => 'sidebar', 'role' => 'navigation'])}}>
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
                    class="nav-link {{ !empty($item['child']) ? 'has-arrow' : '' }} {{ $item['class'] ?? '' }}"
                    href="{{ $item['href'] ?? '#' }}"
                    target="{{ $item['target'] ?? '_self' }}"
                  >
                    <i class="fa {{ $item['icon'] }} fa-fw"></i>
                    <span>{{ $item['title'] }}</span>
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
                            class="nav-link {{ $childItem['class'] ?? '' }}"
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
