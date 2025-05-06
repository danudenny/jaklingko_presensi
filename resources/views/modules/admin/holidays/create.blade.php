@extends('modules.admin.layouts.main')

@section('title', 'Tambah Hari Libur')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Tambah Hari Libur</x-slot>
        <x-slot name="actions">
            <a href="{{ route('holidays.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('holidays.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date -->
                <div>
                    <x-input-label for="date" :value="__('Tanggal')" />
                    <input id="date" type="date" name="date" :value="old('date')" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>

                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Nama Hari Libur')" />
                    <input id="name" type="text" name="name" :value="old('name')" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Type -->
                <div>
                    <x-input-label for="type" :value="__('Tipe')" />
                    <select id="type" name="type" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="cuti_bersama" {{ old('type') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama</option>
                        <option value="libur_nasional" {{ old('type') === 'libur_nasional' ? 'selected' : '' }}>Libur Nasional</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <x-input-label for="description" :value="__('Deskripsi (Opsional)')" />
                    <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center justify-end mt-6">
                <x-button type="submit" class="ml-3">
                    Simpan
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
