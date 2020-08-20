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


namespace Mirasvit\Helpdesk\Ui\Form\Schedule;

class TimezoneOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Framework\Locale\ListsInterface
     */
    private $localeLists;

    /**
     * TimezoneOptionsProvider constructor.
     * @param \Magento\Framework\Locale\ListsInterface $localeLists
     */
    public function __construct(\Magento\Framework\Locale\ListsInterface $localeLists)
    {
        $this->localeLists = $localeLists;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->localeLists->getOptionTimezones();
    }
}
