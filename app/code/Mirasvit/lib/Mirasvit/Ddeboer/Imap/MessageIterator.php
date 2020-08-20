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


// @codingStandardsIgnoreFile
// namespace Mirasvit_Ddeboer\Imap;

class Mirasvit_Ddeboer_Imap_MessageIterator extends ArrayIterator
{
    /**
     * @var resource
     */
    protected $stream;

    /**
     * Constructor.
     *
     * @param \resource $stream         IMAP stream
     * @param array     $messageNumbers Array of message numbers
     */
    public function __construct($stream, array $messageNumbers)
    {
        $this->stream = $stream;

        parent::__construct($messageNumbers);
    }

    /**
     * Get current message.
     *
     * @return Mirasvit_Ddeboer_Imap_Message
     */
    public function current()
    {
        return new Mirasvit_Ddeboer_Imap_Message($this->stream, parent::current());
    }
}
