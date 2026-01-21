<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
    <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
        <div>
            <h2 class="text-lg font-bold text-red-800 flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i> Tong Sampah
            </h2>
            <p class="text-xs text-red-600 mt-1">Item yang dihapus lebih dari 30 hari akan hilang permanen.</p>
        </div>

        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:flex-none">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-red-300 text-xs"></i>
                </div>
                <input type="text" 
                       placeholder="Cari sampah..." 
                       class="w-full md:w-48 pl-8 pr-3 py-2 bg-white border border-red-200 rounded-lg text-xs text-red-800 placeholder-red-300 focus:ring-1 focus:ring-red-500 outline-none transition"
                       onkeyup="app.searchTrash(this.value)">
            </div>

            <button onclick="app.emptyTrash()" class="bg-white border border-red-200 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2 group whitespace-nowrap">
                <i class="fa-solid fa-fire group-hover:animate-pulse"></i> Kosongkan
            </button>
        </div>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0 bg-gray-50" id="trashList">
        <div class="flex flex-col items-center justify-center h-full text-gray-400">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl mb-2"></i>
            <p class="text-xs">Memuat data sampah...</p>
        </div>
    </div>
</div>