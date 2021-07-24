<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Stilaamarpay\Aamarpay\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Stilaamarpay\Aamarpay\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;


/**
 * Pay In Store payment method model
 */
class Aamarpay extends AbstractMethod
{
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'aamarpay';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Stilaamarpay\Aamarpay\Block\Form\Aamarpay';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    protected $_gateUrl = "https://secure.aamarpay.com/request.php";

    protected $_testUrl = "https://sandbox.aamarpay.com/request.php";

    protected $_test;

    protected $orderFactory;

    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->_request = $request;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }


    //@param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface $payment
    public function getAmount($orderId) //\Magento\Framework\Object $payment)
    {
        //\Magento\Sales\Model\OrderFactory
        $orderFactory = $this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

        $order = $orderFactory->create()->loadByIncrementId($orderId);
        //$payment= $order->getPayment();
        // return $payment->getAmount();
        return $order->getGrandTotal();
    }

    protected function getOrder($orderId)
    {
        $orderFactory = $this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);
    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $this->_gateUrl = $this->getConfigData('cgi_url');
        $this->_testUrl = $this->getConfigData('cgi_url_test_mode');
        $this->_test = $this->getConfigData('test');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
            $quote->getShippingAddress()->getShippingMethod()
        );
    }

    public function getGateUrl()
    {
        if ($this->getConfigData('test')) {
            return $this->_testUrl;
        } else {
            return $this->_gateUrl;
        }
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        if (empty($shippingMethod)) {
            $shippingMethod = "No";
        }
        // return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== true;
    }


    public function generateHash($login, $sum, $pass, $id = null)
    {

        $hashData = array(
            "MrchLogin" => $login,
            "OutSum" => $sum,
            "InvId" => $id,
            "currency" => "BDT",
            "pass" => $pass,
        );

        $hash = strtoupper(md5(implode(":", $hashData)));
        return $hash;
    }

    public function sslcommerz_hash_key($store_passwd = "", $post_data = array())
    {
        if (isset($post_data) && isset($post_data['verify_sign']) && isset($post_data['verify_key'])) {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $post_data['verify_key']);

            $new_data = array();
            if (!empty($pre_define_key)) {
                foreach ($pre_define_key as $value) {
                    // if (isset($post_data[$value])) {
                    $new_data[$value] = ($post_data[$value]);
                    // }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = md5($store_passwd);

            # SORT THE KEY AS BEFORE
            ksort($new_data);

            $hash_string = "";
            foreach ($new_data as $key => $value) {
                $hash_string .= $key . '=' . ($value) . '&';
            }
            $hash_string = rtrim($hash_string, '&');

            if (md5($hash_string) == $post_data['verify_sign']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getSslOrederStatus($orderid)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderid);

        return $order->getStatus();
    }

    public function ipnAction($response)
    {
        $tran_id = $response['tran_id'];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($tran_id);

        $status = $order->getStatus();

        if ($this->getConfigData('test')) {
            $validUrl = "https://sandbox.aamarpay.com";
        } else {
            $validUrl = "https://secure.aamarpay.com";
        }

        $store_id = urlencode($this->getConfigData('merchant_id'));
        $password = urlencode($this->getConfigData('pass_word_1'));

        if ($this->_request->getPost()) {
            if ($tran_id != "" && $status == 'pending_payment' && ($response['status'] == 'VALID' || $response['status'] == 'VALIDATED')) {
                $val_id = urlencode($response['val_id']);

                $requested_url = $validUrl . '?val_id=' . $val_id . '&store_id=' . $store_id . '&store_passwd=' . $password;

                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $requested_url);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

                $result = curl_exec($handle);

                $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

                if ($code == 200 && !(curl_errno($handle))) {

                    # TO CONVERT AS ARRAY
                    # $result = json_decode($result, true);
                    # $status = $result['status'];

                    # TO CONVERT AS OBJECT
                    $result = json_decode($result);

                    # TRANSACTION INFO
                    $tran_status = $result->status;

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($tran_id);
                    $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                    if ($tran_status == 'VALID' || $tran_status == 'VALIDATED') {
                        $orderState = Order::STATE_PROCESSING;
                        $order->setState($orderState, true, 'Payment Validated by IPN')->setStatus($orderState);
                        $msg = "Payment Validated by IPN";
                    }
                    $order->save();
                }
            } else {
                $msg = "IPN data missing!";
            }
        } else {
            $msg = "No IPN Request Found!";
        }
        return $msg;
    }

    public function getPostData($orderId)
    {
        echo "";
        // var_dump($orderId);
        // die();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);

        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface');

        $customerSession = $_objectManager->get('Magento\Customer\Model\Session');

        $PostData = [];
        $PostData['OutSum'] = round($this->getAmount($orderId), 2);
        $PostData['InvId'] = intval($orderId);

        //sscl    
        $PostData['store_id'] = $this->getConfigData('merchant_id');
        $PostData['signature_key'] = $this->getConfigData('pass_word_1');
        $PostData['amount'] = round($this->getAmount($orderId), 2);
        $PostData['tran_id'] = $orderId;
        $PostData['currency'] =  $storeManager->getStore()->getCurrentCurrency()->getCode(); //$this->getConfigData('currency');

        $PostData['success_url'] = $storeManager->getStore()->getBaseUrl() . 'aamarpay/payment/response'; //$this->getBaseUrl() 
        $PostData['fail_url'] = $storeManager->getStore()->getBaseUrl() . 'aamarpay/payment/fail';
        $PostData['cancel_url'] = $storeManager->getStore()->getBaseUrl() . 'aamarpay/payment/cancel';
        $PostData['ipn_url'] = $storeManager->getStore()->getBaseUrl() . 'aamarpay/payment/ipn';

        // CUSTOMER INFORMATION 
        $PostData['cus_name']       = $order->getCustomerName();
        $PostData['cus_email']      = $order->getCustomerEmail();
        $PostData['cus_phone']      = $order->getBillingAddress()->getTelephone();
        $PostData['cus_add1']       = $order->getBillingAddress()->getStreet()[0];
        $PostData['cus_city']       = $order->getBillingAddress()->getCity();
        $PostData['cus_state']      = $order->getBillingAddress()->getRegionId();
        $PostData['cus_postcode']   = $order->getBillingAddress()->getPostcode();
        $PostData['cus_country']    = $order->getBillingAddress()->getCountryId();

        $qntty = count($order->getAllItems());

        foreach ($order->getAllItems() as $item) {
            $name[] = $item->getName();
        }
        // $items = implode($name,',');

        $PostData['shipping_method']   = 'YES';
        $PostData['num_of_item']       = "1";
        $PostData['desc']              = "Demo";
        $PostData['product_category']  = 'Ecommerce';
        $PostData['product_profile']   = 'general';

        # SHIPMENT INFORMATION
        $PostData['ship_name']         = $order->getBillingAddress()->getFirstname() . " " . $order->getBillingAddress()->getLastname();
        $PostData['ship_add1']         = $order->getBillingAddress()->getStreet()[0];
        $PostData['ship_city']         = $order->getBillingAddress()->getCity();
        $PostData['ship_state']        = $order->getBillingAddress()->getRegionId();
        $PostData['ship_postcode']     = $order->getBillingAddress()->getPostcode();
        $PostData['ship_country']      = $order->getBillingAddress()->getCountryId();
        $PostData['opt_a']             = $customerSession->getCustomer()->getId();

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->getGateUrl());
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $PostData);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);


        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close($handle);
            echo "FAILED TO CONNECT WITH AAMARPAY API";
            exit;
        }

        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true);

        if ($this->getConfigData('test')) {
            $paymentURL = "https://sandbox.aamarpay.com" . $sslcz;
            return $paymentURL;
            exit;
        } else {
            $paymentURL = "https://secure.aamarpay.com" . $sslcz;
            return $paymentURL;
            exit;
        }
    }

    public function responseAction($response)
    {
        if ($this->_request->getPost()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            // Load customer
            $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($response['opt_a']);

            // Load customer session
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');
            $customerSession->setCustomerAsLoggedIn($customer);

            $state = $this->getSslOrederStatus($response['mer_txnid']);
            if ($state == 'pending_payment') {
                $orderId = $response['mer_txnid'];
                if ($response['pay_status'] == 'Successful') {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
                    $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $orderState = Order::STATE_PROCESSING;
                    $order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
                    $order->save();
                } else {
                    echo "Payment Failed!";
                    $this->errorAction($response['mer_txnid']);
                }
            } else {
                echo "Payment Already Done!";
            }
        }
    }

    public function getPaymentMethod()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();

        return $methodTitle;
    }

    public function getConfigPaymentData()
    {
        return $this->getConfigData('title');
    }

    public function getCusMail()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);

        $PostData['order_id'] = $orderId;
        $PostData['cus_email'] = $order->getCustomerEmail();
        $PostData['url'] = $this->getConfigData('test');
        $PostData['total_amount'] = round($this->getAmount($orderId), 2);
        $PostData['cus_name'] = $order->getCustomerName();
        $PostData['cus_phone'] = $order->getBillingAddress()->getTelephone();
        $PostData['title'] = $this->getConfigData('title');
        $PostData['full_name'] = $order->getBillingAddress()->getFirstname() . " " . $order->getBillingAddress()->getLastname();
        $PostData['country'] = $order->getBillingAddress()->getCountryId();

        // $PostData['company'] = $order->getBillingAddress()->getCompany();
        $PostData['street'] = $order->getBillingAddress()->getStreet();
        $PostData['region'] = $order->getBillingAddress()->getRegionId();
        $PostData['city'] = $order->getBillingAddress()->getCity() . ", " . $order->getBillingAddress()->getPostcode();
        $PostData['telephone'] = $order->getBillingAddress()->getTelephone();

        return $PostData;
    }

    public function errorAction($orderId)
    {
        if($orderId == "CANCEL ORDER"){
            $orderId = $this->_checkoutSession->getLastRealOrderId();
        }
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);

        $orderState = Order::STATE_CANCELED;
        $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
        $order->save();

        //setcookie('cross-site-cookie', 'bar', ['samesite' => 'None', 'secure' => true]);
    }

    public function getSuccessMsg()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface');

        $PostData = [];
        $PostData['cus_name'] = $order->getCustomerName();
        $PostData['cus_email'] = $order->getCustomerEmail();
        // $PostData['cus_phone'] = $order->getBillingAddress()->getTelephone();  
        $PostData['total_amount'] = round($this->getAmount($orderId), 2);
        $PostData['tran_id'] = $orderId;
        $PostData['state'] = $order->getState();

        return $PostData;
    }
}
