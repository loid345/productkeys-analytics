<?php

namespace Dart\Productkeys\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Dart\Productkeys\Controller\Adminhtml\Productkeys\Generatekeys;
use Dart\Productkeys\Helper\Data;
use Dart\Productkeys\Service\ApiService;

class EmailProductkeys implements ObserverInterface
{
    private $issueOnInvoice;
    private $sendEmail;
    private $templateId;
    private $no_keys;
    protected $_logger;

	private $productFactory;
    private $emailTemplates;
    private $storeManager;
    private $scopeConfig;
    private $transportBuilder;
    private $generateKeys;
    private $helperData;
    private $apiService;

    public function __construct(
        ProductFactory $productFactory,
        CollectionFactory $emailTemplates,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        Generatekeys $generateKeys,
		\Dart\Productkeys\Logger\Logger $logger,
        Data $helperData,
        ApiService $apiService
    ) {
        $this->productFactory = $productFactory;
        $this->emailTemplates = $emailTemplates;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->generateKeys = $generateKeys;
        $this->helperData = $helperData;
		$this->_logger = $logger;
        $this->apiService = $apiService;
    }

    public function execute(Observer $observer)
    {
        $productKeysArray = [];

        $globalConfig = null;
        $postToAnApi = null;
        $apiRequestBodyTemplate = null;
        $apiMethod = null;
        $apiEndpoint = null;
        $apiAuthType = null;
        $apiAuthHeader = null;
        $apiContentType = null;

        $order = $observer->getEvent()->getInvoice()->getOrder();

        $products = $this->getProductsFromOrder($order);
        $customer = $this->getCustomerFromOrder($order);

		$invoiced = $observer->getEvent()->getInvoice();

		$invoicedItems = $invoiced ->getAllItems();
        $orderIncId = $order->getIncrementId();

        foreach ($order->getAllItems() as $item)
        {
            $product = $this->productFactory->create()->load($item->getProductId());

            if ($product->getTypeId() === "configurable")
                continue;


            for ($j=0;$j<count($invoicedItems);$j++)
            {
                $__product = $this->productFactory->create()->load($invoicedItems[$j]->getProductId());

                if ($__product->getTypeId() == 'configurable')
                    continue;

				if($invoicedItems[$j]->getSku() == $item->getSku())
                {
					$overall_qty = (int)$invoicedItems[$j]->getQty();

                    $product = $this->productFactory->create()->load($item->getProductId());
                    $storeId = $item->getStoreId();

                    if ($product->getTypeId() == 'configurable')
                    {
                        $product = $this->productFactory->create()->loadByAttribute('sku', $item->getSku());
                    }

                    $globalConfig = $product->getProductkeyOverwritegnrlconfig();
                    if ($product->getProductkeyOverwritegnrlconfig())
                    {
                        $issueOnInvoice = $product->getProductkeyIssueInvoice();
                        $sendEmail = $product->getProductkeySendEmail();
                        $templateId = $product->getProductkeyEmailTemplate();
                        $no_keys = $product->getProductkeyNotAvailable();
                        $key_type = $product->getProductkeyType();
                    }
                    else
                    {
                        $issueOnInvoice = $this->helperData->getGeneralConfig('issue_invoice');
                        $sendEmail = $this->helperData->getGeneralConfig('productkeys_send_email');
                        $templateId = $this->helperData->getGeneralConfig('productkeys_email_template');
                        $no_keys = $this->helperData->getGeneralConfig('productkeys_not_available');
                        $key_type = $this->helperData->getGeneralConfig('productkeys_type');
                    }

                    if ($issueOnInvoice)
                    {
                        $productkey = [];
                        $productkeyIds = [];
                        $keypool = $product->getProductkeyPool();
                        $isProductKey = true;
                        if (empty($keypool))
                        {
                            $keypool = $item->getSku();
                        }

                        $keys_html = '<div class="prdkey_items"><span class="prdkey_product">'.$product->getName().'</span>';
                        $collection = $this->generateKeys->fetchProductKeys($orderIncId, $keypool);
                        $issuedKeysCount = 0;
                        for ($i=0; $i<$overall_qty; $i++)
                        {
                            if (!array_key_exists($i, $collection) || count($collection) < $order->getTotalQtyOrdered())
                            {
                                $productkeyvalues = $this->generateKeys->saveOrderToProductkeys($orderIncId, $keypool, $product, $storeId);

                                if ($productkeyvalues['productkey_availability'] != 'Keys Issued')
                                {
                                    if ($product->getTypeId() == 'configurable')
                                    {
                                        $product = $this->productFactory->create()->loadByAttribute('sku', $item->getSku());
                                        $keypool = $product->getProductkeyPool();
                                        if (empty($keypool))
                                        {
                                            $keypool = $product->getSku();
                                        }
                                        $productkeyvalues = $this->generateKeys->saveOrderToProductkeys($orderIncId, $keypool, $product, $storeId);
                                    }
                                    $inCollection = $productkeyvalues['productkey_availability'];
                                    if ($inCollection != "No Keys")
                                    {
                                        $isProductKey = false;
                                        break;
                                    }
                                }
                                $productkeyIds[$i] = $productkeyvalues['productkey_id'];
                                $productkey[$i] = $productkeyvalues['product_key'];
                            }
                            else
                            {
                                $productkeyIds[$i] = $collection[$i]['id'];
                                $productkey[$i] = $collection[$i]['product_key'];
                            }

                            if (empty($productkey[$i]))
                            {
                                if (empty($no_keys))
                                {
                                    $no_keys = 'Oops! No Productkey Available right now. Please call or email.';
                                }
                                $productkey[$i] = $no_keys;
                            }
                            else
                            {
                                $issuedKeysCount++;
                            }

                            if (empty($key_type))
                            {
                                $key_type = 'Productkey';
                            }

                            if ($i+1 == $overall_qty)
                            {
                                $item->setProductKeyType($key_type);
                                $item->setProductKeys(implode(",", $productkey));
                                $item->setProductKeyIds(implode(",", $productkeyIds));
                                $item->setProductKeysIssued($issuedKeysCount);
                                $item->setProductKeyPool($keypool);
                            }

                            $keys_html .= '<br /><span class="prdkey_type">'.$key_type.':</span> <span class="prdkey_code"><strong>'.$productkey[$i].'</strong></span>';

                            $productKeysArray[$item->getSku()][] = $productkey[$i];
                        }
                        $keys_html .= '</div>';

                        if ($sendEmail && $product->getTypeId() != 'configurable')
                        {
                            if (!is_numeric($templateId) || $templateId == 0)
                            {
                                $templates = $this->emailTemplates->create()->addFieldToFilter('template_code', 'Productkey Delivery');
                                foreach ($templates as $template)
                                {
                                    $templateId = $template->getTemplateId();
                                }
                            }
                            $emailVars =
                            [
                                'storeGroupName' => $order->getStoreGroupName(),
                                'name' => $order->getBillingAddress()->getName(),
                                'keytype' => $key_type,
                                'itemshtml' => $keys_html,
                                'order' => $order
                            ];

                            $templateOptions =
                            [
                                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                                'store' => $this->storeManager->getStore()->getId()
                            ];

                            $sender =
                            [
                                'name' => $this->scopeConfig->getValue('trans_email/ident_sales/name', ScopeInterface::SCOPE_STORE),
                                'email' => $this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE)
                            ];

                            try
                            {
                                if($isProductKey)
                                {
                                    $email_bcc = $this->helperData->getGeneralConfig('productkeys_copy_email');
                                    if($email_bcc)
                                    {
                                        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                                ->setTemplateOptions($templateOptions)
                                                ->setTemplateVars($emailVars)
                                                ->setFrom($sender)
                                                ->addTo($order->getCustomerEmail())
                                                ->addBcc($email_bcc)
                                                ->getTransport();

                                    }
                                    else
                                    {
                                        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                                ->setTemplateOptions($templateOptions)
                                                ->setTemplateVars($emailVars)
                                                ->setFrom($sender)
                                                ->addTo($order->getCustomerEmail())
                                                ->getTransport();
                                    }
                                    $transport->sendMessage();
                                }
                            }
                            catch (\Exception $e)
                            {
                                return $e->getMessage();
                            }
                        }
                    }
                }
			}
		}

