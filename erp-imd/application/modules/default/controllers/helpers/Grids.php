<?php
class Zend_Controller_Action_Helper_Grids extends
                Zend_Controller_Action_Helper_Abstract
{


    function viewColumn($colNeedle,$colHaystack)
    {
        $retColumn = array();
        $col = array();
        foreach ($colNeedle as $key => $value)
        {
            $col = '';
            if (in_array($key,$colHaystack) && $value != '')
            {
                 foreach ($value as $properti => $propValue)
                 {
                     if ($properti == 'isDate')
                     {
                         $properti = "renderer";
                         $propValue = "Ext.util.Format.dateRenderer('d/m/Y')";
                     }
                     $col[] = $properti . ':' . $propValue;
                 }
                 $colRet = '{' .  implode($col,',') . '}';
                 $retColumn[] = $colRet;
            }
        }
        return implode($retColumn,',');
    }

    function mapping($colNeedle,$colHaystack)
    {
        $retColumn = array();
        foreach ($colNeedle as $key => $value)
        {
            $date = '';
            if (is_array($value))
            {
                if (in_array($key,$colHaystack))
                {
                        foreach ($value as $properti => $propValue)
                         {
                             if ($properti == 'isDate' && $propValue == 'true')
                             {
                                    $date = ",type: 'date', dateFormat: 'Y-m-d'";
                             }
                         }
                }
            }
            else
            {
                $key = $value;
            }
             $colRet = "{name: '" . $key . "', mapping: '" . $key . "'" . $date . "}";
             $retColumn[] = $colRet;
        }
        
        return implode($retColumn,',') ;
    }
}

?>
