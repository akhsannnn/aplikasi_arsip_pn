<div class="h-full flex flex-col space-y-4">
    <div class="flex justify-between items-center border-b pb-4 bg-white p-4 rounded-xl shadow-sm shrink-0">
        <div>
            <h2 class="text-xl font-bold text-gray-800"><i class="fa-solid fa-users-gear text-court-green mr-2"></i>Manajemen Pengguna</h2>
            <p class="text-xs text-gray-500">Tambah, edit, atau hapus akses pegawai.</p>
        </div>
        <button onclick="app.openUserModal()" class="bg-court-green hover:bg-court-dark text-white px-4 py-2 rounded-lg shadow text-sm font-bold transition flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Tambah User
        </button>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col overflow-hidden flex-1">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold border-b">
                    <tr>
                        <th class="p-4">Nama Lengkap</th>
                        <th class="p-4">Username</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Terdaftar</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="userList" class="divide-y divide-gray-100">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalUser" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="bg-court-green px-4 py-3 border-b flex justify-between items-center">
            <h3 class="font-bold text-white text-sm" id="modalUserTitle">Form User</h3>
            <button onclick="app.closeModal('modalUser')" class="text-white/70 hover:text-white"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="formUser" class="p-4 space-y-3">
            <input type="hidden" name="id" id="userId">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Nama Lengkap</label>
                <input type="text" name="full_name" id="userFullName" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:ring-1 focus:ring-court-green" required>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Username</label>
                <input type="text" name="username" id="userUsername" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:ring-1 focus:ring-court-green" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Role / Hak Akses</label>
                <select name="role" id="userRole" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:ring-1 focus:ring-court-green">
                    <option value="staff">Staff (Hanya Upload & Lihat)</option>
                    <option value="admin">Admin (Full Akses)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Password</label>
                <input type="password" name="password" id="userPassword" placeholder="(Biarkan kosong jika tidak diubah)" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:ring-1 focus:ring-court-green">
                <p class="text-[10px] text-gray-400 mt-1">*Minimal 6 karakter disarankan.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t mt-2">
                <button type="button" onclick="app.closeModal('modalUser')" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">Batal</button>
                <button type="submit" class="px-4 py-2 bg-court-green text-white rounded-lg text-xs font-bold shadow-md">Simpan</button>
            </div>
        </form>
    </div>
</div>