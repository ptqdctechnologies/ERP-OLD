/**
 *  ux untuk edit remarks semua transaksi
 *  -kecuali PO ada tambahan edit paymentterm
 *  ## Mr.Rius
 *  
 * */
Ext.ns('Ext.ux.grid');

Ext.ux.editRemarks = Ext.extend(Ext.Window, {
    stateful: false,
    initComponent: function() {

        var reset = function() {
            var teks = Ext.getCmp('forms').findByType('textfield');
            Ext.each(teks, function(t, index) {
                t.setValue('');
            });
            var teks = Ext.getCmp('forms').findByType('textarea');
            Ext.each(teks, function(t, index) {
                t.setValue('');
            });

            Ext.getCmp('trano').setValue('');
        };

        var doLogin = function(itemType) {
            if(!this.formAuth.getForm().isValid())
                return false;

            var params = {
                username: Ext.getCmp('username').getValue(),
                password: Ext.getCmp('password').getValue(),
                itemType: itemType
            };
            var select = this.Selectid;
            Ext.Ajax.request({
                url: '/home/cek-auth-remarks-changer',
                method: 'POST',
                params: params,
                success: function(result) {
                    obj = Ext.util.JSON.decode(result.responseText);
                    if (obj.success)
                    {
                        if (obj.auth)
                        {
                            this.dFormMsg.close();
                            update(itemType);
                        }
                        else
                        {
                            Ext.Msg.alert('Error', obj.msg);
                            return false;
                        }
                    }
                    else
                    {
                        Ext.Msg.alert('Error', obj.msg);
                        return false;
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
        }
        var showAuthWindow = function(itemType)
        {

            var formAuth = new Ext.FormPanel({
                layout: 'form',
                frame: true,
                id:'formAuth',
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Username',
                        id: 'username',
                        width: 120,
                        allowBlank:false,
                        enableKeyEvents: true,
                        listeners: {
                            keypress: function(field, e) {
                                if (e.button == 12) {
                                    doLogin(itemType);
                                }
                            }
                        }
                    },
                    {
                        xtype: 'textfield',
                        inputType: 'password',
                        fieldLabel: 'Password',
                        id: 'password',
                        allowBlank:false,
                        width: 120,
                        enableKeyEvents: true,
                        listeners: {
                            keypress: function(field, e) {
                                if (e.button == 12) {
                                    doLogin(itemType);
                                }
                            }
                        }
                    }
                ]
            });

            this.formAuth = formAuth;
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
                            doLogin(itemType);
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

            this.dFormMsg = dFormMsg;
        };

        var trano = new Ext.form.TextField({
            id: 'tranos',
            hidden: true
        });
        var itemType = new Ext.form.TextField({
            id: 'itemType',
            hidden: true
        });

        var remarks = new Ext.form.TextArea({
            fieldLabel: 'Remarks',
            id: 'ket',
            name: 'ket',
            width: 250,
            height: 50
        });
       
        var forms = new Ext.FormPanel({
            layout: 'form',
            id: 'forms',
            frame: true,
            items: [{
                    xtype: 'tranoselector',
                    fieldLabel: 'Select Trano',
                    Tranotype: this.Tranotype,
                    id: 'trano_select',
                    Selectid: 'trano',
                    allowBlank: false,
                    width: 150,
                    callbackFunc: function(trano, itemType)
                    {
                        Ext.Ajax.request({
                        url: '/home/cek-workflow-fa',
                        method: 'POST',
                        params: {trano: trano, type: itemType},
                        success: function(result) {
                                var returnData = Ext.util.JSON.decode(result.responseText);
                                if(returnData.success) {
                                        Ext.Ajax.request({
                                        url: '/home/get-remark-all-transaction',
                                        method: 'POST',
                                        params: {trano: trano, type: itemType},
                                        success: function(result) {
                                        returnData = Ext.util.JSON.decode(result.responseText);

                                        if (returnData.count > 0)
                                        {
                                            Ext.getCmp('ket').setValue(returnData.posts[0].ket);
                                            Ext.getCmp('tranos').setValue(trano);
                                            Ext.getCmp('itemType').setValue(itemType);
                                            Ext.getCmp('paymentterm').setValue(returnData.posts[0].paymentterm);
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
                            }else {
                                Ext.Msg.alert("Error",returnData.msg);
                                return false;
                            }
                            },
                            failure:function( action){
                                    if(action.failureType == 'server'){
                                        obj = Ext.util.JSON.decode(action.response.responseText);
                                        Ext.Msg.alert('Error!', obj.errors.reason);
                                    }
                                 }

                        });
                    }
                },
                remarks
            ]
        });



        var paymentterm = new Ext.form.TextArea({
            fieldLabel: 'Term of Payment',
            id: 'paymentterm',
            name: 'paymentterm',
            width: 250,
            height: 50
        });

        var update = function(itemTypes) {
            var ket = Ext.getCmp('ket').getValue();
            var tranos = Ext.getCmp('tranos').getValue();
            var paymentterm = Ext.getCmp('paymentterm').getValue();

            Ext.Ajax.request({
                url: '/home/update-remark-all-transaction',
                method: 'POST',
                params: {trano: tranos, type: itemTypes, ket: ket, paymentterm: paymentterm},
                success: function(result) {
                    obj = Ext.util.JSON.decode(result.responseText);

                    if (obj.success)
                    {
                        reset();

                        Ext.Msg.alert('Success', 'Data has been updated');
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
        }

        if (this.Tranotype == undefined)
            this.Tranotype = '';


        this.form = forms;
        if (this.paymentTerm != undefined) {
            this.form.add(paymentterm);
            this.height = 225;
        }

        this.items = [
            this.form

        ];
        this.buttons = [
            {
                text: 'Update',
                handler: function()
                {
                    var itemTypes = Ext.getCmp('itemType').getValue();
                    showAuthWindow(itemTypes);

                }, scope: this

            },
            {
                text: 'Cancel',
                handler: function()
                {
                    this.close();
                }, scope: this

            }
        ]

        Ext.ux.editRemarks.superclass.initComponent.call(this);
    },
    layout: 'fit',
    height: 150,
    width: 450

});
