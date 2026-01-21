<aside class="w-64 bg-white shadow-md border-r border-gray-200 flex flex-col z-10 hidden md:flex">
    <nav class="p-3 space-y-1 overflow-y-auto flex-1">
        
        <button onclick="app.setView('dashboard')" class="admin-menu hidden w-full text-left px-3 py-2.5 rounded-lg text-gray-700 hover:bg-green-50 hover:text-court-green font-medium flex gap-3 items-center transition group">
            <i class="fa-solid fa-chart-pie w-5 text-center text-gray-400 group-hover:text-court-green"></i> Dashboard
        </button>

        <div class="pt-5 pb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Arsip Tahunan</div>
        <div id="yearList" class="space-y-1">
            </div>

        <div class="pt-5 pb-2 px-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider admin-menu hidden">Sistem</div>
        
        <button onclick="app.setView('templates')" class="admin-menu hidden w-full text-left px-3 py-2.5 rounded-lg text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 font-medium flex gap-3 items-center transition group">
            <i class="fa-solid fa-wand-magic-sparkles w-5 text-center text-gray-400 group-hover:text-yellow-600"></i> Template Manager
        </button>

        <li class="admin-menu hidden mb-1">
            <a href="#" onclick="app.setView('users')" class="flex items-center px-3 py-2 text-gray-600 hover:bg-green-50 hover:text-court-green rounded-md transition-all group">
                <i class="fa-solid fa-users-gear w-5 text-center mr-2 text-gray-400 group-hover:text-court-green"></i>
                <span class="font-medium text-sm">Manajemen User</span>
            </a>
        </li>

        <button onclick="app.setView('trash')" class="admin-menu hidden w-full text-left px-3 py-2.5 rounded-lg text-gray-700 hover:bg-red-50 hover:text-red-600 font-medium flex gap-3 items-center transition group">
            <i class="fa-solid fa-trash w-5 text-center text-gray-400 group-hover:text-red-500"></i> Tong Sampah
        </button>
    </nav>
    
    <div class="p-4 border-t text-[10px] text-gray-400 text-center">
        &copy; <?= date('Y') ?> Pengadilan Negeri Makassar
    </div>
</aside>