<?php
class TemplateService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // --- READ ---
    public function getTemplates() {
        $data = [];
        $q = $this->db->query("SELECT * FROM templates ORDER BY id DESC");
        while ($r = $q->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function getTemplateItems($templateId) {
        $items = [];
        $q = $this->db->query("SELECT * FROM template_items WHERE template_id = '$templateId' ORDER BY parent_id ASC, name ASC");
        while ($r = $q->fetch_assoc()) $items[] = $r;
        return $items;
    }

    // --- CREATE ---
    public function createTemplate($name, $desc) {
        $n = $this->db->real_escape_string($name);
        $d = $this->db->real_escape_string($desc);
        $this->db->query("INSERT INTO templates (name, description) VALUES ('$n', '$d')");
        return $this->db->insert_id;
    }

    public function addItem($templateId, $name, $parentId = null) {
        $n = $this->db->real_escape_string($name);
        $pid = ($parentId && $parentId != 'null') ? "'$parentId'" : "NULL";
        return $this->db->query("INSERT INTO template_items (template_id, name, parent_id) VALUES ('$templateId', '$n', $pid)");
    }

    // --- DELETE ---
    public function deleteTemplate($id) {
        // Hapus items dulu
        $this->db->query("DELETE FROM template_items WHERE template_id = '$id'");
        // Hapus header template
        return $this->db->query("DELETE FROM templates WHERE id = '$id'");
    }

    public function deleteItem($id) {
        // Hapus item spesifik dari struktur template
        // Note: Idealnya hapus children juga secara rekursif, tapi ini versi simple
        return $this->db->query("DELETE FROM template_items WHERE id = '$id'");
    }

    // --- GENERATE (APPLY TEMPLATE) ---
    public function applyTemplate($templateId, $targetYear) {
        // 1. Cek apakah tahun target sudah ada folder?
        $check = $this->db->query("SELECT id FROM folders WHERE year = '$targetYear' AND deleted_at IS NULL LIMIT 1");
        if ($check->num_rows > 0) {
            return ['success' => false, 'message' => "Tahun $targetYear sudah berisi data. Harap gunakan tahun kosong."];
        }

        // 2. Ambil semua item template
        $items = $this->getTemplateItems($templateId);
        if (empty($items)) return ['success' => false, 'message' => "Template kosong."];

        // 3. Mapping ID Lama -> ID Baru (Untuk menjaga relasi parent-child)
        $idMap = []; 

        // Filter root items (yang tidak punya parent)
        $rootItems = array_filter($items, function($i) { return is_null($i['parent_id']); });
        
        // Mulai proses copy rekursif
        $this->recursiveCopy($rootItems, $items, $idMap, $targetYear);

        return ['success' => true, 'message' => "Berhasil menerapkan template ke tahun $targetYear"];
    }

    private function recursiveCopy($currentLevelItems, $allItems, &$idMap, $targetYear) {
        foreach ($currentLevelItems as $item) {
            $name = $this->db->real_escape_string($item['name']);
            $desc = $this->db->real_escape_string($item['description']);
            
            // Tentukan Parent ID baru berdasarkan mapping
            $newParentId = "NULL";
            if ($item['parent_id'] && isset($idMap[$item['parent_id']])) {
                $newParentId = "'" . $idMap[$item['parent_id']] . "'";
            }

            // Insert ke tabel folders (Arsip Tahunan)
            $this->db->query("INSERT INTO folders (name, description, year, parent_id) VALUES ('$name', '$desc', '$targetYear', $newParentId)");
            $newId = $this->db->insert_id;
            
            // Simpan mapping ID lama ke ID baru
            $idMap[$item['id']] = $newId;

            // Cari anak-anak item ini di level selanjutnya
            $children = array_filter($allItems, function($i) use ($item) { return $i['parent_id'] == $item['id']; });
            
            if (!empty($children)) {
                $this->recursiveCopy($children, $allItems, $idMap, $targetYear);
            }
        }
    }
}