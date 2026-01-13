<?php
class TemplateService {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // --- READ TEMPLATES ---
    public function getTemplates() {
        $data = [];
        $q = $this->db->query("SELECT * FROM templates ORDER BY id DESC");
        if($q) while ($r = $q->fetch_assoc()) $data[] = $r;
        return $data;
    }

    public function getTemplateItems($templateId) {
        $items = [];
        $q = $this->db->query("SELECT * FROM template_items WHERE template_id = '$templateId' ORDER BY parent_id ASC, name ASC");
        if($q) while ($r = $q->fetch_assoc()) $items[] = $r;
        return $items;
    }

    // --- CREATE & EDIT ---
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
        $this->db->query("DELETE FROM template_items WHERE template_id = '$id'");
        return $this->db->query("DELETE FROM templates WHERE id = '$id'");
    }

    public function deleteItem($id) {
        return $this->db->query("DELETE FROM template_items WHERE id = '$id'");
    }

    // --- GENERATE (APPLY TEMPLATE) - FIX ---
    public function applyTemplate($templateId, $targetYear, $userId) {
        $check = $this->db->query("SELECT id FROM folders WHERE year = '$targetYear' AND deleted_at IS NULL LIMIT 1");
        if ($check && $check->num_rows > 0) {
            return ['success' => false, 'message' => "Tahun $targetYear sudah berisi data. Harap gunakan tahun kosong."];
        }

        $items = $this->getTemplateItems($templateId);
        if (empty($items)) return ['success' => false, 'message' => "Template kosong."];

        $idMap = []; 
        $rootItems = array_filter($items, function($i) { return is_null($i['parent_id']); });
        
        $this->recursiveCopy($rootItems, $items, $idMap, $targetYear, $userId);

        return ['success' => true, 'message' => "Berhasil menerapkan template ke tahun $targetYear"];
    }

    private function recursiveCopy($currentLevelItems, $allItems, &$idMap, $targetYear, $userId) {
        foreach ($currentLevelItems as $item) {
            $name = $this->db->real_escape_string($item['name']);
            $desc = $this->db->real_escape_string($item['description']);
            
            // Logic Parent ID Fix
            if ($item['parent_id'] && isset($idMap[$item['parent_id']])) {
                $newParentIdValue = $idMap[$item['parent_id']];
                $sqlParent = "'$newParentIdValue'"; 
            } else {
                $sqlParent = "NULL";
            }
            
            $uid = $userId ? "'$userId'" : "NULL";

            $sql = "INSERT INTO folders (name, description, year, parent_id, created_by) VALUES ('$name', '$desc', '$targetYear', $sqlParent, $uid)";
            
            if($this->db->query($sql)) {
                $newId = $this->db->insert_id;
                $idMap[$item['id']] = $newId;

                $children = array_filter($allItems, function($i) use ($item) { return $i['parent_id'] == $item['id']; });
                if (!empty($children)) {
                    $this->recursiveCopy($children, $allItems, $idMap, $targetYear, $userId);
                }
            }
        }
    }
}
?>