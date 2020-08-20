/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/payment/method-list',
    'MestreMage_OneStepCheckout/js/action/save-default-payment',
    'mage/translate'
], function (
    $,
    _,
    Component,
    ko,
    quote,
    stepNavigator,
    paymentService,
    methodConverter,
    getPaymentInformation,
    checkoutDataResolver,
    methodList,
    saveDefaultPayment,
    $t
) {
    'use strict';

    /** Set payment methods to collection */
    paymentService.setPaymentMethods(methodConverter(window.checkoutConfig.paymentMethods));

    return Component.extend({
        defaults: {
            template: 'MestreMage_OneStepCheckout/payment',
            activeMethod: ''
        },
        isVisible: ko.observable(quote.isVirtual()),
        quoteIsVirtual: quote.isVirtual(),
        isPaymentMethodsAvailable: ko.computed(function () {
            return paymentService.getAvailablePaymentMethods().length > 0;
        }),

        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.navigate();
            
            return this;
        },

        /**
         * Navigate method.
         */
        navigate: function () {
            var self = this;

            getPaymentInformation().done(function () {
                //self.isVisible(true);
                saveDefaultPayment();
            });
        },

        /**
         * @return {*}
         */
        getFormKey: function () {
            return window.checkoutConfig.formKey;
        }
    });
});