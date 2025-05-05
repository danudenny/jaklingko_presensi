@props(['title' => null, 'footer' => null, 'padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200']) }}>
    @if($title)
    <div class="px-4 py-3 sm:px-6 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-medium leading-6 text-gray-900">{{ $title }}</h3>
    </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if($footer)
    <div class="px-4 py-3 sm:px-6 border-t border-gray-200 bg-gray-50">
        {{ $footer }}
    </div>
    @endif
</div>

