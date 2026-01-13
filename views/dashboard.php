<div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Dashboard Overview</h2>
        <span class="text-xs bg-white px-3 py-1 rounded shadow text-gray-500">
            <i class="fa-regular fa-calendar mr-1"></i> <?= date('d F Y') ?>
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-500 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase text-gray-500 font-bold tracking-wider">Total Dokumen</div>
                <div class="text-2xl font-bold text-gray-800 mt-1" id="dashFiles">0</div>
            </div>
            <div class="bg-blue-50 p-3 rounded-full text-blue-500">
                <i class="fa-solid fa-file-lines text-xl"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-green-500 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase text-gray-500 font-bold tracking-wider">Total Folder</div>
                <div class="text-2xl font-bold text-gray-800 mt-1" id="dashFolders">0</div>
            </div>
            <div class="bg-green-50 p-3 rounded-full text-green-500">
                <i class="fa-solid fa-folder text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <h3 class="font-bold text-gray-700 mb-4 border-b pb-2 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-court-gold"></i> Aktivitas Terakhir
        </h3>
        <div id="dashRecent" class="space-y-1">
            <div class="text-gray-400 text-xs italic">Memuat data...</div>
        </div>
    </div>
</div>