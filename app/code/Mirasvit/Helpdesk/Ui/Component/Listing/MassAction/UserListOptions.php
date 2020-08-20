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



namespace Mirasvit\Helpdesk\Ui\Component\Listing\MassAction;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\UrlInterface;
use Zend\Stdlib\JsonSerializable;
use Mirasvit\Helpdesk\Helper\Html;

/**
 * Class UserListOptions
 */
class UserListOptions implements JsonSerializable, OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Additional options params
     *
     * @var array
     */
    protected $data;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Base URL for subactions
     *
     * @var string
     */
    protected $urlPath;

    /**
     * Param name for subactions
     *
     * @var string
     */
    protected $paramName;

    /**
     * Additional params for subactions
     *
     * @var array
     */
    protected $additionalData = [];
    /**
     * @var Html
     */
    private $htmlHelper;

    /**
     * Constructor
     *
     * @param Html $htmlHelper
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Html $htmlHelper,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->htmlHelper = $htmlHelper;
        $this->data = $data;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return $this->jsonSerialize();
    }

    /**
     * Get action options
     *
     * @return array
     */
    public function jsonSerialize()
    {
        if ($this->options === null) {
            $this->options = [];

            $options = $this->htmlHelper->getAdminOwnerOptionArray();
            $this->prepareData();
            foreach ($options as $id => $name) {
                $this->options[$id] = [
                    'type'  => $id,
                    'label' => $name,
                ];

                if ($this->urlPath && $this->paramName) {
                    $this->options[$id]['url'] = $this->urlBuilder->getUrl(
                        $this->urlPath,
                        [$this->paramName => $id]
                    );
                }

                $this->options[$id] = array_merge_recursive(
                    $this->options[$id],
                    $this->additionalData
                );
            }

            $this->options = array_values($this->options);
        }

        return $this->options;
    }

    /**
     * Prepare addition data for subactions
     *
     * @return void
     */
    protected function prepareData()
    {
        foreach ($this->data as $key => $value) {
            switch ($key) {
                case 'urlPath':
                    $this->urlPath = $value;
                    break;
                case 'paramName':
                    $this->paramName = $value;
                    break;
                default:
                    $this->additionalData[$key] = $value;
                    break;
            }
        }
    }
}
