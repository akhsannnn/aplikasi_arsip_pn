<div id="modalFolder" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-court-green px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-white text-sm">Buat Folder Baru</h3>
            <button onclick="app.closeModal('modalFolder')" class="text-white/70 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="formFolder" class="p-4 space-y-3">
            <input type="text" name="name" placeholder="Nama Folder" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-1 focus:ring-court-gold" required>
            <textarea name="desc" placeholder="Keterangan (Opsional)" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-1 focus:ring-court-gold" rows="2"></textarea>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="app.closeModal('modalFolder')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold shadow-md">Simpan</button>
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
                        <p class="text-xs text-gray-400">Support PDF, Excel, Word, Gambar</p>
                    </div>
                </div>
                <div id="filePreviewContainer" class="hidden border-t pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-gray-700">File Terpilih (<span id="fileCount">0</span>)</span>
                        <button type="button" id="btnClearFiles" class="text-[10px] text-red-500 hover:underline font-bold">Hapus Semua</button>
                    </div>
                    <div id="uploadPreviewList" class="space-y-2 max-h-40 overflow-y-auto pr-1"></div>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="app.closeModal('modalUpload')" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
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
            <p class="text-xs text-gray-500 mb-6">Membuat struktur folder ke tahun target.</p>
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Target</label>
                    <input type="number" id="genTargetYear" class="w-full border border-gray-300 rounded-lg p-2 text-sm text-center font-bold" value="<?= date('Y')+1 ?>">
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="app.closeModal('modalGenerate')" class="w-full px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
                    <button onclick="app.applyTemplateAction()" class="w-full px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold shadow">Generate</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalRestore" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-green-600 px-6 py-4 flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-full text-white"><i class="fa-solid fa-rotate-left text-xl"></i></div>
            <h3 class="font-bold text-white text-lg">Pulihkan Item?</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-600 text-sm mb-4">Pulihkan: <strong id="restoreItemName"></strong>?</p>
            <div class="flex gap-3">
                <button onclick="app.closeModal('modalRestore')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-bold">Batal</button>
                <button onclick="app.confirmRestoreAction()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-bold shadow hover:bg-green-700">Ya, Pulihkan</button>
            </div>
        </div>
    </div>
</div>

<div id="modalDeletePerm" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-red-600 px-6 py-4 flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-full text-white"><i class="fa-solid fa-triangle-exclamation text-xl"></i></div>
            <h3 class="font-bold text-white text-lg">Hapus Permanen?</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-600 text-sm mb-4">Hapus <strong id="delPermItemName"></strong> selamanya?</p>
            <div class="flex gap-3">
                <button onclick="app.closeModal('modalDeletePerm')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-bold">Batal</button>
                <button onclick="app.confirmDeletePermAction()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-bold shadow hover:bg-red-700">Hapus Mati</button>
            </div>
        </div>
    </div>
</div>

<div id="modalSoftDelete" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-orange-500 px-6 py-4 flex items-center gap-3">
            <div class="bg-white/20 p-2 rounded-full text-white"><i class="fa-solid fa-trash-can text-xl"></i></div>
            <div>
                <h3 class="font-bold text-white text-lg">Pindahkan ke Sampah?</h3>
                <p class="text-orange-100 text-xs">Item masih bisa dipulihkan nanti.</p>
            </div>
        </div>
        <div class="p-6">
            <p class="text-gray-600 text-sm mb-4">Pindahkan <strong id="softDeleteName"></strong> ke Tong Sampah?</p>
            <div class="flex gap-3">
                <button onclick="app.closeModal('modalSoftDelete')" class="flex-1 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-bold">Batal</button>
                <button onclick="app.confirmSoftDeleteAction()" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg font-bold shadow hover:bg-orange-600">Ya, Pindahkan</button>
            </div>
        </div>
    </div>
</div>