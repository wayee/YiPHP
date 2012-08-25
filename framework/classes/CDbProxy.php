<?php defined('SYSPATH') or die('No direct script access.');

/**
 * 封装PDO操作等
 * 
 * @author Andy Cai (huayicai@gmail.com)
 *
 */
class CDbProxy
{
    public $pdo;
    public $table_name;
    public $class_name;
    public $query;

    /**
     * 构造函数
     *
     * @param string $table_name
     * @param string $class_name
     */
    public function __construct($db_host, $lib_name, $class_name, $table_name = null)
    {
        if(!$this->pdo)
        {
			try{
					$this->pdo = new PDO( 'mysql:host=' . $db_host . ';port=' . DB_PORT . ';dbname=' . $lib_name, DB_USER, DB_PASS,
				  array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8';", PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => true ) );
			}
			catch (Exception $e){
				CUtils::log( 'db.construct', 'catch db connect err: ' . $e->getMessage() );
			}
        }
		$this->init( $class_name, $table_name );
    }

	public function init( $class_name, $table_name )
	{
        $this->class_name = $class_name;
        if(empty($table_name))
        {
            $ref = new ReflectionClass($class_name);
            $this->table_name = $ref->getConstant('TABLE_NAME');
        }
        else
        {
            $this->table_name = $table_name;
        }
	}
    
    public static function update_filed_map($field)
    {
        return '`' . $field . '`=:' . $field;
    }
    
    public static function change_field_map($field)
    {
        return '`' . $field . '`=`' . $field . '`+:' . $field;
    }
    
    public static function to_col($array)
    {
        return reset($array);
    }
    
    /**
     * 比对对象和样本，看是匹配
     *
     * @param object $object
     * @param object $sample
     */
    public static function match_obj_sample($object, $sample)
    {
        foreach ($sample as $prop=>$value)
        {
            if(empty($value) || $object->$prop != $value)
            {
                return false;
            }
        }
        
        return true;
    }


    /**
     * 添加一个对象到数据库
     * @param Object $object 对象
     * @param array $fields 对象的属性数组
     * @param string $onDuplicate 主键或唯一键冲突时执行的更新语句
     * @return int 添加这条记录生成的主键值
     */
    public function add($object, $fields, $onDuplicate = null)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);
        
        $query = 'INSERT INTO `'. $this->table_name . '`(' . $strFields . ') VALUES (' . $strValues . ')';
        
        if ($onDuplicate != null) $query .= ' ON DUPLICATE KEY UPDATE '. $onDuplicate;
        
        $statement = $this->pdo->prepare($query);
        $params = array();

        foreach ($fields as $field)
        {
            $params[$field] = $object->$field;
        }
        
        $statement->execute($params);
        
