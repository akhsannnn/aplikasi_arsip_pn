const app = {
    // --- STATE ---
    year: new Date().getFullYear(),
    folderId: null,
    
    // --- INIT ---
    init: function() {
        this.loadContent();
        this.setupListeners();
    },

  // ... (Bagian atas app.js tetap sama) ...

    // --- CORE FETCH (UPDATE) ---
    loadContent: async function() {
        this.toggleLoading(true);
        try {
            const res = await fetch(`api.php?action=get_content&year=${this.year}&folder_id=${this.folderId}`);
            const json = await res.json();
            
            if(json.success) {
                // Simpan data years untuk logic empty state
                this.availableYears = json.data.years; 
                
                this.renderYears(json.data.years);
                this.renderBreadcrumbs(json.data.breadcrumbs);
                
                // LOGIC BARU: Cek apakah konten kosong?
                const isContentEmpty = (json.data.folders.length === 0 && json.data.files.length === 0);
                
                if (isContentEmpty && this.folderId === null) {
                    // Jika di Root (Awal) dan kosong -> Tampilkan Onboarding
                    this.renderEmptyState(json.data.years);
                } else {
                    // Jika ada isi -> Tampilkan normal
                    document.getElementById('emptyStateContainer').classList.add('hidden');
                    document.getElementById('contentContainer').classList.remove('hidden');
                    this.renderFolders(json.data.folders);
                    this.renderFiles(json.data.files);
                }

                // Update Dropdown Source untuk Modal Generate
                const genSelect = document.getElementById('genSource');
                if(genSelect) {
                    genSelect.innerHTML = json.data.years.map(y => `<option value="${y}">${y}</option>`).join('');
                }
            }
        } catch (error) {
            console.error(error);
            this.showAlert('Error', 'Gagal memuat data.', 'danger');
        }
        this.toggleLoading(false);
    },

    // --- UI LOGIC BARU: EMPTY STATE / ONBOARDING ---
    renderEmptyState: function(existingYears) {
        // Sembunyikan container konten normal
        document.getElementById('contentContainer').classList.add('hidden');
        const container = document.getElementById('emptyStateContainer');
        container.classList.remove('hidden');

        // Cek: Apakah ini benar-benar pengguna baru (Database kosong melompong)?
        const isFreshInstall = existingYears.length === 0;

        if (isFreshInstall) {
            // Tampilan User Baru
            container.innerHTML = `
                <div class="text-center py-20">
                    <div class="bg-green-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fa-solid fa-folder-plus text-5xl text-court-green"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Belum ada dokumen</h2>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">Sistem arsip siap digunakan. Mulailah dengan membuat folder kategori pertama Anda untuk tahun ${this.year}.</p>
                    <button onclick="app.openFolderModal()" class="bg-court-green text-white px-6 py-3 rounded-lg shadow-lg hover:bg-court-dark transition font-bold text-lg">
                        <i class="fa-solid fa-plus mr-2"></i> Buat Folder Pertama
                    </button>
                </div>
            `;
        } else {
            // Tampilan Tahun Kosong (Tapi tahun lain ada isinya)
            // User bisa pilih: Bikin Manual ATAU Copy dari tahun lain
            container.innerHTML = `
                <div class="text-center py-16">
                    <div class="bg-yellow-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-dashed border-court-gold">
                        <i class="fa-regular fa-folder-open text-4xl text-court-gold"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Tahun ${this.year} masih kosong</h2>
                    <p class="text-gray-500 mb-8">Anda bisa membuat folder baru secara manual, atau menyalin struktur dari tahun sebelumnya.</p>
                    
                    <div class="flex justify-center gap-4">
                        <button onclick="app.openFolderModal()" class="bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-50 transition font-medium">
                            <i class="fa-solid fa-folder-plus mr-2 text-court-green"></i> Buat Manual
                        </button>
                        <button onclick="app.openGenerateModalWithTarget(${this.year})" class="bg-court-gold text-white px-5 py-2.5 rounded-lg shadow hover:bg-yellow-600 transition font-medium">
                            <i class="fa-solid fa-copy mr-2"></i> Copy Template Tahun Lain
                        </button>
                    </div>
                </div>
            `;
        }
    },
    
    // Helper baru untuk membuka modal generate dengan target tahun otomatis terisi
    openGenerateModalWithTarget: function(targetYear) {
        document.querySelector('input[name="target_year"]').value = targetYear;
        this.openGenerateModal();
    },

    // ... (Sisa fungsi renderYears, renderBreadcrumbs, dll TETAP SAMA) ...
    // --- RENDERING UI ---
    renderYears: function(years) {
        // [FIX BARU] Pastikan tahun yang sedang aktif (this.year) selalu masuk list
        // meskipun belum ada datanya di database.
        const currentY = parseInt(this.year);
        
        // Konversi semua tahun dari DB ke integer agar perbandingannya benar
        let allYears = years.map(y => parseInt(y));

        // Jika tahun aktif tidak ada di list DB, masukkan manual
        if (!allYears.includes(currentY)) {
            allYears.push(currentY);
        }

        // Urutkan dari tahun terbaru (Descending)
        allYears.sort((a, b) => b - a);

        const html = allYears.map(y => {
            const active = (y == this.year) ? 'bg-court-green text-white border-l-4 border-court-gold shadow' : 'text-gray-700 hover:bg-gray-100';
            
            // Tombol hapus hanya muncul jika tahun ini ada di database (cek array asli 'years')
            // Jika ini cuma tahun default (2026 kosong), jangan kasih tombol hapus biar ga error
            let btnDelete = '';
            if (years.includes(y.toString()) || years.includes(y)) {
                 btnDelete = `<button onclick="app.confirmDeleteYear(${y})" class="p-2 text-gray-300 hover:text-red-400 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-trash text-xs"></i></button>`;
            }

            return `<div class="flex items-center justify-between group mb-1 ${active} rounded pr-2 transition">
                        <button onclick="app.changeYear(${y})" class="flex-1 text-left px-3 py-2 text-sm focus:outline-none"><i class="fa-regular fa-folder-open mr-2"></i> ${y}</button>
                        ${btnDelete}
                    </div>`;
        }).join('');
        
        document.getElementById('yearList').innerHTML = html;
    },

    renderBreadcrumbs: function(crumbs) {
        let html = `<li class="inline-flex items-center"><a href="#" onclick="app.openFolder(null)" class="text-court-green font-bold hover:underline"><i class="fa-solid fa-house"></i> ${this.year}</a></li>`;
        crumbs.forEach(c => {
            html += `<li><div class="flex items-center"><i class="fa-solid fa-chevron-right text-gray-400 mx-2 text-[10px]"></i><span class="text-gray-600 text-xs font-medium uppercase">${c.name}</span></div></li>`;
        });
        document.getElementById('breadcrumbList').innerHTML = html;
    },

    renderFolders: function(folders) {
        document.getElementById('folderContainer').innerHTML = folders.map(f => `
            <div class="group bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md hover:border-court-green transition relative">
                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                    <button onclick="app.openFolderModal(${f.id}, '${f.name}', '${f.description||''}')" class="text-gray-400 hover:text-blue-500 p-1"><i class="fa-solid fa-pen"></i></button>
                    <button onclick="app.confirmDeleteFolder(${f.id})" class="text-gray-400 hover:text-red-500 p-1"><i class="fa-solid fa-trash"></i></button>
                </div>
                <div onclick="app.openFolder(${f.id})" class="cursor-pointer">
                    <div class="text-court-green mb-2"><i class="fa-solid fa-folder text-3xl"></i></div>
                    <h4 class="text-sm font-bold text-gray-700 truncate" title="${f.name}">${f.name}</h4>
                    <p class="text-[10px] text-gray-400 mt-1 h-4 overflow-hidden">${f.description || ''}</p>
                </div>
            </div>
        `).join('');
    },

    renderFiles: function(files) {
        const list = document.getElementById('fileList');
        const empty = document.getElementById('emptyState');
        
        if (files.length === 0) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
        
        empty.classList.add('hidden');
        list.innerHTML = files.map(f => {
            let action = `<a href="${f.filepath}" target="_blank" class="text-court-green hover:underline text-xs font-bold">Unduh</a>`;
            if (f.is_previewable) {
                action = `<button onclick="app.previewFile('${f.filepath}', '${f.filename}', '${f.filetype}')" class="text-court-green hover:text-court-dark font-bold text-xs mr-2 border border-court-green px-2 py-1 rounded">Lihat</button>
                          <a href="${f.filepath}" download class="text-gray-400 hover:text-gray-600 text-xs"><i class="fa-solid fa-download"></i></a>`;
            }
            return `<tr class="hover:bg-gray-50 border-b">
                        <td class="px-6 py-3 text-sm text-gray-800 flex items-center gap-2"><i class="fa-solid fa-file text-gray-400"></i> ${f.filename}</td>
                        <td class="px-6 py-3 text-right flex justify-end items-center gap-2">
                            ${action}
                            <button onclick="app.confirmDeleteFile(${f.id})" class="text-red-400 hover:text-red-600 text-xs ml-2 p-1"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>`;
        }).join('');
    },

    // --- ACTIONS & CONFIRMATIONS ---
    
    // Generic Custom Confirmation (Tailwind Style)
    showConfirm: function(title, message, type, callback) {
        const modal = document.getElementById('modalConfirm');
        const icon = document.getElementById('confirmIcon');
        const btn = document.getElementById('btnConfirmAction');
        
        document.getElementById('confirmTitle').innerText = title;
        document.getElementById('confirmMessage').innerText = message;
        
        // Style based on Type
        if(type === 'danger') {
            icon.className = "mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4";
            icon.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>';
            btn.className = "px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium shadow-lg";
            btn.innerText = "Ya, Hapus";
        } else {
            icon.className = "mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4";
            icon.innerHTML = '<i class="fa-solid fa-info text-blue-600 text-xl"></i>';
            btn.className = "px-4 py-2 bg-court-green text-white rounded hover:bg-court-dark text-sm font-medium shadow-lg";
            btn.innerText = "Ya, Lanjutkan";
        }

        // Setup Callback
        btn.onclick = () => { callback(); this.closeConfirm(); };
        
        modal.classList.remove('hidden');
    },

    closeConfirm: function() {
        document.getElementById('modalConfirm').classList.add('hidden');
    },

    // Specific Actions
    confirmDeleteYear: function(y) {
        this.showConfirm('Hapus Tahun?', `Seluruh folder & file tahun ${y} akan dihapus permanen!`, 'danger', async () => {
            const fd = new FormData(); fd.append('year', y);
            await this.postData('delete_year', fd);
            if(this.year == y) this.year = new Date().getFullYear();
            this.loadContent();
        });
    },

    confirmDeleteFolder: function(id) {
        this.showConfirm('Hapus Folder?', 'Folder beserta isinya akan dihapus.', 'danger', async () => {
            const fd = new FormData(); fd.append('id', id);
            await this.postData('delete_folder', fd);
            this.loadContent();
        });
    },

    confirmDeleteFile: function(id) {
        this.showConfirm('Hapus File?', 'File akan dihapus permanen.', 'danger', async () => {
            const fd = new FormData(); fd.append('id', id);
            await this.postData('delete_file', fd);
            this.loadContent();
        });
    },

    // --- FORM HANDLERS ---
    setupListeners: function() {
        // Generate Form
        document.getElementById('formGenerate').onsubmit = (e) => {
            e.preventDefault();
            const source = e.target.source_year.value;
            const target = e.target.target_year.value;
            this.closeModal('modalGenerate');
            
            this.showConfirm('Generate Struktur?', `Salin struktur dari ${source} ke ${target}?`, 'info', async () => {
                const fd = new FormData(e.target);
                const res = await this.postData('generate_custom', fd);
                if(res.success) { this.year = target; this.loadContent(); }
            });
        };

        // Folder Form
        document.getElementById('formFolder').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            if(!fd.get('id')) fd.append('parent_id', this.folderId || ''); // Parent logic
            
            const action = fd.get('id') ? 'rename_folder' : 'create_folder';
            await this.postData(action, fd);
            this.closeModal('modalFolder');
            this.loadContent();
        };

        // Upload Form
        document.getElementById('formUpload').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('folder_id', this.folderId || '');
            
            // UI Loading state
            const btn = e.target.querySelector('button[type="submit"]');
            const txt = btn.innerText;
            btn.innerText = 'Uploading...'; btn.disabled = true;

            await this.postData('upload_file', fd);
            
            btn.innerText = txt; btn.disabled = false;
            e.target.reset();
            this.closeModal('modalUpload');
            this.loadContent();
        };
    },

    // --- HELPERS ---
    // Tambahkan fungsi ini di dalam object app = { ... }
    
    // --- UI HELPER BARU: Tampilkan Pesan Error/Info ---
    showAlert: function(title, message, type = 'danger') {
        this.showConfirm(title, message, type, () => {}); // Gunakan modal confirm tapi tanpa aksi callback
    },

    // --- UPDATE FUNGSI POSTDATA ---
    postData: async function(action, formData) {
        this.toggleLoading(true);
        try {
            const res = await fetch(`api.php?action=${action}&year=${this.year}`, { 
                method: 'POST', 
                body: formData 
            });

            // Cek apakah response sukses (HTTP 200)
            if (!res.ok) throw new Error(`Server Error: ${res.status}`);

            // Ambil text dulu, jangan langsung .json() untuk debug
            const text = await res.text();
            
            try {
                // Coba ubah ke JSON
                const json = JSON.parse(text);
                this.toggleLoading(false);
                
                if(!json.success) {
                    this.showAlert('Gagal', json.message, 'danger');
                    return {success: false};
                }
                return json;

            } catch (e) {
                // JIKA ERROR PARSING (Biasanya karena ada PHP Warning/Error HTML terbawa)
                console.error("Raw Response:", text); // Lihat di Console Browser
                this.toggleLoading(false);
                this.showAlert('Server Error', 'Terjadi kesalahan di sisi server. Cek Console (F12) untuk detail.\n\nPotongan Error: ' + text.substring(0, 100) + '...', 'danger');
                return {success: false};
            }

        } catch (e) {
            this.toggleLoading(false);
            console.error(e);
            this.showAlert('Koneksi Error', 'Gagal menghubungi server. Pastikan Laragon/Apache aktif.', 'danger');
            return {success: false};
        }
    },

    changeYear: function(y) { this.year = y; this.folderId = null; this.loadContent(); },
    openFolder: function(id) { this.folderId = id; this.loadContent(); },
    
    // Modal Helpers
    openGenerateModal: function() { document.getElementById('modalGenerate').classList.remove('hidden'); },
    openUploadModal: function() { document.getElementById('modalUpload').classList.remove('hidden'); },
    openFolderModal: function(id='', name='', desc='') {
        document.getElementById('modalFolderTitle').innerText = id ? 'Edit Folder' : 'Folder Baru';
        document.getElementById('folderId').value = id;
        document.getElementById('folderName').value = name;
        document.getElementById('folderDesc').value = desc;
        document.getElementById('modalFolder').classList.remove('hidden');
    },
    closeModal: function(id) { document.getElementById(id).classList.add('hidden'); },
    toggleLoading: function(show) {
        const el = document.getElementById('loadingOverlay');
        show ? el.classList.remove('hidden') : el.classList.add('hidden');
    },
    
    previewFile: function(path, name, type) {
        document.getElementById('previewTitle').innerText = name;
        const cont = document.getElementById('previewContent');
        cont.innerHTML = (type == 'pdf') 
            ? `<iframe src="${path}" class="w-full h-full border-none"></iframe>` 
            : `<img src="${path}" class="max-w-full max-h-full shadow-lg">`;
        document.getElementById('modalPreview').classList.remove('hidden');
    }
};


// Start App
document.addEventListener('DOMContentLoaded', () => app.init());