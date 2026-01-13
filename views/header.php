<header class="bg-court-green text-white shadow-lg h-14 flex items-center justify-between px-4 z-20 shrink-0">
    <div class="flex items-center gap-3 w-64">
        <div class="bg-white p-1 rounded-full shadow-sm">
            <i class="fa-solid fa-scale-balanced text-court-green text-lg"></i>
        </div>
        <span class="font-bold text-court-gold tracking-wide text-sm md:text-base">E-ARSIP PRO</span>
    </div>

    <div class="flex items-center gap-4 text-xs md:text-sm">
        <div class="flex items-center gap-2 bg-court-dark px-3 py-1 rounded-full">
            <i class="fa-solid fa-user-circle text-gray-300"></i>
            <span id="userDisplay" class="font-bold text-court-gold truncate max-w-[100px]">User</span>
        </div>
        <button onclick="app.logout()" class="text-red-200 hover:text-white transition" title="Keluar">
            <i class="fa-solid fa-power-off text-lg"></i>
        </button>
    </div>
</header>