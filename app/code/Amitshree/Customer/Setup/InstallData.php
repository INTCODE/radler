<?php

namespace Amitshree\Customer\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{

    /**
     * attribute to identify customer account is approved or not
     */
    const APPROVE_ACCOUNT = 'approve_account';
    const CUSTOMER_DOCUMENTS = 'customer_documents';
    const CHECKED = 'checked';

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         *  Customer attributes
         */
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        /**
         * Create customer attribute account_approve
         */
        $customerSetup->addAttribute(Customer::ENTITY, self::APPROVE_ACCOUNT,
            [
                'type' => 'int',
                'label' => 'Approve Account',
                'input' => 'select',
                'source' => 'Amitshree\Customer\Model\Config\Source\CustomerYesNoOptions',
                'required' => false,
                'default' => '0',
                'visible' => true,
                'user_defined' => true,
                'sort_order' => 215,
                'position' => 215,
                'system' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
            ]);
        $approve_account = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, self::APPROVE_ACCOUNT)
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            ]);
        $approve_account->save();

        /**
         * Create customer attribute customer_documents
         */
        $customerSetup->addAttribute(Customer::ENTITY, self::CUSTOMER_DOCUMENTS,
            [
                'type' => 'text',
                'label' => 'Documents',
                'input' => 'file',
                'source' => '',
                'required' => false,
                'default' => '0',
                'visible' => true,
                'user_defined' => true,
                'sort_order' => 216,
                'position' => 216,
                'system' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false,
            ]);
        $customer_documents = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, self::CUSTOMER_DOCUMENTS)
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['customer_account_create', 'customer_account_edit'],
            ]);
        $customer_documents->save();

        $customerSetup->addAttribute(Customer::ENTITY, 'CheckedDate',
            [
                'type' => 'date',
                'label' => 'Aproved to',
                'input' => 'datetime',
                'source' => '',
                'required' => false,
                'default' => '0',
                'visible' => true,
                'user_defined' => false,
                'sort_order' => 217,
                'position' => 217,
                'system' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
            ]);
        $CheckedDate = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'CheckedDate')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
            ]);
        $CheckedDate->save();

        $setup->endSetup();
    }

}
