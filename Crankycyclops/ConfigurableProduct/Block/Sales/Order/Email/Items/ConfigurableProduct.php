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
use Magento\Downloadable\Model\Link\Purchased\Item;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Downloadable Sales Order Email items renderer
 *
 * @api
 * @since 100.0.2
 */
class ConfigurableProduct extends \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder {

	/**
	 * @var \Magento\Downloadable\Model\Link\Purchased
	 */
	protected $_purchased;

	/**
	 * @var \Magento\Downloadable\Model\Link\PurchasedFactory
	 */
	protected $_purchasedFactory;

	/**
	 * @var \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory
	 */
	protected $_itemsFactory;

	/**
	 * @var \Magento\Framework\UrlInterface
	 */
	private $frontendUrlBuilder;

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Downloadable\Model\Link\PurchasedFactory $purchasedFactory
	 * @param \Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Downloadable\Model\Link\PurchasedFactory $purchasedFactory,
		\Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory $itemsFactory,
		array $data = []
	) {
		$this->_purchasedFactory = $purchasedFactory;
		$this->_itemsFactory = $itemsFactory;
		parent::__construct($context, $data);
	}

	/**
	 * Returns the item's child item, which represents the simple product that
	 * was actually ordered.
	 * Written by James Colannino.
	 *
	 * @return \Magento\Sales\Model\Order
	 */
	public function getChildItem() {

		$childrenItems = $this->getItem()->getChildrenItems();
		return $childrenItems[0];
	}

	public function isSimpleProductDownloadable() {

		return 'downloadable' == $this->getChildItem()->getProduct()->getTypeId() ? true : false;
	}

	/**
	 * Enter description here... (Magento's comment, not mine...)
	 * Modified by James Colannino so that this returns the links associated with
	 * the configurable option's corresponding simple product (should only be
	 * called if the simple product is downloadable.)
	 *
	 * @return \Magento\Downloadable\Model\Link\Purchased
	 */
	public function getLinks() {

		$this->_purchased = $this->_purchasedFactory->create()->load(
			$this->getItem()->getId(),
			'order_item_id'
		);

		$purchasedLinks = $this->_itemsFactory->create()->addFieldToFilter('order_item_id', $this->getChildItem()->getId());
		$this->_purchased->setPurchasedItems($purchasedLinks);

		return $this->_purchased;
	}

	/**
	 * @return null|string
	 */
	public function getLinksTitle() {

		return $this->getLinks()->getLinkSectionTitle() ?: $this->_scopeConfig->getValue(
			Link::XML_PATH_LINKS_TITLE,
			ScopeInterface::SCOPE_STORE
		);
	}

	/**
	 * @param Item $item
	 * @return string
	 */
	public function getPurchasedLinkUrl($item) {

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
	 * @return \Magento\Framework\UrlInterface
	 * @deprecated 100.1.0
	 */
	private function getFrontendUrlBuilder() {

		if (!$this->frontendUrlBuilder) {
			$this->frontendUrlBuilder = ObjectManager::getInstance()->get(\Magento\Framework\Url::class);
		}

		return $this->frontendUrlBuilder;
	}
}

