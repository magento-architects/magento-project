<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Akril\Cart;

class AddItems implements \Magento\CartApi\AddItems
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * GetCarts constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $scope
     * @param array $items
     * @param string $cartId
     */
    public function execute($scope, array $items, $cartId = null)
    {

    }
}