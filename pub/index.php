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
{/** @see \Magento\Webapi\Controller\Rest::dispatch */
    try {
        $pathInfo = $request->getPathInfo();
        if (preg_match("/^\\/[Vv]\\d+/", $pathInfo) === 1) {
            {/** @see \Magento\Webapi\Controller\Rest\SynchronousRequestProcessor::process */
                $time = microtime(true);
                {/** @see \Magento\Webapi\Controller\Rest\Router::match */
                    /** @var \Magento\Webapi\Model\Rest\Config $apiConfig */
                    {/** @see \Magento\Webapi\Model\Rest\Config::getRestRoutes */
                        $requestHttpMethod = $request->getHttpMethod();
                        {/** @see \Magento\Webapi\Model\Config::getServices */
                            $cache = $objectManager->get(\Magento\Framework\ApcuCache::class);
                            $services = $cache->getCachedContent('services', function() use ($objectManager) {
                                return $objectManager->get(\Magento\Webapi\Model\Config\Reader::class)->read();
                            });
                        }
                        $servicesRoutes = $services[Converter::KEY_ROUTES];
                        $routeFactory = $objectManager->get(\Magento\Framework\Controller\Router\Route\Factory::class);
                        // Return the route on exact match
                        if (isset($servicesRoutes[$pathInfo][$requestHttpMethod])) {
                            $methodInfo = $servicesRoutes[$pathInfo][$requestHttpMethod];
                            {/** @see \Magento\Webapi\Model\Rest\Config::_createRoute */
                                /** @var $route \Magento\Webapi\Controller\Rest\Router\Route */
                                $route = $routeFactory->createRoute(
                                    \Magento\Webapi\Controller\Rest\Router\Route::class,
                                    $pathInfo
                                );
                                $route->setServiceClass($methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_CLASS])
                                    ->setServiceMethod($methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_METHOD])
                                    ->setSecure($methodInfo[Converter::KEY_SECURE])
                                    ->setAclResources(array_keys($methodInfo[Converter::KEY_ACL_RESOURCES]))
                                    ->setParameters($methodInfo[Converter::KEY_DATA_PARAMETERS]);
                            }
                        } else {
                            $serviceBaseUrl = preg_match('#^/?\w+/\w+#', $pathInfo, $matches) ? $matches[0] : null;
                            ksort($servicesRoutes, SORT_STRING);
                            foreach ($servicesRoutes as $url => $httpMethods) {
                                // skip if baseurl is not null and does not match
                                if (!$serviceBaseUrl || strpos(trim($url, '/'), trim($serviceBaseUrl, '/')) !== 0) {
                                    // base url does not match, just skip this service
                                    continue;
                                }
                                foreach ($httpMethods as $httpMethod => $methodInfo) {
                                    if (strtoupper($httpMethod) == strtoupper($requestHttpMethod)) {
                                        $aclResources = array_keys($methodInfo[Converter::KEY_ACL_RESOURCES]);
                                        {
                                            /** @see \Magento\Webapi\Model\Rest\Config::_createRoute */
                                            /** @var $route \Magento\Webapi\Controller\Rest\Router\Route */
                                            $route = $routeFactory->createRoute(
                                                \Magento\Webapi\Controller\Rest\Router\Route::class,
                                                $url
                                            );

                                            $route->setServiceClass($methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_CLASS])
                                                ->setServiceMethod($methodInfo[Converter::KEY_SERVICE][Converter::KEY_SERVICE_METHOD])
                                                ->setSecure($methodInfo[Converter::KEY_SECURE])
                                                ->setAclResources($aclResources)
                                                ->setParameters($methodInfo[Converter::KEY_DATA_PARAMETERS]);
                                        }
                                        $routes[] = $route;
                                    }
                                }
                            }
                            $matched = [];
                            foreach ($routes as $route) {
                                /** @var \Magento\Framework\Webapi\Rest\Request $request */
                                {/** @see \Magento\Webapi\Controller\Rest\Router\Route::match */
                                    $pathParts =  explode('/', trim($pathInfo, '/'));
                                    $result = [];
                                    $routeParts = explode('/', $route->getRoutePath());
                                    $variables = [];
                                    foreach ($routeParts as $key => $value) {
                                        if (substr($value, 0, 1) == ':' && substr($value, 1, 1) != ':') {
                                            $variables[$key] = substr($value, 1);
                                            $value = null;
                                        }
                                        $result[$key] = $value;
                                    }
                                    $routeParts = $result;

                                    if (count($pathParts) <> count($routeParts)) {
                                        return false;
                                    }

                                    $result = [];
                                    foreach ($pathParts as $key => $value) {
                                        if (!array_key_exists($key, $routeParts)) {
                                            return false;
                                        }
                                        $variable = isset($variables[$key]) ? $variables[$key] : null;
                                        if ($variable) {
                                            $result[$variable] = urldecode($pathParts[$key]);
                                        } else {
                                            if ($value != $routeParts[$key]) {
                                                return false;
                                            }
                                        }
                                    }
                                    $params = $result;
                                }

                                if ($params !== false) {
                                    $request->setParams($params);
                                    $matched[] = $route;
                                }
                            }
                            var_dump($matched);
                            if (!empty($matched)) {
                                $route = array_pop($matched);
                            } else {
                                throw new \Magento\Framework\Webapi\Exception(
                                    __('Request does not match any route.'),
                                    0,
                                    \Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND
                                );
                            }
                        }
                    }
                }
                if ($route->isSecure() && !$request->isSecure()) {
                    throw new \Magento\Framework\Webapi\Exception(__('Operation allowed only in HTTPS'));
                }
                $serviceClassName = $route->getServiceClass();
                $serviceMethodName = $route->getServiceMethod();
                /*
                 * Valid only for updates using PUT when passing id value both in URL and body
                 */
                if ($request->getHttpMethod() === RestRequest::HTTP_METHOD_PUT) {
                    $inputData = $this->paramsOverrider->overrideRequestBodyIdWithPathParam(
                        $request->getParams(),
                        $request->getBodyParams(),
                        $serviceClassName,
                        $serviceMethodName
                    );
                    $inputData = array_merge($inputData, $request->getParams());
                } else {
                    $inputData = $request->getRequestData();
                }
                $paramsOverrider = $objectManager->get(\Magento\Webapi\Controller\Rest\ParamsOverrider::class);
                $serviceInputProcessor = $objectManager->get(\Magento\Framework\Webapi\ServiceInputProcessor::class);
                $serviceOutputProcessor = $objectManager->get(\Magento\Framework\Webapi\ServiceOutputProcessor::class);
                $inputData = $paramsOverrider->override($inputData, $route->getParameters());
                $inputParams = $serviceInputProcessor->process($serviceClassName, $serviceMethodName, $inputData);
                $service = $objectManager->get($serviceClassName);
                /** @var \Magento\Framework\Api\AbstractExtensibleObject $outputData */
                $outputData = call_user_func_array([$service, $serviceMethodName], $inputParams);
                $outputData = $serviceOutputProcessor->process($outputData, $serviceClassName, $serviceMethodName);
                if ($request->getParam(FieldsFilter::FILTER_PARAMETER) && is_array($outputData)) {
                    $outputData = $this->fieldsFilter->filter($outputData);
                }
                $deploymentConfig = $objectManager->get(\Magento\Framework\App\DeploymentConfig::class);
                $response = $objectManager->create(\Magento\Framework\Webapi\Rest\Response::class);
                $header = $deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_X_FRAME_OPT);
                if ($header) {
                    $response->setHeader('X-Frame-Options', $header);
                }
                $response->prepareResponse($outputData);
            }
        } else if (strpos(ltrim($pathInfo, '/'), self::PROCESSOR_PATH) === 0) {
            $requestedServices = $request->getRequestedServices('all');
            $requestedServices = $requestedServices == Request::ALL_SERVICES
                ? $this->swaggerGenerator->getListOfServices()
                : $requestedServices;
            $responseBody = $this->swaggerGenerator->generate(
                $requestedServices,
                $request->getScheme(),
                $request->getHttpHost(false),
                $request->getRequestUri()
            );
            $this->response->setBody($responseBody)->setHeader('Content-Type', 'application/json');
            return true;
        } else {
            throw new \Magento\Framework\Webapi\Exception(
                \__('Specified request cannot be processed.'),
                404,
                \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST
            );
        }
    } catch (\Exception $e) {
        try {
            $response = $objectManager->create(\Magento\Framework\Webapi\Rest\Response::class);
            $maskedException = $objectManager->get(\Magento\Framework\Webapi\ErrorProcessor::class)->maskException($e);
            $response->setException($maskedException);
        } catch (\Exception $e) {
            mageErrorHandler(0, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace());
        }
    }
}
$response->sendResponse();
