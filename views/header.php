<header class="bg-court-green text-white shadow-lg h-16 flex items-center justify-between px-4 z-20 shrink-0 transition-all">
    <div class="flex items-center gap-4">
        <div class="bg-white p-1.5 rounded-full shadow-sm shrink-0">
            <img src="css/logo.jpg" alt="Logo PN" class="h-12 w-12 rounded-full object-contain">
        </div>
        <span class="font-bold text-court-gold tracking-wide text-sm md:text-base leading-tight">
            SISTEM ARSIP AMPUH PENGADILAN NEGERI MAKASSAR
        </span>
    </div>

    <div class="flex items-center gap-4 text-sm shrink-0">
        <div class="flex items-center gap-2 bg-court-dark px-3 py-1.5 rounded-full">
            <i class="fa-solid fa-user-circle text-gray-300 text-base"></i>
            <span id="userDisplay" class="font-bold text-court-gold truncate max-w-[120px]">User</span>
        </div>
        <button onclick="app.logout()" class="text-red-200 hover:text-white transition p-1" title="Keluar">
            <i class="fa-solid fa-power-off text-lg"></i>
        </button>
    </div>
</header>