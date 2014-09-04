<?php
class mysqli_class {
   
    public $host;
    public $port;
    public $user;
    public $password;
    public $database;
    public $dbc;
    public $stmt;
   
    public function __construct ($host,$user,$password,$database) {
   
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
   
        $this->dbc  = new mysqli($this->host,$this->user,$this->password,$this->database);   
        if ($this->dbc->connect_error) {
            die('数据库连接错误:'.$this->dbc->connect_error);
        }
   
    }
   
    //添加数据
    public function insert (string $table, array $values){
   
        $query_arr = array();
        $query_str = "INSERT INTO `$table` SET ";
        foreach ($values as $key => $value) {
            $query_arr[] = " `$key`='$value' ";
        }
   
        $query_str .= implode(",",$query_arr);
   
        $this->query($query_str);
   
        return $this->dbc->insert_id;
   
    }
   
    //查询数据
    public function select ($table, array $fields = array(), array $where = array()) {
   
        $select_sql   = "SELECT ".implode(',',$fields)." FROM $table ";
   
        $where_parser = $this->sql_parser($where);
   
        $result = $this->perpare_query($select_sql.$where_parser,array_values($where));
        $return_res = array();
           
        if ($result) {
            $select_res = $this->get_res();
               
            while ($row = $select_res->fetch_array(MYSQLI_ASSOC))
            {
                foreach ($row as $k => $r)
                {
                    $return_res[$k] = $r;
                }
            }
        }
   
        return $return_res;
   
    }
   
    //更新数据
    public function update (string $table, array $data, array $where) {
        $where_arr = array();
        $data_arr  = array();
   
        foreach ($where as $key => $value) {
            $where_arr[] = ' `'.$key.'`="'.$value.'"';
        }
   
        $where_str = implode(' AND ',$where_arr);
   
        foreach ($data as $d_key => $d_val) {
            $data_arr[] = ' `'.$d_key.'`="'.$d_val.'"';
        }
        $data_str = implode(' , ', $data_arr);
   
        $update_sql = "UPDATE `".$table."` SET $data_str WHERE $where_str";
        return $this->query($update_sql);
   
    }
   
    //执行sql语句
    public function query ($query_sql) {
        return $this->dbc->query($query_sql);
    }
   
    //预处理sql
    private function perpare_query (string $query_str, array $bind_param) {
   
        $this->stmt = $this->dbc->prepare($query_str);
   
        $type   = '';
        $params = array(0=>'');
   
        foreach ($bind_param as $key => $value) {
            $type     .= $this->get_bind_type($value);
            $params[] = $value;
        }
   
        $params[0] = $type;
   
        call_user_func_array(array($this->stmt,'bind_param'), $this->ref_values($params));
        /**
         * 执行call_user_func_array时报错
         * Warning: Parameter 2 to mysqli_stmt::bind_param() expected to be a reference, value given
         * 意思应该是第二个参数的值应该是传引用,所以用ref_values函数处理了一下
         */
        return $this->stmt->execute();
   
    }
   
    // 拼接预处理条件字符串
    private function sql_parser (array $where) {
           
        if (empty($where)) { return '1'; }
   
        $query_where = array();
   
        foreach ($where as $key => $val) {
            $query_where[] = $key.'='.'?';
        }
   
        return ' WHERE '.implode(" AND ", $query_where);
   
    }
   
    //事物开启
    public function transaction_start () {
        $this->dbc->autocommit(false);
    }
   
    //事物提交
    public function transaction_commit () {
        $this->dbc->commit();
    }
   
    //事物回滚
    public function transaction_rollback () {
        $this->dbc->rollback();  
    }
   
    //获取预处理数据
    private function get_res (){
        return $this->stmt->get_result();
    }
   
    private function ref_values ($arr) {
   
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) { 
            $refs = array(); 
            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key]; 
                return $refs; 
            }
        }
        return $arr;
   
    }
   
    //获取预处理参数类型
    private function get_bind_type ($value) {
   
        $type = gettype($value);
        switch ($type) {
            case 'string':
                return 's';
                break;
   
            case 'integer':
                return 'i';
                break;
   
            case 'blob':
                return 'b';
                break;
   
            case 'double':
                return 'd';
                break;
        }
    }
   
    //关闭连接
    public function __destruct () {
        $this->dbc->close();
    }
   
}
