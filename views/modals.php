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
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-court-gold px-4 py-3 border-b flex justify-between items-center shrink-0">
            <h3 class="font-bold text-white text-sm flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Dokumen
            </h3>
            <button type="button" onclick="app.closeModal('modalUpload')" class="text-white/70 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </div>
        
        <form id="formUpload" class="flex flex-col flex-1 overflow-hidden" enctype="multipart/form-data">
            <div class="p-4 space-y-4 flex-1 overflow-y-auto">
                
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-yellow-50 hover:border-court-gold transition relative group cursor-pointer bg-gray-50">
                    <input type="file" id="fileInput" name="files[]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" multiple required>
                    
                    <div class="space-y-2 pointer-events-none">
                        <div class="bg-white w-12 h-12 rounded-full flex items-center justify-center mx-auto shadow-sm group-hover:scale-110 transition">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-court-gold"></i>
                        </div>
                        <p class="text-sm font-bold text-gray-600 group-hover:text-court-gold">Klik atau Tarik File ke Sini</p>
                        <p class="text-xs text-gray-400">Bisa upload banyak file (PDF, JPG, DOCX)</p>
                    </div>
                </div>

                <div id="filePreviewContainer" class="hidden border-t pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-gray-700">File Terpilih (<span id="fileCount">0</span>)</span>
                        <button type="button" id="btnClearFiles" class="text-[10px] text-red-500 hover:underline font-bold">Hapus Semua</button>
                    </div>
                    <div id="fileList" class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        </div>
                </div>

            </div>

            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="app.closeModal('modalUpload')" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100">Batal</button>
                <button type="submit" id="btnSubmitUpload" class="px-4 py-2 bg-court-gold text-white rounded-lg text-xs font-bold hover:bg-yellow-600 shadow-md">
                    <i class="fa-solid fa-upload mr-1"></i> Mulai Upload
                </button>
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
            
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Target</label>
                    <input type="number" id="genTargetYear" class="w-full border border-gray-300 rounded-lg p-2 text-sm text-center font-bold" value="<?= date('Y')+1 ?>">
                </div>
                
                <div class="flex gap-2 pt-2">
                    <button onclick="app.closeModal('modalGenerate')" class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
                    <button onclick="app.applyTemplateAction()" class="w-full px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold shadow hover:bg-court-dark">Generate Sekarang</button>
                </div>
            </div>
        </div>
    </div>
</div>