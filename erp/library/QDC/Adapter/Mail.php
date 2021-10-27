<?php
class QDC_Adapter_Mail {

    private $mail, $noAuth=false;
    private $tr, $at; //$tr == mail transport, $at == mail attachment
    private $smtpParams;
    private $useHtml=false,$html, $file_content;

    public function __construct($params = '')
    {
        if ($params != '')
        {
            foreach($params as $k => $v)
            {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        $this->smtpParams = Zend_Registry::get("smtp");

        if ($this->noAuth)
            $this->tr = new Zend_Mail_Transport_Smtp($this->smtpParams['hostname']);
        else
        {
            $this->tr = new Zend_Mail_Transport_Smtp($this->smtpParams['hostname'],
                array(
                    'auth' => 'plain',
        //                'auth' => 'login',
        //                'ssl' => 'tls',
                    'username' => $this->smtpParams['username'],
                    'password' => $this->smtpParams['password']));
        }
        Zend_Mail::setDefaultTransport($this->tr);
        $this->mail = new Zend_Mail();
    }

    public static function factory($params=array())
    {
        return new self($params);
    }

    public function send()
    {
        if ($this->useTemplate)
        {
            $templateDir = APPLICATION_PATH . "/mailtemplates";
            $viewConfig = array('basePath' => $templateDir);

            $htmlView = new Zend_View($viewConfig);

            if ($this->viewParams != '')
            {
                foreach($this->viewParams as $k => $v)
                {
                    $htmlView->{$k} = $v;
                }
            }

            try {
                $this->html = $htmlView->render($this->templateName . ".phtml");
            } catch (Zend_View_Exception $e) {
                echo $e->getMessage();
                die;
            }
        }

        $this->mail->setFrom($this->sender);
        $this->mail->setBodyText($this->msgText);
        if ($this->useHtml)
            $this->mail->setBodyHtml($this->html);
        $this->mail->addTo($this->recipient, $this->recipient);
        $this->mail->setSubject($this->subject);
        try {
            $this->mail->send();
        } catch (Zend_Mail_Exception $e) {
            $err = $e->getMessage();
            return $err;
        }

        return true;

    }

    public function sendNoRender()
    {

        $this->mail->setFrom($this->sender);
        $this->mail->setBodyText($this->msgText);
        $this->mail->setBodyHtml($this->html);
        $this->mail->addTo($this->recipient, $this->recipient);
        $this->mail->setSubject($this->subject);
        try {
            $this->mail->send();
        } catch (Zend_Mail_Exception $e) {
            $err = $e->getMessage();
            return $err;
        }

        return true;

    }

    public function sendWithAttachment()
    {
        $this->mail->setFrom($this->sender);
        $this->mail->setBodyText($this->msgText);
        if ($this->useHtml)
        {
            $this->mail->setBodyHtml($this->html);
        }
        $this->mail->addTo($this->recipient, $this->recipient);
        $this->mail->setSubject($this->subject);

//        $this->createAttachment();

        try {
            $this->mail->send();
        } catch (Zend_Mail_Exception $e) {
            $err = $e->getMessage();
            return $err;
        }

        return true;
    }

    public function createAttachment()
    {
        $this->at = new Zend_Mime_Part($this->file_content);
        if ($this->at_type)
            $this->at->type = $this->at_type;
        if ($this->at_disposition)
            $this->at->disposition = $this->at_disposition;
        if ($this->at_encoding)
            $this->at->encoding = $this->at_encoding;
        if ($this->at_file_name)
            $this->at->filename = $this->at_filename;

        $this->mail->addAttachment($this->at);
    }
}

?>