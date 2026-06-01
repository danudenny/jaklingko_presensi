@props(['mobile' => false])

@php
    $navGroups = [
        [
            'label' => 'Utama',
            'items' => [
                ['route' => 'dashboard', 'icon' => 'fa-house', 'text' => 'Dashboard'],
            ]
        ],
        [
            'label' => 'Manajemen',
            'items' => [
                ['route' => 'drivers.index', 'icon' => 'fa-id-card', 'text' => 'Pengemudi'],
                ['route' => 'units.index', 'icon' => 'fa-car', 'text' => 'Unit'],
                ['route' => 'routes.index', 'icon' => 'fa-route', 'text' => 'Rute'],
                ['route' => 'holidays.index', 'icon' => 'fa-calendar-xmark', 'text' => 'Hari Libur'],
            ]
        ],
        [
            'label' => 'Operasional',
            'items' => [
                ['route' => 'renops.index', 'icon' => 'fa-car-tunnel', 'text' => 'Renops'],
                ['route' => 'schedules.index', 'icon' => 'fa-calendar-day', 'text' => 'Jadwal', 'pulse' => true],
                ['route' => 'kilometer-reports.index', 'icon' => 'fa-tachometer-alt', 'text' => 'Laporan Kilometer'],
                ['route' => 'global-kilometer-reports.index', 'icon' => 'fa-users-gear', 'text' => 'Kilometer Global'],
            ]
        ],
        [
            'label' => 'Pelaporan',
            'items' => [
                ['route' => 'leave-requests.index', 'icon' => 'fa-person-walking-arrow-right', 'text' => 'Pengajuan Cuti'],
                ['route' => 'unit-problems.index', 'icon' => 'fa-car-burst', 'text' => 'Laporan Masalah'],
                ['route' => 'maintenance-logs.index', 'icon' => 'fa-road-barrier', 'text' => 'Log Perawatan'],
            ]
        ],
    ];

    if (Auth::user()->isSuperAdmin()) {
        array_splice($navGroups, 1, 0, [[
            'label' => 'Admin',
            'items' => [
                ['route' => 'users.index', 'icon' => 'fa-users', 'text' => 'Pengguna'],
            ]
        ]]);
    }
@endphp

<div
    x-data="{
        hoveredGroup: null,
        expandedGroup: null,
        isCollapsed: {{ $mobile ? 'false' : '$store.sidebar.collapsed' }},
        isActiveRoute(route) {
            return {{ json_encode(request()->routeIs('*')) }}.some(r => route.includes(r));
        },
        toggleGroup(label) {
            this.expandedGroup = this.expandedGroup === label ? null : label;
        }
    }"
    class="{{ $mobile ? 'h-full' : 'h-screen fixed left-0 top-0' }} flex bg-transparent transition-all duration-300 z-30"
    :class="{ 'w-64': !isCollapsed || {{ $mobile ? 'true' : 'false' }}, 'w-[68px]': isCollapsed && !{{ $mobile ? 'true' : 'false' }} }"
