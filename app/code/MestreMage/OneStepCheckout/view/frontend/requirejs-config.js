/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/place-order': {
                'MestreMage_OneStepCheckout/js/model/place-order-mixin': true
            },
            'Magento_Checkout/js/model/quote': {
                'MestreMage_OneStepCheckout/js/model/quote-mixin': true
            }
        }
    }
};