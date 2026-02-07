<?php
/**
 * Dart Productkeys Email Template List
 *
 * @package        Dart_Productkeys
 *
 */
namespace Dart\Productkeys\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Option\ArrayInterface;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\Template\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Templateoptions extends AbstractSource implements ArrayInterface
{
    private $collectionFactory;

    private $emailConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    private $options;

    public function __construct(
        CollectionFactory $collectionFactory,
        Config $emailConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->emailConfig = $emailConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function getCustomTemplates()
    {
        return $this->collectionFactory->create();
    }

    public function getDefaultTemplates()
    {
        // Fetch default Magento templates
        $defaultTemplates = $this->emailConfig->getAvailableTemplates();
        $templates = [];
        foreach ($defaultTemplates as $templateId => $templateData) {
            $templates[$templateId] = $templateData['label'];
        }
        return $templates;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $groups = [];
        foreach ($this->getCustomTemplates() as $template) {
            $groups[$template->getTemplateId()] = $template->getTemplateCode();
        }
        return $groups;
    }

    /**
     * Options getter
     * @return array
     */
    public function toOptionArray()
    {
        $defaultTemplates = $this->getDefaultTemplates();
        $customTemplates = $this->toArray();

        $default = 'productkeys_delivery';

        if ($this->getAttribute() && $this->getAttribute()->getDefaultValue()) {
            $default = $this->getAttribute()->getDefaultValue();
        }

        $defaultExistsInCustom = isset($customTemplates[$default]);

        $label = ($default === 'productkeys_delivery') ? 'Productkey Delivery' : 'Productkey Warning';

        $result = [];
        $result[] = [
            'value' => $default,
            'label' => 'Default ('.$label.')',
            'selected' => true
        ];

        foreach ($customTemplates as $key => $value) {
            $result[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        foreach ($defaultTemplates as $key => $value) {
            if (!isset($customTemplates[$key])) {
                $result[] = [
                    'value' => $key,
                    'label' => $value
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
}
