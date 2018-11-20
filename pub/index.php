<?php
/**
 * Public alias for the application entry point
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define('BP', realpath(__DIR__ . '/..'));
include BP . "/vendor/autoload.php";
$objectManager = \Magento\Framework\App\ObjectManagerFactory::create('webapi_rest', $_SERVER);
/** @var \Magento\Framework\Webapi\Rest\Request $request */
$request = $objectManager->get(\Magento\Framework\Webapi\Rest\Request::class);
/** @var \Magento\Webapi\Controller\Rest $front */
$front = $objectManager->get(\Magento\Webapi\Controller\Rest::class);
$response = $front->dispatch($request);
$response->sendResponse();
