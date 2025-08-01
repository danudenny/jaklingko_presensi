@extends('modules.admin.layouts.main')

@section('title', 'Import Pengemudi')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Import Data Pengemudi</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Kembali
                </a>
                <a href="{{ route('drivers.import.template') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-download mr-1"></i>
                    Download Template (Real Data)
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <div class="mt-6">
        <x-card>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Import Data Pengemudi</h2>
                
                <div class="mb-6">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Silakan unduh template Excel terlebih dahulu. Template ini berisi data pengemudi yang sudah ada di sistem. Pastikan data yang dimasukkan sesuai dengan format yang telah ditentukan.
                                </p>
                                <p class="text-sm text-blue-700 mt-2">
                                    <strong>Catatan:</strong> Jika nama pengemudi sudah ada di sistem, data akan diperbarui. Jika belum ada, data baru akan ditambahkan.
                                </p>
                                <p class="text-sm text-blue-700 mt-2">
                                    <strong>Format Multiple Rute & Unit:</strong> Untuk pengemudi dengan beberapa rute atau unit, pisahkan dengan koma. Contoh: "JAK1, JAK2" atau "B1234, B5678".
                                </p>
                            </div>
                        </div>
                    </div>

                    @if(session('error'))
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        {!! session('error') !!}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('drivers.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <x-input-label for="file" :value="__('File Excel')" />
                            <input type="file" name="file" id="file" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required accept=".xlsx,.xls,.csv">
                            <p class="mt-1 text-sm text-gray-500">Format file yang diperbolehkan: .xlsx, .xls, .csv (max 2MB)</p>
                            @error('file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Format Kolom Excel:</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-gray-600">
                                <div>
                                    <p>- Route (No. Rute, bisa dipisahkan dengan koma untuk multiple rute)</p>
                                    <p>- Unit (No. Unit, bisa dipisahkan dengan koma untuk multiple unit)</p>
                                    <p>- NAMA PRAMUDI</p>
                                    <p>- Type (Batangan/Cadangan)</p>
                                    <p>- No KTP</p>
                                </div>
                                <div>
                                    <p>- No KPP</p>
                                    <p>- No KK</p>
                                    <p>- No Rekening</p>
                                    <p>- Telepon</p>
                                    <p>- Email</p>
                                    <p>- Status (Aktif/Nonaktif)</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-button type="submit" class="bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-upload mr-1"></i>
                                Import Data
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection
