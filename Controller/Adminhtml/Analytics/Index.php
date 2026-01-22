<?php

declare(strict_types=1);

namespace Dart\ProductkeysAnalytics\Controller\Adminhtml\Analytics;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dart_ProductkeysAnalytics::analytics';

    private PageFactory $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dart_ProductkeysAnalytics::analytics');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Keys Analytics'));

        return $resultPage;
    }
}