>
    {{-- Icon Rail --}}
    <div class="flex flex-col w-[68px] shrink-0 bg-[#0c1526] border-r border-[#1a2744]">
        {{-- Logo --}}
        <div class="flex items-center justify-center h-16 border-b border-[#1a2744]">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#3b82f6] to-[#6366f1] flex items-center justify-center shadow-lg shadow-blue-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
        </div>

        {{-- User Avatar --}}
        <div class="flex items-center justify-center py-3 border-b border-[#1a2744]">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#3b82f6] to-[#8b5cf6] flex items-center justify-center ring-2 ring-[#1e3a5f]">
                <span class="text-xs font-bold text-white">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
        </div>

        {{-- Icon Buttons --}}
        <nav class="flex-1 flex flex-col items-center gap-1 py-3 overflow-y-auto">
            @foreach($navGroups as $group)
                <div class="w-full flex flex-col items-center">
                    @foreach($group['items'] as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @mouseenter="{{ $mobile ? 'false' : '!isCollapsed' }} && (hoveredGroup = '{{ $group['label'] }}')"
                            @mouseleave="hoveredGroup = null"
                            @click="{{ $mobile ? 'false' : 'isCollapsed' }} && toggleGroup('{{ $group['label'] }}')"
                            class="relative flex items-center justify-center w-11 h-11 rounded-xl transition-all duration-200 group
                                {{ request()->routeIs($item['route'])
                                    ? 'bg-[#1e3a5f] text-[#60a5fa] shadow-inner'
                                    : 'text-[#64748b] hover:text-[#94a3b8] hover:bg-[#111d32]' }}"
                        >
                            <i class="fa-solid {{ $item['icon'] }} text-[15px]"></i>

                            {{-- Active indicator bar --}}
                            @if(request()->routeIs($item['route']))
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 rounded-r-full bg-[#3b82f6]"></span>
                            @endif

                            {{-- Pulse dot --}}
                            @if(!empty($item['pulse']))
                                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#ef4444] rounded-full animate-pulse"></span>
                            @endif

                            {{-- Tooltip on collapsed --}}
                            <span
                                x-show="isCollapsed && !{{ $mobile ? 'true' : 'false' }}"
                                x-cloak
                                class="absolute left-full ml-3 px-2.5 py-1.5 bg-[#1e293b] text-[#e2e8f0] text-xs font-medium rounded-lg shadow-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none border border-[#334155]"
                            >
                                {{ $item['text'] }}
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Separator between groups --}}
                @if(!$loop->last)
                    <div class="w-6 my-1.5 border-t border-[#1a2744]"></div>
                @endif
            @endforeach
        </nav>

        {{-- Collapse Toggle --}}
        @if(!$mobile)
        <div class="flex items-center justify-center py-3 border-t border-[#1a2744]">
            <button
                @click="$store.sidebar.toggle(); isCollapsed = $store.sidebar.collapsed"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-[#64748b] hover:text-[#94a3b8] hover:bg-[#111d32] transition-all duration-200"
            >
                <svg x-show="!isCollapsed" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg x-show="isCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        @endif
    </div>

    {{-- Expanded Panel --}}
    <div
        x-show="!isCollapsed || {{ $mobile ? 'true' : 'false' }}"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-x-4"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-x-4"
        class="flex flex-col w-[228px] bg-[#0f172a] border-r border-[#1a2744] overflow-hidden"
    >
        {{-- Header --}}
        <div class="flex items-center h-16 px-5 border-b border-[#1a2744]">
            <span class="text-[15px] font-semibold text-[#e2e8f0] tracking-wide">{{ config('app.name', 'Presensi') }}</span>
        </div>

        {{-- User Info --}}
        <div class="px-5 py-3.5 border-b border-[#1a2744]">
            <p class="text-[13px] font-medium text-[#e2e8f0] truncate">{{ Auth::user()->name }}</p>
            <p class="text-[11px] text-[#64748b] mt-0.5">
                @if(Auth::user()->isSuperAdmin())
                    Super Admin
                @else
                    Admin
                @endif
            </p>
        </div>

        {{-- Nav Items --}}
        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-4">
            @foreach($navGroups as $group)
                <div>
                    <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-[#475569]">{{ $group['label'] }}</p>
                    <div class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200
                                    {{ request()->routeIs($item['route'])
                                        ? 'bg-[#1e3a5f] text-[#60a5fa]'
                                        : 'text-[#94a3b8] hover:bg-[#111d32] hover:text-[#e2e8f0]' }}"
                            >
                                <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-[13px]
                                    {{ request()->routeIs($item['route']) ? 'text-[#60a5fa]' : 'text-[#64748b]' }}"></i>
                                <span>{{ $item['text'] }}</span>
                                @if(!empty($item['pulse']))
                                    <span class="ml-auto w-1.5 h-1.5 bg-[#ef4444] rounded-full animate-pulse"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Logout --}}
        <div class="px-3 py-3 border-t border-[#1a2744]">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-[13px] font-medium text-[#94a3b8] hover:bg-[#111d32] hover:text-[#e2e8f0] transition-all duration-200">
                    <i class="fa-solid fa-right-from-bracket w-4 text-center text-[13px] text-[#64748b]"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</div>
