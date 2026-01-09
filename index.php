<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Arsip Pengadilan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { court: { green: '#1b5e20', dark: '#003300', gold: '#c6a700', light: '#f1f8e9' } },
                    fontFamily: { serif: ['Merriweather', 'serif'], sans: ['Roboto', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-court-green text-white shadow-lg z-20 shrink-0">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-scale-balanced text-court-gold text-2xl"></i>
                <div>
                    <h1 class="text-lg font-serif font-bold text-court-gold">SISTEM E-ARSIP</h1>
                    <p class="text-[10px] text-gray-300 uppercase tracking-widest">Pengadilan Negeri</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="app.openGenerateModal()" class="text-xs bg-court-gold text-court-dark px-3 py-1.5 rounded font-bold hover:bg-yellow-500 transition shadow">
                    <i class="fa-solid fa-copy mr-1"></i> Template / Generate
                </button>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <aside class="w-64 bg-white shadow-md border-r border-gray-200 overflow-y-auto hidden md:block z-10 flex-shrink-0">
            <div class="p-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase mb-3">Tahun Arsip</h3>
                <div id="yearList" class="space-y-1"></div>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-gray-50 relative" id="mainArea">
            
            <div id="loadingOverlay" class="hidden absolute inset-0 bg-white/80 z-40 flex flex-col items-center justify-center backdrop-blur-sm">
                <i class="fa-solid fa-circle-notch fa-spin text-4xl text-court-green mb-3"></i>
                <p class="text-court-dark font-bold animate-pulse">Sedang memproses...</p>
            </div>

            <div id="emptyStateContainer" class="hidden h-full flex flex-col justify-center">
                </div>

            <div id="contentContainer">
                
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 bg-white p-4 rounded-lg shadow-sm border-l-4 border-court-gold">
                    <nav class="flex text-sm"><ol class="inline-flex items-center space-x-1" id="breadcrumbList"></ol></nav>
                    <div class="flex gap-2 shrink-0">
                        <button onclick="app.openFolderModal()" class="bg-court-green hover:bg-court-dark text-white px-3 py-2 rounded text-sm shadow transition"><i class="fa-solid fa-folder-plus"></i> Folder</button>
                        <button onclick="app.openUploadModal()" class="bg-court-gold hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm shadow transition"><i class="fa-solid fa-cloud-arrow-up"></i> Upload</button>
                    </div>
                </div>

                <div id="folderContainer" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-8"></div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-100 px-6 py-3 border-b border-gray-200"><h2 class="text-xs font-bold text-gray-700 uppercase">Dokumen</h2></div>
                    <table class="min-w-full divide-y divide-gray-200"><tbody id="fileList" class="bg-white divide-y divide-gray-200"></tbody></table>
                    <div id="emptyState" class="hidden p-8 text-center text-gray-400"><p>Tidak ada dokumen di folder ini.</p></div>
                </div>
            
            </div>
        </main>
    </div>

    <div id="modalConfirm" class="hidden fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 transform scale-100 transition-transform duration-300">
            <div class="text-center">
                <div id="confirmIcon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900" id="confirmTitle">Konfirmasi</h3>
                <p class="text-sm text-gray-500 mt-2" id="confirmMessage">Apakah Anda yakin?</p>
            </div>
            <div class="mt-6 flex justify-center gap-3">
                <button onclick="app.closeConfirm()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm font-medium">Batal</button>
                <button id="btnConfirmAction" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium shadow-lg">Ya, Lakukan</button>
            </div>
        </div>
    </div>

    <div id="modalGenerate" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">
            <button onclick="app.closeModal('modalGenerate')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500"><i class="fa-solid fa-times"></i></button>
            <h3 class="text-lg font-bold text-court-green mb-1">Generate Tahun Baru</h3>
            <p class="text-xs text-gray-500 mb-4">Salin struktur folder dari tahun lama.</p>
            <form id="formGenerate">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Dari Tahun</label><select id="genSource" name="source_year" class="w-full border rounded p-2 text-sm bg-gray-50"></select></div>
                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Ke Tahun Baru</label><input type="number" name="target_year" class="w-full border rounded p-2 text-sm" required></div>
                </div>
                <button type="submit" class="w-full bg-court-green text-white py-2 rounded hover:bg-court-dark font-bold text-sm">Mulai Proses</button>
            </form>
        </div>
    </div>

    <div id="modalFolder" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg w-96 p-6 shadow-xl">
            <h3 class="font-bold text-court-green mb-4" id="modalFolderTitle">Folder</h3>
            <form id="formFolder">
                <input type="hidden" name="id" id="folderId">
                <div class="mb-3"><label class="text-xs font-bold block text-gray-600">Nama Folder</label><input type="text" name="name" id="folderName" required class="w-full border p-2 rounded text-sm focus:border-court-gold outline-none"></div>
                <div class="mb-3"><label class="text-xs font-bold block text-gray-600">Keterangan</label><textarea name="desc" id="folderDesc" class="w-full border p-2 rounded text-sm focus:border-court-gold outline-none"></textarea></div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="app.closeModal('modalFolder')" class="px-3 py-1 bg-gray-200 rounded text-sm">Batal</button>
                    <button type="submit" class="px-3 py-1 bg-court-green text-white rounded text-sm hover:bg-court-dark">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalUpload" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg w-96 p-6 shadow-xl">
            <h3 class="font-bold text-court-green mb-4">Upload Dokumen</h3>
            <form id="formUpload" enctype="multipart/form-data">
                <input type="file" name="file" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-court-light file:text-court-green">
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="app.closeModal('modalUpload')" class="px-3 py-1 bg-gray-200 rounded text-sm">Batal</button>
                    <button type="submit" class="px-3 py-1 bg-court-gold text-white rounded text-sm hover:bg-yellow-600">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalPreview" class="hidden fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden">
            <div class="bg-court-green text-white px-4 py-2 flex justify-between items-center shrink-0">
                <h3 class="font-bold text-sm truncate" id="previewTitle">Preview</h3>
                <button onclick="app.closeModal('modalPreview')" class="text-white hover:text-red-300 text-lg"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="flex-1 bg-gray-200 flex items-center justify-center overflow-auto p-2" id="previewContent"></div>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>