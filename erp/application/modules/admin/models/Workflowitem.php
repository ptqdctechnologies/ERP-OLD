<?php

class Admin_Models_Workflowitem extends Zend_Db_Table_Abstract
{
	protected $_name ='workflow_item';
	protected $db;
	
	public function __construct()
	{
		parent::__construct($this->_option);
		$this->db = Zend_Registry::get('db');
	}

	public function createWorkflowItem($dataArray,$addArray='')
    {
    	$this->db = Zend_Registry::get('db');
    	$rv = array_filter($dataArray,'is_array');
   	 	if(count($rv)>0)
   	 	{
	   	 	foreach($dataArray as $key => $val)
	    	{
	    		$fields = array();
    			$values = array();
	    		if (is_array($val))
	    		{
	    			foreach($val as $key2 => $val2)
	    			{
			    		$fields[] = $key2;
			    		if (!is_numeric($val2))	
			    		{
		    				if ($key2 == 'due_date')
		    				{
		    					$val2 = str_replace('/','-',$val2);
	    						$val2 = date('Y-m-d', strtotime($val2));
    							$dataArray[$key][$key2] = $val2;
		    				}
			    			$values[] = "'" . $val2 . "'";
			    		}
			    		else
			    			$values[] = $val2;
	    			}
	    			if (is_array($addArray))
    				{
		    			foreach ($addArray as $add => $valAdd)
		    			{
		    				$fields[] = $add;
		    				$values[] = $valAdd;
		    			}
    				}
	    			$fieldsSql = implode(',',$fields);
			    	$valuesSql = implode(',',$values);
			    	
			    	$sql = "INSERT INTO
			    				workflow_item
			    				( $fieldsSql )
			    			VALUES
			    				( $valuesSql );";
			    	$this->db->query($sql);
			    	$lastID = $this->db->lastInsertId();
	    			$dataArray[$key]['workflow_item_id'] = $lastID;
	    		}
	    	}
    	}
    	else
    	{
    		$fields = array();
    		$values = array();
    		foreach($dataArray as $key => $val)
    		{
    			$fields[] = $key;
	    		if (!is_numeric($val))	
	    		{
    				if ($key == 'due_date')
    				{
    					$val = str_replace('/','-',$val);
    					$val = date('Y-m-d', strtotime($val));
    					$dataArray[$key] = $val;
    				}
	    			$values[] = "'" . $val . "'";
	    		}
	    		else
	    			$values[] = $val;
    		}
    		
    		if (is_array($addArray))
    		{
	    		foreach ($addArray as $add => $valAdd)
	    		{
	    			$fields[] = $add;
	    			$values[] = $valAdd;
	    		}
    		}
	    	$fieldsSql = implode(',',$fields);
	    	$valuesSql = implode(',',$values);
	    	
	    	$sql = "INSERT INTO
	    				workflow_item
	    				( $fieldsSql )
	    			VALUES
	    				( $valuesSql );";
	    	$this->db->query($sql);
	    	$lastID = $this->db->lastInsertId();
	    	$dataArray['workflow_item_id'] = $lastID;	
    	}
    	
    	return $dataArray;
    	
    }
    
    public function listWorkflowItem($sort='',$dir='',$limit='',$offset='',$generic=false,$search='')
    {
    	if (!$generic)
        {
            if ($search != '')
                $search = " AND $search";
    	    $sql = $this->db->select()
                    ->from(array('w' => 'workflow_item'),array(
                        new Zend_Db_Expr("SQL_CALC_FOUND_ROWS workflow_item_id"),
                        "workflow_item_id",
                        "workflow_item_type_id",
                        "name",
                        "description",
                        "prj_kode",
                        //"sit_kode",
                        "generic"
                    ))
                    ->where("w.generic = 0 " . $search)
                    ->join(array('wt' => 'workflow_item_type'),
                            'w.workflow_item_type_id = wt.workflow_item_type_id',
                            array('workflow_item_type_name' => 'name')
                            )
                    ->order(array($sort . ' ' . $dir))
                    ->limit($limit,$offset);
            if ($search == '')
                $sql->reset(Zend_Db_Select::WHERE);
        }
        else
        {
            if ($search != '')
                $search = " AND $search";
    	    $sql = $this->db->select()
                    ->from(array('w' => 'workflow_item'),array(
                        new Zend_Db_Expr("SQL_CALC_FOUND_ROWS workflow_item_id"),
                        "workflow_item_id",
                        "workflow_item_type_id",
                        "name",
                        "description",
                        "prj_kode",
                        //"sit_kode",
                        "generic"
                    ))
                    ->where("w.generic = 1" . $search)
                    ->join(array('wt' => 'workflow_item_type'),
                            'w.workflow_item_type_id = wt.workflow_item_type_id',
                            array('workflow_item_type_name' => 'name')
                            )
                    ->order(array($sort . ' ' . $dir))
//                    ->group(array("w.workflow_item_type_id","w.prj_kode"))
                    ->limit($limit,$offset);
            
        }
    	$return['data'] = $this->db->fetchAll($sql);
        $return['count'] =  $this->db->fetchOne ('SELECT FOUND_ROWS()');;
        return $return;
    }
    
