<?php
namespace Network\Hideblocks\Plugin;

class AroundBlockHtmlRender
{

    protected $_customerSession;
    protected $_objectManager;
    protected $_block;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->_objectManager = $objectmanager;
    }

    public function aroundToHtml(\Magento\Framework\View\Element\AbstractBlock $subject, \Closure $proceed)
    {
        if(!$this->_objectManager->create('\Magento\Customer\Model\Session')->isLoggedIn()){
            $this->_block = $subject;

            $html = $this->filterBlockByModuleName($this->_block->getModuleName());
            if($html !== false){
                return $html;
            }

            $html = $this->filterBlockByName($this->_block->getNameInLayout());
            if($html !== false){
                return $html;
            }

            $html = $this->filterByCustomAttribute($this->_block);
            if($html !== false){
                return $html;
            }
        }

       // echo '<!-- block: '.$this->_block->getModuleName().' -> '.$this->_block->getNameInLayout().' -->';

        $returnValue = $proceed();
        return $returnValue;
    }

    protected function filterBlockByModuleName($moduleName)
    {
        $array = array(
            'Magento_Checkout' => '',
            'Magento_Paypal' => '',
            'Magento_Review' => '',
            'Magento_Wishlist' => ''
        );

        if(isset($array[$moduleName]))
            return $array[$moduleName];

        return false;
    }

    protected function filterBlockByName($nameInLayout)
    {
        $array = array(
            'product.info.addtocart' => '',
            'addtocart' => '',
            'product.info.simple' => '',
            'product.info.review' => '',
            'product.info.addto' => '',
            'product.info.form.options' => '',
        );
        if(isset($array[$nameInLayout]))
            return $array[$nameInLayout];


//        if (strpos($nameInLayout, 'magento\catalog\pricing\render') !== false) {
//            return '';
//        }

        return false;
    }

    protected function filterByCustomAttribute($block)
    {
        if($block->getHiddenForNotLogged()){
            return $block->getCustomHtml() ? $block->getCustomHtml() : '';
        }
        return false;
    }




}