        if($globalConfig === '1')
        {
            $postToAnApi = $product->getProductkeyPostToAnApi();
            if($postToAnApi === '1')
            {
                try
                {
                    $apiRequestBodyTemplate = $product->getProductkeyApiRequestBody();
                    $apiMethod = $product->getProductkeyApiMethod();
                    $apiEndpoint = $product->getProductkeyApiEndpoint();
                    $apiAuthType = $product->getProductkeyApiAuthType();
                    $apiAuthHeader = $product->getProductkeyApiAuthHeader();
                    $apiContentType = $product->getProductkeyApiContentType();

                    if (strtoupper($apiMethod) === 'GET')
                    {
                        $apiEndpoint = $this->appendQueryParamsToUrl($apiEndpoint, $apiRequestBodyTemplate);
                        $apiRequestBodyTemplate = ''; // GET request should not have a body
                    }
                }
                catch (\Exception $e)
                {
                    $this->_logger->error('API Request Error in EmailProductkeys Class: ' . $e->getMessage());
                }
            }
        }
        else
        {
            $postToAnApi = $this->scopeConfig->getValue('productkeys/general/post_to_api');
            if($postToAnApi === '1')
            {
                try
                {
                    $apiRequestBodyTemplate = $this->scopeConfig->getValue('productkeys/general/api_request_body');
                    $apiMethod = $this->scopeConfig->getValue('productkeys/general/api_method');
                    $apiEndpoint = $this->scopeConfig->getValue('productkeys/general/api_endpoint');
                    $apiAuthType = $this->scopeConfig->getValue('productkeys/general/api_auth_type');
                    $apiAuthHeader = $this->scopeConfig->getValue('productkeys/general/api_auth_header');
                    $apiContentType = $this->scopeConfig->getValue('productkeys/general/api_content_type');

                    if (strtoupper($apiMethod) === 'GET')
                    {
                        $apiEndpoint = $this->appendQueryParamsToUrl($apiEndpoint, $apiRequestBodyTemplate);
                        $apiRequestBodyTemplate = ''; // GET request should not have a body
                    }
                }
                catch (\Exception $e)
                {
                    $this->_logger->error('API Request Error in EmailProductkeys Class: ' . $e->getMessage());
                }
            }
        }

