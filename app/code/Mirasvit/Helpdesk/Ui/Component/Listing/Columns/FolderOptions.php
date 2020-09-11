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

use Mirasvit\Helpdesk\Model\Config as Config;

class FolderOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @param string|false $emptyOption
     * @return array
     */
    public function toOptionArray($emptyOption = false)
    {
        $arr = [];
        if ($emptyOption) {
            $arr[0] = ['value' => 0, 'label' => __('-- Please Select --')];
        }
        $arr[] = ['value' => Config::FOLDER_INBOX, 'label' => __('Inbox')];
        $arr[] = ['value' => Config::FOLDER_ARCHIVE, 'label' => __('Archive')];
        $arr[] = ['value' => Config::FOLDER_SPAM, 'label' => __('Spam')];
        return $arr;
    }
}
