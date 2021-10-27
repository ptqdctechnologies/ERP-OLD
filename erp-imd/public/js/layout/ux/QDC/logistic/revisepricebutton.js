var showAuthWindow = function(url, callback)
{
    if (callback == undefined)
        callback = function() {
        };
    var formAuth = new Ext.FormPanel({
        layout: 'form',
        frame: true,
        items: [
            {
                xtype: 'textfield',
                fieldLabel: 'Username',
                id: 'username',
                width: 120
            },
            {
                xtype: 'textfield',
                inputType: 'password',
                fieldLabel: 'Password',
                id: 'password',
                width: 120
            }
        ]
    });
    var dFormMsg = new Ext.Window({
        id: 'auth_window',
        layout: 'fit',
        width: 300,
        height: 150,
        title: 'Authentication Required',
        stateful: false,
        modal: true,
        resizable: true,
        items: [
            formAuth
        ],
        buttons: [
            {
                text: 'OK',
                handler: function()
                {
                    var params = {
                        username: Ext.getCmp('username').getValue(),
                        password: Ext.getCmp('password').getValue()
                    };
                    var select = this.Selectid;
                    Ext.Ajax.request({
                        url: url,
                        method: 'POST',
                        params: params,
                        success: function(result) {
                            obj = Ext.util.JSON.decode(result.responseText);
                            if (obj.success)
                            {
                                callback();
                                dFormMsg.close();
                            }
                            else
                            {
                                Ext.Msg.alert('Error', obj.msg);
                            }
                        },
                        failure: function(action) {
                            if (action.failureType == 'server') {
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                Ext.Msg.alert('Error!', obj.errors.reason);
                            } else {
                                Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                            }
                        }

                    });
                }, scope: this
            },
            {
                text: 'Cancel',
                handler: function()
                {
                    dFormMsg.close();
                }
            }
        ]
    });
    dFormMsg.show();
};
var showRevisePriceWindow = function(url, trano, callback)
{
    if (callback == undefined)
        callback = function() {
        };
    Ext.apply(Ext.form.VTypes, {
        numericText: "Only numbers are allowed.",
        numericMask: /[0-9]/,
        numericRe: /(^-?dd*.d*$)|(^-?dd*$)|(^-?.dd*$)/,
        numeric: function(v) {
            return function() {
                return this.numericRe.test(v)
            };
        }
    });
   
   var formAuth = new Ext.FormPanel({
            layout: 'form',
            frame: true,
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: 'New Price',
                    id: 'new_price',
                    width: 100,
                    vtype: 'numeric',
                    priceDelemiter: ','
                }
            ]
        });
   

    var dFormMsg = new Ext.Window({
        id: 'uploader_window',
        layout: 'vbox',
        width: 275,
        height: 120,
        title: 'Revise Price',
        stateful: false,
        modal: true,
        resizable: true,
        items: [
            formAuth
        ],
        buttons: [
            {
                text: 'OK',
                handler: function()
                {
//                    var trano = params.trano;
                    var price = Ext.getCmp('new_price').getValue();
                   
                    var newparam = {trano: trano, price: price};

                    Ext.Ajax.request({
                        url: url,
                        method: 'POST',
                        params: newparam,
                        success: function(result) {
                            obj = Ext.util.JSON.decode(result.responseText);
                            if (obj.success)
                            {
                                callback();
                                dFormMsg.close();
                            }
                            else
                            {
                                Ext.Msg.alert('Error', obj.msg);
                            }
                        },
                        failure: function(action) {
                            if (action.failureType == 'server') {
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                Ext.Msg.alert('Error!', obj.errors.reason);
                            } else {
                                Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                            }
                        }

                    });
                }, scope: this
            },
            {
                text: 'Cancel',
                handler: function()
                {
                    dFormMsg.close();
                }
            }
        ]
    });
    dFormMsg.show();
};