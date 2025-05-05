@props([
    'type' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors rounded-md';
    
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ][$size] ?? 'px-4 py-2 text-sm';
    
    $typeClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white border border-transparent focus:ring-blue-500',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white border border-transparent focus:ring-gray-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white border border-transparent focus:ring-green-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white border border-transparent focus:ring-red-500',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white border border-transparent focus:ring-yellow-500',
        'info' => 'bg-indigo-600 hover:bg-indigo-700 text-white border border-transparent focus:ring-indigo-500',
        'light' => 'bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 focus:ring-gray-500',
        'dark' => 'bg-gray-800 hover:bg-gray-900 text-white border border-transparent focus:ring-gray-500',
        'link' => 'bg-transparent hover:underline text-blue-600 border-0 shadow-none',
    ][$type] ?? 'bg-blue-600 hover:bg-blue-700 text-white border border-transparent focus:ring-blue-500';
    
    $disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer';
    
    $classes = $baseClasses . ' ' . $sizeClasses . ' ' . $typeClasses . ' ' . $disabledClasses . ' ' . ($attributes->get('class') ?? '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
