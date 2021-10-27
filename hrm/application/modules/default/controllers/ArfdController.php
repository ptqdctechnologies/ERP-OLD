<?php

class ArfdController extends Zend_Controller_Action
{
    private $db;
    private $DEFAULT;
    public function init()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $this->db = $bootstrap->getResource('connection');
        $session = new Zend_Session_Namespace('login');
        $models = array(
            "AdvanceRequestFormD",
            "AdvanceRequestFormH"
        );

        $this->DEFAULT = QDC_Model_Default::init($models);
    }

    public function indexAction()
    {
        // action body
    }

    public function listAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $listType = $request->getParam('type');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $prd = new Default_Models_ProcurementRequest();

        $return['posts'] = $prd->fetchAll(null, array($sort . ' ' . $dir), $limit, $offset)->toArray();
        $return['count'] = $prd->fetchAll()->count();
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function listbyparamsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();

        $columnName = $request->getParam('name');
        $columnValue = $request->getParam('data');

        $offset = (isset($_POST['start'])) ? $_POST['start'] : 0;
        $limit = (isset($_POST['limit'])) ? $_POST['limit'] : 100;
        $sort = (isset($_POST['sort'])) ? $_POST['sort'] : 'trano';
        $dir = (isset($_POST['dir'])) ? $_POST['dir'] : 'ASC';

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * FROM procurement_arfd WHERE ' . $columnName . ' LIKE \'%' . $columnValue . '%\' ORDER BY ' . $sort . ' '  . $dir . ' LIMIT ' . $offset . ',' . $limit;

        $fetch = $this->db->query($sql);
        $return['posts'] = $fetch->fetchAll();
        $return['count'] = $this->db->fetchOne('SELECT FOUND_ROWS()');
        //the posts
        Zend_Loader::loadClass('Zend_Json');
        $json = Zend_Json::encode($return);
        //result encoded in JSON

        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);
    }

    public function getRequesterAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $trano = $this->getRequest()->getParam("trano");

        $select = $this->db->select()
            ->from(array($this->DEFAULT->AdvanceRequestFormD->__name()),array("requester"))
            ->where("trano = '$trano'");

        $arfd = $this->db->fetchAll($select);

        if ($arfd)
        {
            $person = array();$oldARFFormat = false;
            foreach($arfd as $k => $v)
            {
                $uid = $v['requester'];
                if ($uid != '')
                    $person[$uid] = QDC_User_Ldap::factory(array("uid" => $v['requester']))->getName();
                else
                {
                    $oldARFFormat = true;
                }
            }

            if ($oldARFFormat)
            {
                $select = $this->db->select()
                    ->from(array($this->DEFAULT->AdvanceRequestFormH->__name()),array("requester2"))
                    ->where("trano = '$trano'");

                $arfd = $this->db->fetchRow($select);
                $person = array(
                    $arfd['requester2']
                );
            }

            $return = array(
                "success" => true,
                "requester" => implode(", ",$person)
            );
        }
        else
        {
            $return = array(
                "success" => false
            );
        }

        $json = Zend_Json::encode($return);
        $this->getResponse()->setHeader('Content-Type', 'text/javascript');
        $this->getResponse()->setBody($json);

    }
}

?>