<?php

namespace Crankycyclops\ConfigurableProduct\Block\Sales\Order\Email\Items;

use Magento\Downloadable\Block\Sales\Order\Email\Items\Order\Downloadable;
use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Link\Purchased;
use Magento\Downloadable\Model\Link\PurchasedFactory;
use Magento\Framework\UrlInterface;
use Magento\Downloadable\Model\Link\Purchased\Item;
use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Downloadable Sales Order Email items renderer
 *
 * @api
 * @since 100.0.2
 */
class ConfigurableProduct extends Downloadable
{
    protected Purchased $purchased;

    protected PurchasedFactory $purchasedFactory;

    protected CollectionFactory $itemsFactory;

    /**
     * Constructor.
     *
     * @param Context                                                     $context
     * @param PurchasedFactory                                            $purchasedFactory
     * @param CollectionFactory                                           $itemsFactory
     * @param array                                                       $data
     * @param \Magento\Downloadable\Model\Sales\Order\Link\Purchased|null $purchasedLink
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Link\PurchasedFactory $purchasedFactory,
        \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory,
        array $data = [],
        ?\Magento\Downloadable\Model\Sales\Order\Link\Purchased $purchasedLink = null
    ) {
        parent::__construct(
            $context,
            $purchasedFactory,
            $itemsFactory,
            $data,
            $purchasedLink
        );
        $this->purchasedFactory = $purchasedFactory;
        $this->itemsFactory     = $itemsFactory;
    }

    /**
     * Returns the item's child item, which represents the simple product that
     * was actually ordered.
     * Written by James Colannino.
     *
     * @return Order|bool
     */
    public function getChildItem()
    {
        /* @phpstan-ignore-next-line */
        $childrenItems = $this->getItem()->getChildrenItems();

        return $childrenItems[0] ?? false;
    }

    /**
     * @return bool
     */
    public function isSimpleProductDownloadable(): bool
    {
        $orderItem = $this->getChildItem();

        /* @phpstan-ignore-next-line */
        if (!is_bool($orderItem) && $orderItem->getProduct() && $orderItem->getProduct()->getTypeId()) {
            return 'downloadable' === $orderItem->getProduct()->getTypeId(); /* @phpstan-ignore-line */
        }

        return false;
    }

    /**
     * Modified by James Colannino so that this returns the links associated with
     * the configurable option's corresponding simple product (should only be
     * called if the simple product is downloadable.)
     *
     * @return Purchased
     */
    public function getLinks()
    {
        $this->purchased = $this->purchasedFactory->create()->load(
            $this->getData('item')->getId(),
            'order_item_id'
        );

        $purchasedLinks = $this->itemsFactory->create()->addFieldToFilter(
            'order_item_id',
            $this->getChildItem()->getId()
        );
        $this->purchased->setData('purchased_items', $purchasedLinks);

        return $this->purchased;
    }
}
