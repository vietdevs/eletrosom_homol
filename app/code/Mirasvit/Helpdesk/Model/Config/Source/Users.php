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



namespace Mirasvit\Helpdesk\Model\Config\Source;

class Users implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\User\Model\ResourceModel\User\CollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
     */
    public function __construct(
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
    ) {
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $options = [];
        $users = $this->userCollectionFactory->create();

        foreach ($users as $user) {
            $options[$user->getId()] = $user->getUsername();
        }

        return $options;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->toArray() as $k => $v) {
            $result[] = ['value' => $k, 'label' => $v];
        }

        return $result;
    }
}
