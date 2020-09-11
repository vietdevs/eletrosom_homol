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



namespace Mirasvit\Helpdesk\Block\MspRecaptcha\Frontend\ReCaptcha;

use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Element\Template;

class Recaptcha extends Template
{

    /**
     * @var array
     */
    private $data;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var EncoderInterface
     */
    private $encoder;
    /**
     * @var Template\Context
     */
    private $context;

    /**
     * @param Template\Context $context
     * @param DecoderInterface $decoder
     * @param EncoderInterface $encoder
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        DecoderInterface $decoder,
        EncoderInterface $encoder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->context = $context;
        $this->data = $data;
        $this->decoder = $decoder;
        $this->encoder = $encoder;
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        if (!class_exists('MSP\ReCaptcha\Model\LayoutSettings', false)) {
            return '';
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \MSP\ReCaptcha\Model\LayoutSettings $layoutSettings */
        $layoutSettings = $objectManager->get('MSP\ReCaptcha\Model\LayoutSettings');
        /** @var \MSP\ReCaptcha\Block\Frontend\ReCaptcha $captchaBlock */
        $captchaBlock = $this->getLayout()->createBlock('MSP\ReCaptcha\Block\Frontend\ReCaptcha',
            $this->getNameInLayout() . '-origin',
            [
                'context' => $this->context,
                'decoder' => $this->decoder,
                'encoder' => $this->encoder,
                'layoutSettings' => $layoutSettings,
                'data' => [
                    'jsLayout' => $this->jsLayout
                ],
            ]);
        if ($captchaBlock) {
            $captchaBlock->setTemplate($this->getTemplate());

            return $captchaBlock->toHtml();
        } else {
            return '';
        }
    }
}
