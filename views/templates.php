<div class="h-full flex flex-col space-y-4">
    <div class="flex justify-between items-center border-b pb-4 bg-white p-4 rounded shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Manajemen Template</h2>
            <p class="text-xs text-gray-500">Buat struktur folder standar untuk digunakan berulang kali.</p>
        </div>
        <button onclick="app.createTemplate()" class="bg-court-gold hover:bg-yellow-600 text-white px-4 py-2 rounded shadow text-sm font-bold transition">
            <i class="fa-solid fa-plus mr-2"></i> Template Baru
        </button>
    </div>

    <div class="flex-1 overflow-hidden grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded shadow flex flex-col overflow-hidden h-full">
            <div class="p-3 bg-gray-50 border-b font-bold text-court-green text-sm uppercase">Daftar Template</div>
            <div id="templateList" class="flex-1 overflow-y-auto p-2 space-y-2">
                <div class="text-center text-gray-400 text-xs py-10">Memuat...</div>
            </div>
        </div>

        <div class="md:col-span-2 bg-white rounded shadow flex flex-col overflow-hidden h-full relative">
            
            <div id="templateEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                <i class="fa-solid fa-arrow-left text-4xl mb-4"></i>
                <p>Pilih template di samping untuk mengedit strukturnya</p>
            </div>

            <div id="templateDetail" class="hidden flex flex-col h-full">
                <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800" id="detailName">Nama Template</h3>
                        <p class="text-xs text-gray-500" id="detailDesc">-</p>
                    </div>
                    <button id="btnDeleteTemplate" class="text-red-500 text-xs hover:text-red-700 font-bold border border-red-200 bg-red-50 px-3 py-1 rounded">
                        <i class="fa-solid fa-trash mr-1"></i> Hapus Template
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 bg-white" id="templateTree">
                    </div>

                <div class="p-4 border-t bg-gray-50">
                    <h4 class="font-bold text-xs text-gray-600 mb-2 uppercase">Tambah Folder ke Template ini</h4>
                    <form id="formAddTemplateFolder" class="flex gap-2">
                        <input type="hidden" name="template_id" id="inputTempId">
                        
                        <select name="parent_id" id="inputTempParent" class="border rounded px-3 py-2 text-sm bg-white w-1/3">
                            <option value="">-- Di Root (Paling Luar) --</option>
                            </select>
                        
                        <input type="text" name="name" placeholder="Nama Folder Baru" class="border rounded px-3 py-2 text-sm flex-1 outline-none focus:border-court-green" required>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-bold shadow">
                            <i class="fa-solid fa-plus"></i> Tambah
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>