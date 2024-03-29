<?php
/**
 * Copyright 2019 aheadWorks. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aheadworks\StoreCredit\Test\Unit\Block\Sales\Order;

use Aheadworks\StoreCredit\Block\Sales\Order\Total;
use Magento\Framework\DataObject\Factory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class Aheadworks\StoreCredit\Test\Unit\Block\Sales\Order\TotalTest
 */
class TotalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractBlock
     */
    private $abstractBlockMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LayoutInterface
     */
    private $layoutMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Factory
     */
    private $factoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrderInterface
     */
    private $orderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Invoice
     */
    private $invoiceMock;

    /**
     * @var Total
     */
    private $object;

    /**
     * @var string
     */
    private $nameInLayout = 'aw_store_credit.sales.order.total';

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->abstractBlockMock = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSource', 'getOrder', 'addTotal'])
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAwUseStoreCredit',
                ]
            )
            ->getMockForAbstractClass();

        $this->invoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getAwStoreCreditAmount',
                    'getAwStoreCreditRefunded',
                    'getAwStoreCreditReimbursed'
                ]
            )
            ->getMock();

        $this->prepareContext();

        $this->factoryMock = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $data = [
            'context' => $this->contextMock,
            'factory' => $this->factoryMock,
        ];

        $this->object = $objectManager->getObject(Total::class, $data);
    }

    /**
     * Prepare context mock
     */
    private function prepareContext()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getLayout'
                ]
            )
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(LayoutInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getParentName',
                    'getBlock',
                ]
            )
            ->getMockForAbstractClass();

        $this->contextMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
    }

    /**
     * Test getOrder method for null parent block
     */
    public function testGetOrderMethodFalseParentBlock()
    {
        $this->expectsParentBlockFalse();

        $this->assertNull($this->object->getOrder());
    }

    /**
     * Test getOrder method
     */
    public function testGetOrderMethod()
    {
        $this->expectsParentBlock('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->assertSame($this->orderMock, $this->object->getOrder());
    }

    /**
     * Test getSource method for null parent block
     */
    public function testGetSourceMethodFalseParentBlock()
    {
        $this->expectsParentBlockFalse();

        $this->assertNull($this->object->getSource());
    }

    /**
     * Test getSource method
     */
    public function testGetSourceMethod()
    {
        $this->expectsParentBlock('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getSource')
            ->willReturn($this->invoiceMock);

        $this->assertSame($this->invoiceMock, $this->object->getSource());
    }

    /**
     * Test initTotals for null parent block
     */
    public function testInitTotalMethodFalseParentBlock()
    {
        $this->expectsParentBlockFalse();
        $this->object->initTotals();
    }

    /**
     * Test initTotals for null getOrder
     */
    public function testInitTotalMethodNullGetOrder()
    {
        $this->expectsParentBlock('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getOrder')
            ->willReturn(null);

        $this->object->initTotals();
    }

    /**
     * Test initTotals for null $order->getAwUseStoreCredit()
     */
    public function testInitTotalMethodNullGetAwUseStoreCredit()
    {
        $this->expectsParentBlockTwice('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getSource')
            ->willReturn($this->invoiceMock);

        $this->abstractBlockMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('getAwUseStoreCredit')
            ->willReturn(null);

        $this->object->initTotals();
    }

    /**
     * Test initTotals for null getSource
     */
    public function testInitTotalMethodNullGetSource()
    {
        $this->expectsParentBlockTwice('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->abstractBlockMock->expects($this->once())
            ->method('getSource')
            ->willReturn(null);

        $this->object->initTotals();
    }

    /**
     * Test initTotals method
     */
    public function testInitTotalMethod()
    {
        $label = 'Store Credit';
        $value = 16;

        $this->expectsParentBlockThreeTimes('order_totals');

        $this->abstractBlockMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->expects($this->once())
            ->method('getAwUseStoreCredit')
            ->willReturn(true);

        $this->abstractBlockMock->expects($this->once())
            ->method('getSource')
            ->willReturn($this->invoiceMock);

        $this->invoiceMock->expects($this->once())
            ->method('getAwStoreCreditAmount')
            ->willReturn($value);

        $dataObjectMock = $this->createMock(DataObject::class);

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with(
                [
                    'code' => 'aw_store_credit',
                    'strong' => false,
                    'label' => $label,
                    'value' =>  $value,
                ]
            )
            ->willReturn($dataObjectMock);

        $this->abstractBlockMock->expects($this->once())
            ->method('addTotal')
            ->with($dataObjectMock)
            ->willReturnSelf();

        $this->object->initTotals();
    }

    /**
     * Expects getParentBlock method
     *
     * @param string $parentName
     * @return void
     */
    private function expectsParentBlock($parentName)
    {
        $this->object->setNameInLayout($this->nameInLayout);

        $this->layoutMock->expects($this->once())
            ->method('getParentName')
            ->with($this->nameInLayout)
            ->willReturn($parentName);
        $this->layoutMock->expects($this->once())
            ->method('getBlock')
            ->with($parentName)
            ->willReturn($this->abstractBlockMock);
    }

    /**
     * Expects getParentBlock method exactly 2
     *
     * @param string $parentName
     * @return void
     */
    private function expectsParentBlockTwice($parentName)
    {
        $this->object->setNameInLayout($this->nameInLayout);

        $this->layoutMock->expects($this->exactly(2))
            ->method('getParentName')
            ->with($this->nameInLayout)
            ->willReturn($parentName);
        $this->layoutMock->expects($this->exactly(2))
            ->method('getBlock')
            ->with($parentName)
            ->willReturn($this->abstractBlockMock);
    }

    /**
     * Expects getParentBlock method exactly 3
     *
     * @param string $parentName
     * @return void
     */
    private function expectsParentBlockThreeTimes($parentName)
    {
        $this->object->setNameInLayout($this->nameInLayout);

        $this->layoutMock->expects($this->exactly(3))
            ->method('getParentName')
            ->with($this->nameInLayout)
            ->willReturn($parentName);
        $this->layoutMock->expects($this->exactly(3))
            ->method('getBlock')
            ->with($parentName)
            ->willReturn($this->abstractBlockMock);
    }

    /**
     * Expects getParentBlock method, return null parentName
     *
     * @return void
     */
    private function expectsParentBlockFalse()
    {
        $this->object->setNameInLayout($this->nameInLayout);

        $this->layoutMock->expects($this->once())
            ->method('getParentName')
            ->with($this->nameInLayout)
            ->willReturn(null);
    }
}
