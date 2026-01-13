<div class="h-full flex flex-col">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 bg-white p-3 rounded-xl shadow-sm border border-gray-100">
        <nav id="breadcrumb" class="text-sm font-medium text-gray-600 flex items-center flex-wrap gap-1 mb-2 md:mb-0">
            </nav>
        
        <div class="flex gap-2">
            <button onclick="app.openModal('modalFolder')" class="bg-court-green hover:bg-court-dark text-white px-4 py-2 rounded-lg text-xs font-bold shadow transition flex items-center gap-2">
                <i class="fa-solid fa-folder-plus"></i> <span class="hidden md:inline">Folder Baru</span>
            </button>
            <button onclick="app.openModal('modalUpload')" class="bg-court-gold hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-xs font-bold shadow transition flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> <span class="hidden md:inline">Upload File</span>
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto pr-1">
        <div id="folderContainer" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider flex justify-between">
                <span>Nama Dokumen</span>
                <span>Aksi</span>
            </div>
            <table class="w-full text-sm text-left">
                <tbody id="fileList" class="divide-y divide-gray-100">
                    </tbody>
            </table>
            
            <div id="emptyState" class="hidden p-10 text-center text-gray-400">
                <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-regular fa-folder-open text-3xl text-gray-300"></i>
                </div>
                <p class="text-sm">Folder ini kosong.</p>
            </div>
        </div>
    </div>
</div>