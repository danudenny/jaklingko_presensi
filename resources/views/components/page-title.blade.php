@props(['title', 'description' => null, 'actions' => null])

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ $title }}
            </h2>
            @if($description)
                <p class="mt-1 text-sm text-gray-500">
                    {{ $description }}
                </p>
            @endif
        </div>
        @if($actions)
            <div class="mt-4 flex md:mt-0 md:ml-4">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
