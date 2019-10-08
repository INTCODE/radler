<?php

ini_set('display_errors',1);
$mageRoot = ((__DIR__));

require $mageRoot . '/app/bootstrap.php';


$objectManagerFactory = \Magento\Framework\App\Bootstrap::createObjectManagerFactory(BP, []);
$objectManager = $objectManagerFactory->create([]);
$_state  = $objectManager->get('Magento\Framework\App\State');
$_state->setAreaCode('global');

$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

$results = $connection->fetchAll('select * from quote_item order by quote_id desc');
foreach ($results as $result) {
    print_r($result); die();
}