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



namespace Mirasvit\Helpdesk\Api\Repository\Ticket;


interface FolderRepositoryInterface
{
    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function save($ticket);

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function markAsInbox($ticket);

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function markAsArchive($ticket);

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function markAsSpam($ticket);

    /**
     * @param \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return \Mirasvit\Helpdesk\Api\Data\TicketInterface|\Mirasvit\Helpdesk\Model\Ticket
     */
    public function markAsNotSpam($ticket);
}