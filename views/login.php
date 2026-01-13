<div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-sm text-center relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-2 bg-court-gold"></div>
    
    <div class="mb-6">
        <i class="fa-solid fa-scale-balanced text-5xl text-court-green mb-3"></i>
        <h2 class="text-2xl font-bold text-gray-800">Login Sistem</h2>
        <p class="text-xs text-gray-500">Silakan masuk untuk mengakses arsip</p>
    </div>

    <form id="formLogin" class="space-y-4 text-left">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Username</label>
            <div class="relative">
                <i class="fa-solid fa-user absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="text" name="username" class="w-full border border-gray-300 pl-8 p-2 rounded-lg text-sm outline-none focus:border-court-green focus:ring-1 focus:ring-court-green transition" placeholder="Masukkan username" required>
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Password</label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="password" name="password" class="w-full border border-gray-300 pl-8 p-2 rounded-lg text-sm outline-none focus:border-court-green focus:ring-1 focus:ring-court-green transition" placeholder="Masukkan password" required>
            </div>
        </div>
        <button type="submit" class="w-full bg-court-green text-white py-2.5 rounded-lg font-bold hover:bg-court-dark transition shadow-lg mt-2">
            MASUK APLIKASI
        </button>
    </form>
</div>