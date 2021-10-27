<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 12/23/11
 * Time: 9:39 AM
 * To change this template use File | Settings | File Templates.
 */
 
class Finance_ProfitlossController extends Zend_Controller_Action
{

    private $layoutprofitloss;
    private $session;

    public function init()
    {
        $this->layoutprofitloss = new Finance_Models_LayoutProfitloss();
        $this->session = new Zend_Session_Namespace('login');
    }

    public function menulayoutAction ()
    {
        
    }

    public function insertlayoutprofitlossAction ()
    {
        
    }

    public function doinsertlayoutprofitlossAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_kode_text');
        $type = $this->getRequest()->getParam('type');
        $dk = $this->getRequest()->getParam('dk');
        $hd = $this->getRequest()->getParam('hd');
        $level = $this->getRequest()->getParam('level');
        $parent= $this->getRequest()->getParam('coa_kode_parent');

        $where = "coa_kode = '$coa_kode'";

        $cek = $this->layoutprofitloss->fetchRow($where);

        if ($cek)
        {
            $return = array("success" => false, "pesan" => "Sorry, COA has been ready to use");
        }else{

            $urut = $this->getUrut($level,$parent);
            if ($urut === false)
            {
                $return = array("success" => false, "pesan" => "Invalid Level Or Parent/Header COA is not exists!");
                return false;
            }
            $insert = array(
                'coa_kode' => $coa_kode,
                'coa_nama' => $coa_nama,
                'tipe' => $type,
                'dk' => $dk,
                'hd' => $hd,
                'level' => $level,
                'urut' => $urut,
                'uid' => $this->session->userName,
                'tgl' => date("Y-m-d")
            );

            $this->layoutprofitloss->insert($insert);

            $return = array("success" => true);
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function doupdatelayoutprofitlossAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $coa_kode = $this->getRequest()->getParam('coa_kode');
        $coa_nama = $this->getRequest()->getParam('coa_kode_text');
        $type = $this->getRequest()->getParam('type');
        $dk = $this->getRequest()->getParam('dk');
        $hd = $this->getRequest()->getParam('hd');
        $level = $this->getRequest()->getParam('level');
        $parent= $this->getRequest()->getParam('coa_kode_parent');
        $idEdit = $this->getRequest()->getParam("id_edit");

        $cek = $this->layoutprofitloss->fetchRow("id = $idEdit");
        if (!$cek)
        {
            echo "{success: false, pesan: 'Invalid Record'}";
            return false;
        }

        if ($coa_kode != $cek['coa_kode'])
        {
            $where = "coa_kode = '$coa_kode'";

            $cek = $this->layoutprofitloss->fetchRow($where);
            if ($cek)
            {
                $return = array("success" => false, "pesan" => "Sorry, COA Code Exists, Please Delete : $coa_kode first!");
            }
        }


        $oldUrut = $cek['urut'];
        $oldLevel = $cek['level'];
        $oldHeader = $cek['hd'];
        $arr = explode(".",$oldUrut);
        $oldPrefix = $arr[0];
        $this->layoutprofitloss->delete("id = $idEdit");

        if ($parent != '')
        {
            $cekParent = $this->layoutprofitloss->fetchRow("coa_kode = '$parent'");
            if ($cekParent)
            {
                $cekParent = $cekParent->toArray();
                $prefixParent = explode(".",$cekParent['urut']);
                $newPrefix = $prefixParent[0];
            }
        }
        else
            $newPrefix = $oldPrefix;

        if ($level != $oldLevel || $hd != $oldHeader || $oldPrefix != $newPrefix )
        {
            $cek = $this->layoutprofitloss->fetchAll("id != $idEdit AND urut LIKE '$oldUrut%'");
            if ($cek)
            {
                $this->layoutprofitloss->delete("id != $idEdit AND urut LIKE '$oldUrut%'");
            }
            $urut = $this->getUrut($level,$parent);
        }
        else
            $urut = $oldUrut;

        if ($urut === false)
        {
            $return = array("success" => false, "pesan" => "Invalid Level Or Parent/Header COA is not exists!");
            return false;
        }

        $insert = array(
            'coa_kode' => $coa_kode,
            'coa_nama' => $coa_nama,
            'tipe' => $type,
            'dk' => $dk,
            'hd' => $hd,
            'level' => $level,
            'urut' => $urut,
            'uid' => $this->session->userName,
            'tgl' => date("Y-m-d")
        );

        $this->layoutprofitloss->insert($insert);

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    private function getUrut($level=0,$parent='')
    {
        if ($level == 0)
            return false;
        if ($level == 1)
        {
            $cek = $this->layoutprofitloss->fetchRow("level = 1",array("urut DESC"));
            if ($cek)
            {
                $cek = $cek->toArray();
                $urutBaru = intval($cek['urut']) + 1;
            }
            else
                $urutBaru = 1;
        }
        else
        {
            $cek = $this->layoutprofitloss->fetchRow("coa_kode = '$parent'");
            if ($cek)
            {
                $prefix = $cek['urut'];

                $cek2 = $this->layoutprofitloss->fetchRow("level = $level AND urut LIKE '$prefix%'",array("LPAD(urut,30,'0') DESC"));
                if ($cek2)
                {
                    $cek2 = $cek2->toArray();
                    $arrUrut = explode(".",$cek2["urut"]);
                    $lastUrut = $arrUrut[$level-1];
                    $urutBaru = $prefix . "." . (intval($lastUrut) + 1);
                }
                else
                {
                    $urutBaru = $prefix . "." . 1;
                }
            }
            else
                return false;
        }

        return $urutBaru;

    }


    public function editlayoutprofitlossAction ()
    {
        
    }

    public function getcoaAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 40;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'id';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';
        $option = $this->getRequest()->getParam("option");
        $search = $this->getRequest()->getParam("search");
        $query = $this->getRequest()->getParam("query");
        $idEdit = $this->getRequest()->getParam("id_edit");

        if ($option != '' && $search != '')
            $where = "$option LIKE '%$search%'";

        if ($query != '')
        {
            $jsonQuery = Zend_Json::decode($query);
            $tmp = array();
            foreach ($jsonQuery as $k => $v)
            {
                foreach ($v as $k2 => $v2)
                {
                    $tmp[] = "$k2 = '$v2'";
                }
            }
            if ($where == '')
                $where = implode(" AND ", $tmp);
            else
                $where .= " AND " . implode(" AND ", $tmp);

        }

        if ($idEdit != '')
            $where .= " AND id != $idEdit";

        $data['data'] = $this->layoutprofitloss->fetchAll($where,array($sort . " " . $dir),$limit,$offset)->toArray();

        $data['total'] = $this->layoutprofitloss->fetchAll($where)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getcoatreeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $node = $this->getRequest()->getParam("node");
        $tree = array();

        if ($node == 'posts')
        {
            $firstLevel = $this->layoutprofitloss->fetchAll("level = 1");
            if ($firstLevel)
            {
                $firstLevel = $firstLevel->toArray();
                foreach($firstLevel as $k => $v){
                    $tree[] = array(
                        "text" => $v['coa_kode'] . " " . $v['coa_nama'],
                        "id" => $v['urut'],
                        "cls" => "level1",
                        "coa_kode" => $v['coa_kode'],
                        "id_data" => $v['id'],
                        "urut" => $v['urut']
                    );
                }
            }
        }
        else
        {
            $arr = explode(".",$node);
            $level = count($arr) + 1;
            $i = 0;
            $nextLevel = $this->layoutprofitloss->fetchAll("level = $level AND urut LIKE '$node.%'");
            foreach($nextLevel as $k => $v){
                $tree[$i] = array(
                    "text" => $v['coa_kode'] . " " . $v['coa_nama'],
                    "id" => $v['urut'],
                    "coa_kode" => $v['coa_kode'],
                    "id_data" => $v['id'],
                    "urut" => $v['urut']
                );
                if ($v['hd'] == 'Detail')
                {
                    $tree[$i]['leaf']  = true;
                    $tree[$i]['cls'] = "level{$level}d";
                }
                else
                    $tree[$i]['cls'] = "level$level";
                $i++;
            }
        }

        $json = Zend_Json::encode($tree);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getlayoutprofitlossAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam("id");

        $cek = $this->layoutprofitloss->fetchRow("id = $id");
        if ($cek)
        {
            $data = $cek->toArray();
            if ($data['level'] > 1 && ($data['hd'] == 'Header' || $data['hd'] == 'Detail'))
            {
                $level = intval($data['level']) - 1;
                $arr = explode(".",$data['urut']);
                $count = count($arr);
                unset($arr[$count-1]);
                $urut = implode(".",$arr);
                $cek2 = $this->layoutprofitloss->fetchRow("level = $level AND urut = '$urut'");
                if ($cek2)
                {
                    $data['coa_kode_parent'] = $cek2['coa_kode'];
                }
            }
            $return = array("success" => true, "data" => $data);
        }
        else
            $return = array("success" => false, "pesan" => "Invalid Record!");

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function deletelayoutprofitlossAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam("id");

        $cek = $this->layoutprofitloss->fetchRow("id = $id");
        if ($cek)
        {
            $this->layoutprofitloss->delete("id = $id");
            $return = array("success" => true);
        }
        else
            $return = array("success" => false, "pesan" => "Invalid Record!");

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }
}