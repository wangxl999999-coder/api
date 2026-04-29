<?php

namespace Core;

class Model
{
    protected $db;
    protected $table = '';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            throw new \Exception("记录不存在");
        }
        return $result;
    }

    public function all()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function create($data)
    {
        $fillableData = $this->filterFillable($data);
        $fillableData['created_at'] = date('Y-m-d H:i:s');
        $fillableData['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert($this->table, $fillableData);
    }

    public function update($id, $data)
    {
        $fillableData = $this->filterFillable($data);
        $fillableData['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update(
            $this->table,
            $fillableData,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    public function delete($id)
    {
        return $this->db->delete(
            $this->table,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    public function where($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} :value ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, ['value' => $value]);
    }

    public function first($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} :value LIMIT 1";
        return $this->db->fetch($sql, ['value' => $value]);
    }

    public function count($where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}";
        $result = $this->db->fetch($sql, $params);
        return (int)$result['count'];
    }

    public function paginate($page = 1, $pageSize = 10, $where = '1=1', $params = [])
    {
        $offset = ($page - 1) * $pageSize;

        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int)$countResult['total'];

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'pagination' => [
                'page' => (int)$page,
                'page_size' => (int)$pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize)
            ]
        ];
    }

    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }

        $result = [];
        foreach ($this->fillable as $key) {
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
            }
        }
        return $result;
    }
}
