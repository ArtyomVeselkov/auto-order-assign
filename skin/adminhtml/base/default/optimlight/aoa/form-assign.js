/*
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

/**
 *
 * @param {object} source
 */
var optimlightAoaFormAssign = function(source) {
    /**
     *
     * @type {optimlightAoaFormAssign}
     */
    var self = this;
    /**
     *
     * @type {string}
     */
    this.html = '';
    /**
     *
     * @type {boolean}
     */
    this.showFlag = false;
    /**
     *
     * @type {boolean|string}
     */
    this.appendToSelector = false;
    /**
     *
     * @type {boolean|string}
     */
    this.showCtrl = false;
    /**
     *
     * @type {boolean}
     */
    this.initilized = false;
    /**
     *
     * @type {string}
     */
    this.action = '';
    /**
     *
     * @type {number}
     */
    this.orderId = 0;
    /**
     *
     * @type {string}
     */
    this.history = '';
    /**
     *
     * @type {string}
     */
    this.uid = 'optimlight-aoa-uid';
    /**
     *
     * @type {object|{}}
     */
    this.dialogWindow = {};
    /**
     *
     * @type {{orderId: string, history: string}}
     */
    this.formMap = {
        'orderId': '#order-id',
        'history': '#history',
        'messages': '#messages'
    };
    /**
     *
     * @type {string}
     */
    this.messages = '';

    /**
     *
     * @param {object} source
     */
    this.init = function(source) {
        if ('object' === typeof source) {
            self.html = source.html;
            self.showFlag = source.show;
            self.appendToSelector = source.appendTo;
            self.action = source.action;
            self.showCtrl = source.showCtrl;
            self.orderId = source.orderId;
            self.history = source.history;
            self.messages = source.messages;
            self.uid = self.uid + this.generateUid();
            if (self.showFlag) {
                document.addEventListener('DOMContentLoaded', function () {
                    if (!self.initilized) {
                        self.initilized = true;
                        self.append();
                    }
                });
                document.onreadystatechange = function () {
                    if ('interactive' === document.readyState && !self.initilized) {
                        self.initilized = true;
                        self.append();
                    }
                }
            }
        }
    };

    /**
     *
     */
    this.append = function() {
        if (self.appendToSelector) {
            var ref;
            if (!this.showCtrl) {
                return;
            }
            ref = document.querySelector(self.showCtrl);
            if ('object' === typeof ref && 'object' === typeof ref.parentNode) {
                // 1-st. Doesn't work in some cases.
                /*
                  ref.addEventListener('click', function(event) {
                      self.show();
                  });
                */
                // 2-d. Doesn't work in some cases.
                /*
                  $(ref).on('click', function() {
                      self.show()
                  });
                */
                // 3-d.
                ref.setAttribute('data-optimlight-uid', this.uid);
                optimlightAoaFormAssign.pushToRegistry(this.uid, this);
            }
        }
    };

    /**
     * Generate UID.
     *
     * @returns {string}
     */
    this.generateUid = function() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    };

    /**
     *
     */
    this.show = function(orderId, history) {
        if ('undefined' !== typeof orderId) {
            this.orderId = orderId;
        }
        if ('undefined' !== typeof history) {
            this.history = history;
        }
        if ($(this.uid) && 'undefined' !== typeof(Windows) && 'object' === this.dialogWindow && this.dialogWindow.__isLoaded) {
            Windows.focus(this.uid);
        } else {
            this.createDialogWindow();
        }
        this.update();
    };

    /**
     * Refresh values of form elements.
     */
    this.update= function() {
        if ('object' === typeof this.dialogWindow && this.dialogWindow.__isLoaded) {
            var key, id, ref;
            for (key in this.formMap) {
                id = this.formMap[key];
                if (this.formMap.hasOwnProperty(key)) {
                    ref = this.dialogWindow.element.querySelector(id);
                    if (ref) {
                        ref.value = this[key];
                    }
                }
            }
        }
    };

    /**
     *
     */
    this.createDialogWindow = function() {
        var self = this,
            html = this.html;
        this.dialogWindow = Dialog.confirm(html, {
            closable: true,
            resizable: false,
            draggable: true,
            className: 'magento',
            windowClassName: 'popup-window',
            title: 'Reassign current order to a customer',
            top: 100,
            width: 505,
            height: 500,
            zIndex: 90,
            recenterAuto: false,
            hideEffect: Element.hide,
            showEffect: Element.show,
            id: this.uid,
            okLabel: 'Submit',
            cancelLabel: 'Cancel',
            onOk: function (element) {
                var form = $(element.element.querySelector('form')),
                    data = form ? form.serialize(true) : {},
                    json = data ? JSON.stringify(data) : '';
                new Ajax.Request(self.action, {
                    method: 'POST',
                    parameters: {
                        isAjax: 1,
                        method: 'POST',
                        form_key: FORM_KEY,
                        data: json
                    },
                    onComplete: function(transport) {
                        if (transport.responseText.isJSON()) {
                            var response = transport.responseText.evalJSON();
                            self.messages = response.messages;
                            if (true !== response.status) {
                                new Effect.Shake(Windows.focusedWindow.getId());
                            } else {
                                location.reload(true);
                            }
                        } else {
                            self.messages = 'Request was unsuccessful, try again.';
                            new Effect.Shake(Windows.focusedWindow.getId());
                        }
                        self.update();
                    }
                });
            },
            onClose: function (element) {

            }
        });
        this.dialogWindow.__isLoaded = true;
    };

    this.init(source);
};

/**
 *
 * @type {{}|object}
 * @private
 */

optimlightAoaFormAssign.__optimlightRegistry = {};
/**
 *
 * @param {string} key
 * @param {optimlightAoaFormAssign} item
 */
optimlightAoaFormAssign.pushToRegistry = function(key, item) {
    optimlightAoaFormAssign.__optimlightRegistry[key] = item;
};

/**
 *
 * @param {HTMLElement} element
 * @param {string} method
 * @param {number} [orderId]
 * @param {string} [history]
 */
optimlightAoaFormAssign.callBy = function(element, method, orderId, history) {
    try {
        var key;
        if ('object' === typeof element) {
            key = element.getAttribute('data-optimlight-uid');
        } else {
            key = element;
        }
        if (key && 'undefined' !== typeof optimlightAoaFormAssign.__optimlightRegistry[key] && 'string' === typeof method) {
            optimlightAoaFormAssign.__optimlightRegistry[key][method](orderId, history);
        }
    } catch (e) {
        console.log('Unable call method: ' + method + ' by key: ' + key);
        console.log(e);
    }
};