<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Similiar to the block defined in
 * Magento\Downloadable\Block\Sales\Order\Email\Items\Order\Downloadable
 * except that this will also include links to downloadable options from
 * configurable products.
 *
 * TODO: I should be extending the Downloadable block from Magento_Downloadable
 * and overriding the functions that are different instead of copying
 * the class and modifying the copy. Forgive me. I was tired when I
 * wrote this :) Will fix in an upcoming commit.
 *
 * Modifications Copyright © James Colannino.
 */

// @codingStandardsIgnoreFile

namespace Crankycyclops\ConfigurableProduct\Block\Sales\Order\Email\Items;

use Magento\Downloadable\Model\Link;
use Magento\Downloadable\Model\Link\Purchased;
use Magento\Downloadable\Model\Link\Purchased\Item;
use Magento\Downloadable\Model\Link\PurchasedFactory;
use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;
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
class ConfigurableProduct extends DefaultOrder
{
    protected Purchased $_purchased;

    protected PurchasedFactory $_purchasedFactory;

    protected CollectionFactory $_itemsFactory;

    private UrlInterface $frontendUrlBuilder;

    /**
     * @param Context           $context
     * @param PurchasedFactory  $purchasedFactory
     * @param CollectionFactory $itemsFactory
     * @param array             $data
     */
    public function __construct(
        Context $context,
        PurchasedFactory $purchasedFactory,
        CollectionFactory $itemsFactory,
        array $data = []
    ) {
        $this->_purchasedFactory = $purchasedFactory;
        $this->_itemsFactory     = $itemsFactory;
        parent::__construct($context, $data);
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
        $childrenItems = $this->getItem()->getChildrenItems();

        return $childrenItems[0] ?? false;
    }

    public function isSimpleProductDownloadable(): bool
    {
        if ($this->getChildItem() && $this->getChildItem()->getProduct() && $this->getChildItem()->getProduct()->getTypeId()) {
            return 'downloadable' === $this->getChildItem()->getProduct()->getTypeId();
        }
        return false;
    }

    /**
     * Enter description here... (Magento's comment, not mine...)
     * Modified by James Colannino so that this returns the links associated with
     * the configurable option's corresponding simple product (should only be
     * called if the simple product is downloadable.)
     *
     * @return Purchased
     */
    public function getLinks()
    {
        $this->_purchased = $this->_purchasedFactory->create()->load(
            $this->getItem()->getId(),
            'order_item_id'
        );

        $purchasedLinks = $this->_itemsFactory->create()->addFieldToFilter('order_item_id',
            $this->getChildItem()->getId());
        $this->_purchased->setPurchasedItems($purchasedLinks);

        return $this->_purchased;
    }

    /**
     * @return null|string
     */
    public function getLinksTitle()
    {
        return $this->getLinks()->getLinkSectionTitle() ?: $this->_scopeConfig->getValue(
            Link::XML_PATH_LINKS_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param Item $item
     *
     * @return string
     */
    public function getPurchasedLinkUrl($item): string
    {
        $url = $this->getFrontendUrlBuilder()->getUrl(
            'downloadable/download/link',
            [
                'id' => $item->getLinkHash(),
                '_scope' => $this->getOrder()->getStore(),
                '_secure' => true,
                '_nosid' => true
            ]
        );

        return $url;
    }

    /**
     * Get frontend URL builder
     *
     * @return UrlInterface
     * @deprecated 100.1.0
     */
    private function getFrontendUrlBuilder(): UrlInterface
    {
        if (!$this->frontendUrlBuilder) {
            $this->frontendUrlBuilder = ObjectManager::getInstance()->get(Url::class);
        }

        return $this->frontendUrlBuilder;
    }
}

