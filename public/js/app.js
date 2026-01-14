const app = {
    year: new Date().getFullYear(),
    folderId: null,
    userRole: 'staff',

    init: function() {
        this.checkSession();
        this.setupListeners();
    },

    // --- AUTH ---
    checkSession: async function() {
        try {
            const res = await this.postData('check_session');
            if(res.success) {
                document.getElementById('userDisplay').innerText = res.data.user;
                this.userRole = res.data.role;

                // Handle Menu Admin
                const adminMenus = document.querySelectorAll('.admin-menu');
                adminMenus.forEach(el => {
                    if(this.userRole === 'admin') el.classList.remove('hidden');
                    else el.classList.add('hidden');
                });

                // Load Sidebar dulu sebelum menentukan tampilan
                await this.loadSidebar();

                this.showScreen('app');

                // Redirect Pintar
                // Jika Staff, paksa ke Content (Arsip)
                if (this.userRole === 'staff') {
                    this.setView('content');
                } else {
                    // Admin default ke Dashboard
                    const isDashHidden = document.getElementById('viewDashboard').classList.contains('hidden');
                    const isContentHidden = document.getElementById('viewContent').classList.contains('hidden');
                    if(isDashHidden && isContentHidden) this.setView('dashboard');
                }
            } else {
                this.showScreen('login');
            }
        } catch(e) {
            console.error(e);
            this.showScreen('login');
        }
    },

    login: async function(fd) {
        const res = await this.postData('login', fd);
        if(res.success) this.checkSession(); else alert(res.message);
    },

    logout: async function() {
        await this.postData('logout');
        location.reload();
    },

    // --- NAVIGATION ---
    showScreen: function(name) {
        document.getElementById('loginScreen').classList.add('hidden');
        document.getElementById('appScreen').classList.add('hidden');
        document.getElementById(name+'Screen').classList.remove('hidden');
    },

    setView: function(name) {
        ['viewDashboard', 'viewContent', 'viewTemplates', 'viewTrash'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        });
        const target = document.getElementById('view'+name.charAt(0).toUpperCase() + name.slice(1));
        if(target) target.classList.remove('hidden');

        if(name === 'dashboard') this.loadDashboard();
        if(name === 'content') this.loadContent();
        if(name === 'trash') this.loadTrash();
        if(name === 'templates') this.loadTemplates();
    },

    // --- DATA LOADING ---
    loadSidebar: async function() {
        const res = await this.postData('get_sidebar');
        if(res.success) {
            const el = document.getElementById('yearList');
            const years = res.data.map(y => parseInt(y));
            
            // LOGIKA PINDAH TAHUN OTOMATIS
            // Jika tahun yang dipilih (default 2026) tidak ada di database,
            // tapi ada tahun lain (misal 2027), pindah ke tahun tersebut.
            if (years.length > 0 && !years.includes(parseInt(this.year))) {
                this.year = years[0]; // Pindah ke tahun pertama yang tersedia
            }

            if (years.length === 0) {
                el.innerHTML = '<div class="px-3 text-[10px] text-gray-400">Belum ada arsip.</div>';
            } else {
                el.innerHTML = res.data.map(y => {
                    const deleteBtn = this.userRole === 'admin' 
                        ? `<button onclick="event.stopPropagation();app.deleteYear(${y})" class="text-gray-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-trash-can text-[10px]"></i></button>`
                        : '';
                    const activeClass = y == this.year ? 'font-bold text-court-green bg-green-50' : '';
                    
                    return `
                    <div class="group flex items-center justify-between w-full px-3 py-1.5 rounded transition hover:bg-gray-100 mb-1 cursor-pointer ${activeClass}" onclick="app.year=${y};app.folderId=null;app.setView('content')">
                        <div class="flex items-center text-xs text-gray-600">
                            <i class="fa-regular fa-folder mr-2 text-court-gold"></i> 
                            <span>Arsip ${y}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            ${deleteBtn}
                            <i class="fa-solid fa-chevron-right text-[9px] text-gray-300"></i>
                        </div>
                    </div>`;
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
        }
        this.loading(false);
    },

    // ... (Fungsi Dashboard, Template, Trash sama seperti sebelumnya) ...
    // Sertakan fungsi helper renderFolders, renderFiles dll.
    
    // --- RENDERING HELPERS (Pastikan bagian ini ada) ---
    renderFolders: function(folders) {
        const el = document.getElementById('folderContainer');
        el.innerHTML = folders.map(f => {
            const delBtn = this.userRole === 'admin' 
                ? `<button onclick="event.stopPropagation();app.deleteItem('folder', ${f.id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 bg-white rounded-full p-1 shadow transition"><i class="fa-solid fa-trash text-xs"></i></button>` 
                : '';
            return `
            <div onclick="app.openFolder(${f.id})" class="bg-white p-3 rounded shadow-sm border hover:border-court-green cursor-pointer relative group transition hover:-translate-y-1">
                <i class="fa-solid fa-folder text-3xl text-court-green mb-2"></i>
                <div class="font-bold text-gray-700 truncate text-xs" title="${f.name}">${f.name}</div>
                <div class="text-[10px] text-gray-400 truncate">${f.description || '-'}</div>
                ${delBtn}
            </div>`;
        }).join('');
    },

    renderFiles: function(files) {
        const el = document.getElementById('fileList');
        const empty = document.getElementById('emptyState');
        if(files.length === 0) {
            el.innerHTML = ''; empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
            el.innerHTML = files.map(f => {
                const delBtn = this.userRole === 'admin' 
                    ? `<button onclick="app.deleteItem('file', ${f.id})" class="text-gray-300 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>`
                    : '<span class="text-[10px] text-gray-300"><i class="fa-solid fa-lock"></i></span>';
                return `
                <tr class="border-b hover:bg-blue-50 transition">
                    <td class="p-3 flex items-center gap-3">
                        <i class="fa-solid fa-file text-gray-400"></i>
                        <a href="${f.filepath}" target="_blank" class="hover:underline text-court-dark text-sm font-medium">${f.filename}</a>
                    </td>
                    <td class="p-3 text-right">${delBtn}</td>
                </tr>`;
            }).join('');
        }
    },

    renderBreadcrumb: function(crumbs) {
        let html = `<span onclick="app.openFolder(null)" class="cursor-pointer font-bold text-court-green hover:underline"><i class="fa-solid fa-house"></i> ${this.year}</span>`;
        crumbs.forEach(c => html += ` <i class="fa-solid fa-chevron-right text-[10px] text-gray-400 mx-1"></i> <span onclick="app.openFolder(${c.id})" class="cursor-pointer hover:underline text-gray-600 text-xs font-bold uppercase">${c.name}</span>`);
        document.getElementById('breadcrumb').innerHTML = html;
    },

    // --- ACTIONS & LISTENERS (Sama seperti sebelumnya, singkat demi karakter) ---
    // Pastikan fungsi postData, loading, openModal, closeModal, setupListeners tetap ada
    
    // ... [Copy fungsi lainnya dari file app.js sebelumnya] ...
    
    postData: async function(action, fd = null) {
        const opts = fd ? { method: 'POST', body: fd } : {};
        try {
            const res = await fetch(`api.php?action=${action}`, opts);
            if(!res.ok) throw new Error('API Error');
            return await res.json();
        } catch(e) {
            console.error(e);
            return {success: false, message: 'Server Connection Error'};
        }
    },
    loading: function(show) {
        const el = document.getElementById('loading');
        if(el) show ? el.classList.remove('hidden') : el.classList.add('hidden');
    },
    openFolder: function(id) { this.folderId = id; this.loadContent(); },
    openModal: function(id) { document.getElementById(id).classList.remove('hidden'); },
    closeModal: function(id) { document.getElementById(id).classList.add('hidden'); },
    
    // Load Dashboard, Template, Trash dll pastikan ada
    loadDashboard: async function() { /* ... */ },
    loadTemplates: async function() { /* ... */ },
    loadTrash: async function() { /* ... */ },
    
    // Setup Listeners Login, Upload, dll
    setupListeners: function() {
        const formLogin = document.getElementById('formLogin');
        if(formLogin) formLogin.onsubmit = (e) => { e.preventDefault(); this.login(new FormData(e.target)); };
        
        // Folder
        const formFolder = document.getElementById('formFolder');
        if(formFolder) formFolder.onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            if(!fd.get('id')) fd.append('parent_id', this.folderId || '');
            const res = await this.postData('create_folder', fd);
            if(res.success) { this.closeModal('modalFolder'); this.loadContent(); }
        };

        // Upload
        const formUpload = document.getElementById('formUpload');
        if(formUpload) formUpload.onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('folder_id', this.folderId || '');
            // Multiple file handling logic
            const res = await this.postData('upload_file', fd);
            if(res.success) { this.closeModal('modalUpload'); this.loadContent(); } else { alert(res.message); }
        };
        // Template Listeners...
    }
};

document.addEventListener('DOMContentLoaded', () => app.init());