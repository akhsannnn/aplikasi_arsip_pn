const app = {
    // State Aplikasi
    year: new Date().getFullYear(),
    folderId: null,
    userRole: 'staff',
    tempAction: { type: null, id: null },
    searchTimeout: null,

    init: function() {
        this.checkSession();
        this.setupListeners();
    },

    // --- 1. AUTH & SESSION ---
    checkSession: async function() {
        try {
            const res = await this.postData('check_session');
            if(res.success) {
                document.getElementById('userDisplay').innerText = res.data.user;
                this.userRole = res.data.role;

                // Tampilkan menu khusus admin
                const adminMenus = document.querySelectorAll('.admin-menu');
                adminMenus.forEach(el => {
                    if(this.userRole === 'admin') el.classList.remove('hidden');
                    else el.classList.add('hidden');
                });

                // Load sidebar dan tentukan tampilan awal
                await this.loadSidebar();
                this.showScreen('app');

                if (this.userRole === 'staff') {
                    this.setView('content'); // Staff langsung ke Arsip
                } else {
                    // Admin ke Dashboard jika belum ada view aktif
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

    // --- 2. NAVIGATION ---
    showScreen: function(name) {
        document.getElementById('loginScreen').classList.add('hidden');
        document.getElementById('appScreen').classList.add('hidden');
        document.getElementById(name+'Screen').classList.remove('hidden');
    },

    setView: function(name) {
        // Tambahkan 'viewUsers' ke array list ini
        ['viewDashboard', 'viewContent', 'viewTemplates', 'viewTrash', 'viewUsers'].forEach(id => {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        });
        const target = document.getElementById('view'+name.charAt(0).toUpperCase() + name.slice(1));
        if(target) target.classList.remove('hidden');

        if(name === 'dashboard') this.loadDashboard();
        if(name === 'content') this.loadContent();
        if(name === 'trash') this.loadTrash();
        if(name === 'templates') this.loadTemplates();
        if(name === 'users') this.loadUsers(); // <--- TAMBAHKAN INI
    },

    // --- 3. DATA LOADING ---
    // --- LOAD DATA (SIDEBAR) ---
   // --- UPDATE FUNGSI INI DI app.js ---
    loadSidebar: async function() {
        const res = await this.postData('get_sidebar');
        if(res.success) {
            const el = document.getElementById('yearList');
            
            // 1. Ambil tahun dari Database
            let years = res.data.map(y => parseInt(y));
            
            // 2. FIX: Selalu tampilkan Tahun Kalender Saat Ini (Misal 2026)
            // Agar user selalu bisa kembali ke tahun ini untuk upload, walau datanya kosong.
            const calendarYear = new Date().getFullYear();
            if (!years.includes(calendarYear)) {
                years.push(calendarYear);
            }

            // 3. FIX: Selalu tampilkan Tahun yang Sedang Dibuka
            // Agar highlight tidak hilang saat user membuka tahun kosong
            const currentYear = parseInt(this.year);
            if (!years.includes(currentYear)) {
                years.push(currentYear);
            }

            // 4. Urutkan dari terbaru & Hapus duplikat
            years = [...new Set(years)].sort((a, b) => b - a);

            if (years.length === 0) {
                el.innerHTML = '<div class="px-3 text-[10px] text-gray-400">Belum ada arsip.</div>';
            } else {
                el.innerHTML = years.map(y => {
                    const deleteBtn = this.userRole === 'admin' 
                        ? `<button onclick="event.stopPropagation();app.deleteYear(${y})" class="text-gray-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition"><i class="fa-solid fa-trash-can text-[10px]"></i></button>`
                        : '';
                    
                    // Style Highlight
                    const isActive = y === currentYear;
                    const activeClass = isActive 
                        ? 'font-bold text-court-green bg-green-50 shadow-sm ring-1 ring-green-100' 
                        : 'hover:bg-gray-50 text-gray-600';
                    const iconClass = isActive ? 'text-court-green' : 'text-court-gold';
                    
                    return `
                    <div class="group flex items-center justify-between w-full px-3 py-2 rounded-md transition-all duration-200 mb-1 cursor-pointer ${activeClass}" onclick="app.changeYear(${y})">
                        <div class="flex items-center text-xs">
                            <i class="fa-regular fa-folder mr-2 ${iconClass} text-sm"></i> 
                            <span>Arsip ${y}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            ${deleteBtn}
                            <i class="fa-solid fa-chevron-right text-[9px] ${isActive ? 'text-green-500' : 'text-gray-300'}"></i>
                        </div>
                    </div>`;
                }).join('');
            }
        }
    },

    // --- FUNGSI BARU: GANTI TAHUN DENGAN MULUS ---
    changeYear: function(y) {
        this.year = y;
        this.folderId = null; // Reset ke folder root
        this.loadSidebar();   // Refresh sidebar agar highlight pindah ke tahun yang diklik
        this.setView('content'); // Pindah ke tampilan konten
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

    // --- FUNGSI SEARCH BAR ---
    search: function(keyword) {
        // Jika input kosong, kembalikan ke tampilan normal (folder saat ini)
        if(keyword.length === 0) {
            this.loadContent();
            return;
        }

        // Jika kurang dari 3 huruf, jangan cari dulu (tapi jangan refresh)
        if(keyword.length < 3) return;

        // Debounce: Tunggu user selesai mengetik 500ms baru request ke server
        // Agar tidak spam request setiap ketikan huruf
        if(this.searchTimeout) clearTimeout(this.searchTimeout);
        
        this.searchTimeout = setTimeout(async () => {
            // Tampilkan loading kecil di breadcrumb
            document.getElementById('breadcrumb').innerHTML = `<span class="text-gray-400"><i class="fa-solid fa-circle-notch fa-spin"></i> Mencari "${keyword}"...</span>`;
            
            const res = await this.postData(`search&keyword=${keyword}`);
            
            if(res.success) {
                // Update Breadcrumb jadi status pencarian
                document.getElementById('breadcrumb').innerHTML = `<span class="font-bold text-court-gold"><i class="fa-solid fa-magnifying-glass"></i> Hasil Pencarian: "${keyword}"</span> <span class="text-xs text-gray-400 ml-2 cursor-pointer hover:underline" onclick="document.getElementById('searchInput').value='';app.loadContent()">(Klik X di pencarian untuk kembali)</span>`;
                
                this.renderFolders(res.data.folders);
                this.renderFiles(res.data.files);
                
                // Jika kosong
                if(res.data.folders.length === 0 && res.data.files.length === 0) {
                    document.getElementById('fileList').innerHTML = '<tr><td colspan="2" class="text-center py-12 text-gray-400"><i class="fa-regular fa-face-frown text-2xl mb-2"></i><br>Tidak ditemukan dokumen dengan nama tersebut.</td></tr>';
                    document.getElementById('folderContainer').innerHTML = '';
                    document.getElementById('emptyState').classList.add('hidden');
                }
            }
        }, 500);
    },

    // --- FUNGSI SEARCH SAMPAH ---
    searchTrash: function(keyword) {
        if(this.searchTimeout) clearTimeout(this.searchTimeout);
        
        this.searchTimeout = setTimeout(async () => {
            // Jika kosong, load ulang semua sampah
            if(keyword.length === 0) {
                this.loadTrash();
                return;
            }
            // Jika < 3 huruf, jangan request
            if(keyword.length < 3) return;

            // Tampilkan loading di list sampah
            document.getElementById('trashList').innerHTML = '<div class="text-center py-10 text-gray-400"><i class="fa-solid fa-circle-notch fa-spin"></i> Mencari...</div>';
            
            const res = await this.postData(`search_trash&keyword=${keyword}`);
            if(res.success) {
                // Gunakan fungsi render yang sama dengan loadTrash
                this.renderTrashItems(res.data);
            }
        }, 500);
    },

    loadDashboard: async function() {
        const res = await this.postData('dashboard');
        if(res.success) {
            document.getElementById('dashFiles').innerText = res.data.total_files;
            document.getElementById('dashFolders').innerText = res.data.total_folders;
            
            const l = document.getElementById('dashRecent');
            if(res.data.recent.length === 0) {
                l.innerHTML = '<div class="text-gray-400 text-xs text-center py-6 border-2 border-dashed border-gray-100 rounded-lg">Belum ada aktivitas.</div>';
            } else {
                l.innerHTML = res.data.recent.map(i => {
                    let icon = i.type === 'upload' ? 'fa-file-arrow-up text-blue-500 bg-blue-50' : 'fa-folder-plus text-green-500 bg-green-50';
                    let action = i.type === 'upload' ? 'mengupload' : 'membuat';
                    
                    // FORMAT: APA | DIMANA | SIAPA | KAPAN
                    return `
                    <div class="flex items-center gap-3 border-b border-gray-100 py-3 px-2 hover:bg-gray-50 transition rounded-lg">
                        <div class="w-10 h-10 rounded-full ${icon} flex items-center justify-center shrink-0 shadow-sm">
                            <i class="fa-solid ${i.type === 'upload' ? 'fa-file' : 'fa-folder'} text-sm"></i>
                        </div>
                        
                        <div class="flex-1 min-w-0 grid grid-cols-1 md:grid-cols-2 gap-1">
                            <div>
                                <div class="font-bold text-gray-800 text-sm truncate" title="${i.name}">${i.name}</div>
                                <div class="text-[10px] text-gray-500 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-court-gold"></i> 
                                    <span>${i.location_clean}</span>
                                </div>
                            </div>

                            <div class="md:text-right">
                                <div class="text-xs font-bold text-gray-600">
                                    <i class="fa-regular fa-user text-blue-400 mr-1"></i> ${i.full_name || 'System'}
                                </div>
                                <div class="text-[10px] text-gray-400">
                                    ${i.time}
                                </div>
                            </div>
                        </div>
                    </div>`;
                }).join('');
            }
        }
    },

    // 1. Load Data Awal
    loadTrash: async function() {
        const res = await this.postData('get_trash');
        if(res.success) {
            this.renderTrashItems(res.data);
        }
    },

    // 2. Render UI (Dipakai oleh loadTrash DAN searchTrash)
    renderTrashItems: function(data) {
        const el = document.getElementById('trashList');
        if(!el) return;

        if (data.folders.length === 0 && data.files.length === 0) {
            el.innerHTML = `<div class="flex flex-col items-center justify-center h-96 text-gray-400"><div class="bg-gray-100 p-6 rounded-full mb-4"><i class="fa-regular fa-trash-can text-4xl text-gray-300"></i></div><h3 class="text-sm font-bold text-gray-600">Tidak ditemukan</h3><p class="text-xs text-gray-400 mt-1">Tong sampah kosong atau tidak ada hasil.</p></div>`;
            return;
        }

        let html = '';
        const row = (type, item) => {
            // Logic Icon
            let icon = 'fa-folder text-court-gold';
            let bgIcon = 'bg-yellow-50';
            
            if (type === 'file') {
                bgIcon = 'bg-blue-50';
                const ext = (item.filename || '').split('.').pop().toLowerCase();
                icon = 'fa-file text-gray-400';
                if (['pdf'].includes(ext)) icon = 'fa-file-pdf text-red-500';
                else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word text-blue-500';
                else if (['xls', 'xlsx', 'csv'].includes(ext)) icon = 'fa-file-excel text-green-600';
                else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) icon = 'fa-file-image text-purple-500';
            }

            return `
            <div class="bg-white p-4 border-b border-gray-100 flex items-center justify-between hover:bg-gray-50 transition group">
                <div class="flex items-center gap-4 overflow-hidden">
                    <div class="${bgIcon} w-10 h-10 rounded-lg flex items-center justify-center shrink-0 shadow-sm">
                        <i class="fa-solid ${icon} text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-gray-800 truncate">${item.name || item.filename}</div>
                        <div class="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                            <span class="bg-gray-100 px-1.5 rounded text-[10px] font-medium text-gray-500 uppercase">${type}</span>
                            <span><i class="fa-regular fa-trash-can mr-1"></i> ${item.deleter || 'Unknown'}</span>
                            <span class="text-gray-300">|</span>
                            <span>${item.deleted_at}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                    <button onclick="app.openRestoreModal('${type}', ${item.id}, '${item.name || item.filename}')" class="bg-white border border-green-200 text-green-600 hover:bg-green-600 hover:text-white p-2 rounded-lg shadow-sm transition" title="Pulihkan"><i class="fa-solid fa-rotate-left"></i></button>
                    <button onclick="app.openDeletePermModal('${type}', ${item.id}, '${item.name || item.filename}')" class="bg-white border border-red-200 text-red-600 hover:bg-red-600 hover:text-white p-2 rounded-lg shadow-sm transition" title="Hapus Permanen"><i class="fa-solid fa-times"></i></button>
                </div>
            </div>`;
        };

        data.folders.forEach(f => html += row('folder', f));
        data.files.forEach(f => html += row('file', f));
        el.innerHTML = html;
    },

    // --- 4. TEMPLATE MANAGER ---
    loadTemplates: async function() {
        const res = await this.postData('get_templates');
        
        if(res.success) {
            const list = document.getElementById('templateList');
            if(!list) return; // Pastikan elemen ada

            if(res.data.length === 0) {
                list.innerHTML = '<div class="text-center text-gray-400 text-xs mt-10">Belum ada template.</div>';
                return;
            }

            // UPDATE: Render dengan aman (Escape tanda kutip agar JS tidak error)
            list.innerHTML = res.data.map(t => {
                const safeName = t.name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                const safeDesc = (t.description || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                
                return `
                <div onclick="app.loadTemplateDetail(${t.id}, '${safeName}', '${safeDesc}')" 
                     class="p-3 border rounded-lg cursor-pointer bg-white hover:bg-blue-50 hover:border-blue-300 flex justify-between items-center group transition mb-2 shadow-sm">
                    <div class="overflow-hidden">
                        <div class="font-bold text-gray-700 text-sm truncate">${t.name}</div>
                        <div class="text-[10px] text-gray-400 truncate w-full pr-2">${t.description || '-'}</div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-blue-400"></i>
                </div>`;
            }).join('');
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
            const sel = document.getElementById('inputTempParent'); 
            const roots = this.buildTreeStructure(res.data); 
            sel.innerHTML = '<option value="">-- Folder Utama (Paling Atas) --</option>'; 
            this.populateDropdownRecursive(roots, sel); 
            if(res.data.length===0) tree.innerHTML='<div class="text-gray-400 text-xs text-center mt-4">Kosong</div>'; 
            else tree.innerHTML = this.renderTreeRecursive(roots, id, name, desc); 
        } 
    },

    // --- USER MANAGEMENT ---
    loadUsers: async function() {
        const res = await this.postData('get_users');
        if(res.success) {
            const tbody = document.getElementById('userList');
            if(!tbody) return;
            
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-400">Tidak ada data.</td></tr>';
                return;
            }

            tbody.innerHTML = res.data.map(u => {
                const roleBadge = u.role === 'admin' 
                    ? '<span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold">Admin</span>' 
                    : '<span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold">Staff</span>';
                
                return `
                <tr class="hover:bg-gray-50 border-b last:border-0">
                    <td class="p-4 font-bold text-gray-700">${u.full_name}</td>
                    <td class="p-4 text-gray-600">${u.username}</td>
                    <td class="p-4">${roleBadge}</td>
                    <td class="p-4 text-xs text-gray-400">${u.created_at}</td>
                    <td class="p-4 text-right">
                        <button onclick="app.openUserModal(${u.id}, '${u.username}', '${u.full_name}', '${u.role}')" class="text-blue-500 hover:text-blue-700 mr-3 text-xs font-bold border border-blue-200 bg-blue-50 px-2 py-1 rounded"><i class="fa-solid fa-edit"></i> Edit</button>
                        <button onclick="app.deleteUser(${u.id}, '${u.full_name}')" class="text-red-500 hover:text-red-700 text-xs font-bold border border-red-200 bg-red-50 px-2 py-1 rounded"><i class="fa-solid fa-trash"></i> Hapus</button>
                    </td>
                </tr>`;
            }).join('');
        }
    },

    openUserModal: function(id = null, username = '', fullname = '', role = 'staff') {
        document.getElementById('userId').value = id || '';
        document.getElementById('userUsername').value = username;
        document.getElementById('userFullName').value = fullname;
        document.getElementById('userRole').value = role;
        document.getElementById('userPassword').value = ''; // Reset password
        document.getElementById('userPassword').placeholder = id ? "(Biarkan kosong jika tidak diubah)" : "Password Baru";
        document.getElementById('userPassword').required = !id; // Wajib jika buat baru
        
        document.getElementById('modalUserTitle').innerText = id ? "Edit User" : "Tambah User Baru";
        this.openModal('modalUser');
    },

    deleteUser: async function(id, name) {
        if(!confirm(`Yakin ingin menghapus user "${name}"?`)) return;
        const fd = new FormData();
        fd.append('id', id);
        const res = await this.postData('delete_user', fd);
        if(res.success) this.loadUsers(); else alert(res.message);
    },

    // --- 5. HELPER RENDER FILE & FOLDER (Termasuk Ikon) ---
  renderFolders: function(folders) {
        const el = document.getElementById('folderContainer');
        el.innerHTML = folders.map(f => {
            const canDelete = this.userRole === 'admin' || this.userRole === 'staff';
            
            const isSearch = document.getElementById('breadcrumb').innerText.includes('Pencarian');
            const yearBadge = (isSearch || f.year != this.year) 
                ? `<span class="bg-blue-100 text-blue-700 text-[9px] px-1.5 py-0.5 rounded ml-1 border border-blue-200 font-mono">${f.year}</span>` 
                : '';

            const delBtn = canDelete 
                ? `<button onclick="event.stopPropagation();app.openSoftDeleteModal('folder', ${f.id}, '${f.name}')" class="absolute top-2 right-2 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 bg-white rounded-full p-1 shadow transition"><i class="fa-solid fa-trash text-xs"></i></button>` 
                : '';

            return `
            <div onclick="app.openFolder(${f.id})" class="bg-white p-3 rounded shadow-sm border hover:border-court-green cursor-pointer relative group transition hover:-translate-y-1">
                <i class="fa-solid fa-folder text-3xl text-court-green mb-2"></i>
                <div class="font-bold text-gray-700 truncate text-xs flex items-center gap-1" title="${f.name}">
                    ${f.name} ${yearBadge}
                </div>
                <div class="text-[10px] text-gray-400 truncate">${f.description || '-'}</div>
                
                <div class="text-[9px] text-gray-300 mt-2 flex justify-between items-center border-t pt-1 border-gray-100">
                    <span>${f.created_at ? f.created_at.split(' ')[0] : '-'}</span>
                    <span title="${f.creator || 'Admin'}"><i class="fa-regular fa-user"></i></span>
                </div>

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
                // ... (Kode deleteBtn & iconClass sama seperti sebelumnya) ...
                const canDelete = this.userRole === 'admin' || this.userRole === 'staff';
                const delBtn = canDelete ? `<button onclick="app.openSoftDeleteModal('file', ${f.id}, '${f.filename}')" class="text-gray-300 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>` : '';
                const ext = f.filename.split('.').pop().toLowerCase();
                let iconClass = 'fa-file text-gray-400';
                if(['pdf'].includes(ext)) iconClass = 'fa-file-pdf text-red-500'; 
                // ... dst ...

                // Badge Tahun (Dimana)
                const isSearch = document.getElementById('breadcrumb').innerText.includes('Pencarian');
                const yearBadge = (isSearch && f.year) ? `<span class="bg-gray-100 text-gray-600 text-[10px] px-1.5 rounded ml-2 border border-gray-200 font-bold">Arsip ${f.year}</span>` : '';

                return `
                <tr class="border-b hover:bg-blue-50 transition">
                    <td class="p-3 flex items-start gap-3">
                        <i class="fa-solid ${iconClass} text-xl w-6 text-center mt-1"></i>
                        <div class="min-w-0 flex-1">
                            <a href="${f.filepath}" target="_blank" class="hover:underline text-court-dark text-sm font-bold truncate block">
                                ${f.filename} ${yearBadge}
                            </a>
                            
                            <div class="text-[10px] text-gray-500 mt-1 flex items-center gap-3">
                                <span class="flex items-center gap-1"><i class="fa-regular fa-clock text-court-gold"></i> ${f.uploaded_at}</span>
                                <span class="flex items-center gap-1"><i class="fa-regular fa-user text-blue-400"></i> ${f.uploader || 'Admin'}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-3 text-right align-middle">${delBtn}</td>
                </tr>`;
            }).join('');
        }
    },

    renderBreadcrumb: function(crumbs) { let html = `<span onclick="app.openFolder(null)" class="cursor-pointer font-bold text-court-green hover:underline"><i class="fa-solid fa-house"></i> ${this.year}</span>`; crumbs.forEach(c => html += ` <i class="fa-solid fa-chevron-right text-[10px] text-gray-400 mx-1"></i> <span onclick="app.openFolder(${c.id})" class="cursor-pointer hover:underline text-gray-600 text-xs font-bold uppercase">${c.name}</span>`); document.getElementById('breadcrumb').innerHTML = html; },

    // --- 6. ACTIONS & LISTENERS ---
    setupListeners: function() {
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

        // --- UPLOAD HANDLER (DENGAN PREVIEW) ---
        // --- GANTI BAGIAN INI DI DALAM setupListeners() ---
        
        const formUpload = document.getElementById('formUpload');
        const fileInput = document.getElementById('fileInput');
        // PERBAIKAN: Gunakan ID baru 'uploadPreviewList' agar tidak nyasar ke tabel belakang
        const fileList = document.getElementById('uploadPreviewList'); 
        const previewContainer = document.getElementById('filePreviewContainer');
        const fileCountSpan = document.getElementById('fileCount');
        const btnClear = document.getElementById('btnClearFiles');

        if(fileInput) {
            fileInput.onchange = (e) => {
                const files = e.target.files;
                // Pastikan elemen ada sebelum diisi
                if(fileList) fileList.innerHTML = '';
                
                if(files.length > 0) {
                    if(previewContainer) previewContainer.classList.remove('hidden');
                    if(fileCountSpan) fileCountSpan.innerText = files.length;
                    
                    Array.from(files).forEach(file => {
                        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                        const ext = file.name.split('.').pop().toLowerCase();
                        
                        // Icon Preview
                        let icon = 'fa-file text-gray-400';
                        if (['pdf'].includes(ext)) icon = 'fa-file-pdf text-red-500';
                        else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word text-blue-500';
                        else if (['xls', 'xlsx', 'csv'].includes(ext)) icon = 'fa-file-excel text-green-600';
                        else if (['ppt', 'pptx'].includes(ext)) icon = 'fa-file-powerpoint text-orange-500';
                        else if (['jpg', 'jpeg', 'png'].includes(ext)) icon = 'fa-file-image text-purple-500';

                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between bg-white p-2 border rounded shadow-sm text-xs';
                        div.innerHTML = `<div class="flex items-center gap-3 overflow-hidden"><i class="fa-solid ${icon} text-lg"></i><div class="min-w-0"><div class="font-bold text-gray-700 truncate">${file.name}</div><div class="text-[10px] text-gray-400">${sizeMB} MB</div></div></div><div class="text-green-500 font-bold text-[10px]">Siap</div>`;
                        
                        if(fileList) fileList.appendChild(div);
                    });
                } else { 
                    if(previewContainer) previewContainer.classList.add('hidden'); 
                }
            };
        }
        
        if(btnClear) btnClear.onclick = () => { fileInput.value = ''; previewContainer.classList.add('hidden'); };

        if(formUpload) formUpload.onsubmit = async (e) => {
            e.preventDefault();
            if(!fileInput.files.length) return alert("Pilih file!");
            const btn = document.getElementById('btnSubmitUpload'); 
            const originalText = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Mengupload...`; btn.disabled = true;
            const fd = new FormData(e.target);
            fd.append('folder_id', this.folderId || '');
            const res = await this.postData('upload_file', fd);
            btn.innerHTML = originalText; btn.disabled = false;
            if(res.success) { this.closeModal('modalUpload'); fileInput.value = ''; previewContainer.classList.add('hidden'); alert(res.message); this.loadContent(); } else { alert(res.message); }
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
    
        // Tambahkan ini di dalam setupListeners: function() { ... }
        const formUser = document.getElementById('formUser');
        if(formUser) {
            formUser.onsubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const action = fd.get('id') ? 'update_user' : 'create_user';
                
                const btn = formUser.querySelector('button[type="submit"]');
                const oldText = btn.innerText;
                btn.innerText = '...'; btn.disabled = true;

                const res = await this.postData(action, fd);
                
                btn.innerText = oldText; btn.disabled = false;
                
                if(res.success) {
                    this.closeModal('modalUser');
                    this.loadUsers();
                    alert(res.message);
                } else {
                    alert(res.message);
                }
            };
        }
    },

   // --- HELPER FUNCTIONS ---
    postData: async function(action, fd = null) {
        const opts = fd ? { method: 'POST', body: fd } : {};
        
        // PERBAIKAN PENTING: Tambahkan &year=${this.year} ke URL
        // Ini memastikan File yang diupload masuk ke tahun yang sedang dibuka, bukan tahun default server.
        try {
            const url = `api.php?action=${action}&year=${this.year}`; 
            const res = await fetch(url, opts);
            
            if(!res.ok) throw new Error('API Error');
            return await res.json();
        } catch(e) { 
            console.error(e); 
            return {success: false, message: 'Server Connection Error'}; 
        }
    },
    
    loading: function(show) { const el = document.getElementById('loading'); if(el) show ? el.classList.remove('hidden') : el.classList.add('hidden'); },
    openFolder: function(id) { this.folderId = id; this.loadContent(); },
    openModal: function(id) { document.getElementById(id).classList.remove('hidden'); },
    closeModal: function(id) { document.getElementById(id).classList.add('hidden'); },
    
    openRestoreModal: function(type, id, name) { this.tempAction = { type: type, id: id }; document.getElementById('restoreItemName').innerText = name; this.openModal('modalRestore'); },
    confirmRestoreAction: async function() { const { type, id } = this.tempAction; if(!type || !id) return; const fd = new FormData(); fd.append('type', type); fd.append('id', id); const btn = document.querySelector('#modalRestore button.bg-green-600'); const oldText = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...'; btn.disabled = true; const res = await this.postData('restore_item', fd); btn.innerHTML = oldText; btn.disabled = false; if(res.success) { this.closeModal('modalRestore'); this.loadTrash(); } else { alert(res.message); } },
    
    openDeletePermModal: function(type, id, name) { this.tempAction = { type: type, id: id }; document.getElementById('delPermItemName').innerText = name; this.openModal('modalDeletePerm'); },
    confirmDeletePermAction: async function() { const { type, id } = this.tempAction; if(!type || !id) return; const fd = new FormData(); fd.append('type', type); fd.append('id', id); const btn = document.querySelector('#modalDeletePerm button.bg-red-600'); const oldText = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Menghapus...'; btn.disabled = true; const res = await this.postData('delete_permanent', fd); btn.innerHTML = oldText; btn.disabled = false; if(res.success) { this.closeModal('modalDeletePerm'); this.loadTrash(); } else { alert(res.message); } },
    
    // --- SOFT DELETE MODAL LOGIC ---
    openSoftDeleteModal: function(type, id, name) {
        this.tempAction = { type: type, id: id };
        document.getElementById('softDeleteName').innerText = name;
        this.openModal('modalSoftDelete');
    },

    confirmSoftDeleteAction: async function() {
        const { type, id } = this.tempAction;
        if(!type || !id) return;

        const btn = document.querySelector('#modalSoftDelete button.bg-orange-500');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...';
        btn.disabled = true;

        const fd = new FormData();
        fd.append('type', type);
        fd.append('id', id);

        const res = await this.postData('delete_item', fd);
        
        btn.innerHTML = oldText;
        btn.disabled = false;

        if(res.success) {
            this.closeModal('modalSoftDelete');
            this.loadContent(); // Refresh halaman folder saat ini
        } else {
            alert(res.message);
        }
    },

    emptyTrash: async function() { if(!confirm('Yakin ingin mengosongkan sampah?')) return; const res = await this.postData('empty_trash'); if(res.success) this.loadTrash(); else alert(res.message); },
    deleteItem: async function(type, id) { if(!confirm('Pindahkan ke sampah?')) return; const fd = new FormData(); fd.append('type', type); fd.append('id', id); const res = await this.postData('delete_item', fd); if(res.success) this.loadContent(); },
    
    deleteYear: async function(y) { if(!confirm(`Hapus arsip ${y}?`)) return; const fd=new FormData(); fd.append('year',y); const r=await this.postData('delete_year',fd); if(r.success){ this.loadSidebar(); if(this.year==y) location.reload(); } else alert(r.message); },
    deleteTemplate: async function(id) { if(!confirm('Hapus template?')) return; const fd=new FormData(); fd.append('id',id); await this.postData('delete_template',fd); this.loadTemplates(); document.getElementById('templateDetail').classList.add('hidden'); document.getElementById('templateEmptyState').classList.remove('hidden'); },
    deleteTemplateItem: async function(id, tid, tn, td) { if(!confirm('Hapus folder template?')) return; const fd=new FormData(); fd.append('id',id); await this.postData('delete_template_item',fd); this.loadTemplateDetail(tid,tn,td); },
    createTemplate: async function() { const n=prompt("Nama:"); if(!n) return; const d=prompt("Deskripsi:"); const fd=new FormData(); fd.append('name',n); fd.append('description',d); await this.postData('create_template',fd); this.loadTemplates(); },
    applyTemplateAction: async function() { const t=document.getElementById('inputTempId').value; const y=document.getElementById('genTargetYear').value; if(!t||!y) return alert("Data kurang"); const btn=document.querySelector('#modalGenerate button.bg-court-green'); btn.innerText='...'; btn.disabled=true; const fd=new FormData(); fd.append('template_id',t); fd.append('target_year',y); const r=await this.postData('apply_template',fd); btn.innerText='Generate'; btn.disabled=false; if(r.success){ this.closeModal('modalGenerate'); this.year=y; this.folderId=null; this.loadSidebar(); this.setView('content'); } else alert(r.message); },
    
    buildTreeStructure: function(items) { const r=[],l={}; items.forEach(i=>{l[i.id]={...i,children:[]}}); items.forEach(i=>{ if(i.parent_id&&l[i.parent_id]) l[i.parent_id].children.push(l[i.id]); else r.push(l[i.id]); }); return r; },
    renderTreeRecursive: function(nodes, tid, tn, td, l=0) { if(!nodes) return ''; return nodes.map(n=>`<div class="relative"><div class="flex justify-between hover:bg-gray-50 py-1" style="padding-left:${l*20+10}px"><div class="flex gap-2 text-sm text-gray-700">${l>0?'└─':''} <i class="fa-solid fa-folder text-court-gold"></i> ${n.name}</div><button onclick="app.deleteTemplateItem(${n.id},${tid},'${tn}','${td}')" class="text-red-300 hover:text-red-500"><i class="fa-solid fa-trash text-xs"></i></button></div>${this.renderTreeRecursive(n.children,tid,tn,td,l+1)}</div>`).join(''); },
    populateDropdownRecursive: function(nodes, sel, l=0) { if(!nodes) return; nodes.forEach(n=>{ const o=document.createElement('option'); o.value=n.id; o.text="— ".repeat(l)+n.name; sel.add(o); if(n.children) this.populateDropdownRecursive(n.children, sel, l+1); }); }
};

document.addEventListener('DOMContentLoaded', () => app.init());