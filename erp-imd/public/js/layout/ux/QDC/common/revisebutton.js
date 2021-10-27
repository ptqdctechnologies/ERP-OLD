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
var showReviseWindow = function(url, params, jsonFile, callback)
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
    var editor = new Ext.ux.grid.RowEditor({
        saveText: 'Update',
        clicksToEdit: 2,
        listeners: {
            'afteredit': function(ed, obj, rec, index) {
                var recs = stores.getAt(index);
                var qty = parseFloat(recs.data['qty'].toString().replace(/\$|\,/g, ''));
                var price = parseFloat(recs.data['price'].toString().replace(/\$|\,/g, ''));

                recs.data['totalPrice'] = qty * price;

                ed.record.commit(); //Commit changes into store

                hitungTotal();

            }
        }
    });
    var editorcancel = new Ext.ux.grid.RowEditor({
        saveText: 'Update',
        clicksToEdit: 2,
        listeners: {
            'afteredit': function(ed, obj, rec, index) {

                var recs = storescancel.getAt(index);
                var qty = parseFloat(recs.data['qty'].toString().replace(/\$|\,/g, ''));
                var price = parseFloat(recs.data['price'].toString().replace(/\$|\,/g, ''));

                recs.data['totalPrice'] = qty * price;

                ed.record.commit(); //Commit changes into store

                hitungTotalCancel();

            }
        }
    });
    var record = Ext.data.Record.create([{
            name: 'id',
            type: 'integer'
        }, {
            name: 'trano',
            type: 'string'
        }, {
            name: 'arf_no',
            type: 'string'
        }, {
            name: 'tgl_asf',
            type: 'date',
            dateFormat: 'Y-m-d'
        }, {
            name: 'tgl_arf',
            type: 'date',
            dateFormat: 'Y-m-d'
        }, {
            name: 'urut',
            type: 'string'
        }, {
            name: 'prj_kode',
            type: 'string'
        }, {
            name: 'prj_nama',
            type: 'string'
        }, {
            name: 'sit_kode',
            type: 'string'
        }, {
            name: 'sit_nama',
            type: 'string'
        }, {
            name: 'workid',
            type: 'string'
        }, {
            name: 'workname',
            type: 'string'
        }, {
            name: 'kode_brg',
            type: 'string'
        }, {
            name: 'nama_brg',
            type: 'string'
        }, {
            name: 'qty',
            type: 'string'
        }, {
            name: 'uom',
            type: 'string'
        }, {
            name: 'price',
            type: 'float'
        }, {
            name: 'totalPrice',
            type: 'float'
        }, {
            name: 'totalPriceInArfh',
            type: 'float'
        }, {
            name: 'ket',
            type: 'string'
        }, {
            name: 'petugas',
            type: 'string'
        }, {
            name: 'val_kode',
            type: 'string'
        }, {
            name: 'rateidr',
            type: 'float'
        }, {
            name: 'totalASF',
            type: 'float'
        },
        {
            name: 'total',
            type: 'float'
        }, {
            name: 'totalPriceASF',
            type: 'float'
        }, {
            name: 'status',
            type: 'string'
        }, {
            name: 'cfs_kode',
            type: 'string'
        }, {
            name: 'arf_trano_ref',
            type: 'string'
        }, {
            name: 'arf_caption_id',
            type: 'string'
        }, {
            name: 'caption_id',
            type: 'string'
        }, {
            name: 'trano_ref',
            type: 'string'
        }
    ]);

    var stores = new Ext.data.Store({
        id: 'store-asf',
        reader: new Ext.data.JsonReader({fields: record}),
        listeners: {
            'load': function(record) {
                hitungTotal();
            }
        }
    });
    var storescancel = new Ext.data.Store({
        id: 'store-asfcancel',
        reader: new Ext.data.JsonReader({fields: record}),
        listeners: {
            'load': function(record) {
                hitungTotalCancel();
            }
        }
    });

    var totalasf = 0;
    var totalasfcancel = 0;

    function hitungTotal()
    {
        totalasf = 0;
        stores.each(function(items) {
            totalasf += (parseFloat(items.data['totalPrice']));
        });
        Ext.getCmp('totalasffooter').setText('Total ASF : ' + Ext.util.Format.number(totalasf, '0,0.00'));
    }
    function hitungTotalCancel()
    {
        totalasfcancel = 0;
        storescancel.each(function(items) {
            totalasfcancel += (parseFloat(items.data['totalPrice']));
        });
        Ext.getCmp('totalasffootercancel').setText('Total ASF : ' + Ext.util.Format.number(totalasfcancel, '0,0.00'));
    }
    function hitungSubtotal()
    {
        var qty = parseFloat(Ext.getCmp('qty').toString().replace(/\$|\,/g, ''));
        var harga = parseFloat(Ext.getCmp('harga').toString().replace(/\$|\,/g, ''));
        Ext.getCmp('total').setValue(qty * harga);
    }

    var sm = new Ext.grid.CheckboxSelectionModel({singleSelect: true});
    var rm = new Ext.grid.RowSelectionModel();
    var rm2 = new Ext.grid.RowSelectionModel();

    function getNewID() {
        //count the max record from origin Store
        var maxRec = storescancel.getTotalCount();
        var maxRecFilter = storescancel.getCount();
        var newID = maxRec + maxRecFilter + 1;
        //Clear filter
        storescancel.clearFilter();
        return newID;
    }
    function getNewIDCancel() {
        //count the max record from origin Store
        var maxRec = stores.getTotalCount();
        var maxRecFilter = stores.getCount();
        var newID = maxRec + maxRecFilter + 1;
        //Clear filter
        stores.clearFilter();
        return newID;
    }


    var gridpanel = new Ext.grid.GridPanel({
        store: stores,
        title: 'Expense Claim',
        id: 'grid-panel',
        frame: true,
        width: 700,
        flex: 1,
        sm: rm,
        plugins: [editor],
        columns: [new Ext.grid.RowNumberer(),
            {
                header: 'ARF Number',
                dataIndex: 'arf_no',
                sortable: true
            }, {
                header: 'Work ID',
                dataIndex: 'workid',
                sortable: true
            },
            {
                header: 'Work Name',
                dataIndex: 'workname',
                sortable: true
            },
            {
                header: 'Name Material',
                dataIndex: 'nama_brg',
                sortable: true
            },
            {
                header: 'Qty',
                dataIndex: 'qty',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'qty',
                    vtype: 'numeric',
                    width: 100,
                    listeners: {
                        change: function(a, b) {
                        }
                    }
                }
            },
            {
                header: 'Price',
                dataIndex: 'price',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'price',
                    vtype: 'numeric',
                    width: 100,
                    listeners: {
                        change: function(a, b) {
                        }
                    }
                }
            },
            {
                header: 'Total',
                dataIndex: 'totalPrice',
                sortable: true,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '0.00';
                },
            },
        ],
        bbar: new Ext.Toolbar({
            id: 'temp_bbar',
            style: "text-align:right;",
            items: [{
                    xtype: 'label',
                    id: 'totalasffooter',
                    style: 'color:red;font-weight:bold;margin-right:40px;font-size:12'
                }
            ],
            layout: 'fit'
        }),
        tbar: new Ext.Toolbar({
            id: 'temp_tbarcancel',
            style: "text-align:left;",
            items: [{
                    xtype: 'tbbutton',
                    text: 'Copy Data to "Ammount Due To Company"',
                    iconCls: 'silk-add',
                    handler: function()
                    {
                        if (!Ext.getCmp('grid-panel').getSelectionModel().getCount()) {
                            return false;
                        }
                        var records = [];

                        var gridasf = Ext.getCmp('grid-panel').getSelectionModel().getSelections();
                        var oriasf = stores.getAt(stores.findExact('id', gridasf[0].data['id']));

                        Ext.each(gridasf, function(store) {
                            records.push(store.copy());
                        });

                        storescancel.add(records);
                        hitungTotalCancel();

                    }
                }, '-'
            ]

        })

    });
    var gridpanelcancel = new Ext.grid.GridPanel({
        title: 'Amount Due To Company',
        store: storescancel,
        id: 'grid-panelcancel',
        frame: true,
        width: 700,
        flex: 1,
        sm: rm2,
        plugins: [editorcancel],
        columns: [new Ext.grid.RowNumberer(),
            {
                header: 'ARF Number',
                dataIndex: 'arf_no',
                sortable: true
            }, {
                header: 'Work ID',
                dataIndex: 'workid',
                sortable: true
            },
            {
                header: 'Work Name',
                dataIndex: 'workname',
                sortable: true
            },
            {
                header: 'Name Material',
                dataIndex: 'nama_brg',
                sortable: true
            },
            {
                header: 'Qty',
                dataIndex: 'qty',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'qty',
                    vtype: 'numeric',
                    width: 100,
                    listeners: {
                        change: function(a, b) {
                        }
                    }
                }
            },
            {
                header: 'Price',
                dataIndex: 'price',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'price',
                    vtype: 'numeric',
                    width: 100,
                    listeners: {
                        change: function(a, b) {
                        }
                    }
                }
            },
            {
                header: 'Total',
                dataIndex: 'totalPrice',
                sortable: true,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '0.00';
                },
            },
        ],
        bbar: new Ext.Toolbar({
            id: 'temp_bbarcancel',
            style: "text-align:right;",
            items: [{
                    xtype: 'label',
                    id: 'totalasffootercancel',
                    style: 'color:red;font-weight:bold;margin-right:40px;font-size:12'
                }
            ],
            layout: 'fit'
        }),
        tbar: new Ext.Toolbar({
            id: 'temp_tbarcancel',
            style: "text-align:left;",
            items: [{
                    xtype: 'tbbutton',
                    text: 'Copy Data to "Expense Claim"',
                    iconCls: 'silk-add',
                    handler: function()
                    {
                        if (!Ext.getCmp('grid-panelcancel').getSelectionModel().getCount()) {
                            return false;
                        }

                        var records = [];

                        var gridasf = Ext.getCmp('grid-panelcancel').getSelectionModel().getSelections();
                        var oriasf = storescancel.getAt(stores.findExact('id', gridasf[0].data['id']));

                        Ext.each(gridasf, function(store) {
                            records.push(store.copy());
                        });

                        stores.add(records);
                        hitungTotal();
                    }
                }, '-'
            ]

        })

    });

    stores.loadData(Ext.util.JSON.decode(params.posts));
    storescancel.loadData(Ext.util.JSON.decode(params.posts2));


    var dFormMsg = new Ext.Window({
        id: 'uploader_window',
        layout: 'vbox',
        width: 750,
        height: 500,
        title: 'Revise ASF Value',
        stateful: false,
        modal: true,
        resizable: true,
        items: [
            gridpanel, gridpanelcancel
        ],
        buttons: [
            {
                text: 'OK',
                handler: function()
                {
                    var json = '';
                    var json2 = '';
                    var parameter = params.etc;

                    Ext.getCmp('grid-panel').getStore().each(function(store) {
                        json += Ext.util.JSON.encode(store.data) + ',';
                    });

                    json = '[' + json.substring(0, json.length - 1) + ']'; //JSON format fix

                    Ext.getCmp('grid-panelcancel').getStore().each(function(store) {
                        json2 += Ext.util.JSON.encode(store.data) + ',';
                    });
                    json2 = '[' + json2.substring(0, json2.length - 1) + ']'; //JSON format fix

                    var newparam = {posts: json, etc: params.etc, posts2: json2, file: params.file, revisi: true};

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