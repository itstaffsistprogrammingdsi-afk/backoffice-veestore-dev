@php use Illuminate\Support\Str; @endphp
<ul class="menu-sub">
  @foreach ($menu as $submenu)
    @continue(isset($canSeeItem) && !$canSeeItem($submenu))
    @php
      $hasChild   = isset($submenu->submenu) && is_iterable($submenu->submenu);
      $isActive   = isset($matchItem) ? $matchItem($submenu) : false;
      $activeCls  = $isActive ? ($hasChild ? 'active open' : 'active') : '';
      $linkCls    = $hasChild ? 'menu-link menu-toggle' : 'menu-link';
      $href       = isset($submenu->url) ? url($submenu->url) : 'javascript:void(0);';
    @endphp

    <li class="menu-item {{ $activeCls }}">
      <a href="{{ $href }}"
         class="{{ $linkCls }}"
         @if(isset($submenu->slug)) data-menu-slug="{{ $submenu->slug }}" @endif
         @if (!empty($submenu->target)) target="_blank" @endif>
        @isset($submenu->icon)<i class="{{ $submenu->icon }}"></i>@endisset
        <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
        @isset($submenu->badge)
          <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
        @endisset
        @if(isset($submenu->slug) && array_key_exists((string) $submenu->slug, $menuNotificationCounts ?? []))
          @php
            $submenuNotificationCount = (int) (
              $menuNotificationCounts[$submenu->slug] ?? 0
            );
          @endphp
          <span class="badge bg-danger rounded-pill ms-auto js-menu-notification-badge {{ $submenuNotificationCount > 0 ? '' : 'd-none' }}"
                data-notification-menu="{{ $submenu->slug }}">
            {{ $submenuNotificationCount }}
          </span>
        @endif
      </a>

      @isset($submenu->submenu)
        @include('layouts.sections.menu.submenu', ['menu' => $submenu->submenu, 'matchItem' => $matchItem, 'canSeeItem' => $canSeeItem])
      @endisset
    </li>
  @endforeach
</ul>
