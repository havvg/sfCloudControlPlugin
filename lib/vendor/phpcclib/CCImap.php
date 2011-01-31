<?php

class CCImap
{
    private $_server = "imap.googlemail.com";
    private $_port = 993;
    private $_email;
    private $_password;
    
    public function __construct($email, $password)
    {
        $this->_email = $email;
        $this->_password = $password;
    }
    
    public function getNewestActivationCode()
    {
        $counter = 10;
        $messages = $this->_getMailList();
        while(count($messages) == 0) {
            sleep(5);
            $messages = $this->_getMailList();
            $counter -= 1;
            if($counter == 0){
                throw new Exception('No mail');
            }
        }
        return $this->_parseMail($messages);
    }
    
    public function deleteAll()
    {
        $mbox = imap_open(sprintf("{%s:%d/imap/ssl}INBOX", $this->_server, $this->_port), $this->_email, $this->_password);
        $mailList = imap_search($mbox,'ALL');
        $bodyList = array();
        if(is_array($mailList)) {
            foreach($mailList as $num) {
                imap_delete($mbox, $num);
            }
        }
    }
    
    private function _getMailList()
    {
        $mbox = imap_open(sprintf("{%s:%d/imap/ssl}INBOX", $this->_server, $this->_port), $this->_email, $this->_password);
        $mailList = imap_search($mbox,'ALL');
        $bodyList = array();
        if(is_array($mailList)) {
            foreach($mailList as $num) {
                $body = imap_fetchbody($mbox, $num, "1");
                array_push($bodyList, $body);
                imap_delete($mbox, $num);
            }
        }
        return $bodyList;
    }
    
    private function _parseMail($bodyList)
    {
        $pattern = '/interface:[\W]+([\w]+)[\W]+The code/';
        $activationCode = '';
        foreach ($bodyList as $body) {
            if(preg_match($pattern, $body, $matches)) {
                $activationCode = $matches[1];
            }
        }
        return $activationCode;
    }
}