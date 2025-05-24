@extends('modules.admin.layouts.main')

@section('title', 'Generate Laporan Kilometer Global')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Generate Laporan Kilometer Global</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('global-kilometer-reports.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <div class="max-w-5xl mx-auto">
        <x-card>
            <h2 class="mb-4 text-lg font-medium text-gray-900">Generate Laporan Kilometer Global</h2>
            
            <form method="POST" action="{{ route('global-kilometer-reports.generate') }}">
                @csrf
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="year" value="Tahun" class="font-medium" />
                        <select id="year" name="year" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ old('year', date('Y')) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('year')" class="mt-2" />
                    </div>
                    
                    <div>
                        <x-input-label for="month" value="Bulan" class="font-medium" />
                        <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($months as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" {{ old('month', date('n')) == $monthNumber ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('month')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6">
                    <x-input-label value="Periode" class="font-medium" />
                    <div class="flex mt-2 space-x-4">
                        <div>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="period" value="1" checked>
                                <span class="ml-2">Periode 1 (1-15)</span>
                            </label>
                        </div>
                        <div>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="period" value="2">
                                <span class="ml-2">Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->day }})</span>
                            </label>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('period')" class="mt-2" />
                </div>

                <div class="p-4 mt-6 border border-yellow-200 rounded-md bg-yellow-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-yellow-600 fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Perhatian:</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Proses ini akan menghapus dan menggenerasi ulang semua data laporan kilometer global untuk periode yang dipilih. Pastikan data kilometer dan jadwal driver sudah benar sebelum melanjutkan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <x-primary-button type="submit">
                        <i class="mr-2 fas fa-sync-alt"></i>
                        Generate Laporan
                    </x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
