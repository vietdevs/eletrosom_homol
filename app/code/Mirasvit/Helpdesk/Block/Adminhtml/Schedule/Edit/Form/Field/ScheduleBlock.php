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


namespace Mirasvit\Helpdesk\Block\Adminhtml\Schedule\Edit\Form\Field;

use Magento\Framework\Escaper;
use Magento\Framework\Data\Form\Element;

class ScheduleBlock extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;

    /**
     * ScheduleBlock constructor.
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Backend\Block\Template\Context $context,
        $data = []
    ) {
        $this->formFactory = $formFactory;

        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Mirasvit\Helpdesk\Model\Schedule $schedule */
        $schedule = $objectManager->get('Magento\Framework\Registry')->registry('current_schedule');
        $fieldset = $this->formFactory->create()->addFieldset('edit_fieldset', ['legend' => __('General Information')]);
        $element = $fieldset->addField(
            'working_time',
            'Mirasvit\Helpdesk\Block\Adminhtml\Schedule\Edit\Form\Field\Schedule',
            [
                'label' => __('Working days/hours'),
                'name'  => 'working_time',
                'value' => $schedule->getWorkingHours(),
            ],
            'working_hours'
        );

        return $element->getHtml();
    }
}
