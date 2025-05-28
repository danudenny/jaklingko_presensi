<div x-show="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50" x-cloak>
    <div @click.away="closeGenerateModal()" class="relative w-full max-w-lg p-6 mx-auto bg-white rounded-lg shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Generate Laporan Kilometer Global</h3>
            <button @click="closeGenerateModal()" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="{{ route('global-kilometer-reports.generate') }}">
            @csrf
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="year" value="Tahun" class="font-medium" />
                    <select id="year" name="year" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @php
                            $currentYear = Carbon\Carbon::now()->year;
                            $years = range($currentYear - 2, $currentYear + 1);
                        @endphp
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year', date('Y')) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('year')" class="mt-2" />
                </div>
                
                <div>
                    <x-input-label for="month" value="Bulan" class="font-medium" />
                    <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @php
                            $months = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ];
                        @endphp
                        @foreach($months as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ request('month', date('n')) == $monthNumber ? 'selected' : '' }}>
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
                            <input type="radio" class="form-radio" name="period" value="1" {{ request('period', 1) == 1 ? 'checked' : '' }}>
                            <span class="ml-2">Periode 1 (1-15)</span>
                        </label>
                    </div>
                    <div>
                        <label class="inline-flex items-center">
                            <input type="radio" class="form-radio" name="period" value="2" {{ request('period', 1) == 2 ? 'checked' : '' }}>
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

            <div class="flex justify-end mt-6 space-x-2">
                <button type="button" @click="closeGenerateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Batal
                </button>
                <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="mr-2 fas fa-sync-alt"></i>
                    Generate Laporan
                </button>
            </div>
        </form>
    </div>
</div>