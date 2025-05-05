@props(['id' => 'drawer', 'maxWidth' => 'md', 'closeButton' => true])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    'full' => 'sm:max-w-full',
][$maxWidth];
@endphp

<div
    x-data="{
        show: false,
        title: '',
        init() {
            this.$watch('show', value => {
                if (value) {
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
        },
        open(title = '') {
            this.title = title;
            this.show = true;
        },
        close() {
            this.show = false;
        }
    }"
    x-on:open-drawer.window="$event.detail.id === '{{ $id }}' ? open($event.detail.title || '') : null"
    x-on:close-drawer.window="$event.detail.id === '{{ $id }}' ? close() : null"
    x-on:toggle-drawer.window="$event.detail.id === '{{ $id }}' ? (show ? close() : open($event.detail.title || '')) : null"
    x-on:keydown.escape.window="close()"
    x-show="show"
    class="fixed inset-0 z-50 overflow-hidden"
    style="display: none;"
>
    <!-- Overlay -->
    <div 
        x-show="show" 
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 z-20 bg-gray-600 bg-opacity-75"
    ></div>
    
    <!-- Drawer Panel -->
    <div 
        x-show="show"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 flex z-40 w-full {{ $maxWidth }} bg-white shadow-xl"
    >
        <div class="relative flex-1 flex flex-col w-full">
            <!-- Drawer Header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900" x-text="title"></h2>
                @if($closeButton)
                <button 
                    @click="close()"
                    class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                >
                    <span class="sr-only">Close drawer</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                @endif
            </div>
            
            <!-- Drawer Content -->
            <div class="flex-1 overflow-y-auto p-4">
                {{ $slot }}
            </div>
            
            <!-- Drawer Footer -->
            @if(isset($footer))
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $footer }}
            </div>
            @endif
        </div>
    </div>
</div>
