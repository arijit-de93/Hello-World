<?php
/**
 * Observer file to update prices
 * @author a.nu.rag
 *
 */
class Insync_Companyrule_Model_Price_Observer{
	/**
	 * invokes when cart updated
	 * @param unknown_type $observer
	 * @author a.nu.rag
	 */
	public function logCartUpdate($observer){

		foreach ($observer->getCart()->getQuote()->getAllVisibleItems() as $quote_item ) {
		/* @var $item Mage_Sales_Model_Quote_Item */

			$website_id=Mage::getModel('core/store')->load($quote_item->getData('store_id'))->getWebsiteId();

			$company_id=Mage::helper('insync_company')
			->getCustomerCompany(Mage::getSingleton('customer/session')->getCustomer()->getId());

			$product_id=$quote_item->getProductId();

			//$qty=$quote_item->getQty();
			
			/* Product Group wise Qty set */
			Mage::log('logCartUpdate', null, 'arijit1.log');
			$quote = Mage::getModel('checkout/cart')->getQuote();
			$item_group = Mage::getModel('catalog/product')->load($product_id)->getProduct_group();	//	item_group -> current item
			Mage::log($item_group, null, 'arijit1.log');
			foreach ($quote->getAllItems() as $item) {
				//Mage::log('Itm Qty'.$item->getQty(), null, 'arijit1.log');
		      //$product_group = $item->getProduct()->getData('product_group');//->getProduct_group();	
		      $product_group = Mage::getModel('catalog/product')->load($item->getProductId())->getData('product_group');
				Mage::log('productGroup'.$product_group, null, 'arijit1.log');//	product_group -> quote items
				//Mage::log($product_group, null, '19-Oct-15.log');
				if ($item_group == $product_group) {
					$qty += $item->getQty();
				}
			}
			
			
			Mage::log($qty, null, 'arijit1.log');
			/* Tier-Price by product_group */
			$quotePrice = $quote_item->getPrice();
			Mage::log('quote Price', null, 'arijit1.log');
			Mage::log($quotePrice, null, 'arijit1.log');
			$product = Mage::getModel('catalog/product')->load($product_id);
			$productTierPrice = $product->getTierPrice($qty);
			if (!empty($productTierPrice)) {
				Mage::log('Tier Price', null, 'arijit1.log');
				Mage::log($productTierPrice, null, 'arijit1.log');
				$quotePrice = min($productTierPrice, $quotePrice);
			}
			/* ------------------------- */
			
			if($company_id){
				if(!empty($website_id) && !empty($company_id) && !empty($product_id) && !empty($qty) ){
						
					$new_price ="";
						
					$product = Mage::getModel('catalog/product')->load($product_id);
					$finalPrice = $product->getFinalPrice($qty);
						
					// Product specific price
					$prod_price= Mage::helper('insync_companyrule')->getNewPrice($website_id,$company_id,$product_id,$qty);

					// Category specific price
					$cat_price=Mage::helper('insync_companyrule')->getCategoryPrice($product_id,$finalPrice,$company_id,$website_id);
					// Get applicable price
					$new_price=Mage::helper('insync_companyrule')->getMinPrice($prod_price,$cat_price,$finalPrice);
					Mage::log('new_price'.$new_price, null, 'arijit1.log');
					if($new_price!=''){

						//if($new_price < $quote_item->getPrice()){
						$quote_item->setOriginalCustomPrice($new_price);
						$quote_item->save();
						//}
					}
					else{
						// add new calculated price
						$product = Mage::getModel('catalog/product')->load($product_id);
						$finalPrice = $product->getFinalPrice($quote_item->getQty());

						$quote_item->setOriginalCustomPrice($finalPrice);
						$quote_item->save();
					}
				}
			}
			unset($qty);
		}
	}
	/**
	 * invokes when product added to cart
	 * @param unknown_type $observer
	 * @author a.nu.rag
	 */
	public function logCartAdd($observer){

		$company_id=Mage::helper('insync_company')
		->getCustomerCompany(Mage::getSingleton('customer/session')->getCustomer()->getId());
		if($company_id){
			$event = $observer->getEvent();

// 			$quote_item = $event->getQuoteItem();

// 			$website_id=Mage::getModel('core/store')->load($quote_item->getData('store_id'))->getWebsiteId();


// 			$product_id=$quote_item->getProductId();

// 			$qty=$quote_item->getQty();
			
// 			$quotePrice='';
// 			if($quote_item->getPrice() == ''){
// 				$product = Mage::getModel('catalog/product')->load($product_id);
// 				$quotePrice = $product->getFinalPrice($quote_item->getQty());
// 			}else{
// 				$quotePrice = $quote_item->getPrice();
// 			}
// 			if(!empty($website_id) && !empty($company_id) && !empty($product_id) && !empty($qty) ){
// 				$new_price="";
// 				// Product specific price
// 				$prod_price = Mage::helper('insync_companyrule')->getNewPrice($website_id,$company_id,$product_id,$qty);

// 				// Category specific price
// 				$cat_price=Mage::helper('insync_companyrule')->getCategoryPrice($product_id,$quotePrice,$company_id,$website_id);

// 				// Get applicable price
// 				$new_price=Mage::helper('insync_companyrule')->getMinPrice($prod_price,$cat_price,$quotePrice);
					
// 				if($new_price!=''){
// 					//if($new_price < $quotePrice){
// 					$quote_item->setOriginalCustomPrice($new_price);
// 					//$quote_item->save();
// 					//}
// 				}
// 			}
			
			/* Product Group wise Qty set */
			Mage::log('logCartAdd', null, 'arijit.log');

			$quote = Mage::getModel('checkout/cart')->getQuote();
			/* Array ( [product_group] => qty ) */
			$productGroup_qty = array();
			foreach ($quote->getAllItems() as $item) {
				$_product_group = Mage::getModel('catalog/product')->load($item->getProductId())->getData('product_group');
				if(array_key_exists($_product_group,$productGroup_qty))
					$productGroup_qty[$_product_group] += $item->getQty();
				else 
					$productGroup_qty[$_product_group] = $item->getQty();				
			}
			Mage::log($productGroup_qty, null, 'arijit.log');
			
			/* Set each Quote Item Custom Price as per product_group qty */
			foreach ($quote->getAllItems() as $quote_item) {
				Mage::log('forloop', null, 'arijit.log');
// 				$quote_item = $event->getQuoteItem();
	
				$website_id=Mage::getModel('core/store')->load($quote_item->getData('store_id'))->getWebsiteId();
	
				
				$product_id=$quote_item->getProductId();
				
				$product_group = Mage::getModel('catalog/product')->load($product_id)->getData('product_group');
				Mage::log('product_group => '.$product_group, null, 'arijit.log');
				$qty = $productGroup_qty[$product_group];	//	$quote_item->getQty();
				Mage::log('qty => '.$qty, null, 'arijit.log');
				$quotePrice='';
				if($quote_item->getPrice() == ''){
					$product = Mage::getModel('catalog/product')->load($product_id);
					$quotePrice = $product->getFinalPrice($quote_item->getQty());
				}else{
					$quotePrice = $quote_item->getPrice();
				}
				
				/* Tier-Price by product_group */
				Mage::log('quote Price', null, 'arijit.log');
				Mage::log($quotePrice, null, 'arijit.log');
				$product = Mage::getModel('catalog/product')->load($product_id);
				$productTierPrice = $product->getTierPrice($qty);
				if (!empty($productTierPrice)) {
					Mage::log('Tier Price', null, 'arijit.log');
					Mage::log($productTierPrice, null, 'arijit.log');
// 					$quotePrice = min($productTierPrice, $quotePrice);
					$quotePrice = $productTierPrice;
				}
				/* ------------------------- */
				
				if(!empty($website_id) && !empty($company_id) && !empty($product_id) && !empty($qty) ){
					$new_price="";
					// Product specific price
					$prod_price = Mage::helper('insync_companyrule')->getNewPrice($website_id,$company_id,$product_id,$qty);
	
					// Category specific price
					$cat_price=Mage::helper('insync_companyrule')->getCategoryPrice($product_id,$quotePrice,$company_id,$website_id);
	
					// Get applicable price
					$new_price=Mage::helper('insync_companyrule')->getMinPrice($prod_price,$cat_price,$quotePrice);
					Mage::log('new_price => '.$new_price, null, 'arijit.log');
					if($new_price!=''){
						//if($new_price < $quotePrice){
						$quote_item->setOriginalCustomPrice($new_price);
						//$quote_item->save();
						//}
					}
				}
				unset($qty, $product_group);
			}

			/* !!!!!!!!!!!!!!!!!!!! */
		}
	}
	/**
	 * Show company specific prices in product description, if available
	 *
	 * Enter description here ...
	 * @param unknown_type $observer
	 * @author a.nu.rag
	 */
	public function desc_view($observer)
	{
		$company_id=Mage::helper('insync_company')
		->getCustomerCompany(Mage::getSingleton('customer/session')->getCustomer()->getId());
		if($company_id){
			$event = $observer->getEvent();
			$product = $event->getProduct();

			$website_id=Mage::getModel('core/store')->load($product->getData('store_id'))->getWebsiteId();
			$product_id=$product->getEntityId();
			$qty="1";


			// process percentage discounts only for simple products
			if ($product->getSuperProduct() && $product->getSuperProduct()->isConfigurable()) {
			} else {

				if(!empty($website_id) && !empty($company_id) && !empty($product_id) ){
					$new_price="";
					// Product specific price
					$prod_price = Mage::helper('insync_companyrule')->getNewPrice($website_id,$company_id,$product_id,$qty);
					// Category specific price
					$cat_price=Mage::helper('insync_companyrule')->getCategoryPrice($product_id,$product->getFinalPrice(),$company_id,$website_id);
					// Get applicable price
					$new_price=Mage::helper('insync_companyrule')->getMinPrice($prod_price,$cat_price,$product->getFinalPrice());

					if($new_price!=''){
						// compare prices
						$product->setFinalPrice($new_price);
					}
				}
			}
		}
		return $this;
	}


