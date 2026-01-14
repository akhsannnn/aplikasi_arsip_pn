<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
    <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center shrink-0">
        <div>
            <h2 class="text-lg font-bold text-red-800 flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i> Tong Sampah
            </h2>
            <p class="text-xs text-red-600 mt-1">Item yang dihapus lebih dari 30 hari akan hilang permanen.</p>
        </div>
        <button onclick="app.emptyTrash()" class="bg-white border border-red-200 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2 group">
            <i class="fa-solid fa-fire group-hover:animate-pulse"></i> Kosongkan Sampah
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0 bg-gray-50" id="trashList">
        <div class="flex flex-col items-center justify-center h-full text-gray-400">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i>
            <p class="text-xs">Memuat data sampah...</p>
        </div>
    </div>
</div>