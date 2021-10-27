<?php
include_once('config.php');
//Class ini harus singleton!

class Database
{

private static $instance;
protected $user,$pass,$host,$dbname;
    
    // Constructor
    private function __construct() 
    {
	//Ubah username,password, hostname & nama database disini
        $this->user = userDB;
	$this->pass = passDB;
	$this->host = hostDB;
	$this->dbname = nameDB;
	
	$this->instance = mysql_connect($this->host,$this->user,$this->pass) or die(mysql_error());
	mysql_select_db($this->dbname,$this->instance);
    }

    // The singleton method
    public static function dbConnectionInit() 
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }

    public static function dbConnectionClose() 
    {
        if (!isset(self::$instance)) {
            mysql_close(self::$instance);
            self::$instance = null;
        }

	return null;
    }

    public function query($sql)
    {
	$fetch = mysql_query($sql,$this->instance) or die(mysql_error());
	if ($fetch === true || $fetch === false)
		return $fetch;
	$cek = mysql_fetch_array($fetch);
	if ($cek != null)
	{
		$rows = mysql_num_rows($fetch);	
		if ($rows > 1)
		{
			$result = array();
			while($data = mysql_fetch_assoc($fetch))
			{
				$result[] = $data;
			}
		}
		else
		{
			$result = $cek;
		}
		mysql_free_result($fetch);
		return $result;
	}
	return $cek;
    } 
}

?>
