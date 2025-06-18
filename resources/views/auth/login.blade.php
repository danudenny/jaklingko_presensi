<x-guest-layout>
    <div class="fixed inset-0 flex items-center justify-center bg-gray-100">
        <div class="w-full px-6 py-8 overflow-hidden bg-white shadow-md sm:max-w-md sm:rounded-lg">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
                <p class="mt-1 text-gray-600">Sign in to your account</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-6">
                    <x-input-label for="email" :value="__('Email Address')" class="font-medium text-gray-700" />
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                        <x-text-input id="email" class="block w-full pl-10 mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="your.email@example.com" />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <x-input-label for="password" :value="__('Password')" class="font-medium text-gray-700" />
                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-blue-600 hover:text-blue-800" href="{{ route('password.request') }}">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <x-text-input id="password" class="block w-full pl-10 mt-1"
                                    type="password"
                                    name="password"
                                    required autocomplete="current-password"
                                    placeholder="••••••••" />
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="block mb-6">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="text-blue-600 border-gray-300 rounded shadow-sm focus:ring-blue-500" name="remember">
                        <span class="text-sm text-gray-600 ms-2">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center justify-center">
                    <x-primary-button class="justify-center w-full py-3 bg-blue-600 hover:bg-blue-700">
                        {{ __('Sign in') }}
                    </x-primary-button>
                </div>
            </form>
            
            <!-- Demo Login Options -->
            {{-- @if (app()->environment('local', 'development', 'staging'))
            <div class="pt-6 mt-8 border-t border-gray-200">
                <h3 class="mb-4 text-sm font-medium text-center text-gray-700">Demo Login Options</h3>
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" 
                            onclick="document.getElementById('email').value='admin@jaklingko.com';document.getElementById('password').value='password';" 
                            class="flex items-center justify-center px-4 py-2 text-purple-700 transition bg-purple-100 rounded-md hover:bg-purple-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 005 10a6 6 0 0012 0c0-.34-.035-.668-.1-.983A5.002 5.002 0 0010 11z" clip-rule="evenodd" />
                        </svg>
                        Super Admin
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('email').value='user@jaklingko.com';document.getElementById('password').value='password';" 
                            class="flex items-center justify-center px-4 py-2 text-green-700 transition bg-green-100 rounded-md hover:bg-green-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                        Regular Admin
                    </button>
                </div>
                <p class="mt-3 text-xs text-center text-gray-500">Click a button to prefill credentials, then click Sign in.</p>
            </div>
            @endif --}}

            <div class="mt-6 text-sm text-center text-gray-600">
                <p> {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</x-guest-layout>
