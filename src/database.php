<?php
Class Database {
    private  $host = "mysql:host=localhost;dbname=blog;charset=utf8";
    private  $username = "root";
    private  $password = "";
    private  $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
    private  $connection;

    public function Connect(){

        $this->connection = new PDO($this->host, $this->username,$this->password,$this->options);
        $this->connection -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $this -> connection;  
    
    }

    public function run($sqlstring, $arguments = NULL){

        if (!$arguments){

            return $this -> connection -> query($sqlstring);
        
        }else{

            $statement = $this -> connection -> prepare($sqlstring);
            $statement -> execute($arguments);
            return $statement;
        
        }
    }
}

$db = new Database();
$db -> Connect();

?>