	/**
	 * Show company specific prices in catalog search page, if available
	 * @param unknown_type $observer
	 * @return Insync_Companyrule_Model_Price_Observer
	 * @author a.nu.rag
	 */
	public function list_view($observer)
	{

		$company_id=Mage::helper('insync_company')
		->getCustomerCompany(Mage::getSingleton('customer/session')->getCustomer()->getId());

		if($company_id){
			$event = $observer->getEvent();
			$myCustomPrice = 10;
			$products = $observer->getCollection();

			foreach( $products as $product )
			{

				$website_id=Mage::getModel('core/store')->load($product->getData('item_store_id'))->getWebsiteId();
				$website_id=($website_id)?$website_id:1;
				$product_id=$product->getEntityId();
				$qty="1";
				$new_price="";
				// process percentage discounts only for simple products
				if ($product->getSuperProduct() && $product->getSuperProduct()->isConfigurable()) {
				} else {

					if(!empty($website_id) && !empty($company_id) && !empty($product_id) && !empty($qty) ){
						$new_price='';
						// Product Specific price
						$prod_price = Mage::helper('insync_companyrule')->getNewPrice($website_id,$company_id,$product_id,$qty);
						
						// Category specific price
						$cat_price=Mage::helper('insync_companyrule')->getCategoryPrice($product_id,$product->getFinalPrice(),$company_id,$website_id);
						
						// Get applicable price
						$new_price=Mage::helper('insync_companyrule')->getMinPrice($prod_price,$cat_price,$product->getFinalPrice());

						if($new_price!=''){
							//$product->setPrice( $new_price );
							$product->setFinalPrice($new_price);
						}
					}
				}
			}
		}
		return $this;
	}

}