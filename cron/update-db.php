<?php

include_once('config.php');

function microtime_float ()
{
    list ($msec, $sec) = explode(' ', microtime());
    $microtime = (float)$msec + (float)$sec;
    return $microtime;
}
// Get starting time.
$start = microtime_float();

include_once('db_class.php');
include_once('zend.php');

$db = Database::dbConnectionInit();

$tables = $db->query('SHOW TABLES');

foreach ($tables as $key => $val)
{
	foreach ($val as $key2 => $val2)
	{
		$cektables = $db->query("SELECT k.column_name
									FROM information_schema.table_constraints t
									JOIN information_schema.key_column_usage k
									USING(constraint_name,table_schema,table_name)
									WHERE t.constraint_type='PRIMARY KEY'
									  AND t.table_schema='" . nameDB . "'
									  AND t.table_name='$val2'"
							  );
		if (!$cektables)
		{
			echo "Found no primary key in table " . $val2 . "\n";
			echo "Adding primary key for table " . $val2 . "\n";
			$result = $db->query("ALTER TABLE `$val2` ADD COLUMN `id` INTEGER  NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)");
			if ($result)
				echo "Adding primary key : Success! \n";
			else
				echo "Adding primary key : Fail! \n";	
		}				  
		
	}
}

?>
