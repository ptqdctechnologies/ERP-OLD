/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/*!
 * Ext JS Library 3.3.0
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

Ext.ns('Ext.ux.form');


Ext.ux.form.LogisticPriceAuth = Ext.extend(Ext.form.Field, {
    showAuthWindow: function(t) {

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
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'New Price',
                    id: 'new_price',
                    width: 100,
                    vtype: 'alphanumericonly',
                    priceDelemiter: ','
                }
            ]
        });

        var dFormMsg = new Ext.Window({
            id: 'auth_window',
            layout: 'fit',
            width: 300,
            height: 170,
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
                        var price = Ext.getCmp('new_price').getValue();
                        if (price == '')
                        {
                            Ext.Msg.alert('Error', 'Please select new price');
                            return false;
                        }

                        var params = {
                            username: Ext.getCmp('username').getValue(),
                            password: Ext.getCmp('password').getValue()
                        };

                        var select = this.Selectid;
                        var kode = this.kode_brg;
                        var nama = this.nama_brg;

                        Ext.Ajax.request({
                            url: '/logistic/logisticbarang/cek-auth-price-changer',
                            method: 'POST',
                            params: params,
                            success: function(result) {
                                obj = Ext.util.JSON.decode(result.responseText);

                                if (obj.success)
                                {
                                    if (obj.auth)
                                    {
//                                        Ext.getCmp(select).setValue(price);
                                        Ext.Ajax.request({
                                            url: '/logistic/logisticbarang/update-harga-barang',
                                            method: 'POST',
                                            params: {
                                                kode_brg: kode,
                                                nama_brg: nama,
                                                harga: price
                                            },
                                            success: function(result) {
                                                obj = Ext.util.JSON.decode(result.responseText);

                                                if (obj.success)
                                                {
                                                    trano = obj.number;
                                                    msg = "Your request has been submitted into workflow "+ "Your request number is <b>"+trano+"</b>";
                                                    Ext.MessageBox.show({
                                                        title: 'Info',
                                                        msg: msg,
                                                        buttons: Ext.MessageBox.OK,
                                                        icon: Ext.MessageBox.INFO
                                                    });
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
//                                        

                                    }
                                    else
                                    {
                                        Ext.Msg.alert('Error', obj.msg);
                                    }
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

    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 100;

        if (this.Enableeditable == undefined)
            this.Enableeditable = false;

        if (this.selectValue == undefined)
            this.selectValue = '';

//        if (this.kodebrg == undefined)
//            this.kodebrg = '';
//        if (this.namabrg == undefined)
//            this.namabrg = '';

        if (!this.el) {
            this.selectPrice = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: this.Enableeditable,
                value: this.selectValue
            });

            if (!this.disabled)
                this.selectPrice.onTriggerClick = this.showAuthWindow.createDelegate(this);

            this.fieldCt = new Ext.Container({
                autoEl: {
                    id: this.id
                },
                renderTo: ct,
                cls: 'ext-project-selector',
                layout: 'table',
                layoutConfig: {
                    columns: 2
                },
                defaults: {
                    hideParent: true
                },
                items: [
                    this.selectPrice
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectPrice]);

        }
        Ext.ux.form.LogisticPriceAuth.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.LogisticPriceAuth.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('logisticpriceauth', Ext.ux.form.LogisticPriceAuth);
