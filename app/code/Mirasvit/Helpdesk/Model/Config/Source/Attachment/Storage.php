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



namespace Mirasvit\Helpdesk\Model\Config\Source\Attachment;

use Mirasvit\Helpdesk\Model\Config as Config;

class Storage implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toArray()
    {
        return [
            Config::ATTACHMENT_STORAGE_FS => __('File System'),
            Config::ATTACHMENT_STORAGE_DB => __('Database'),
        ];
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

    /************************/
}
