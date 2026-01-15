<div class="h-full flex flex-col space-y-4">
    <div class="flex justify-between items-center border-b pb-4 bg-white p-4 rounded-xl shadow-sm shrink-0">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Template Manager</h2>
            <p class="text-xs text-gray-500">Buat standar folder arsip untuk digunakan setiap tahun.</p>
        </div>
        <button onclick="app.createTemplate()" class="bg-court-gold hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow text-sm font-bold transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Buat Template
        </button>
    </div>

    <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-3 gap-6 min-h-0">
        
        <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col overflow-hidden h-full">
            <div class="p-3 bg-gray-50 border-b font-bold text-gray-600 text-xs uppercase tracking-wider shrink-0">
                Daftar Template
            </div>
            <div id="templateList" class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
                <div class="text-center text-gray-400 text-xs py-10 flex flex-col items-center">
                    <i class="fa-solid fa-circle-notch fa-spin mb-2"></i>
                    Memuat data...
                </div>
            </div>
        </div>

        <div class="md:col-span-2 bg-white rounded-xl shadow border border-gray-100 flex flex-col overflow-hidden h-full relative">
            
            <div id="templateEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 bg-gray-50/50 z-10">
                <div class="bg-white p-4 rounded-full shadow-sm mb-4">
                    <i class="fa-solid fa-arrow-left text-2xl text-court-gold"></i>
                </div>
                <p class="text-sm font-medium">Pilih template di kiri untuk melihat detail.</p>
            </div>

            <div id="templateDetail" class="hidden flex flex-col h-full bg-white z-20">
                <div class="p-4 border-b flex justify-between items-center bg-gray-50 shrink-0">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-layer-group text-court-green"></i> 
                            <span id="detailName">Nama Template</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1" id="detailDesc">-</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="app.openModal('modalGenerate')" class="bg-court-green text-white text-xs px-3 py-1.5 rounded hover:bg-court-dark transition font-bold shadow-sm flex items-center gap-1">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Pakai
                        </button>
                        <button id="btnDeleteTemplate" class="text-red-500 text-xs hover:text-red-700 font-bold border border-red-200 bg-white px-3 py-1.5 rounded transition" title="Hapus Template">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar" id="templateTree">
                    </div>

                <div class="p-4 border-t bg-gray-50 shrink-0">
                    <h4 class="font-bold text-xs text-gray-600 mb-2 uppercase flex items-center gap-2">
                        <i class="fa-solid fa-plus-circle text-court-gold"></i> Tambah Folder Struktur
                    </h4>
                    <form id="formAddTemplateFolder" class="flex gap-2 items-center">
                        <input type="hidden" name="template_id" id="inputTempId">
                        
                        <select name="parent_id" id="inputTempParent" class="border border-gray-300 rounded-lg px-3 py-2 text-xs bg-white w-1/3 focus:ring-1 focus:ring-court-gold outline-none text-gray-600">
                            <option value="">-- Di Root (Paling Luar) --</option>
                        </select>
                        
                        <input type="text" name="name" placeholder="Nama Folder Baru..." class="border border-gray-300 rounded-lg px-3 py-2 text-xs flex-1 outline-none focus:border-court-green focus:ring-1 focus:ring-court-green" required>
                        
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-bold shadow transition">
                            Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>