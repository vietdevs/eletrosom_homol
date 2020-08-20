<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.127
 * @copyright Copyright (C) 2020 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model\System\Config\Source\Core;

class Store implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @param \Magento\Store\Model\System\Store $systemStore
     */
    public function __construct(
        \Magento\Store\Model\System\Store $systemStore
    ) {
        $this->systemStore = $systemStore;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->systemStore->getStoreValuesForForm(false, false);
    }

    /************************/
}
