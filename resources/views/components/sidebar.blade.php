@props(['mobile' => false])

@php
    $currentRoute = request()->route()->getName();

    $navGroups = [
        [
            'label' => 'Utama',
            'icon' => 'fa-house',
            'items' => [
                ['route' => 'dashboard', 'icon' => 'fa-house', 'text' => 'Dashboard'],
            ]
        ],
        [
            'label' => 'Manajemen',
            'icon' => 'fa-id-card',
            'items' => [
                ['route' => 'drivers.index', 'icon' => 'fa-id-card', 'text' => 'Pengemudi'],
                ['route' => 'units.index', 'icon' => 'fa-car', 'text' => 'Unit'],
                ['route' => 'routes.index', 'icon' => 'fa-route', 'text' => 'Rute'],
                ['route' => 'holidays.index', 'icon' => 'fa-calendar-xmark', 'text' => 'Hari Libur'],
            ]
        ],
        [
            'label' => 'Operasional',
            'icon' => 'fa-car-tunnel',
            'items' => [
                ['route' => 'renops.index', 'icon' => 'fa-car-tunnel', 'text' => 'Renops'],
                ['route' => 'schedules.index', 'icon' => 'fa-calendar-day', 'text' => 'Jadwal', 'pulse' => true],
                ['route' => 'kilometer-reports.index', 'icon' => 'fa-tachometer-alt', 'text' => 'Laporan Kilometer'],
                ['route' => 'global-kilometer-reports.index', 'icon' => 'fa-users-gear', 'text' => 'Kilometer Global'],
            ]
        ],
        [
            'label' => 'Pelaporan',
            'icon' => 'fa-person-walking-arrow-right',
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
            'icon' => 'fa-users-cog',
            'items' => [
                ['route' => 'users.index', 'icon' => 'fa-users', 'text' => 'Pengguna'],
            ]
        ]]);
    }

    $currentRouteName = request()->route()->getName() ?? '';
    $currentBase = explode('.', $currentRouteName)[0];
    $activeGroupIndex = 0;
    foreach ($navGroups as $index => $group) {
        foreach ($group['items'] as $item) {
            $itemBase = explode('.', $item['route'])[0];
            // Match by base route name: schedules.generate -> schedules -> matches schedules.index group
            if ($currentBase === $itemBase) {
                $activeGroupIndex = $index;
                break 2;
            }
        }
    }
@endphp

<div
    x-data="{ activeGroup: {{ $activeGroupIndex }} }"
    class="{{ $mobile ? 'h-full' : 'h-screen' }} flex transition-all duration-300"
    :class="{ 'w-[68px]': $store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }}, 'w-[296px]': !$store.sidebar.collapsed || {{ $mobile ? 'true' : 'false' }} }"
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

        {{-- Group Icons --}}
        <nav class="flex-1 flex flex-col items-center gap-1 py-3 overflow-y-auto">
            @foreach($navGroups as $groupIndex => $group)
                <button
                    @click="activeGroup = {{ $groupIndex }}; $store.sidebar.collapsed && ($store.sidebar.toggle())"
                    @mouseenter="$store.sidebar.collapsed && (activeGroup = {{ $groupIndex }})"
                    class="group relative flex items-center justify-center w-11 h-11 rounded-xl transition-all duration-200"
                    :class="activeGroup === {{ $groupIndex }} ? 'bg-[#1e3a5f] text-[#60a5fa]' : 'text-[#64748b] hover:text-[#94a3b8] hover:bg-[#111d32]'"
                >
                    <i class="fa-solid {{ $group['icon'] }} text-[15px]"></i>

                    <span x-show="activeGroup === {{ $groupIndex }}" class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 rounded-r-full bg-[#3b82f6]"></span>

                    @if(collect($group['items'])->contains('pulse', true))
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#ef4444] rounded-full animate-pulse"></span>
                    @endif

                    {{-- Tooltip --}}
                    <span
                        x-show="$store.sidebar.collapsed"
                        x-cloak
                        class="absolute left-full ml-3 px-2.5 py-1.5 bg-[#1e293b] text-[#e2e8f0] text-xs font-medium rounded-lg shadow-xl whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none border border-[#334155]"
                    >
                        {{ $group['label'] }}
                    </span>
                </button>
            @endforeach
        </nav>

        {{-- Collapse Toggle --}}
        @if(!$mobile)
        <div class="flex items-center justify-center py-3 border-t border-[#1a2744]">
            <button
                @click="$store.sidebar.toggle()"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-[#64748b] hover:text-[#94a3b8] hover:bg-[#111d32] transition-all duration-200"
            >
                <svg x-show="!$store.sidebar.collapsed" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                <svg x-show="$store.sidebar.collapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        @endif
    </div>

    {{-- Sub Panel --}}
    <div
        x-show="!$store.sidebar.collapsed || {{ $mobile ? 'true' : 'false' }}"
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

        {{-- Active Group Items --}}
        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-4">
            @foreach($navGroups as $groupIndex => $group)
                <div x-show="activeGroup === {{ $groupIndex }}" x-transition>
                    <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-[0.15em] text-[#475569]">{{ $group['label'] }}</p>
                    <div class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            @php
                                $itemBase = explode('.', $item['route'])[0];
                                $isActive = $currentBase === $itemBase;
                            @endphp
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-all duration-200
                                    {{ $isActive
                                        ? 'bg-[#1e3a5f] text-[#60a5fa]'
                                        : 'text-[#94a3b8] hover:bg-[#111d32] hover:text-[#e2e8f0]' }}"
                            >
                                <i class="fa-solid {{ $item['icon'] }} w-4 text-center text-[13px]
                                    {{ $isActive ? 'text-[#60a5fa]' : 'text-[#64748b]' }}"></i>
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
