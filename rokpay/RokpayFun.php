<?php
class RokpayFun extends Module
{

    public $apiUrl = "https://staging.rokpay.cloud:8081/rokpay/order/verify-shop";

    

    public $environment = array(
        "staging" => "https://staging.rokpay.cloud:8081/rokpay/order/verify-shop",
        "production" => "https://rokpay.cloud:8081/rokpay/order/verify-shop"
    );

    public function getEnvironmentURL(){
        return $this->environment;
    }

    public function getDefault($is_test_mode=true){
            return $is_test_mode?$this->environment['staging']:$this->environment['production'];
    }

    public function insertData($id_cart, $customer)
    {
        $db = \Db::getInstance();
        $txnid = time();
        try
        {
            $db->insert('rokpay_payments', array(
                'id_cart' => $id_cart,
                'status' => 0,
                'date_added' => date('Y-m-d H:i:s') ,
                'date_updated' => date('Y-m-d H:i:s') ,
                'id_customer' => $customer->id,
                'id_transaction' => $txnid,
            ));
        }
        catch(Exception $e)
        {
            echo 'Caught exception: ', $e->getMessage() , "\n";
        }
        $data = array(
            "id_order" => $db->Insert_ID() ,
            "txnid" => $txnid
        );
        return $data;
    }

    public function updatetData($customer_order_id, $shopOrderId)
    {
        Db::getInstance()->update('rokpay_payments', array(
            'id_order' => $customer_order_id,
            "status" => "sucess"
        ) , 'id = ' . (int)$shopOrderId);
    }
    public function Rp_digest_hash($apiKey, $shopNumber = "")
    {
        $digest = $shopNumber . $apiKey;
        $digestHash = hash('sha512', $digest);
        return $digestHash;
    }
    public function makeCurlPost($data, $url)
    {

        $ch = curl_init($url);
        $payload = json_encode($data);

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json'
        ));
        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Execute the POST request
        $result = curl_exec($ch);
        $response = json_decode($result);
       /* echo "<pre>";
        print_r($response);
        exit;*/
        // Close cURL resource
        curl_close($ch);


        return $response;

    }
    public function testAPI($shopnumber, $apikey,$url)
    {
        $digestHash = $this->Rp_digest_hash($apikey, $shopnumber);
        $data = array(
            "amount" => "21",
            "cancellationUrl" => "",
            "currency" => "USD",
            "failureUrl" => "",
            "products" => array(
                array(
                    "name" => "abc",
                    "price" => 10,
                    "quantity" => 1
                ) ,
                array(
                    "name" => "abc1",
                    "price" => 11,
                    "quantity" => 1
                )
            ) ,
            "shopNumber" => $shopnumber,
            "shopOrderId" => 1124293,
            "shopTransactionId" => "Txnid122",
            "successUrl" => "",
            "digest" => $digestHash,
             "shippingAmount"=>10,
            "discounts"=>array(array("name"=>"test","type"=>"fixed","value"=>20)),
            "shopOrderNumber"=>112429311
        );

        $response = $this->makeCurlPost($data, $url);
        return $response;
    }
    public function verifyShop($cart, $data)
    {   

               
        //$apiUrl = "https://rokpay.cloud:8081/rokpay/order/verify-shop";
        $amount = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $apikey = Configuration::get('ROKPAY_API_KEY');
        $cancellationUrl = $this
            ->context
            ->link
            ->getModuleLink('rokpay', 'validation', array(
            "response" => "cancel",
            "shopOrderId" => $data["id_order"]
        ));
        $currency = new Currency($cart->id_currency);
        $currency = $currency->iso_code;
        $failureUrl = $this
            ->context
            ->link
            ->getModuleLink('rokpay', 'validation', array(
            "response" => "fail",
            "shopOrderId" => $data["id_order"]
        ));
        $products = array();
        $product = array(
            "name" => "",
            "price" => 0,
            "quantity" => 0
        );
        $shopnumber = Configuration::get('ROKPAY_SHOP_NUMBER');
        $shopOrderId = $data["id_order"];
        $shopTransactionId = $data["txnid"];
        $successUrl = $this
            ->context
            ->link
            ->getModuleLink('rokpay', 'validation', array(
            "response" => "success",
            "shopOrderId" => $data["id_order"]
        ));

        $digestHash = $this->Rp_digest_hash($apikey, $shopnumber);
        foreach ($cart->getProducts() as $key => $object)
        {
            $product["name"] = $object["name"];
            $product["price"] = $object["price"];
            $product["quantity"] = $object["cart_quantity"];
            array_push($products, $product);
        }
        $shippingAmount = $cart->getSummaryDetails()["total_shipping"];
        $discounts = array();
        $discount = array("name"=>"","type"=>"","value"=>"");
        foreach ($cart->getSummaryDetails()['discounts'] as $disc_object)
        {
            $discount["name"] = $disc_object["name"];
            $discount["type"] = $disc_object["reduction_percent"]!=0?"percentual":"fixed";
            $discount["value"] = $disc_object["value_real"];
            array_push($discounts, $discount);
        }
        $data = array(
            "amount" => $amount,
            "cancellationUrl" => $cancellationUrl,
            "currency" => $currency,
            "failureUrl" => $failureUrl,
            "products" => $products,
            "shopNumber" => $shopnumber,
            "shopOrderId" => $shopOrderId,
            "shopTransactionId" => $shopTransactionId,
            "successUrl" => $successUrl,
            "digest" => $digestHash,
            "shippingAmount"=>$shippingAmount,
            "discounts"=>$discounts,
            "shopOrderNumber"=>$shopOrderId
        );
        
      
        $response = $this->makeCurlPost($data, $this->apiUrl);

       /* print_r($response);
        exit;*/
        $redirect_url = "";
        if (array_key_exists("error", get_object_vars($response)))
        {
            $redirect_url = $data['failureUrl'];
        }
        elseif (array_key_exists("orderRequest", get_object_vars($response)))
        {
            $redirect_url = $response
                ->orderRequest->paymentUrl;
        }
        Tools::redirect($redirect_url);
    }

}

