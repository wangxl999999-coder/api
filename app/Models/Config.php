<?php

namespace App\Models;

use Core\Model;

class Config extends Model
{
    protected $table = 'configs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'group_name', 'key_name', 'value', 'name', 
        'description', 'type', 'options', 'sort', 'status'
    ];

    public function getByKey($keyName)
    {
        return $this->first('key_name', '=', $keyName);
    }

    public function getValue($keyName, $default = null)
    {
        $config = $this->getByKey($keyName);
        return $config ? $config['value'] : $default;
    }

    public function getByGroup($groupName)
    {
        $sql = "SELECT * FROM configs WHERE group_name = :group_name AND status = 1 ORDER BY sort ASC";
        return $this->db->fetchAll($sql, ['group_name' => $groupName]);
    }

    public function getAllGroups()
    {
        $sql = "SELECT DISTINCT group_name FROM configs ORDER BY group_name ASC";
        return $this->db->fetchAll($sql);
    }

    public function updateByKey($keyName, $value)
    {
        return $this->db->update(
            $this->table,
            ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')],
            'key_name = :key_name',
            ['key_name' => $keyName]
        );
    }

    public function batchUpdate($data)
    {
        $this->db->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                $this->updateByKey($key, $value);
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getAllKeyValue()
    {
        $sql = "SELECT key_name, value FROM configs WHERE status = 1";
        $results = $this->db->fetchAll($sql);
        $configs = [];
        foreach ($results as $row) {
            $configs[$row['key_name']] = $row['value'];
        }
        return $configs;
    }
}
