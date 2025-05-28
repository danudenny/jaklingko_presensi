<div class="mt-6">
    <x-card>
        <h3 class="flex items-center mb-4 text-lg font-medium text-gray-900">
            <i class="mr-2 text-gray-500 fas fa-info-circle"></i>
            Keterangan
        </h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="p-4 rounded-lg bg-gray-50">
                <h4 class="mb-3 text-sm font-semibold text-gray-700 uppercase">Indikator Hari</h4>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="w-6 h-6 mr-3 border border-yellow-200 rounded bg-yellow-50"></div>
                        <span class="text-sm text-gray-700">Hari Libur</span>
                        <div class="relative w-6 h-6 ml-2 bg-yellow-50 highlight-holiday"></div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-6 h-6 mr-3 border border-orange-200 rounded bg-orange-50"></div>
                        <span class="text-sm text-gray-700">Hari Sabtu/Minggu</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-6 h-6 mr-3 bg-red-100 border border-red-200 rounded"></div>
                        <span class="text-sm text-gray-700">Kilometer di bawah 150</span>
                    </div>
                </div>
            </div>
            <div class="p-4 rounded-lg bg-gray-50">
                <h4 class="mb-3 text-sm font-semibold text-gray-700 uppercase">Indikator Status</h4>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-6 h-6 mr-3">
                            <i class="text-yellow-500 fas fa-exclamation-triangle"></i>
                        </div>
                        <span class="text-sm text-gray-700">Unit dalam maintenance</span>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-6 h-6 mr-3">
                            <i class="text-red-500 fas fa-exclamation-circle"></i>
                        </div>
                        <span class="text-sm text-gray-700">Unit tidak aktif</span>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-6 h-6 mr-3">
                            <span class="text-xs text-gray-500">(2)</span>
                        </div>
                        <span class="text-sm text-gray-700">Jumlah driver yang berbagi kilometer</span>
                    </div>
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-6 h-6 mr-3 bg-gray-100 rounded-full">
                        </div>
                        <span class="text-sm text-gray-700">Tidak ada kilometer</span>
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</div>