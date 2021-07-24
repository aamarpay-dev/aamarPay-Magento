## aamarPay - Magento

aamarPay-Online Payment Gateway For Bangladesh

This Module Work for Magento Version 2.3.x

### Installation Steps

Please follow these steps to install the aamarPay Payment Gateway module.

Step 1: Download module from Github.

Step 2: Log in to your server using FTP or Cpanel.

Step 3: Upload Stilaamarpay (Magento 2 Payment Module) inside of app/code directory.

Step 4: Now go to your server root (public_html) and Upload cacheflush.php, updatemodule.php.

Step 5: Go to browser and run [www.yourdomain.com/updatemodule.php] to update your module.

Step 6: Go to browser again and run [www.yourdomain.com/cacheflush.php] to flush magento cache.

Step 7: Log in to your Magento admin account.

Step 8: Navigate to STORES > Configuration > SALES > Payment Methods > Other Payment Methods: > aamarPay Payment Gateway : then follow below steps

- Enabled : Yes (If No then Make it Yes).
- Title : Set your own title what you want to see in checkout page.
- New Order Status : Pending Payment.
- Test Mode : Yes (If No then Make it Yes).
- Allowed Shipping Methods : Your Shipping Methods if have any.
- Store/API ID : Your Valid Store ID Provided from aamarPay.
- Signature Key : Your Signature Key Provided from aamarPay.
- Payment from Applicable Countries : Default / If haven't any. 
- Payment from Specific Countries : Default / If haven't any. 
- Instructions : Your Instructions.
- Sort Order : It can be empty.

Now Click on Save Config button.

Step 10: Navigate to SYSTEM> Cache Management: Select all Cache type and click on Flush Magento Cache. Or you can do step 5 & 6 to update module and flush cache.

Step 11: Now you can test module is working or not. If you can see your payment option at checkout page then you have installed Module successfully :)

Step 12: Do a test transaction.

Thank You!

For any issue, feel free to email us at support@aamarpay.com