        $apiRequestBody = $this->constructApiRequestBody($products, $customer, $productKeysArray, $apiRequestBodyTemplate);

        try
        {
            $this->apiService->sendRequest
            (
                $apiEndpoint,
                $apiMethod,
                $apiAuthType,
                $apiAuthHeader,
                $apiRequestBody,
                $apiContentType
            );
        }
        catch (\Exception $e)
        {
            $this->_logger->error('final failure response in email product key class: ' . $e->getMessage());
        }
    }

    protected function getProductsFromOrder($order)
    {
        try
        {
            return $order->getAllVisibleItems();
        }
        catch (\Exception $e)
        {
            $this->_logger->error('Get Products Details Error: ' . $e->getMessage());
        }
    }

    protected function getCustomerFromOrder($order)
    {
        try
        {
            $name = $order->getBillingAddress()->getName();
            $email = $order->getCustomerEmail();

            return ['name' => $name, 'email' => $email];
        }
        catch (\Exception $e)
        {
            $this->_logger->error('Get Customer Details Error: ' . $e->getMessage());
        }
    }

    protected function appendQueryParamsToUrl($url, $queryParams)
    {
        try
        {
            $urlComponents = parse_url($url);
            parse_str($urlComponents['query'] ?? '', $existingParams);
            parse_str($queryParams, $newParams);
            $queryParams = http_build_query(array_merge($existingParams, $newParams));
            return $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'] . '?' . $queryParams;
        }
        catch (\Exception $e)
        {
            $this->_logger->error('Get Request Details Error: ' . $e->getMessage());
        }
    }

    protected function constructApiRequestBody($products, $customer, $assignedProductKeys, $apiRequestBodyTemplate)
    {
        $apiRequestBody =[];

        try
        {

                if (count($assignedProductKeys)>0)
                {
                    foreach ($assignedProductKeys as $key => $value)
                    {
                        $product = $this->productFactory->create()->loadByAttribute('sku', $key);
                        foreach ($value as $productKey) {
                            $replacements =
                            [
                                '{{product_name}}' => $product->getName(),
                                '{{sku}}' => $key,
                                '{{user_name}}' => $customer['name'],
                                '{{user_email}}' => $customer['email'],
                                '{{product_key}}' => rtrim($productKey)
                            ];

                            $body = strtr($apiRequestBodyTemplate, $replacements);

                            $decodedBody = json_decode($body);
                            $apiRequestBody[] = (object) $decodedBody;
                        }
                    }
                }
            $apiRequestBody = json_encode($apiRequestBody);
            return $apiRequestBody;
        }
        catch (\Exception $e)
        {
            $this->_logger->error('API Request Body Error: ' . $e->getMessage());
            return '';
        }
    }
}