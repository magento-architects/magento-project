<?php
/**
 * Public alias for the application entry point
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use \Magento\Webapi\Model\Config\Converter;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use \Magento\Framework\Webapi\Rest\Response\FieldsFilter;
use \Magento\Framework\Config\ConfigOptionsListConstants;
define('BP', realpath(__DIR__ . '/..'));
include BP . "/vendor/autoload.php";
$objectManager = createObjectManager('webapi_rest', $_SERVER);
/** @var \Magento\Framework\Webapi\Rest\Request $request */
$request = $objectManager->get(\Magento\Framework\Webapi\Rest\Request::class);
/** @var \Magento\Webapi\Controller\Rest $front */
$front = $objectManager->get(\Magento\Webapi\Controller\Rest::class);
$response = $front->dispatch($request);
$response->sendResponse();
