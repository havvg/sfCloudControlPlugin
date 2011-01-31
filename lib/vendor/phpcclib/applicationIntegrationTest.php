<?php
include_once 'phpcclib.php';
include_once 'CCImap.php';

class ApplicationIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CCAPI
     */
    private static $_api = null;
    private static $_userName = "testintegrationtest";
    private static $_email = "test@cloudcontrol.de";
    private static $_password = "integrationtest1234";
    private static $_imapPassword = '9598S2';
    
    public static function setUpBeforeClass()
    {
        self::$_api = new CCAPI('https://api.cloudcontrolled.dev');
        self::$_api->createUser(self::$_userName, self::$_email, self::$_password);
        $imap = new CCImap(self::$_email, self::$_imapPassword);
        $activationCode = $imap->getNewestActivationCode();
        try {
            self::$_api->activateUser(self::$_userName, $activationCode);
        } catch (ForbiddenError $e) {
            $imap->deleteAll();            
        }
        self::$_api->createAndSetToken(self::$_email, self::$_password);
    }
    
    public static function tearDownAfterClass()
    {
        self::$_api->createAndSetToken(self::$_email, self::$_password);
        self::$_api->deleteUser(self::$_userName);
    }
    
    public function setUp()
    {
    }
    
    public function tearDown()
    {
        self::$_api->createAndSetToken(self::$_email, self::$_password);
        $appList = self::$_api->getApplicationList();
        foreach ($appList as $appInfo) {
            $deploymentList = self::$_api->getDeploymentList($appInfo->name);
            foreach ($deploymentList as $deployment) {
                $deploymentname = substr($deployment->name, strlen($appInfo->name)+1);
                self::$_api->deleteDeployment($appInfo->name, $deploymentname);
            }
            self::$_api->deleteApplication($appInfo->name);
        }
        // take some time to remove all properly
        sleep(10);
    }
    
    public function testCreateAppication()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        $result = self::$_api->createApplication($applicationName, $type);    
        $this->assertEquals($applicationName, $result->name);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository', $applicationName), $result->repository);
        $this->assertEquals(self::$_userName, $result->owner->username);
        $this->assertEquals(self::$_email, $result->owner->email);
        $this->assertEquals($type, $result->type->name);
        $this->assertEmpty($result->deployments);
        $this->assertEmpty($result->invitations);
    }
    
    public function testCreateAppicationTwice()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        $result = self::$_api->createApplication($applicationName, $type);
        try {
            self::$_api->createApplication($applicationName, $type);
        } catch (BadRequestError $e) {
            return;
        }
        $this->fail("Expected BadRequestError has not been raised!");
    }
    
    public function testGetApplicationDetails()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        $result =self::$_api->getApplicationDetails($applicationName);     
        $this->assertEquals($applicationName, $result->name);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository', $applicationName), $result->repository);
        $this->assertEquals(self::$_userName, $result->owner->username);
        $this->assertEquals(self::$_email, $result->owner->email);
        $this->assertEquals($type, $result->type->name);
        $this->assertEmpty($result->deployments);
        $this->assertEmpty($result->invitations);
    }
    
    public function testDeleteApplication()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        $result = self::$_api->createApplication($applicationName, $type);
        $this->assertEquals($applicationName, $result->name);
        $result = self::$_api->deleteApplication($applicationName);
        $this->assertTrue($result);
        try {
            self::$_api->getApplicationDetails($applicationName);
        } catch (GoneError $e) {
            return;
        }
        $this->fail("Expected GoneError has not been raised!");
    }
    
    public function testGetApplicationList()
    {
        $appList = array();
        $type = 'php';
        $applicationName = "integrationtest" . rand(0,10000);
        $appList[] = self::$_api->createApplication($applicationName, $type);
        
        $applicationName = $applicationName . 1;
        $appList[] = self::$_api->createApplication($applicationName, $type);
        
        $result = self::$_api->getApplicationList();
        foreach ($result as $x) {
            foreach ($appList as $y) {
                if ($x->name != $y->name) continue;
                $this->assertEquals(trim($y->name), trim($x->name));
            }
        }
    }
    
    public function testCreateDeployment()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        $deploymentName = "deployment" . rand(0,10000);
        // first deployments name is ever default!
        $result = self::$_api->createDeployment($applicationName, $deploymentName);
                
        $this->assertEquals(sprintf('%s.cloudcontrolled.dev', $applicationName), $result->default_subdomain);
        $this->assertEquals(sprintf('%s/default', $applicationName), $result->addons[0]->deployment->name);
        $this->assertEquals('alias.free', $result->addons[0]->addon_option->name);
        $this->assertEquals(sprintf('%s/default', $applicationName), $result->name);
        $this->assertEquals(1, $result->max_boxes);
        $this->assertEquals(1, $result->min_boxes);
        $this->assertEquals(0, $result->billed_boxes->free_boxes);
        $this->assertEquals(0, $result->billed_boxes->costs);
        $this->assertEquals(0, $result->billed_boxes->boxes);
        $this->assertTrue($result->is_default);
        $this->assertEquals(0, $result->billed_addons[0]->hours);
        $this->assertEquals(0, $result->billed_addons[0]->costs);
        $this->assertEquals('alias.free', $result->billed_addons[0]->addon);
        $this->assertEquals('not deployed', $result->state);
        $this->assertEquals(0, $result->version);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository', $applicationName), $result->branch);
        $this->assertNotEmpty($result->dep_id);
        $this->assertEquals(sprintf('sftp://%s@cloudcontrolled.dev/', $result->dep_id), $result->static_files);
        $this->assertEquals($applicationName, $result->app->name);
        $this->assertEquals($type, $result->app->type->name);
        if ($result->aliases[0]->is_default) {
            $defaultAlias = $result->aliases[0];
            $secondAlias = $result->aliases[1];
        } else {
            $defaultAlias = $result->aliases[1];
            $secondAlias = $result->aliases[0];
        }
        $this->assertEquals(sprintf('%s.cloudcontrolled.dev', $applicationName), $defaultAlias->name);
        $this->assertTrue($defaultAlias->is_verified);
        $this->assertTrue($defaultAlias->is_default);
        
        $this->assertEquals(sprintf('%s.%s.cloudcontrolled.dev', $deploymentName, $applicationName), $secondAlias->name);
        $this->assertFalse($secondAlias->is_verified);
        $this->assertFalse($secondAlias->is_default);
    }
    
    public function testGetDeploymentDetails()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        $deploymentName = "deployment" . rand(0,10000);
        // first deployments name is ever default!        
        self::$_api->createDeployment($applicationName, $deploymentName);
        $result = self::$_api->getDeploymentDetails($applicationName, 'default');
        
        $this->assertEquals(sprintf('%s.cloudcontrolled.dev', $applicationName), $result->default_subdomain);
        $this->assertEquals(sprintf('%s/default', $applicationName), $result->addons[0]->deployment->name);
        $this->assertEquals('alias.free', $result->addons[0]->addon_option->name);
        $this->assertEquals(sprintf('%s/default', $applicationName), $result->name);
        $this->assertEquals(1, $result->max_boxes);
        $this->assertEquals(1, $result->min_boxes);
        $this->assertEquals(0, $result->billed_boxes->free_boxes);
        $this->assertEquals(0, $result->billed_boxes->costs);
        $this->assertEquals(0, $result->billed_boxes->boxes);
        $this->assertTrue($result->is_default);
        $this->assertEquals(0, $result->billed_addons[0]->hours);
        $this->assertEquals(0, $result->billed_addons[0]->costs);
        $this->assertEquals('alias.free', $result->billed_addons[0]->addon);
        $this->assertEquals('not deployed', $result->state);
        $this->assertEquals(0, $result->version);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository', $applicationName), $result->branch);
        $this->assertNotEmpty($result->dep_id);
        $this->assertEquals(sprintf('sftp://%s@cloudcontrolled.dev/', $result->dep_id), $result->static_files);
        $this->assertEquals($applicationName, $result->app->name);
        $this->assertEquals($type, $result->app->type->name);
        if ($result->aliases[0]->is_default) {
            $defaultAlias = $result->aliases[0];
            $secondAlias = $result->aliases[1];
        } else {
            $defaultAlias = $result->aliases[1];
            $secondAlias = $result->aliases[0];
        }
        $this->assertEquals(sprintf('%s.cloudcontrolled.dev', $applicationName), $defaultAlias->name);
        $this->assertTrue($defaultAlias->is_verified);
        $this->assertTrue($defaultAlias->is_default);
        
        $this->assertEquals(sprintf('%s.%s.cloudcontrolled.dev', $deploymentName, $applicationName), $secondAlias->name);
        $this->assertFalse($secondAlias->is_verified);
        $this->assertFalse($secondAlias->is_default);    
    }
    
    public function testGetDeploymentList()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        $deploymentList = array();
        
        // first deployments name is ever default!
        self::$_api->createDeployment($applicationName);
        $deploymentList[] = self::$_api->getDeploymentDetails($applicationName, 'default');
        
        $deploymentName = "deployment" . rand(0,10000);
        self::$_api->createDeployment($applicationName, $deploymentName);
        $deploymentList[] = self::$_api->getDeploymentDetails($applicationName, $deploymentName);
        
        $result = self::$_api->getDeploymentList($applicationName);
        $this->assertEquals(2, count($result));
        
        if ($result[0]->name == sprintf('%s/default', $applicationName)) {
            $defaultDeployment = $result[0];
            $additionalDeployment = $result[1];
        } else {
            $defaultDeployment = $result[1];
            $additionalDeployment = $result[0];
        }
        $this->assertEquals(sprintf('%s.cloudcontrolled.dev', $applicationName), $defaultDeployment->default_subdomain);
        $this->assertEquals(sprintf('%s/default', $applicationName), $defaultDeployment->name);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository', $applicationName), $defaultDeployment->branch);
        $this->assertEquals(2, count($defaultDeployment->aliases));
        
        $this->assertEquals(sprintf('%s.%s.cloudcontrolled.dev', $deploymentName, $applicationName), $additionalDeployment->default_subdomain);
        $this->assertEquals(sprintf('%s/%s', $applicationName, $deploymentName), $additionalDeployment->name);
        $this->assertEquals(sprintf('bzr+ssh://%s@cloudcontrolled.dev/repository/%s', $applicationName, $deploymentName), $additionalDeployment->branch);
        $this->assertEquals(1, count($additionalDeployment->aliases));
        $this->assertEquals(sprintf('%s.%s.cloudcontrolled.dev', $deploymentName, $applicationName), $additionalDeployment->aliases[0]->name);
        $this->assertTrue($additionalDeployment->aliases[0]->is_verified);
        $this->assertTrue($additionalDeployment->aliases[0]->is_default);
    }
    
    public function testDeleteDeployment()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        
        // first deployments name is ever default!
        self::$_api->createDeployment($applicationName);
        $deployment = self::$_api->getDeploymentDetails($applicationName, 'default');
        
        self::$_api->deleteDeployment($applicationName, 'default');
        try {
            self::$_api->getDeploymentDetails($applicationName, 'default');
        } catch (GoneError $e) {
            return;
        }
        $this->fail("Expected GoneError has not been raised!");
    }

    public function testCreateAlias()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        
        // first deployments name is ever default!
        self::$_api->createDeployment($applicationName);
        $alias = 'www.myalias.de';
        self::$_api->createAlias($applicationName, 'default', $alias);
        $deployment = self::$_api->getDeploymentDetails($applicationName, 'default');
        foreach($deployment->aliases as $aliasInfo) {
            if (strpos($aliasInfo->name, $alias) !== false) {
                $this->assertEquals($alias, $aliasInfo->name);
                $this->assertFalse($aliasInfo->is_verified);
                $this->assertFalse($aliasInfo->is_default);
            }
        }
    }

    public function testGetAliasDetails()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        
        // first deployments name is ever default!
        self::$_api->createDeployment($applicationName);
        $alias = 'www.myalias.de';
        self::$_api->createAlias($applicationName, 'default', $alias);
        $aliasInfo = self::$_api->getAliasDetails($applicationName, 'default', $alias);
        $this->assertEquals($alias, $aliasInfo->name);
        $this->assertFalse($aliasInfo->is_verified);
        $this->assertFalse($aliasInfo->is_default);
    }

    public function testDeleteAlias()
    {
        $applicationName = "integrationtest" . rand(0,10000);
        $type = 'php';
        self::$_api->createApplication($applicationName, $type);
        
        // first deployments name is ever default!
        self::$_api->createDeployment($applicationName);
        $alias = 'www.myalias.de';
        self::$_api->createAlias($applicationName, 'default', $alias);
        self::$_api->deleteAlias($applicationName, 'default', $alias);
        try {
            $aliasInfo = self::$_api->getAliasDetails($applicationName, 'default', $alias);
        } catch (GoneError $e) {
            return;
        }
        $this->fail("Expected GoneError has not been raised!");
    }
    
    public function testGetAllAddonList()
    {
        $result = self::$_api->getAllAddonList();
        foreach ($result as $addonInfo) {
            if (strpos($addonInfo->name, 'mysql') !== false) {
                $this->assertEquals('mysql', $addonInfo->name);
                $this->assertEquals('mysql.free', $addonInfo->options[0]->name);
            }
            if (strpos($addonInfo->name, 'alias') !== false) {
                $this->assertEquals('alias', $addonInfo->name);
                $this->assertEquals('alias.free', $addonInfo->options[0]->name);
            }
        }
    }
}
