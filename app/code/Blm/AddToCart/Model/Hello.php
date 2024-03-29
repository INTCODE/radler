<?php
namespace Blm\AddToCart\Model;
use Blm\AddToCart\Api\HelloInterface;

class Hello implements HelloInterface
{
    /**
     * Returns greeting message to user
     *
     * @api

     * @return string Greeting message with users name.
     */
    public function name() {
        return "Hello, ";
    }


                      /**
     * Sum an array of numbers.
     *
     * @api
     * @param int $productId The array of numbers to sum.
     * @param int $addressId The array of numbers to sum.
     * @param int $type The array of numbers to sum.
     * @param int $quoteId The array of numbers to sum.
     * @param int $qty The array of numbers to sum.
     * @param string $key The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function addCrossSell($productId,$addressId,$type,$quoteId,$qty,$key){


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();





        try {

            $sql="INSERT INTO blm_crontab
            (quoteId, productId, `type`, qty, address)
            VALUES ('$quoteId', '$productId', '$type', '$qty', '$addressId')";
            $connection->query($sql);

            $productAdd=$objectManager->get('Magento\Catalog\Model\Product')->load($productId);

            $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
    
            $prams=array('uenc'=>'uenc','product'=>$productId,'addressId'=>$addressId,'form_key'=>$key,'qty'=>$qty,'super_attribute'=>array('152'=>$type));
    
            $cart->addProduct($productAdd, $prams);
            $cart->save();
            return 'added';


        } catch (\Throwable $th) {
            return 'error';
        }
    




     }

                     /**
     * Sum an array of numbers.
     *
     * @api
     * @param int $productId The array of numbers to sum.
     * @param int $addressId The array of numbers to sum.
     * @param int $type The array of numbers to sum.
     * @param int $quoteId The array of numbers to sum.
     * @param int $qty The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function add($productId,$addressId,$type,$quoteId,$qty){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);

        $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
        $q = $quoteFactory->create()->load($quoteId);
        foreach ($q->getAllItems() as $key => $value) {
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
    
            if($type!=0){
            if($value->getParentItemId()){
                $product = $objectManager->get('Magento\Catalog\Model\Product')->load($value->getProductId());
                $packageId=$product->getCustomAttribute('package_type')->getValue();
                if($packageId==$type){
                    if($qty<=0){

                        $quote = $quoteFactory->create()->load($quoteId);
                        $quote->removeItem($value->getParentItemId());
                        $quote->save();

                    }else{

                        $quote = $quoteFactory->create()->load($quoteId);
                        $state=$quote->hasProductId($productId);

                        if($state==1){
                            $itemChenge=$q->getItemById($value->getParentItemId());
                            $itemChenge->setQty($qty);
                            $itemChenge->save();
                        }else{
                            $prams=array('uenc'=>'uenc','product'=>$productId,'addressId'=>$addressId,'form_key'=>'IfRhR9Hl6mxQY1Mo','qty'=>$qty,'super_attribute'=>array('152'=>$type));

                        $productAdd=$objectManager->get('Magento\Catalog\Model\Product')->load($productId);
                        $cart->addProduct($productAdd, $prams);
                        $cart->save();
                        }
      
                    }

               }

            }
        }elseif($type==0){
            if(!$value->getParentItemId() && $value->getProductType()=='simple'){
                if($value->getProductId()==$productId){
                    $quote = $quoteFactory->create()->load($quoteId);
                  
                    $state=$quote->hasProductId($productId);
                    if($qty<=0){
                        $quote->removeItem($value->getId());
                        $quote->save();
                    }else{

                        if($state==1){
                            $itemChenge=$q->getItemById($value->getId());
                            $itemChenge->setQty($qty);
                            $itemChenge->save();
                       }else{
                        $prams=array('uenc'=>'uenc','product'=>$productId,'addressId'=>$addressId,'form_key'=>'IfRhR9Hl6mxQY1Mo','qty'=>$qty);

                            $productAdd=$objectManager->get('Magento\Catalog\Model\Product')->load($productId);
                            $cart->addProduct($productAdd, $prams);
                            $cart->save();
                       }


                    }
             

                }else{
                    $prams=array('uenc'=>'uenc','product'=>$productId,'addressId'=>$addressId,'form_key'=>'IfRhR9Hl6mxQY1Mo','qty'=>$qty);

                            $productAdd=$objectManager->get('Magento\Catalog\Model\Product')->load($productId);
                            $cart->addProduct($productAdd, $prams);
                            $cart->save();
                }
                // $itemChenge=$q->getItemById($value->getParentItemId());
                // $itemChenge->setQty($qty);
                // $itemChenge->save();

            }

        }

        }

      //  return $product->getId();

        $sql="SELECT b.crontab_id
        FROM blm_crontab b
        WHERE b.productId= $productId AND b.address=$addressId AND b.`type`=$type AND b.quoteId=$quoteId";

        $result = $connection->fetchAll($sql);

        if(isset($result[0])){
            if($qty<=0){
                $sql="DELETE FROM blm_crontab WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type AND address=$addressId";
            }else{

                $sql="UPDATE blm_crontab
                SET
                    qty='$qty'
                WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type AND address=$addressId ";
            }
     
        }else{
            $sql="INSERT INTO blm_crontab
            (quoteId, productId, `type`, qty, address)
            VALUES ('$quoteId', '$productId', '$type', '$qty', '$addressId')";

        }
     // $connection->query($sql);
           if($connection->query($sql)){
            $lastInsertId = $connection->lastInsertId();
               if(isset($lastInsertId) && $lastInsertId!=0){
                return $lastInsertId;
               }else{
            return 'zaktualizowano';
               }
           }else{
            return 'error';
           }


     }


                                /**
     * Sum an array of numbers.
     *
     * @api

     * @param int $quoteId The array of numbers to sum.
     * @param int $productId The array of numbers to sum.
     * @param int $type The array of numbers to sum.
     * @param int $addressId The array of numbers to sum.
     * @param int $qty The array of numbers to sum.
     * @param string $flag The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function editCart($quoteId,$productId,$type,$addressId,$qty,$flag){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);

        $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
        $quote = $quoteFactory->create()->load($quoteId);



  

        switch ($flag) 
            {



            case 'address':

        $sql="SELECT *
        FROM blm_crontab b
        WHERE b.productId= $productId AND b.quoteId=$quoteId AND b.`type`=$type";

        $result = $connection->fetchAll($sql);

        if(!$result){
            return 'not found';
        }

            $sql="UPDATE blm_crontab
            SET
                address='$addressId'
            WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type ";


            if($connection->query($sql)){
                return 'updated product address';
            }else{
                return 'error';
            }

            break;




            case 'qty':
         
            $sql="SELECT *
            FROM blm_crontab b
            WHERE b.productId= $productId AND b.address=$addressId AND b.`type`=$type AND b.quoteId=$quoteId";

            $result = $connection->fetchAll($sql);

            if(!$result){
                return 'not found';
            }

            if($qty==0){
                $sql="DELETE FROM blm_crontab WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type AND address=$addressId ";
                if($connection->query($sql)){
                    return 'updated product qty';
                }else{
                    return 'error';
                }
            }

            $sql="UPDATE blm_crontab
            SET
                qty='$qty'
                WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type AND address=$addressId ";

                if($connection->query($sql)){
                    return 'updated product qty';
                }else{
                    return 'error';
                }
            

            break;






            case 'type':
            
            if($type==21){
                $currType=22;
            }else{
                $currType=21;
            }
            $sql="SELECT *
            FROM blm_crontab b
            WHERE b.productId= $productId AND b.address=$addressId AND b.`type`=$currType AND b.quoteId=$quoteId";

            


            $result = $connection->fetchAll($sql);

            $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
            $quote = $quoteFactory->create()->load($quoteId);
             $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
            
            if(!$result){
                return 'not found';
            }
            $sql="SELECT *
            FROM blm_crontab b
            WHERE b.productId= $productId AND b.address=$addressId AND b.`type`=$type AND b.quoteId=$quoteId";

            $result = $connection->fetchAll($sql);

            if(!$result){
                $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
                if($type==0){

                }else{
                    $_children = $product->getTypeInstance()->getUsedProducts($product);
                    foreach ($_children as $key => $value) {
                        $packageId=$value->getCustomAttribute('package_type')->getValue();
                     if($packageId==$type){
                         $prod = $objectManager->create('Magento\Catalog\Model\Product')->load($value->getId());
                           
                         $value->save();
                     }
                        # code...
                    }
                }
                // $paramsParent = array(
                //     'product' => $product->getId(), //product Id
                //     'qty'   =>1 //quantity of product                        
                // );

                // $cart->addProduct($product, $paramsParent);

                $option = array('152'=>$type);
                $formKey = $objectManager->create('\Magento\Framework\Data\Form\FormKey')->getFormKey(); 
                $params = array(
                    'form_key' => $formKey,
                    'product' => $prod->getId(), //product Id
                    'qty'   =>$qty, //quantity of product            
                    'super_attribute'   =>$option //quantity of product            
                ); 
                
                $cart->addProduct($prod, $params);
                $cart->save();


                $sql="UPDATE blm_crontab
                SET
                    `type`='$type'
                    WHERE quoteId=$quoteId AND productId=$productId AND `type`=$currType AND address=$addressId";    

                    //  $items = $cart->getQuote()->getAllItems();

                
            }else{
               // $qtyCurr=$result[0]['qty'];
                $finalQty=$result[0]['qty']+$qty;
                $del="DELETE FROM blm_crontab WHERE productId= $productId AND address=$addressId AND `type`=$currType AND quoteId=$quoteId";  
                $connection->query($del);

                $sql="UPDATE blm_crontab
                SET
                    qty='$finalQty'
                    WHERE productId= $productId AND address=$addressId AND`type`=$type AND quoteId=$quoteId";
            }


            if($connection->query($sql)){
                return 'updated product type';
            }else{
                return 'error';
            }

            // $sql="UPDATE blm_crontab
            // SET
            //     `type`='$type'
            //     WHERE quoteId=$quoteId AND productId=$productId AND `type`=$type AND address=$addressId";


            //     if($connection->query($sql)){
            //         return 'updated product qty';
            //     }else{
            //         return 'error';
            //     }
   
            break;

            default:
            return 'error';
            break;
            }

        return 'error';

     }



            /**
     * Sum an array of numbers.
     *
     * @api
     * @param int $productId The array of numbers to sum.
     * @param int $addressId The array of numbers to sum.
     * @param int $type The array of numbers to sum.
     * @param int $quoteId The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function get($productId, $addressId, $type, $quoteId){


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');

        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        $StockStateSel = $objectManager->get('\Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');

        $configProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);

        if($type==0){
            $sku=$configProduct->getSku();
            $salable = $StockStateSel->execute($sku,1);

            $stockSQL="SELECT b.qty
            FROM blm_crontab b
            WHERE b.quoteId=$quoteId AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressId
            ";

            $res = $connection->fetchAll($stockSQL);
            $qtyRest=0;
            foreach ($res as $key => $value) {
                $qtyRest+=$value['qty'];
            }

            $stock=$salable;
            $stock-=$qtyRest;
            if($stock<0){
                $stock=0;
            }
            $sql="SELECT qty
            FROM blm_crontab b
       WHERE b.quoteId=$quoteId AND b.productId=$productId AND b.`type`=$type AND b.address=$addressId";
        }else{

            $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);

            foreach ($_children as $key => $child) {
                $packageId=$child->getCustomAttribute('package_type')->getValue();
                if($type==$packageId){

                    $sku=$child->getSku();
                    $salable = $StockStateSel->execute($sku,1);


                    $stockSQL="SELECT b.qty
                    FROM blm_crontab b
                    WHERE b.quoteId=$quoteId AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressId
                    ";
        
                    $res = $connection->fetchAll($stockSQL);
                    $qtyRest=0;
                    foreach ($res as $key => $value) {
                        $qtyRest+=$value['qty'];
                    }
                   
                    $stock=$salable;
                    $stock-=$qtyRest;
                    if($stock<0){
                        $stock=0;
                    }
                }
            }

            $sql="SELECT b.qty,b.type
            FROM blm_crontab b
            WHERE b.productId=$productId AND b.address=$addressId AND b.`type`=$type AND b.quoteId=$quoteId";
        }



        $result = $connection->fetchAll($sql);
        

        if(isset($result[0])){
            if (!array_key_exists("type",$result)){
                $typeRes=0;
            }else{
                $typeRes=$result[0]['type'];
            }
            $arr = array("qty" => $result[0]['qty'], "productId" => $productId,"stock"=>$stock,'type'=>$type);
            return json_encode($arr);
        }else{
            return json_encode(array("qty" => 0, "productId" => $productId, "stock"=>$stock,'type'=>$type));
        }

        //return $productId.",".$quoteId.",".$type;

    }


                      /**
     * Sum an array of numbers.
     *
     * @api

     * @param int $addressId The array of numbers to sum.
     * @param int $quoteId The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function getCartByAddress($addressId,$quoteId){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');



    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
    $root= $storeManager->getStore()->getBaseUrl();


        $rootPath=$root.'/pub/media/catalog/product';

        $totalCost=null;
        $addressCost=null;
        $totalQty=null;
        $addressQty=null;
        $res=array();
        $addressRes=array();

        // $sql="SELECT *
        // FROM blm_crontab b
        // Where b.quoteId=$quoteId AND b.address=$addressId";
        // $result = $connection->fetchAll($sql);

        $sqlTotal="SELECT *
        FROM blm_crontab b
        Where b.quoteId=$quoteId";
        $result = $connection->fetchAll($sqlTotal);

        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
        $StockStateSel = $objectManager->get('\Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');
        foreach ($result as $key => $value) {

            $configProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($value['productId']);
            if(!$configProduct->getId())
                return '[]';

            $type=$value['type'];

            if($type!=0){
                $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);

                foreach ($_children as $k => $v) {


                $packageId=$v->getCustomAttribute('package_type')->getValue();

                    if($type==$packageId){
                        $url=$v->getProductUrl();
                        $image=$v->getData('image');
                        $result[$key]['url']=$url;

                        $totalCost+=$v->getPrice() * $value['qty'];
                        $totalQty+= $value['qty'];

                        if($addressId==$value['address']){
                        $res['url']=$url;
                        $res['image']=$rootPath.$image;
                        $res['productId']=$value['productId'];
                        $res['crontab_id']=$value['crontab_id'];
                        $res['name']=$configProduct->getName();
                        $res['price']=$v->getPrice();
                        $res['type']=$value['type'];
                        $res['qty']=$value['qty'];
                        $res['cost']=$v->getPrice();
                        $res['address']=$value['address'];
                        $sku=$v->getSku();
                        $salable = $StockStateSel->execute($sku,1); 

                        $productId=$value['productId'];
                        $type=$value['type'];

                        $stockSQL="SELECT b.qty
                        FROM blm_crontab b
                        WHERE b.quoteId=$quoteId AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressId
                        ";
            
                        $resStock = $connection->fetchAll($stockSQL);
                        $qtyRest=0;
                        foreach ($resStock as $key => $val) {
                            $qtyRest+=$val['qty'];
                        }
                     
                        $stock=$salable;
                        $stock-=$qtyRest;
                        if($stock<0){
                            $stock=0;
                        }
                        

                        $res['stock']=$stock;
                        

                        array_push($addressRes,$res);


                        $result[$key]['image']=$image;
                        $price=$v->getPrice();
                        $qty=$value['qty'];
                        $addressCost+=$price*$qty;
                        $addressQty+=$qty;
                    }
                }
            }
            }else{

            $totalCost+=$configProduct->getPrice() * $value['qty'];
            $totalQty+= $value['qty'];

                if($addressId==$value['address']){
                $url=$configProduct->getProductUrl();
                $image=$configProduct->getImage();
                $result[$key]['url']=$url;
                $result[$key]['image']=$image;

                $res['url']=$url;
                $res['image']=$rootPath.$image;
                $res['productId']=$value['productId'];
                $res['crontab_id']=$value['crontab_id'];
                 $res['type']=$value['type'];
                $res['name']=$configProduct->getName();
                $res['qty']=$value['qty'];
                $res['price']=$configProduct->getPrice();
                $res['cost']=$configProduct->getPrice();
                $res['address']=$value['address'];
                $sku=$configProduct->getSku();
                $salable = $StockStateSel->execute($sku,1); 

                
                $productId=$value['productId'];
                $type=$value['type'];
                
                $stockSQL="SELECT b.qty
                FROM blm_crontab b
                WHERE b.quoteId=$quoteId AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressId
                ";
    
                $resStock = $connection->fetchAll($stockSQL);
                $qtyRest=0;
                foreach ($resStock as $key => $val) {
                    $qtyRest+=$val['qty'];
                }
               
                $stock=$salable;
                $stock-=$qtyRest;
                if($stock<0){
                    $stock=0;
                }

                $res['stock']=$stock;

                array_push($addressRes,$res);

                $price=$configProduct->getPrice();
                $qty=$value['qty'];
                $addressCost+=$price*$qty;
                $addressQty+=$qty;
               }
            }
            }

            $ad=array('addressCost'=>$addressCost,'addressQty'=>$addressQty,'totalCost'=>$totalCost,'totalQty'=>$totalQty);

            $array=array('data'=>$addressRes,'TotalData'=>$ad);

            if($array){
                return json_encode($array);
            }else{
                return "[]";
            }



     }



                                /**
     * Sum an array of numbers.
     *
     * @api

 
     * @param int $quoteId The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function getCart($quoteId){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
       $root= $storeManager->getStore()->getBaseUrl();
      
      
       $qf= $objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
       $quote=$qf->get($quoteId);
       $customerId = $quote->getCustomer()->getId();
       $customerAddress=array();
        $rootPath=$root.'/pub/media/catalog/product';
    
       $customerObj = $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
       foreach ($customerObj->getAddresses() as $address)
        {
            $customerAddress[] = $address->toArray();

        }
    

        $totalCost=null;
        $addressCost=null;
        $totalQty=null;
        $addressQty=null;
        $res=array();
        $addressRes=array();

        // $sql="SELECT *
        // FROM blm_crontab b
        // Where b.quoteId=$quoteId AND b.address=$addressId";
        // $result = $connection->fetchAll($sql);


        $sqlTotal="SELECT *
        FROM blm_crontab b
        Where b.quoteId=$quoteId";
        $result = $connection->fetchAll($sqlTotal);



          
  
        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
        $StockStateSel = $objectManager->get('\Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');
        foreach ($result as $key => $value) {

            $configProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($value['productId']);

            $type=$value['type'];

            if($type!=0){
                $_children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);

                foreach ($_children as $k => $v) {

                $packageId=$v->getCustomAttribute('package_type')->getValue();

                    if($type==$packageId){
                        $url=$v->getProductUrl();
                        $image=$v->getData('image');
                        $result[$key]['url']=$url;

                        $totalCost+=$v->getPrice() * $value['qty'];
                        $totalQty+= $value['qty'];

                       
                        $res['url']=$url;
                        $res['image']=$rootPath.$image;
                        $res['productId']=$value['productId'];
                        $res['price']=$v->getPrice();
                        $res['name']=$v->getName();
                        $res['type']=$value['type'];
                        $res['qty']=$value['qty'];
                        $res['address']=$value['address'];
                        $sku=$v->getSku();
                        $salable = $StockStateSel->execute($sku,1); 
                        
                        $res['stock']=$salable;

                        array_push($addressRes,$res);


                        $result[$key]['image']=$image;
                        $price=$v->getPrice();
                        $qty=$value['qty'];
                        $addressCost+=$price*$qty;
                        $addressQty+=$qty;
                    
                }
            }
            }else{

            $totalCost+=$configProduct->getPrice() * $value['qty'];
            $totalQty+= $value['qty'];

                $url=$configProduct->getProductUrl();
                $image=$configProduct->getData('image');
                $result[$key]['url']=$url;
                $result[$key]['image']=$image;

                $res['url']=$url;
                $res['image']=$rootPath.$image;
                $res['productId']=$value['productId'];
                $res['type']=$value['type'];
                $res['price']=$configProduct->getPrice();
                $res['name']=$configProduct->getName();
                $res['qty']=$value['qty'];
                $res['address']=$value['address'];

                $sku=$configProduct->getSku();
                $salable = $StockStateSel->execute($sku,1); 
                $res['stock']=$salable;


                array_push($addressRes,$res);

                $price=$configProduct->getPrice();
                $qty=$value['qty'];
                $addressCost+=$price*$qty;
                $addressQty+=$qty;
               }
            }
            $minAmount = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('sales/minimum_order/amount');
            $ad=array('addressCost'=>$addressCost,'addressQty'=>$addressQty,'totalCost'=>$totalCost,'totalQty'=>$totalQty);

            $array=array('data'=>$addressRes,'TotalData'=>$ad,'addresses'=>$customerAddress, 'minimumAmount'=>$minAmount);


            //file_put_contents("testowyxd.txt", file_get_contents("testowyxd.txt")."\n============addressQty=============\n".print_r($array, true));



            if($array){
                return json_encode($array);
            }else{
                return "[]";
            }
     }


                                /**
     * Sum an array of numbers.
     *
     * @api

     * @param string $CartData The array of numbers to sum.
     * @return string The sum of the numbers.
     */
     public function getCartQty($CartData){


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $CartData=json_decode($CartData);

       // $CartData=array('address'=>6,'quoteid'=>28,'quote'=>array(array('productid'=>34,'type'=>21),array('productid'=>34,'type'=>22),array('productid'=>27,'type'=>0)));

        $products=null;
        $types=null;
        $addressid=$CartData->address;
        $quoteid=$CartData->quoteid;
        $tab=array();

        if(empty($CartData->quote)){
            return "[]";
        }
        foreach ($CartData->quote as $key => $value) {

            array_push($tab,array('productId'=>$value->productid,'type'=>$value->type,'qty'=>0,'stock'=>0));
           $products.='(productId='.$value->productid.' AND '.'type='.$value->type.') OR ';
        }
        $products=rtrim($products,' OR ');

        $sql="SELECT q.productId,q.qty,q.type
        FROM blm_crontab q
        WHERE q.quoteId=$quoteid AND q.address=$addressid AND($products)";


        $result = $connection->fetchAll($sql);

            $tab = array_map("unserialize",
            array_unique(array_map("serialize", $tab)));

             foreach ($tab as $key => $value) {
                 foreach ($result as $k => $v) {
                     if($v['productId']==$value['productId']){
                        unset($tab[$key]);
                     }
                     # code...
                 }
             }

             $res = array_merge($result, $tab);

        $StockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
        $StockStateSel = $objectManager->get('\Magento\InventorySalesApi\Api\GetProductSalableQtyInterface');


//         foreach ($res as $key => $value) {

//             $productId=$value['productId'];
//             $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);
      

//            if($value['type']==0){
//             $sku=$product->getSku();
//             $salable = $StockStateSel->execute($sku,1);

//             $type=$value['type'];

//             $stockSQL="SELECT b.qty
//                 FROM blm_crontab b
//                 WHERE b.quoteId=$quoteid AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressid
//                 ";
    
//                 $qtyRes = $connection->fetchAll($stockSQL);
                
//                 $qtyRest=0;
//                 foreach ($res as $key => $var) {
//                     $qtyRest+=$var['qty'];
//                 }
//                // file_put_contents("testowyxd.txt", file_get_contents("testowyxd.txt")."\n============qtyRest=============\n".print_r($qtyRest, true));
//                 $stock=$salable;
//                 $stock-=$qtyRest;
//                 if($stock<0){
//                     $stock=0;
//                 }

//             $result[$key]['stock']=$salable;
//             $result[$key]['productId']=$value['productId'];
//             $result[$key]['type']=$value['type'];
//             $result[$key]['qty']=$value['qty'];
//             }else{
//            $_children = $product->getTypeInstance()->getUsedProducts($product);
//                 foreach ($_children as $k => $child) {
//                     $packageId=$child->getCustomAttribute('package_type')->getValue();
//                      if($packageId==$value['type']){
//                          $sku=$child->getSku();
//                            $salable = $StockStateSel->execute($sku,1);

//                            $type=$value['type'];

//                            $stockSQL="SELECT b.qty
//                            FROM blm_crontab b
//                            WHERE b.quoteId=$quoteid AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressid
//                            ";
               
//                            $resStock = $connection->fetchAll($stockSQL);
//                            $qtyRest=0;
//                            foreach ($resStock as $key => $val) {
//                                $qtyRest+=$val['qty'];
//                            }
//                            //file_put_contents("testowyxd.txt", file_get_contents("testowyxd.txt")."\n============qtyRest=============\n".print_r($qtyRest, true));
//                            $stock=$salable;
//                            $stock-=$qtyRest;
//                            if($stock<0){
//                                $stock=0;
//                            }
                           
//                         if(!isset($result[$key]['productId'])){
//                            // file_put_contents("testowyxd.txt", file_get_contents("testowyxd.txt")."\n============result=============\n".print_r($result, true));
//                          //   file_put_contents("testowyxd.txt", file_get_contents("testowyxd.txt")."\n============value=============\n".print_r($value, true));
// //
//                             $result[$key]['productId']=$value['productId'];
//                             $result[$key]['type']=$value['type'];
//                             $result[$key]['qty']=$value['qty'];
//                             $result[$key]['stock']=$salable;
//                         }
//                     }
//                 }
//            }
//         }

        $result=array();
foreach ($res as $keyTemp => $tempVal) {
    
    $productId=$tempVal['productId'];
    $type=$tempVal['type'];
    $addressid=$CartData->address;
    $quoteid=$CartData->quoteid;


    $product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId);

    if($tempVal['type']!=0){

        $_children = $product->getTypeInstance()->getUsedProducts($product);
                       foreach ($_children as $k => $child) {
                        
                        $packageId=$child->getCustomAttribute('package_type')->getValue();
                      if($packageId==$tempVal['type']){
                        $sku=$child->getSku();
                        $salable = $StockStateSel->execute($sku,1);

                        $stock=null;
                        $stockSQL="SELECT b.qty
                                               FROM blm_crontab b
                                                   WHERE b.quoteId=$quoteid AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressid
                                                   ";
                                       
                                                   $resStock = $connection->fetchAll($stockSQL);

                        if($resStock){
                            $qtyRest=0;
                            foreach ($resStock as $key => $val) {
                                $qtyRest+=$val['qty'];
                          }
                            
                           $stock=$salable;
                           $stock-=$qtyRest;
                           if($stock<0){
                               $stock=0;
                            }
                        }else{
                            $stock=$salable;
                        }

                        $result[$keyTemp]['productId']=$tempVal['productId'];
                        $result[$keyTemp]['type']=$tempVal['type'];
                        $result[$keyTemp]['qty']=$tempVal['qty'];
                        $result[$keyTemp]['stock']=$stock;

                      }
                       }
        // $sku=$product->getSku();
        // $salable = $StockStateSel->execute($sku,1);
    }else{

        $sku=$product->getSku();

        try {
        $salable = $StockStateSel->execute($sku,1);
        //code...
        } catch (\Throwable $th) {
            $salable=0;
        }

                       $stockSQL="SELECT b.qty
                        FROM blm_crontab b
                       WHERE b.quoteId=$quoteid AND b.productId=$productId AND b.`type`=$type AND b.address !=$addressid
                         ";
            
                     $resStock = $connection->fetchAll($stockSQL);
                        
                     if($resStock){
                        $qtyRest=0;
                        foreach ($resStock as $key => $val) {
                            $qtyRest+=$val['qty'];
                      }
                      
                       $stock=$salable;
                       $stock-=$qtyRest;
                       if($stock<0){
                           $stock=0;
                        }
                    }else{
                        $stock=$salable;
                    }

        $result[$keyTemp]['productId']=$tempVal['productId'];
        $result[$keyTemp]['type']=$tempVal['type'];
        $result[$keyTemp]['qty']=$tempVal['qty'];
        $result[$keyTemp]['stock']=$stock;

    }


}

        if($result){
            return json_encode($result);
        }else{
            return "[]";

        }


     }



}