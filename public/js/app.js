const app = {
    // State Aplikasi
    year: new Date().getFullYear(),
    folderId: null,

    // --- INITIALIZATION ---
    init: function() {
        this.checkSession();
        this.setupListeners();
        this.loadSidebar();
    },

    // --- AUTHENTICATION ---
    checkSession: async function() {
        try {
            const res = await this.postData('check_session');
            if(res.success) {
                // User Login -> Tampilkan App
                if(document.getElementById('userDisplay')) {
                    document.getElementById('userDisplay').innerText = res.data.user;
                }
                this.showScreen('app');
                this.setView('dashboard');
            } else {
                // User Guest -> Tampilkan Login
                this.showScreen('login');
            }
        } catch(e) {
            console.error(e);
            this.showScreen('login');
        }
    },

    login: async function(fd) {
        const res = await this.postData('login', fd);
        if(res.success) location.reload();
        else alert(res.message);
    },

    logout: async function() {
        await this.postData('logout');
        location.reload();
    },

    // --- LOAD DATA AWAL ---
    loadSidebar: async function() {
        try {
            const res = await this.postData('get_sidebar');
            if(res.success) {
                const years = res.data;
                const el = document.getElementById('yearList');
                
                if(years.length === 0) {
                    el.innerHTML = '<div class="px-3 text-[10px] text-gray-400">Belum ada arsip.</div>';
                } else {
                    el.innerHTML = years.map(y => `
                        <button onclick="app.year=${y};app.folderId=null;app.setView('content')" 
                                class="w-full text-left px-3 py-1.5 text-xs rounded transition hover:bg-gray-100 text-gray-600 flex justify-between items-center group">
                            <span><i class="fa-regular fa-folder mr-2"></i> Arsip ${y}</span>
                            <i class="fa-solid fa-chevron-right text-[9px] opacity-0 group-hover:opacity-100"></i>
                        </button>
                    `).join('');
                }
            }
        } catch(e) { console.error("Gagal load sidebar", e); }
    },

    // --- NAVIGATION (SPA LOGIC) ---
    showScreen: function(name) {
        // Toggle antara Login Screen & App Screen
        document.getElementById('loginScreen').classList.add('hidden');
        document.getElementById('appScreen').classList.add('hidden');
        
        const target = document.getElementById(name+'Screen');
        if(target) target.classList.remove('hidden');
    },

    setView: function(name) {
        // Sembunyikan semua view
        ['viewDashboard', 'viewContent', 'viewTemplates', 'viewTrash'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        });
        
        // Tampilkan view target
        const target = document.getElementById('view'+name.charAt(0).toUpperCase() + name.slice(1));
        if(target) target.classList.remove('hidden');

        // Load data sesuai view
        if(name === 'dashboard') this.loadDashboard();
        if(name === 'content') this.loadContent();
        if(name === 'trash') this.loadTrash();
        if(name === 'templates') this.loadTemplates();
    },

    // --- DATA LOADING ---
    loadDashboard: async function() {
        const res = await this.postData('dashboard');
        if(res.success) {
            document.getElementById('dashFiles').innerText = res.data.total_files;
            document.getElementById('dashFolders').innerText = res.data.total_folders;
            
            const list = document.getElementById('dashRecent');
            if(res.data.recent.length === 0) {
                list.innerHTML = '<div class="text-gray-400 text-xs italic">Belum ada aktivitas.</div>';
            } else {
                // RENDER TABEL AKTIVITAS
                list.innerHTML = res.data.recent.map(f => {
                    // Tentukan warna badge berdasarkan role
                    const roleBadge = f.role === 'admin' 
                        ? '<span class="bg-red-100 text-red-600 text-[9px] px-1.5 py-0.5 rounded font-bold ml-1">ADMIN</span>' 
                        : '<span class="bg-blue-100 text-blue-600 text-[9px] px-1.5 py-0.5 rounded font-bold ml-1">STAFF</span>';
                    
                    const uploaderName = f.full_name || f.username || 'Unknown';

                    return `
                    <div class="flex justify-between items-center border-b border-gray-100 py-3 last:border-0 hover:bg-gray-50 px-2 rounded transition">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="bg-blue-50 text-blue-500 w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-file-arrow-up text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-gray-700 truncate">${f.filename}</div>
                                <div class="text-[10px] text-gray-400 flex items-center">
                                    Oleh: <span class="font-medium text-gray-600 ml-1">${uploaderName}</span> ${roleBadge}
                                </div>
                            </div>
                        </div>
                        <div class="text-[10px] text-gray-400 shrink-0 ml-2">
                            ${f.uploaded_at}
                        </div>
                    </div>
                    `;
                }).join('');
            }
        }
    },

    loadContent: async function() {
        this.loading(true);
        const res = await this.postData(`get_content&year=${this.year}&folder_id=${this.folderId}`);
        if(res.success) {
            this.renderBreadcrumb(res.data.breadcrumbs);
            this.renderFolders(res.data.folders);
            this.renderFiles(res.data.files);
            this.renderYears(res.data.years);
        }
        this.loading(false);
    },

    // --- TEMPLATE MANAGER ---
    loadTemplates: async function() {
        const res = await this.postData('get_templates');
        if(res.success) {
            const list = document.getElementById('templateList');
            if(res.data.length === 0) {
                list.innerHTML = '<div class="text-center text-gray-400 text-xs mt-10">Belum ada template.</div>';
                return;
            }
            list.innerHTML = res.data.map(t => `
                <div onclick="app.loadTemplateDetail(${t.id}, '${t.name}', '${t.description||''}')" class="p-3 border rounded cursor-pointer hover:bg-blue-50 hover:border-blue-300 flex justify-between items-center group transition">
                    <div>
                        <div class="font-bold text-gray-700 text-sm">${t.name}</div>
                        <div class="text-[10px] text-gray-400 truncate w-32">${t.description||'-'}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-300"></i>
                </div>
            `).join('');
        }
    },

    loadTemplateDetail: async function(id, name, desc) {
        document.getElementById('templateEmptyState').classList.add('hidden');
        document.getElementById('templateDetail').classList.remove('hidden');
        
        document.getElementById('detailName').innerText = name;
        document.getElementById('detailDesc').innerText = desc;
        document.getElementById('inputTempId').value = id;
        
        // Setup Delete Button
        const btnDel = document.getElementById('btnDeleteTemplate');
        btnDel.onclick = () => app.deleteTemplate(id);

        const res = await this.postData(`get_template_items&template_id=${id}`);
        if(res.success) {
            const treeContainer = document.getElementById('templateTree');
            const parentSelect = document.getElementById('inputTempParent');
            
            // 1. BANGUN STRUKTUR TREE (Nested)
            // Kita pakai struktur ini untuk tampilan List DAN Dropdown
            const rootItems = this.buildTreeStructure(res.data);

            // 2. ISI DROPDOWN SECARA HIERARKIS (Solusi Masalah Anda)
            // Ganti istilah "Root" jadi "Folder Utama" agar user paham
            parentSelect.innerHTML = '<option value="">-- Folder Utama (Paling Atas) --</option>';
            this.populateDropdownRecursive(rootItems, parentSelect);

            // 3. RENDER TREE VIEW (Daftar Folder di layar)
            if(res.data.length === 0) {
                treeContainer.innerHTML = '<div class="text-center text-gray-400 mt-10 text-xs">Folder Kosong. Tambahkan folder di bawah.</div>';
            } else {
                treeContainer.innerHTML = this.renderTreeRecursive(rootItems, id, name, desc);
            }
        }
    },

    // Fungsi untuk mengisi Dropdown dengan indentasi visual
    populateDropdownRecursive: function(nodes, selectEl, level = 0) {
        if (!nodes) return;
        
        nodes.forEach(node => {
            const opt = document.createElement('option');
            opt.value = node.id;
            
            // MEMBUAT VISUALISASI TINGKATAN
            // Level 0: "Nama Folder"
            // Level 1: "— Nama Folder"
            // Level 2: "—— Nama Folder"
            let prefix = "";
            for(let i=0; i<level; i++) {
                prefix += "— "; // Menggunakan garis untuk menandakan anak folder
            }
            
            // Gabungkan garis + nama folder
            opt.text = prefix + node.name;
            
            selectEl.add(opt);

            // Jika punya anak, panggil fungsi ini lagi (Rekursif)
            if (node.children && node.children.length > 0) {
                this.populateDropdownRecursive(node.children, selectEl, level + 1);
            }
        });
    },

    // --- TAMBAHKAN FUNGSI BARU INI DI BAWAH loadTemplateDetail ---
    
    // Mengubah data flat database menjadi struktur pohon (nested children)
    buildTreeStructure: function(items) {
        const rootItems = [];
        const lookup = {};
        
        // Inisialisasi lookup
        items.forEach(item => {
            lookup[item.id] = { ...item, children: [] };
        });
        
        // Hubungkan anak ke orang tua
        items.forEach(item => {
            if (item.parent_id !== null && lookup[item.parent_id]) {
                lookup[item.parent_id].children.push(lookup[item.id]);
            } else {
                rootItems.push(lookup[item.id]);
            }
        });
        
        return rootItems;
    },

    // Fungsi Render Rekursif (Bisa menangani kedalaman tak terbatas)
    renderTreeRecursive: function(nodes, tempId, tempName, tempDesc, level = 0) {
        if (!nodes || nodes.length === 0) return '';
        
        return nodes.map(node => {
            // Padding dinamis berdasarkan level kedalaman (20px per level)
            const paddingLeft = level * 25 + 10;
            
            // Ikon folder berbeda warna jika sub-folder
            const iconClass = level === 0 ? 'text-court-gold fa-folder' : 'text-gray-400 fa-folder-open';
            const textClass = level === 0 ? 'font-bold text-gray-800' : 'text-gray-600';
            const borderClass = level === 0 ? 'border-b border-gray-100' : '';

            // Konektor visual (garis L) untuk anak
            const connector = level > 0 ? `<span class="text-gray-300 mr-2" style="font-family: monospace;">└─</span>` : '';

            return `
            <div class="relative">
                <div class="flex items-center justify-between py-2 hover:bg-gray-50 group transition ${borderClass}" 
                     style="padding-left: ${paddingLeft}px; padding-right: 10px;">
                    
                    <div class="flex items-center gap-2 text-sm ${textClass}">
                        ${connector}
                        <i class="fa-solid ${iconClass}"></i> 
                        <span>${node.name}</span>
                    </div>

                    <button onclick="app.deleteTemplateItem(${node.id}, ${tempId}, '${tempName}', '${tempDesc}')" 
                            class="text-red-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition p-1 rounded hover:bg-red-50"
                            title="Hapus folder ini">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
                
                ${this.renderTreeRecursive(node.children, tempId, tempName, tempDesc, level + 1)}
            </div>
            `;
        }).join('');
    },

    createTemplate: async function() {
        const name = prompt("Nama Template Baru:");
        if(!name) return;
        const desc = prompt("Deskripsi (Opsional):");
        const fd = new FormData(); fd.append('name', name); fd.append('description', desc);
        
        await this.postData('create_template', fd);
        this.loadTemplates();
    },

    deleteTemplate: async function(id) {
        if(!confirm('Hapus template ini beserta seluruh strukturnya?')) return;
        const fd = new FormData(); fd.append('id', id);
        await this.postData('delete_template', fd);
        this.loadTemplates();
        document.getElementById('templateDetail').classList.add('hidden');
        document.getElementById('templateEmptyState').classList.remove('hidden');
    },

    deleteTemplateItem: async function(itemId, tempId, tempName, tempDesc) {
        if(!confirm('Hapus folder ini dari template?')) return;
        const fd = new FormData(); fd.append('id', itemId);
        await this.postData('delete_template_item', fd);
        this.loadTemplateDetail(tempId, tempName, tempDesc);
    },

