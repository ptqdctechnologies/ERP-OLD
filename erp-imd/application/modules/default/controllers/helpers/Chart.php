<?php
class Zend_Controller_Action_Helper_Chart extends
                Zend_Controller_Action_Helper_Abstract
{

    function randomColor(){
    mt_srand((double)microtime()*1000000);
    $c = '';
    while(strlen($c)<6){
        $c .= sprintf("%02X", mt_rand(0, 255));
    }
        return '#'.$c;
    }

    function buildChart($dataArray,$title='',$xAxisName='',$yAxisName='',$link='',$isDrillDown=false)
    {
        $dom = new DomDocument("1.0", "UTF8");
        $el = $dom->createElement('graph');
        $graph = $dom->appendChild($el);
        if (!isset($title))
            $title = 'Chart';

        $graph->setAttribute('caption',$title);
        $graph->setAttribute('xAxisName',$xAxisName);
        $graph->setAttribute('yAxisName',$yxisName);
        foreach ($dataArray as $key => $value)
        {
            $set = $dom->createElement("set");
            $graph->appendChild($set);
            $set->setAttribute('name',$key);
            $set->setAttribute('value',$value);
            $set->setAttribute('color',$this->randomColor());
            if (isset($link) && !$isDrillDown)
            {
                $set->setAttribute('link',$link);
            }
            elseif (isset($link) && $isDrillDown)
            {
                $prj_kode = explode('-',$key);
                $prj_kode = $prj_kode[0];
                $set->setAttribute('link',$link . "('" . $prj_kode . "');");
            }
        }
      return $dom->saveXML();
       
    }

    function buildChart2($dataArray,$graphArray,$isDrillDown=false,$link='')
    {
        $dom = new DomDocument("1.0", "UTF8");
        $el = $dom->createElement('graph');
        $graph = $dom->appendChild($el);
        foreach ($graphArray as $key => $value)
        {
            $graph->setAttribute($key,$value);
        }
        
        foreach ($dataArray as $key => $value)
        {
            if (is_array($value))
            {
                if ($key == 'categories')
                {
                    $categories = $dom->createElement('categories');
                    $graph->appendChild($categories);
                    for($i=0;$i<count($dataArray['categories']);$i++)
                    {
                        $category = $dom->createElement('category');
                        $categories->appendChild($category);
                        $category->setAttribute('name',$dataArray['categories'][$i]);
                    }
                }
                else
                {
                    $elemen = $dom->createElement('dataset');
                    $graph->appendChild($elemen);
                    $elemen->setAttribute('seriesName',$key);
                    $elemen->setAttribute('color',$this->randomColor());
                    $elemen->setAttribute('showValues','0');

                    for($i=0;$i<count($dataArray[$key]);$i++)
                    {
                        $set = $dom->createElement('set');
                        $elemen->appendChild($set);
                        $set->setAttribute('value',$dataArray[$key][$i]);
                    }
                }
                
            }
            else
            {
                $set = $dom->createElement("set");
                $graph->appendChild($set);
                $set->setAttribute('name',$key);
                $set->setAttribute('value',$value);
                $set->setAttribute('color',$this->randomColor());
                if (isset($link) && !$isDrillDown)
                {
                    $set->setAttribute('link',$link);
                }
                elseif (isset($link) && $isDrillDown)
                {
                    $prj_kode = explode('-',$key);
                    $prj_kode = $prj_kode[0];
                    $set->setAttribute('link',$link . "('" . $prj_kode . "');");
                }
            }
        }
      return $dom->saveXML();

    }
}
?>