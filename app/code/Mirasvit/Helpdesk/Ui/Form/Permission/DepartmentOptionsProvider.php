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


namespace Mirasvit\Helpdesk\Ui\Form\Permission;

class DepartmentOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory
     */
    private $collectionFactory;

    /**
     * DepartmentOptionsProvider constructor.
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $collectionFactory
     */
    public function __construct(\Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $values = $this->collectionFactory->create()->toOptionArray();
        array_unshift($values, ['value' => 0, 'label' => __('All Departments')]);

        return $values;
    }
}