// --- PASTE KODE INI SETELAH deleteTemplateItem ---

    applyTemplateAction: async function() {
        // 1. Ambil ID Template & Tahun Target
        const templateId = document.getElementById('inputTempId').value;
        const targetYear = document.getElementById('genTargetYear').value;

        // 2. Validasi
        if(!templateId) { alert("Template ID hilang. Refresh halaman."); return; }
        if(!targetYear) { alert("Masukkan tahun target!"); return; }

        // 3. Konfirmasi
        if(!confirm(`Yakin ingin generate folder tahun ${targetYear}?`)) return;

        // 4. Loading State
        const btn = document.querySelector('#modalGenerate button[onclick="app.applyTemplateAction()"]');
        const oldText = btn.innerText;
        btn.innerText = 'Memproses...'; btn.disabled = true;

        // 5. Kirim ke API
        const fd = new FormData();
        fd.append('template_id', templateId);
        fd.append('target_year', targetYear);

        try {
            const res = await this.postData('apply_template', fd);
            if(res.success) {
                alert(res.message);
                this.closeModal('modalGenerate');
                // Refresh halaman dan buka tahun baru
                this.year = targetYear;
                this.folderId = null;
                this.checkSession(); 
            } else {
                alert(res.message);
            }
        } catch(e) {
            console.error(e);
            alert("Terjadi kesalahan koneksi.");
        } finally {
            btn.innerText = oldText; btn.disabled = false;
        }
    },

    // --- RENDERING HELPERS ---
    renderFolders: function(folders) {
        const el = document.getElementById('folderContainer');
        el.innerHTML = folders.map(f => `
            <div onclick="app.openFolder(${f.id})" class="bg-white p-3 rounded shadow-sm border hover:border-court-green cursor-pointer relative group transition hover:-translate-y-1">
                <i class="fa-solid fa-folder text-3xl text-court-green mb-2"></i>
                <div class="font-bold text-gray-700 truncate text-xs" title="${f.name}">${f.name}</div>
                <div class="text-[10px] text-gray-400 truncate">${f.description || '-'}</div>
                <button onclick="event.stopPropagation();app.deleteItem('folder', ${f.id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 bg-white rounded-full p-1 shadow"><i class="fa-solid fa-trash text-xs"></i></button>
            </div>
        `).join('');
    },

    renderFiles: function(files) {
        const el = document.getElementById('fileList');
        const empty = document.getElementById('emptyState');
        
        if(files.length === 0) {
            el.innerHTML = ''; empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
            el.innerHTML = files.map(f => `
                <tr class="border-b hover:bg-blue-50 transition">
                    <td class="p-3 flex items-center gap-3">
                        <i class="fa-solid fa-file text-gray-400"></i>
                        <a href="${f.filepath}" target="_blank" class="hover:underline text-court-dark text-sm font-medium">${f.filename}</a>
                    </td>
                    <td class="p-3 text-right">
                        <button onclick="app.deleteItem('file', ${f.id})" class="text-gray-300 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            `).join('');
        }
    },
    
    renderBreadcrumb: function(crumbs) {
        let html = `<span onclick="app.openFolder(null)" class="cursor-pointer font-bold text-court-green hover:underline"><i class="fa-solid fa-house"></i> ${this.year}</span>`;
        crumbs.forEach(c => html += ` <i class="fa-solid fa-chevron-right text-[10px] text-gray-400 mx-1"></i> <span onclick="app.openFolder(${c.id})" class="cursor-pointer hover:underline text-gray-600 text-xs font-bold uppercase">${c.name}</span>`);
        document.getElementById('breadcrumb').innerHTML = html;
    },

    renderYears: function(years) {
        const allYears = [...new Set([...years.map(y=>parseInt(y)), parseInt(this.year)])].sort((a,b)=>b-a);
        document.getElementById('yearList').innerHTML = allYears.map(y => `
            <button onclick="app.year=${y};app.folderId=null;app.loadContent()" class="w-full text-left px-3 py-1 text-xs rounded transition ${y==this.year ? 'bg-court-green text-white shadow' : 'text-gray-600 hover:bg-gray-200'}">${y}</button>
        `).join('');
    },

    loadTrash: async function() {
        const res = await this.postData('get_trash');
        if(res.success) {
            let html = '';
            const row = (type, item) => `
                <div class="flex justify-between items-center bg-white p-3 border-b hover:bg-red-50">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid ${type=='folder'?'fa-folder text-court-gold':'fa-file text-gray-400'}"></i> 
                        <span class="text-sm font-medium">${item.name || item.filename}</span>
                    </div>
                    <button onclick="app.restoreItem('${type}', ${item.id})" class="text-green-600 text-xs font-bold hover:underline bg-green-50 px-2 py-1 rounded">Pulihkan</button>
                </div>`;
            
            res.data.folders.forEach(f => html += row('folder', f));
            res.data.files.forEach(f => html += row('file', f));
            document.getElementById('trashList').innerHTML = html || '<div class="text-center py-10 text-gray-400 bg-gray-50 rounded border-dashed border-2 border-gray-200">Sampah Kosong</div>';
        }
    },

    // --- ACTIONS ---
    openFolder: function(id) {
        this.folderId = id;
        this.loadContent();
    },

    deleteItem: async function(type, id) {
        if(!confirm('Hapus item ini?')) return;
        const fd = new FormData(); fd.append('type', type); fd.append('id', id);
        await this.postData('delete_item', fd);
        this.loadContent();
    },

    restoreItem: async function(type, id) {
        const fd = new FormData(); fd.append('type', type); fd.append('id', id);
        await this.postData('restore_item', fd);
        this.loadTrash();
    },

    // --- HELPERS ---
    postData: async function(action, fd = null) {
        const opts = fd ? { method: 'POST', body: fd } : {};
        try {
            const res = await fetch(`api.php?action=${action}`, opts);
            if(!res.ok) throw new Error('Network response was not ok');
            return await res.json();
        } catch(e) {
            console.error("API Error", e);
            return {success: false, message: 'Server Connection Error'};
        }
    },

    loading: function(show) {
        const el = document.getElementById('loading');
        if(el) show ? el.classList.remove('hidden') : el.classList.add('hidden');
    },

    setupListeners: function() {
        // Form Listeners
        const formLogin = document.getElementById('formLogin');
        if(formLogin) formLogin.onsubmit = (e) => { e.preventDefault(); this.login(new FormData(e.target)); };
        
        const formFolder = document.getElementById('formFolder');
        if(formFolder) formFolder.onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            if(!fd.get('id')) fd.append('parent_id', this.folderId || '');
            const res = await this.postData('create_folder', fd);
            if(res.success) { this.closeModal('modalFolder'); this.loadContent(); }
        };

        const formUpload = document.getElementById('formUpload');
        if(formUpload) formUpload.onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('folder_id', this.folderId || '');
            const btn = e.target.querySelector('button'); btn.innerText = 'Uploading...'; btn.disabled=true;
            const res = await this.postData('upload_file', fd);
            btn.innerText = 'Upload'; btn.disabled=false;
            if(res.success) { this.closeModal('modalUpload'); this.loadContent(); } else { alert(res.message); }
        };

        const formAddTemp = document.getElementById('formAddTemplateFolder');
        if(formAddTemp) {
            formAddTemp.onsubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                await this.postData('add_template_item', fd);
                e.target.reset();
                const id = document.getElementById('inputTempId').value;
                const name = document.getElementById('detailName').innerText;
                const desc = document.getElementById('detailDesc').innerText;
                this.loadTemplateDetail(id, name, desc);
            }
        }
    },

    // Modal Helpers
    openModal: function(id) { document.getElementById(id).classList.remove('hidden'); },
    closeModal: function(id) { document.getElementById(id).classList.add('hidden'); }
};

document.addEventListener('DOMContentLoaded', () => app.init());