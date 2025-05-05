@props(['messages' => null, 'message' => null])

@php
    // Support both single message and messages array
    $errorMessages = $messages ?? (is_null($message) ? [] : [$message]);
@endphp

@if (!empty($errorMessages))
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ((array) $errorMessages as $errorMessage)
            <li>{{ $errorMessage }}</li>
        @endforeach
    </ul>
@endif
