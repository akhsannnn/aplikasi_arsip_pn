<div id="modalFolder" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-court-green px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-white text-sm">Buat Folder Baru</h3>
            <button onclick="app.closeModal('modalFolder')" class="text-white/70 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="formFolder" class="p-4 space-y-3">
            <input type="text" name="name" placeholder="Nama Folder (Contoh: Surat Masuk)" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:border-court-gold focus:ring-1 focus:ring-court-gold" required>
            <textarea name="desc" placeholder="Keterangan singkat (Opsional)" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm outline-none focus:border-court-gold focus:ring-1 focus:ring-court-gold" rows="2"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="app.closeModal('modalFolder')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold hover:bg-court-dark shadow-md">Simpan Folder</button>
            </div>
        </form>
    </div>
</div>

<div id="modalUpload" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="bg-court-gold px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-white text-sm">Upload Dokumen</h3>
            <button onclick="app.closeModal('modalUpload')" class="text-white/70 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="formUpload" class="p-4 space-y-4" enctype="multipart/form-data">
            
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition relative">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2"></i>
                <p class="text-xs text-gray-500">Klik untuk memilih file</p>
                <input type="file" name="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="app.closeModal('modalUpload')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 bg-court-gold text-white rounded-lg text-xs font-bold hover:bg-yellow-600 shadow-md">Mulai Upload</button>
            </div>
        </form>
    </div>
</div>

<div id="modalGenerate" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <i class="fa-solid fa-wand-magic-sparkles text-xl text-court-green"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Terapkan Template?</h3>
            <p class="text-xs text-gray-500 mb-6">Ini akan membuat struktur folder template ke tahun target yang Anda pilih.</p>
            
            <form id="formGenerateLogic" class="text-left space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Target</label>
                    <input type="number" id="genTargetYear" class="w-full border border-gray-300 rounded-lg p-2 text-sm text-center font-bold" value="<?= date('Y')+1 ?>">
                </div>
                
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="app.closeModal('modalGenerate')" class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
                    <button type="button" onclick="app.applyTemplateAction()" class="w-full px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold shadow hover:bg-court-dark">Generate Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>