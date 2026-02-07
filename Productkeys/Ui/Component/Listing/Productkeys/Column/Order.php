<?php
namespace Dart\Productkeys\Ui\Component\Listing\Productkeys\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Order extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $orderId = $item['orderinc_id'];
                $item[$this->getData('name')] = sprintf(
                    '<a href="%s">%s</a>',
                    $this->getContext()->getUrl('sales/order/view', ['order_id' => $orderId]),
                    $orderId
                );
            }
        }

        return $dataSource;
    }
}
