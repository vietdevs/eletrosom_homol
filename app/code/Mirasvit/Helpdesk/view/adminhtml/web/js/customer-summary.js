define([
    'uiComponent',
    'ko',
    'jquery',
    'jquery/ui',
    "mage/mage",
    "mage/backend/suggest"
], function (Component, ko, $) {
    'use strict';

    $( document ).ajaxSend(function( event, jqxhr, settings ) {
        if (typeof settings.blockAjaxLoader != 'undefined') {
            $(settings.blockAjaxLoader).show();
        }
    });
    $( document ).ajaxComplete(function( event, request, settings ) {
        if (typeof settings.blockAjaxLoader != 'undefined') {
            $(settings.blockAjaxLoader).hide();
        }
    });

    return Component.extend({
        defaults: {
            template: 'Mirasvit_Helpdesk/customer-summary'
        },
        isShowSummary: ko.observable(false),
        isShowCustomer: ko.observable(false),
        isEditCustomerMode: ko.observable(false),
        isEditOrderMode: ko.observable(false),
        isEditCc: ko.observable(false),
        isEditBcc: ko.observable(false),
        isCustomerNote: ko.observable(false),
        customer: ko.observable({}),
        customerOrders: ko.observable({}),
        rmas: ko.observable({}),
        orderId: ko.observable(0),
        emailTo: ko.observable(),
        emailCc: ko.observable(),
        emailBcc: ko.observable(),
        options: {
            searchField: '[data-field=helpdesk_search_field]',
            searchCustomerField: '[data-field=helpdesk_search_customer]',
        },

        initialize: function () {
            this._super();
            this._bind();
            this._initVars();
        },
        _initVars: function () {
            this.loaderImg = this._loaderImg;

            this.customer(this._customer);

            this.rmas(this._rmas);
            this.ordersUrl = this._ordersUrl;

            this.orderId(this._orderId);
            this.emailTo(this._emailTo);
            this.emailCc(this.customer().cc);
            this.emailBcc(this.customer().bcc);
            if (this.customer().id > 0 || this.customer().email || this.customer().bcc || this.customer().cc) {
                this.isShowSummary(1);
            }
            this.isShowCustomer(this.customer().id > 0);
            if (this._customer.hasOrders) {
                this.loadOrders();
            }
        },
        loadOrders: function () {
            var self = this;
            if (!this.ordersUrl) {
                return;
            }
            var orderId = self.orderId();
            self.orderId(0);// we need this to trigger order selection
            $.ajax(this.ordersUrl, {
                method : "get",
                dataType: 'json',
                success : function(response) {
                    if (typeof response.error != 'undefined') {
                        console.log(response.error);
                        return;
                    }
                    self.customer().orders = response;
                    self.customerOrders(response);
                    self.orderId(orderId);
                },
            });
        },
        _bind: function () {
            $(document).on('suggestselect', this.options.searchField, $.proxy(this['onSuggestSelect'], this));
            $(document).on('suggestselect', this.options.searchCustomerField, $.proxy(this['onSuggestSelect2'], this));
        },
        onSuggestSelect: function (e, ui) {
            var customer = ui.item;
            this.customer(customer);
            this.emailTo(customer.email);
            this.customerOrders(this.customer().orders);
            this.isShowSummary(1);
            this.isShowCustomer(1);
            this.isEditOrderMode(1);
            this.ordersUrl = this.customer().ordersUrl;
            this.loadOrders();
        },
        onSuggestSelect2: function (e, ui) {
            this.customer(ui.item);
            this.isEditCustomerMode(0);
            this.customerOrders(this.customer().orders);
            this.isShowCustomer(1);
            this.isEditOrderMode(1);
            this.ordersUrl = this.customer().ordersUrl;
            this.loadOrders();
        },
        showEdit: function () {
            this.isEditCustomerMode(1);
        },
        showEditOrder: function () {
            this.isEditOrderMode(1);
        },
        onOrderChange: function (data, e) {
            var orderId = $(e.target).val();
            this.orderId(orderId);
            this.isEditOrderMode(0);
        },
        getOrderById: function (orderId) {
            if (!this.customer().orders.length) {
                return false;
            }
            var newOrder = {}
            $.each(this.customer().orders, function (i, order) {
                if (order.id == orderId) {
                    newOrder = order;
                }
            });
            return newOrder;
        },
        hasOrders: function () {
            return this.customer().hasOrders;
        },
        showEditCc: function () {
            this.isEditCc(1);
        },
        showCustomerNote: function () {
            if (this.isCustomerNote()) {
                this.isCustomerNote(0);
            } else {
                this.isCustomerNote(1);
            }
        },
        onCcChange: function (data, e) {
            var cc = $(e.target).val();
            this.emailCc(cc);
            this.isEditCc(0);
        },
        showEditBcc: function () {
            this.isEditBcc(1);
        },
        onBccChange: function (data, e) {
            var bcc = $(e.target).val();
            this.emailBcc(bcc);
            this.isEditBcc(0);
        },
        hasRmas: function () {
            return this.rmas().length > 0;
        }
    });
});
