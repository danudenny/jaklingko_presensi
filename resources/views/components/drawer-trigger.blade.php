@props(['id' => 'drawer', 'title' => '', 'type' => 'button', 'class' => ''])

<button 
    type="{{ $type }}" 
    {{ $attributes->merge(['class' => $class]) }}
    x-data="{}"
    @click="$dispatch('open-drawer', { id: '{{ $id }}', title: '{{ $title }}' })"
>
    {{ $slot }}
</button>
