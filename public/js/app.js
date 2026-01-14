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
   loadDashboard: async function() {
        const res = await this.postData('dashboard');
        if(res.success) {
            document.getElementById('dashFiles').innerText = res.data.total_files;
            document.getElementById('dashFolders').innerText = res.data.total_folders;
            
            const list = document.getElementById('dashRecent');
            if(res.data.recent.length === 0) {
                list.innerHTML = '<div class="text-gray-400 text-xs italic text-center py-4">Belum ada aktivitas.</div>';
            } else {
                list.innerHTML = res.data.recent.map(item => {
                    const badge = item.role === 'admin' 
                        ? '<span class="bg-red-100 text-red-600 text-[9px] px-1 rounded font-bold ml-1">ADM</span>' 
                        : '<span class="bg-blue-100 text-blue-600 text-[9px] px-1 rounded font-bold ml-1">STF</span>';
                    
                    // Logic Icon & Warna Berbeda
                    let icon = item.type === 'upload' ? 'fa-file-arrow-up' : 'fa-folder-plus';
                    let bgClass = item.type === 'upload' ? 'bg-blue-50 text-blue-500' : 'bg-green-50 text-green-500';
                    let actionText = item.type === 'upload' ? 'Mengupload file' : 'Membuat folder';

                    return `
                    <div class="flex items-center justify-between border-b border-gray-50 py-3 last:border-0 hover:bg-gray-50 px-2 rounded transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="${bgClass} w-8 h-8 rounded-full flex items-center justify-center shrink-0">
                                <i class="fa-solid ${icon} text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-gray-700 truncate">
                                    <span class="font-normal text-gray-500">${actionText}:</span> ${item.name}
                                </div>
                                <div class="text-[10px] text-gray-400 flex items-center mt-0.5">
                                    <i class="fa-regular fa-user mr-1"></i> ${item.full_name || 'User'} ${badge}
                                </div>
                            </div>
                        </div>
                        <div class="text-[10px] text-gray-400 whitespace-nowrap ml-2 flex flex-col items-end">
                            <span class="font-mono">${item.time ? item.time.split(' ')[1] : '-'}</span>
                            <span class="text-[9px]">${item.time ? item.time.split(' ')[0] : '-'}</span>
                        </div>
                    </div>`;
                }).join('');
            }
        }
    },

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
    
   // --- LOAD TRASH (UPDATE) ---
    loadTrash: async function() {
        const res = await this.postData('get_trash');
        if(res.success) {
            let html = '';
            // Render Baris Item Sampah
            const row = (type, item) => `
                <div class="flex justify-between items-center bg-white p-3 border-b hover:bg-red-50 transition group">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <i class="fa-solid ${type=='folder'?'fa-folder text-court-gold':'fa-file text-gray-400'} text-lg"></i> 
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-gray-700 truncate">${item.name || item.filename}</div>
                            <div class="text-[10px] text-gray-400">
                                Dihapus oleh: <span class="font-medium">${item.deleter || 'Unknown'}</span> 
                                (${item.deleted_at})
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button onclick="app.restoreItem('${type}', ${item.id})" class="bg-green-100 text-green-700 border border-green-200 px-3 py-1 rounded text-xs font-bold hover:bg-green-200 transition">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Pulihkan
                        </button>
                        <button onclick="app.deletePermanent('${type}', ${item.id})" class="bg-red-100 text-red-700 border border-red-200 px-3 py-1 rounded text-xs font-bold hover:bg-red-200 transition">
                            <i class="fa-solid fa-times mr-1"></i> Hapus Permanen
                        </button>
                    </div>
                </div>`;
            
            if (res.data.folders.length === 0 && res.data.files.length === 0) {
                document.getElementById('trashList').innerHTML = `
                    <div class="flex flex-col items-center justify-center h-64 text-gray-300">
                        <i class="fa-regular fa-trash-can text-4xl mb-2"></i>
                        <p class="text-sm">Tong sampah kosong.</p>
                    </div>`;
            } else {
                res.data.folders.forEach(f => html += row('folder', f));
                res.data.files.forEach(f => html += row('file', f));
                document.getElementById('trashList').innerHTML = html;
            }
        }
    },

    // --- ACTIONS BARU (HAPUS PERMANEN) ---
    
    // 1. Hapus 1 Item Permanen
    deletePermanent: async function(type, id) {
        if(!confirm('PERINGATAN: Apakah Anda yakin ingin menghapus item ini secara PERMANEN?\n\nFile yang dihapus tidak dapat dikembalikan lagi!')) return;
        
        const fd = new FormData();
        fd.append('type', type);
        fd.append('id', id);
        
        const res = await this.postData('delete_permanent', fd);
        if(res.success) {
            this.loadTrash(); // Refresh list sampah
        } else {
            alert("Gagal menghapus: " + res.message);
        }
    },

    // 2. Kosongkan Semua Sampah
    emptyTrash: async function() {
        if(!confirm('BAHAYA: Anda akan menghapus SEMUA item di tong sampah secara permanen.\n\nTindakan ini TIDAK BISA DIBATALKAN. Lanjutkan?')) return;
        
        const res = await this.postData('empty_trash');
        if(res.success) {
            alert(res.message);
            this.loadTrash();
        } else {
            alert("Gagal mengosongkan sampah: " + res.message);
        }
    },

    // Update deleteItem biasa agar ada konfirmasi jelas
    deleteItem: async function(type, id) {
        if(!confirm('Pindahkan item ini ke Tong Sampah?')) return;
        
        const fd = new FormData(); 
        fd.append('type', type); 
        fd.append('id', id);
        
        const res = await this.postData('delete_item', fd);
        if(res.success) {
            this.loadContent(); // Refresh halaman konten
        } else {
            alert(res.message);
        }
    },
    
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