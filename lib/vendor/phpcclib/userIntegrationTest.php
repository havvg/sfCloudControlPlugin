<?php
include_once 'phpcclib.php';
include_once 'CCImap.php';

class UserIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CCAPI
     */
    private $_api = null;
    
    private $_userName;
    private $_email;
    private $_password;
    private $_imapPassword;
    private $_manuallyDeleted = false;
    
    public function setUp()
    {
        $this->_api = new CCAPI('https://api.cloudcontrolled.dev');
        
        $this->_userName = "testintegrationtest";
        $this->_email = "test@cloudcontrol.de";
        $this->_password = "integrationtest1234";
        $this->_imapPassword = '9598S2';
    }
    
    public function tearDown()
    {
        if ($this->_manuallyDeleted === true) return;
        
        try {
            $token = $this->_api->createAndSetToken($this->_email, $this->_password);
            $this->_api->deleteUser($this->_userName);
        } catch (UnauthorizedError $e) {
            print "User is already deleted, or not activated!";
        }
    }
    
    public function testCreateAndActivateUser()
    {   
        $result = $this->_api->createUser($this->_userName, $this->_email, $this->_password);
        $imap = new CCImap($this->_email, $this->_imapPassword);
        $activationCode = $imap->getNewestActivationCode();
        
        $this->assertEquals(get_class($result), 'stdClass');
        $this->assertEquals($this->_userName, $result->username);
        $this->assertEquals($this->_email, $result->email);
        $this->assertEmpty($result->first_name);
        $this->assertEmpty($result->last_name);
        $this->assertEmpty($result->is_active);
        
        $result = $this->_api->activateUser($this->_userName, $activationCode);
        $this->assertTrue($result);
    }
    
    public function testDeleteUser()
    {
        $this->_createuser();
        $result = $this->_api->deleteUser($this->_userName);
        $this->assertTrue($result);
        $this->_manuallyDeleted = true;
        try {
            $this->_api->deleteUser($this->_userName);
        } catch (UnauthorizedError $e) {
            return;
        }
        $this->fail("Expected UnauthorizedError has not been raised!");
    }
    

    public function testUserAddKey()
    {
        $this->_createuser();
        $publicKey = file_get_contents(getcwd() . "/testkey1.pub");
        $result = $this->_api->createUserKey($this->_userName, $publicKey);
        $this->assertEquals(trim($publicKey), trim($result->key));
        $this->assertNotEmpty(trim($result->key_id));
    }
    
    public function testUserGetKeyList()
    {
        $this->_createuser();
        $keyList = array();
        $publicKey1 = file_get_contents(getcwd() . "/testkey1.pub");
        $keyList[] = $this->_api->createUserKey($this->_userName, $publicKey1);
        
        $publicKey2 = file_get_contents(getcwd() . "/testkey2.pub");
        $keyList[] = $this->_api->createUserKey($this->_userName, $publicKey2);
        
        $result = $this->_api->getUserKeyList($this->_userName);
        $this->assertEquals(count($result), count($keyList));
        
        foreach ($result as $x) {
            foreach ($keyList as $y) {
                if ($x->key_id != $y->key_id) continue;
                $this->assertEquals(trim($y->key), trim($x->key));
            }
        }
    }
    
    public function testUserDeleteKey()
    {
        $this->_createuser();
        $publicKey = file_get_contents(getcwd() . "/testkey1.pub");
        $result = $this->_api->createUserKey($this->_userName, $publicKey);
        
        $this->_api->deleteUserKey($this->_userName, $result->key_id);
        $result = $this->_api->getUserKeyList($this->_userName);
        $this->assertEmpty($result); 
    }
    
    public function testGetUserList()
    {
        $this->_createuser();
        $result = $this->_api->getUserList();
        $this->assertEquals(1, count($result));
        
        $this->assertEquals(get_class($result[0]), 'stdClass');
        $this->assertEquals($this->_userName, $result[0]->username);
        $this->assertEquals($this->_email, $result[0]->email);
        $this->assertEmpty($result[0]->first_name);
        $this->assertEmpty($result[0]->last_name);
        $this->assertTrue($result[0]->is_active);
    }
    
    public function testUserDetails()
    {
        $this->_createuser();
        $result = $this->_api->getUserDetails($this->_userName);
        $this->assertEquals(get_class($result), 'stdClass');
        $this->assertEquals($this->_userName, $result->username);
        $this->assertEquals($this->_email, $result->email);
        $this->assertEmpty($result->first_name);
        $this->assertEmpty($result->last_name);
        $this->assertTrue($result->is_active);
    }
    
    private function _createuser()
    {
        $result = $this->_api->createUser($this->_userName, $this->_email, $this->_password);
        $imap = new CCImap($this->_email, $this->_imapPassword);
        $activationCode = $imap->getNewestActivationCode();
        $this->_api->activateUser($this->_userName, $activationCode);
        $this->_api->createAndSetToken($this->_email, $this->_password);
    }
}