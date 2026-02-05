<?php
/**
 * DB - Query Builder Simples
 * Facilita queries comuns sem escrever SQL puro
 */

namespace App;

class DB
{
    protected static $pdo;
    protected $table;
    protected $wheres = [];
    protected $orderBy;
    protected $limit;
    protected $select = '*';
    
    /**
     * Define a conexão PDO
     */
    public static function setConnection($pdo)
    {
        self::$pdo = $pdo;
    }
    
    /**
     * Seleciona a tabela
     */
    public static function table($table)
    {
        $instance = new self();
        $instance->table = $table;
        return $instance;
    }
    
    /**
     * Define colunas a selecionar
     */
    public function select($columns)
    {
        if (is_array($columns)) {
            $this->select = implode(', ', $columns);
        } else {
            $this->select = $columns;
        }
        return $this;
    }
    
    /**
     * Adiciona condição WHERE
     */
    public function where($column, $operator, $value = null)
    {
        // Se apenas 2 parâmetros, assume operador =
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    /**
     * Adiciona ORDER BY
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = "$column $direction";
        return $this;
    }
    
    /**
     * Adiciona LIMIT
     */
    public function limit($count)
    {
        $this->limit = (int) $count;
        return $this;
    }
    
    /**
     * Executa query e retorna todos os resultados
     */
    public function get()
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        $params = [];
        
        // Adiciona WHEREs
        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        // Adiciona ORDER BY
        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        
        // Adiciona LIMIT
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Retorna primeiro resultado
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Conta registros
     */
    public function count()
    {
        $this->select = 'COUNT(*) as total';
        $result = $this->first();
        return $result ? (int) $result['total'] : 0;
    }
    
    /**
     * Insere registro
     */
    public function insert($data)
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        return self::$pdo->lastInsertId();
    }
    
    /**
     * Atualiza registros
     */
    public function update($data)
    {
        $sets = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        // Adiciona WHEREs
        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Deleta registros
     */
    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        $params = [];
        
        // Adiciona WHEREs
        if (!empty($this->wheres)) {
            $conditions = [];
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
