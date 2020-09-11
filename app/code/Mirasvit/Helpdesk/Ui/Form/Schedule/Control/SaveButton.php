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


namespace Mirasvit\Helpdesk\Ui\Form\Schedule\Control;

use Mirasvit\Helpdesk\Ui\Form\Button;

class SaveButton extends Button
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $data = parent::getButtonData();
        $data['data_attribute'] = [
            'mage-init' => ['button' => ['event' => 'save']],
            'form-role' => 'save',
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonUrl()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Save');
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return 'save';
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 90;
    }
}
