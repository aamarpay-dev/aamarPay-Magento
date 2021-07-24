define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
 
        return Component.extend({
            defaults: {
                template: 'Stilaamarpay_Aamarpay/payment/aamarpay'
            },
            getInstructions: function () {
                console.log("try to find instructions:"+this.item.method);
                //return window.checkoutConfig.payment.instructions[this.item.method];
            }
        });
    }
);