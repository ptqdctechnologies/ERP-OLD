<?php
/**
 * Created by JetBrains PhpStorm.
 * User: emir
 * Date: 2/20/12
 * Time: 8:51 AM
 * To change this template use File | Settings | File Templates.
 */

class Admin_NewsController extends Zend_Controller_Action
{

    private $news;
    private $files;
    private $counter;

    public function init()
    {
        $this->db = Zend_Registry::get('db');
        $this->session = new Zend_Session_Namespace('login');
        $this->news = new Admin_Models_News();
        $this->files = new Default_Models_Files();
        $this->counter = new Default_Models_MasterCounter();
    }

    public function menuAction ()
    {
        
    }

    public function insertnewsAction ()
    {
        
    }

    public function doinsertnewsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $title = $this->getRequest()->getParam('title');
        $description = $this->getRequest()->getParam('description');
        $typenews = $this->getRequest()->getParam('type');
        $file =  $this->getRequest()->getParam('fileJson');
        $jsonFile = Zend_Json::decode($file);

        $type = 'NWS';

        $trano = $this->counter->setNewTrans($type);
        $uid = $this->session->userName;
        $tgl = date('Y-m-d H:i:s');

        $insertnews = array(
            "trano" => $trano,
            "uid" => $uid,
            "tgl" => $tgl,
            "type" => $typenews,
            "judul" => $title,
            "isi" => preg_replace('#<script(.*?)>(.*?)</script>#is', '',$description)
        );

        $this->news->insert($insertnews);

        if (count($jsonFile) > 0)
        {
            foreach ($jsonFile as $key => $val)
            {
                $arrayInsert = array (
                    "trano" => $trano,
                    "prj_kode" => null,
                    "date" => date("Y-m-d H:i:s"),
                    "uid" => $this->session->userName,
                    "filename" => $val['filename'],
                    "savename" => $val['savename']
                );
                $this->files->insert($arrayInsert);
            }
        }

        $return = array("success" => true);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function viewnewsAction ()
    {
        
    }

    public function getnewsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $option = $this->getRequest()->getParam('option');
        $textsearch = $this->getRequest()->getParam('search');
        $id = $this->getRequest()->getParam('id');

        if ($textsearch != null || $textsearch != '')
        {
            $search = "$option LIKE '%$textsearch%'";
        }

        if($id != '')
        {
            $search = "id = $id";
        }

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 10;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'tgl';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'desc';

        $data['data'] = $this->news->fetchAll($search,array($sort . " " . $dir),$limit,$offset)->toArray();

        foreach($data['data'] as $k => $v)
        {
            $isiClean = strip_tags($v['isi']);
            $data['data'][$k]['tgl'] = date("d M Y H:i",strtotime($v['tgl']));
            $data['data'][$k]['excerpt'] = substr($isiClean,0,100) . " ...";
            $data['data'][$k]['author'] = QDC_User_Ldap::factory(array("uid" => $v['uid']))->getName();
            $cekfile = $this->files->fetchAll("trano = '{$v['trano']}'");
            if ($cekfile)
            {
                $cekfile = $cekfile->toArray();
                $data['data'][$k]['attachment'] = $cekfile;
            }
            else
                $data['data'][$k]['attachment'] = array();
        }

        $data['total'] = $this->news->fetchAll($search)->count();

        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function dodeletenewsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $id = $this->getRequest()->getParam('id');

        $this->news->delete("id = '$id'");

        $return = array("success" => true);

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function editnewsAction ()
    {
        $id = $this->getRequest()->getParam('id');

        $news = $this->news->fetchRow("id = '$id'");

        $active = $news['active'];

        if ($active == 1)
        {
            $active = 'false';
        }else{
            $active = 'true';
        }

        $this->view->trano = $news['trano'];
        $this->view->judul = $news['judul'];
        $this->view->isi = $news['isi'];
        $this->view->type = $news['type'];
        $this->view->active = $active;
    }

    public function doupdatenewsAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $title = $this->getRequest()->getParam('title');
        $description = $this->getRequest()->getParam('description');
        $typenews = $this->getRequest()->getParam('type');
        $trano = $this->getRequest()->getParam('trano');
        $status = $this->getRequest()->getParam('status');

        if ($status != 'false')
        {
            $active = 0;
        }else{
            $active = 1;
        }

        $updatenews = array(
            "judul" => $title,
            "isi" => $description,
            "type" => $typenews,
            "active" => $active
        );

        $this->news->update($updatenews,"trano = '$trano'");

        $return = array("success" => true);
        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type','text/javascript');
        $this->getResponse()->setBody($json);
    }


}