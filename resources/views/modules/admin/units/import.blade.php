@extends('modules.admin.layouts.main')

@section('title', 'Import Unit')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Import Unit</h1>
        <a href="{{ route('units.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p>{{ session('error') }}</p>
    </div>
    @endif

    @if(session('warning'))
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
        <p>{{ session('warning') }}</p>
        
        @if(session('failures'))
        <div class="mt-3">
            <h4 class="font-bold">Detail Error:</h4>
            <ul class="list-disc pl-5 mt-2">
                @foreach(session('failures') as $failure)
                <li>
                    Baris {{ $failure->row() }}: 
                    @foreach($failure->errors() as $error)
                        {{ $error }}
                    @endforeach
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Pool Unit Import -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Import Unit Pool (Milik Sendiri)</h2>
            <p class="text-sm text-gray-600 mb-4">
                Gunakan fitur ini untuk mengimpor data unit yang merupakan milik perusahaan (pool). 
                Semua kolom wajib diisi sesuai dengan template.
            </p>
            
            <form action="{{ route('units.import.pool') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="mb-4">
                    <label for="pool_file" class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                    <input type="file" name="file" id="pool_file" accept=".xlsx, .xls, .csv" 
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-500">Format file: .xlsx, .xls, .csv (max 2MB)</p>
                </div>
                
                <div class="flex justify-between items-center">
                    <a href="{{ route('units.import.pool.template') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-download mr-1"></i> Download Template
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-upload mr-2"></i> Import
                    </button>
                </div>
            </form>
            
            <div class="mt-4 border-t pt-4">
                <h3 class="text-md font-medium text-gray-700 mb-2">Format Kolom:</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1 text-left">Kolom</th>
                                <th class="px-2 py-1 text-left">Keterangan</th>
                                <th class="px-2 py-1 text-left">Contoh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr>
                                <td class="px-2 py-1 font-medium">unit_number</td>
                                <td class="px-2 py-1">Nomor Unit</td>
                                <td class="px-2 py-1">001</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">plate_number</td>
                                <td class="px-2 py-1">Nomor Plat</td>
                                <td class="px-2 py-1">B 1234 ABC</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">unit_reg</td>
                                <td class="px-2 py-1">Nomor Registrasi</td>
                                <td class="px-2 py-1">REG001</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">serial_number</td>
                                <td class="px-2 py-1">Nomor Seri</td>
                                <td class="px-2 py-1">SN12345</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">kir</td>
                                <td class="px-2 py-1">KIR</td>
                                <td class="px-2 py-1">KIR123</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">expired_stnk</td>
                                <td class="px-2 py-1">Tanggal Expired STNK</td>
                                <td class="px-2 py-1">2025-12-31</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">expired_kir</td>
                                <td class="px-2 py-1">Tanggal Expired KIR</td>
                                <td class="px-2 py-1">2025-12-31</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">expired_kp</td>
                                <td class="px-2 py-1">Tanggal Expired KP</td>
                                <td class="px-2 py-1">2025-12-31</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">status</td>
                                <td class="px-2 py-1">Status (aktif/nonaktif/maintenance)</td>
                                <td class="px-2 py-1">aktif</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">route_codes</td>
                                <td class="px-2 py-1">Kode Rute (dipisahkan koma)</td>
                                <td class="px-2 py-1">R1,R2,R3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Non-Pool Unit Import -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Import Unit Non-Pool (Bukan Milik Sendiri)</h2>
            <p class="text-sm text-gray-600 mb-4">
                Gunakan fitur ini untuk mengimpor data unit yang bukan milik perusahaan. 
                Hanya perlu mengisi nomor unit dan plat nomor.
            </p>
            
            <form action="{{ route('units.import.non-pool') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                @csrf
                <div class="mb-4">
                    <label for="non_pool_file" class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                    <input type="file" name="file" id="non_pool_file" accept=".xlsx, .xls, .csv" 
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-xs text-gray-500">Format file: .xlsx, .xls, .csv (max 2MB)</p>
                </div>
                
                <div class="flex justify-between items-center">
                    <a href="{{ route('units.import.non-pool.template') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-download mr-1"></i> Download Template
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-upload mr-2"></i> Import
                    </button>
                </div>
            </form>
            
            <div class="mt-4 border-t pt-4">
                <h3 class="text-md font-medium text-gray-700 mb-2">Format Kolom:</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1 text-left">Kolom</th>
                                <th class="px-2 py-1 text-left">Keterangan</th>
                                <th class="px-2 py-1 text-left">Contoh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr>
                                <td class="px-2 py-1 font-medium">unit_number</td>
                                <td class="px-2 py-1">Nomor Unit</td>
                                <td class="px-2 py-1">001</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">plate_number</td>
                                <td class="px-2 py-1">Nomor Plat</td>
                                <td class="px-2 py-1">B 1234 ABC</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-1 font-medium">status</td>
                                <td class="px-2 py-1">Status (aktif/nonaktif/maintenance)</td>
                                <td class="px-2 py-1">aktif</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
