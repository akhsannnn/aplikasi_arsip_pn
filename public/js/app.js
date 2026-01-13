const app = {
    // State Aplikasi
    year: new Date().getFullYear(),
    folderId: null,

    // --- INITIALIZATION ---
    init: function() {
        this.checkSession();
        this.setupListeners();
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
            document.getElementById('dashRecent').innerHTML = res.data.recent.map(f => `
                <div class="flex justify-between border-b py-2 text-xs">
                    <span class="truncate w-2/3"><i class="fa-solid fa-file mr-2 text-gray-400"></i> ${f.filename}</span>
                    <span class="text-gray-500">${f.uploaded_at}</span>
                </div>
            `).join('');
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
        
        const btnDel = document.getElementById('btnDeleteTemplate');
        if(btnDel) btnDel.onclick = () => app.deleteTemplate(id);

        const res = await this.postData(`get_template_items&template_id=${id}`);
        if(res.success) {
            const tree = document.getElementById('templateTree');
            const parentSelect = document.getElementById('inputTempParent');
            
            parentSelect.innerHTML = '<option value="">-- Di Root (Paling Luar) --</option>';

            if(res.data.length === 0) {
                tree.innerHTML = '<div class="text-center text-gray-400 mt-10">Folder Kosong</div>';
                return;
            }

            tree.innerHTML = res.data.map(i => {
                const opt = document.createElement('option');
                opt.value = i.id;
                opt.text = i.name;
                parentSelect.add(opt);

                const isChild = i.parent_id !== null;
                return `
                <div class="flex items-center justify-between p-2 border-b hover:bg-gray-50 group">
                    <div class="flex items-center gap-2 text-sm ${isChild ? 'ml-6 text-gray-600' : 'font-bold text-gray-800'}">
                        <i class="fa-solid ${isChild ? 'fa-turn-up rotate-90 mr-1 text-gray-300' : 'fa-folder text-court-gold'}"></i> 
                        ${i.name}
                    </div>
                    <button onclick="app.deleteTemplateItem(${i.id}, ${id}, '${name}', '${desc}')" class="text-red-300 hover:text-red-500 opacity-0 group-hover:opacity-100 px-2">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>`;
            }).join('');
        }
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

    // TAMBAHKAN KODE INI:
    applyTemplateAction: async function() {
        // 1. Ambil ID Template dari input hidden di halaman detail
        const templateId = document.getElementById('inputTempId').value;
        if(!templateId) { 
            alert("Terjadi kesalahan: ID Template tidak ditemukan. Silakan refresh halaman."); 
            return; 
        }

        // 2. Ambil Tahun Target dari Modal
        const targetYear = document.getElementById('genTargetYear').value;
        if(!targetYear) { 
            alert("Harap masukkan tahun target!"); 
            return; 
        }

        // 3. Konfirmasi user
        if(!confirm(`Yakin ingin membuat struktur folder tahun ${targetYear} berdasarkan template ini?`)) return;

        // 4. Kirim Data ke API
        const fd = new FormData();
        fd.append('template_id', templateId);
        fd.append('target_year', targetYear);

        // Tampilkan loading di tombol (opsional UX)
        const btn = document.querySelector('#modalGenerate button[onclick="app.applyTemplateAction()"]');
        const oldText = btn.innerText;
        btn.innerText = 'Memproses...';
        btn.disabled = true;

        try {
            const res = await this.postData('apply_template', fd);
            
            if(res.success) {
                alert(res.message);
                this.closeModal('modalGenerate');
                
                // Langsung arahkan user ke tahun yang baru dibuat
                this.year = targetYear;
                this.folderId = null; // Reset ke root
                this.checkSession(); // Refresh tampilan
            } else {
                alert(res.message);
            }
        } catch (e) {
            alert("Gagal menghubungi server.");
        } finally {
            // Kembalikan tombol seperti semula
            btn.innerText = oldText;
            btn.disabled = false;
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