        return $this->pdo->lastInsertId();
    }

	/**
	 * @param string tbl_name 要插入的数据库表名
     * @param Object $object 对象
     * @param array $fields 对象的属性数组
     * @param string $onDuplicate 主键或唯一键冲突时执行的更新语句
     * @return int 添加这条记录生成的主键值
     */
    public function simple_add($tbl_name, $object, $fields, $onDuplicate = null)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);
        
        $query = 'INSERT INTO `'. $tbl_name . '`(' . $strFields . ') VALUES (' . $strValues . ')';
        
        if ($onDuplicate != null) $query .= 'ON DUPLICATE KEY UPDATE '. $onDuplicate;
        
        $statement = $this->pdo->prepare($query);
        $params = array();
        
        foreach ($fields as $field)
        {
            $params[$field] = $object->$field;
        }
        
        $statement->execute($params);
        
        return $this->pdo->lastInsertId();
    }
 
    /**
     * REPLACE模式添加一个对象到数据库
     * @param Object $object 对象
     * @param array $fields 对象的属性数组
     * @return int 添加这条记录生成的主键值
     */
    public function replace($object, $fields)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);
        
        $query = 'REPLACE INTO `'. $this->table_name . '`(' . $strFields . ') VALUES (' . $strValues . ')';
        $statement = $this->pdo->prepare($query);
        $params = array();
        
        foreach ($fields as $field)
        {
            $params[$field] = $object->$field;
        }
        
        $statement->execute($params);
        
        return $this->pdo->lastInsertId();
    }
   
    /**
     * 更新所有符合条件的对象
     *
     * @param array $fields
     * @param array $params
     * @param string $where
     */
    public function update($fields, $params, $where, $change=false)
    {
        if ($change)
        {
            $updateFields = array_map(__CLASS__ . '::change_field_map', $fields);
        } else {
            $updateFields = array_map(__CLASS__ . '::update_filed_map', $fields);
        }
        
        $strUpdateFields = implode(',', $updateFields);		
        $query = 'UPDATE `' . $this->table_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

	/**
     *
     * @param array $fields
     * @param array $params
     * @param string $where
     */
    public function simple_update($strUpdateFields, $params, $where)
    {
        $query = 'UPDATE `' . $this->table_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
		$statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

	/**
     * @param array $fields
     * @param array $params
     * @param string $where
     */
    public function simple_update2($tbl_name, $fields, $params, $where, $change=false)
    {
        if ($change)
        {
            $updateFields = array_map(__CLASS__ . '::change_field_map', $fields);
        } else {
            $updateFields = array_map(__CLASS__ . '::update_filed_map', $fields);
        }
        
        $strUpdateFields = implode(',', $updateFields);		
        $query = 'UPDATE `' . $tbl_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

	/**
	 * @param $string $tbl_name
     * @param $string $strUpdateFileds
     * @param array $params
     * @param string $where
     */
    public function simple_update3($tbl_name, $strUpdateFields, $params, $where)
    {
        $query = 'UPDATE `' . $tbl_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
		$statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }
    
    public function fetch_arr($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $query = "SELECT " . $fields . " FROM `" . $this->table_name . "` WHERE " . $where;
        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        if($limit)
        {
            $query .= " limit " . $limit;
        }
        
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $statement->setFetchMode(PDO::FETCH_BOTH);
        return $statement->fetchAll();
    }
    
    /**
     * 获取满足条件的所有结果集，不包含数字键
     */
	public function fetch_arr_only_assoc($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null, $lock = null)
    {
        $query = "SELECT " . $fields . " FROM `" . $this->table_name . "` WHERE " . $where;
        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        if($limit)
        {
            $query .= " limit " . $limit;
        }
        
		if ($lock)
		{
			$query .= " " . $lock;
		}
		
        $statement = $this->pdo->prepare($query);
        
    	if(!$statement->execute($params))
        {
        	throw new Exception("数据读取错误");
        }
        
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetchAll();
    }
    
    public function fetch_col($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $results = $this->fetch_arr($where, $params, $fields, $orderBy, $limit);
        
        return empty($results) ? array() : array_map('reset', $results);
    }
    
    /**
     * 取得所有符合条件的对象
     *
     * @param string $where sql条件
     * @param array $params sql参数
     * @param string $fields sql字段
     * @return array 对象数组
     */
    public function fetch_all($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $query = "SELECT " . $fields . " FROM `" . $this->table_name . "` WHERE " . $where;

        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        if($limit)
        {
            $query .= " limit " . $limit;
        }
        
        $statement = $this->pdo->prepare($query);
        
        if(!$statement->execute($params))
        {
        	throw new Exception("数据读取错误");
        }
       
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->class_name);
        return $statement->fetchAll();
    }

	/**
     * 指定表名取得所有符合条件的对象
     *
     * @param string $where sql条件
     * @param array $params sql参数
     * @param string $fields sql字段
     * @return array 对象数组
     */
    public function simple_fetch_all($tbl_name, $where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null, $lock = null)
    {
        $query = "SELECT " . $fields . " FROM `" . $tbl_name . "` WHERE " . $where;

        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        if($limit)
        {
            $query .= " limit " . $limit;
        }

		if ($lock)
		{
			$query .= " " . $lock;
		}
        
        $statement = $this->pdo->prepare($query);
        
        if(!$statement->execute($params))
        {
        	throw new Exception("数据读取错误");
        }
       
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->class_name);
        return $statement->fetchAll();
    }

    /**
     * 根据样本查找对象
     *
     * @param object sample
     * @param string $fields
     * @return array
     */
    public function fetch_all_by_sample($sample, $fields = '*', $orderBy = null, $limit = null)
    {
        $where = self::create_where_by_sample($sample);
        $params = self::create_params_by_sample($sample);
        
        return $this->fetch_all($where, $params, $fields, $orderBy, $limit);
    }
    
    /**
     * 根据样本找到一个对象
     *
     * @param object $sample
     * @param string $fields
     * @return object
     */
    public function fetch_one_by_sample($sample, $fields = '*', $orderBy = null)
    {
        $where = self::create_where_by_sample($sample);
        $params = self::create_params_by_sample($sample);

        return $this->fetch_obj($where, $params, $fields, $orderBy);
    }
    
    public function fetch_obj($where = '1', $params = null, $fields = '*', $orderBy = null, $lock = '' )
    {
        $query = "SELECT " . $fields . " FROM `" . $this->table_name . "` WHERE " . $where;

        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        $query .= " limit 1";
        $query .= $lock;
		$this->query = $query;
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->class_name);
        return $statement->fetch();
    }
    
    public function simple_fetch_obj($tbl_name, $where = '1', $params = null, $fields = '*', $orderBy = null)
    {
        $query = "SELECT " . $fields . " FROM `" . $tbl_name . "` WHERE " . $where;

        if($orderBy)
        {
            $query .= " order by " .$orderBy;
        }
        
        $query .= " limit 1";        
		$this->query = $query;
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->class_name);
        return $statement->fetch();
    }

    /**
     * 取得符合条件的第一条记录的第一个值
     * @param string $where
     * @param array $params
     * @param string $fields
     * @return unknown
     */
    public function fetch_value($where = '1', $params = null, $fields = '*', $lock = '' )
    {
        $query = "SELECT ".$fields." FROM `".$this->table_name."` WHERE " . $where . " limit 1";
        
    	$query .= $lock;
        
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchColumn();
    }
    
    /**
     * 取得获取符合条件的记录数
     * @param string $field
     * @param string $where
     * @param array $params
     * @return count
     */
    public function count( $field, $where = '1', $params = null )
    {
        $query = "SELECT count(". $field . ") FROM `" . $this->table_name . "` WHERE " . $where;
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchColumn();  
    }
	
	/**
	 * 取得获取符合条件的总和
	 * @param string $field
	 * @param string $where
	 * @param array $params
	 * @return sum
	 */
    public function sum( $field, $where = '1', $params = null )
    {
        $query = "SELECT sum(". $field . ") FROM `" . $this->table_name . "` WHERE " . $where;
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchColumn();  
    }
    
    /**
     * 删除符合条件的记录
     *
     * @param string $where
     * @param array $params
	 * @param string $table_name
     */
    public function remove($where, $params, $table_name = '')
    {
        $where = trim($where);
        if (empty($where)) return;

		if ('' != $table_name)
			$query = "DELETE FROM `" . $table_name . "` WHERE " . $where;
		else
			$query = "DELETE FROM `" . $this->table_name . "` WHERE " . $where;

        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }
    
    /**
     * 更新所有符合条件的对象(如果实际更新到的行数为0，则返回失败)
     * @param array $fields
     * @param array $params
     * @param string $where
     */
    public function update_real($fields, $params, $where, $change=false)
    {
    	if ($change)
            $updateFields = array_map(__CLASS__ . '::change_field_map', $fields);
        else
            $updateFields = array_map(__CLASS__ . '::update_filed_map', $fields);
        
        $strUpdateFields = implode(',', $updateFields);		
        $query = 'UPDATE `' . $this->table_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
        $statement = $this->pdo->prepare($query);
        $re = $statement->execute($params);
        return $re === true && $statement->rowCount() > 0;
    }
    
    /**
     * 简单的更新 (如果实际更新到的行数为0，则返回失败)
     * @param array $fields
     * @param array $params
     * @param string $where
     */
 	public function simple_update_real($strUpdateFields, $params, $where)
    {
        $query = 'UPDATE `' . $this->table_name . '` SET ' . $strUpdateFields . ' WHERE ' . $where;
        
		$statement = $this->pdo->prepare($query);
        $re = $statement->execute($params);
        return $re === true && $statement->rowCount() > 0;
    }
    
    private static function create_where_by_sample($sample)
    {
        $where = '';

        foreach ($sample as $prop=>$value)
        {
            if(empty($value))
            {
                continue;
            }
            
            if(!empty($where))
            {
                $where .= ' AND ';
            }

            $where .= '`' . $prop . '`=:' . $prop;
        }
        
        return $where;
    }
    
    private static function create_params_by_sample($sample)
    {
        $params = array();
        
        foreach ($sample as $prop=>$value)
        {
            if(empty($value))
            {
                continue;
            }
            $params[$prop] = $value;
        }

        return $params;
    }

	 /**
     * @param string $sql
     * @return object
     */
    public function execute_sql($sql, $params = null)
    {
        $statement = $this->pdo->prepare($sql);
		$statement->execute($params);
       
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetchAll();
    }

	public function execute_sql2($sql, $params = null)
    {
        $statement = $this->pdo->prepare($sql);
		return $statement->execute($params);
    }
	
	public function execute_ret_value($sql, $params = null)
    {
		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);
		return $statement->fetchColumn();
    }
} // End CDbProxy