<?php
include_once 'phpcclib.php';

$applicationName = "worker";
$deploymentName = "default";
$userEmail = "myname@myhost.de";
$userPassword = "mysecredPassword";
$workerFilePath = "worker.php";

/**
 * pay attention, your worker have to run at least one second
 * you can run your worker in infinite loop ( while(true){...} )
 * or exit with exitcodes 0 (success) or 2 (failure)
 */

$api = new CCAPI();
$api->createAndSetToken($userEmail, $userPassword);

/* add the worker addon, if you haven't already */
$result = $api->addAddon($applicationName, $deploymentName, 'worker.free');
print_r($result);

/* add a worker */
$workerDetail = $api->addWorker($applicationName, $deploymentName, $workerFilePath);
print_r($workerDetail);

/* get workers details */
$workerDetail = $api->getWorkerDetails($applicationName, $deploymentName, $workerDetail->wrk_id);
print_r($workerDetail);

/* get workers list */
$workerList = $api->getWorkerList($applicationName, $deploymentName);
foreach ($workerList as $worker) {
    /* remove worker */
    $api->removeWorker($applicationName, $deploymentName, $worker->wrk_id);
}