	public function deleteWorkflowItem($workflowtypeID)
    {
    	$sql = "DELETE FROM workflow_item WHERE workflow_item_id=$workflowtypeID";
    	$this->db->query($sql);
    }
    
	public function updateWorkflowItem($dataArray,$criteriaArray)
    {
    	$metadata = $this->db->describeTable('workflow_item');
        $columnNames = array_keys($metadata);
    	$values = array();
    	$updateArray = $dataArray;
    	
    	$rv = array_filter($dataArray,'is_array');
   	 	if(count($rv)>0)
   	 	{
	    	foreach($updateArray as $key => $val)
	    	{
	    		if (is_array($val))
	    		{
	    			foreach($val as $key2 => $val2)
	    			{
	    				
	    				if (!in_array($key2,$columnNames))
	    					continue;
			    		if (!is_numeric($val))	
			    		{
			    			
			    			if ($key2 == 'workflow_item_id')
		    				{
		    					$criteriaArray[] = "workflow_item_id=$val";
		    					unset($updateArray[$key]['workflow_item_id']); 
    							continue;
		    				}
			    			$values[] = "$key2 = '" . $val2 . "'";
			    		}
			    		else
			    		{
				    		if ($key2 == 'workflow_item_id')
		    				{
		    					$criteriaArray[] = "workflow_item_id=$val";
		    					unset($updateArray[$key]['workflow_item_id']); 
    							continue;
		    				}
			    			$values[] = "$key2 = $val2";	
			    		}
	    			}
    	
			    	$criteriasSql = implode(' AND ',$criteriaArray);
			    	$valuesSql = implode(',',$values);
			    	
			    	$sql = "UPDATE
			    				workflow_item
			    				SET $valuesSql 
			    			WHERE
			    				( $criteriasSql )";
			    	$this->db->query($sql);
	    		}
	    	}
   	 	}
   	 	else
   	 	{
    		$values = array();
    		foreach($updateArray as $key => $val)
    		{
    			if (!in_array($key,$columnNames))
    				continue;
	    		if (!is_numeric($val))	
	    		{
    				if ($key == 'workflow_item_id')
    				{
    					$criteriaArray[] = "workflow_item_id=$val";
    					unset($updateArray['workflow_item_id']); 
    					continue;
    				}
	    			$values[] = "$key = '" . $val . "'";
	    		}
	    		else
	    		{
	    			if ($key == 'workflow_item_id')
    				{
    					$criteriaArray[] = "workflow_item_id=$val";
    					unset($updateArray['workflow_item_id']); 
    					continue;
    				}
	    			$values[] = "$key = '" . $val . "'";
	    		}
    		}
	    	
	    	$valuesSql = implode(',',$values);
	    	
	    	$criteriasSql = implode(' AND ',$criteriaArray);
	    	$valuesSql = implode(',',$values);
	    	
	    	$sql = "UPDATE
	    				workflow_item
	    				SET $valuesSql 
	    			WHERE
	    				( $criteriasSql )";
	    	$this->db->query($sql);
   	 	}
   	 	return $dataArray;
    	
    }

    public function listWorkflowItemGeneric($sort='',$dir='',$limit='',$offset='',$byone=false,$search='')
    {

        $sql = $this->db->select()
                ->from(array('w' => 'workflow_item'))
                ->where("w.generic = 1 $search")
                ->order(array($sort . ' ' . $dir))
                    ->group(array("w.name"))
                ->limit($limit,$offset);

        if ($byone)
        $sql->reset( Zend_Db_Select::GROUP );

        $return = $this->db->fetchAll($sql);
        return $return;
    }


    public function __name()
    {
        return $this->_name;
    }

    public function getProject($workflow_item_id='',$generic=0)
    {
        $select = $this->db->select()
            ->from(array($this->_name),array(
                "prj_kode"
            ))
            ->where("workflow_item_id=?",$workflow_item_id)
            ->where("generic=?",$generic);

        return $this->db->fetchOne($select);
    }
}

?>