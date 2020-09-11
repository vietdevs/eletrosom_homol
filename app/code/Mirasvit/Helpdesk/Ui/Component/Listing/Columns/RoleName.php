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


namespace Mirasvit\Helpdesk\Ui\Component\Listing\Columns;

use Magento\Authorization\Model\RoleFactory;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class RoleName extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var RoleFactory
     */
    private $roleFactory;

    /**
     * RoleName constructor.
     * @param RoleFactory $roleFactory
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        RoleFactory $roleFactory,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->roleFactory = $roleFactory;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $config = $this->getConfiguration();
        if (!isset($config['columnName'])) {
            return $dataSource;
        }
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($this->getData('name') == $config['columnName']) {
                    $item[$this->getData('name')] = $this->prepareItem($item[$this->getData('name')]);
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param int $id
     * @return string
     */
    protected function prepareItem($id)
    {
        if (!$id) {
            return __('All Roles');
        }
        $role = $this->roleFactory->create();
        $this->roleFactory->create()->getResource()->load($role, $id);

        return $role->getRoleName();
    }
}
