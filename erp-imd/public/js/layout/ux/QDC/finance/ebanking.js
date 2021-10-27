Ext.ns('Ext.ux.grid');

Ext.ux.grid.gridEbanking = Ext.extend(Ext.grid.GridPanel, {
    BULK_ID: null,
    dataAdd: null,
    dataPayment: null,
    dataSupplier: null,
    paymentType: null,
    initAction: function() {
        var that = this;
        var rowactions = new Ext.ux.grid.RowActions({
            actions: [
                {
                    iconCls: 'icon-delete',
                    qtip: 'Delete',
                    id: 'edit'
                }
            ]
            , index: 'actions'
            , header: ''
        });

        rowactions.on('action', function(grid, record, action, row, col) {

            if (action == 'icon-delete')
            {
                Ext.MessageBox.confirm('Confirm', 'This action will delete this item, Proceed?',
                    function(btn)
                    {
                        if (btn == 'yes')
                        {
                            grid.getStore().remove(record);
                            grid.getView().refresh();
                            if(grid.getStore().getCount() == 0)
                            {
                                that.paymentType = null;
                            }
                        }
                    }, this
                );
            }
        }, this);

        return rowactions;
    },
    getJSON: function(callback)
    {
        var json = '';

        if (this.store.getCount() == 0)
            return false;

        this.store.each(function(store) {
            var encode = Ext.util.JSON.encode(store.data);
            if (encode != undefined)
                json += encode + ',';
        }, this);
        json = '[' + json.substring(0, json.length - 1) + ']';

        if (callback != undefined)
            callback(json);
        else
            return json;
    },
    loadData: function(json)
    {
        if (!json)
            return false;

        this.store.removeAll();
        this.store.loadData(json);
        this.getView().refresh();
    },
    recordTrano: new Ext.data.Record.create([
        {name: "trano"},
        {name: "ref_number"},
        {name: "item_type"},
        {name: "total"},
        {name: "bulk_id"},
        {name: "val_kode"},
        {name: "bank_name"},
        {name: "bank_city"},
        {name: "bank_branch"},
        {name: "bank_no"},
        {name: "bank_account_name"},
        {name: "bank_bi_code"},
        {name: "bank_transaction_type"},
        {name: "remark_1"},
        {name: "remark_2"},
        {name: "id"},
    ]),
    getSupplierDetail: function(params,callback)
    {
        var theData = this.dataAdd;
        Ext.getBody().mask('Please wait...');
        Ext.Ajax.request({
            url: '/default/suplier/get-supplier-detail',
            method: 'POST',
            params: params,
            success: function(result)
            {
                Ext.getBody().unmask();
                var returnData = Ext.util.JSON.decode(result.responseText);

                var dataBlank = {
                    bank_name: '',
                    bank_city: '',
                    bank_branch: '',
                    bank_account_name: '',
                    bank_no: '',
                    supplier_name: ''
                }

                if (callback != undefined)
                {
                    theData = QDC_Object_MergeRecursive(theData,dataBlank);
                    var data = QDC_Object_MergeRecursive(theData,returnData);
                    callback(data);
                }

            },
            failure: function(action)
            {
                Ext.getBody().unmask();
                if (action.failureType == 'server') {
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                } else {
                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                }
            }
        },this);
    },

    showPaymentForm: function() {
        var that = this;

        var alreadyEbanking = false;

        var clearInfo = function() {
            Ext.getCmp('voc_trano').setValue('');
            Ext.getCmp('doc_trano').setValue('');
            Ext.getCmp('invoice_no').setValue('');
            Ext.getCmp('total').setValue('');
            Ext.getCmp('payment-selector').setValue('');

            Ext.getCmp('status_ebanking').setValue('');
            Ext.getCmp('tgl_ebanking').setValue('');
            Ext.getCmp('uid_ebanking').setValue('');
            Ext.getCmp('filename_ebanking').setValue('');
            alreadyEbanking = false;
        };

        var checkEbanking = function(rec) {
            Ext.getBody().mask('Please wait...');
            Ext.Ajax.request({
                url: '/finance/ebanking/check-ebanking-bulk',
                method: 'POST',
                params: {
                    trano: rec.get("trano"),
                    item_type: Ext.getCmp('payment-selector').getTransactionType()
                },
                success: function(result)
                {
                    Ext.getBody().unmask();
                    var returnData = Ext.util.JSON.decode(result.responseText);
                    if (returnData.success)
                    {
                        var data = returnData.data;
                        alreadyEbanking = true;
                        Ext.getCmp('status_ebanking').setValue('<b color="red">YES</b>');
                        Ext.getCmp('tgl_ebanking').setValue(data.tgl);
                        Ext.getCmp('uid_ebanking').setValue(data.uid);
                        Ext.getCmp('filename_ebanking').setValue(data.filename);
                    }
                    else
                    {
                        alreadyEbanking = false;
                        Ext.getCmp('status_ebanking').setValue('<b>NO</b>');
                        Ext.getCmp('tgl_ebanking').setValue('');
                        Ext.getCmp('uid_ebanking').setValue('');
                        Ext.getCmp('filename_ebanking').setValue('');
                    }

                },
                failure: function(action)
                {
                    Ext.getBody().unmask();
                    if (action.failureType == 'server') {
                        obj = Ext.util.JSON.decode(action.response.responseText);
                        Ext.Msg.alert('Error!', obj.errors.reason);
                    } else {
                        Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                    }
                }
            },this);
        }

        var paymentForm = new Ext.FormPanel({
            height: 400,
            autoWidth: true,
            frame: true,
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: 'Transaction Type',
                    id: 'trans-type',
                    allowBlank: false,
                    width: 100,
                    store: new Ext.data.SimpleStore({
                        fields: ['nilai'],
                        data: [
                            ['ARF'],
                            ['BRFP'],
                            ['PPN-REM'],
                            ['REM'],
                            ['RPI'],
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'nilai',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    emptyText: 'Select a type ...',
                    listeners: {
                        'select' : function(c,n,o) {
                            Ext.getCmp('payment-selector').setTransactionType( n.data['nilai']);
                            clearInfo();
                        }
                    }
                },
                {
                    xtype: 'paymentselector',
                    SelectWidth: 150,
                    id: 'payment-selector',
                    Selectid: 'payment-item',
                    fieldLabel: 'Payment Trano',
                    name: 'payment_trano',
                    callback: function(rec){

                        that.dataAdd = rec;
                        Ext.getCmp('voc_trano').setValue(rec.get("voc_trano"));
                        Ext.getCmp('doc_trano').setValue(rec.get("doc_trano"));
                        Ext.getCmp('invoice_no').setValue(rec.get("invoice_no"));
                        Ext.getCmp('total').setValue(rec.get("val_kode") + " " + Ext.util.Format.number(parseFloat(rec.get("total_bayar")),'0,0.00'));

                        checkEbanking(rec);
                    }
                },
                {
                    xtype: 'fieldset',
                    title: 'Payment Detail',
                    items: [
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'BPV Trano',
                            id: 'voc_trano',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Ref Number',
                            id: 'doc_trano',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Payment Date',
                            id: 'tgl',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Total Payment',
                            id: 'total',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Invoice No',
                            id: 'invoice_no',
                            value: ' ',
                        }
                    ]
                },
                {
                    xtype: 'fieldset',
                    title: 'Ebanking Detail',
                    items: [
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Ebanking',
                            id: 'status_ebanking',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Date',
                            id: 'tgl_ebanking',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'PIC',
                            id: 'uid_ebanking',
                            value: ' ',
                        },
                        {
                            xtype: 'displayfield',
                            fieldLabel: 'Filename',
                            id: 'filename_ebanking',
                            value: ' ',
                        },
                    ]
                }
            ]
        });

        var windowSave = new Ext.Window({
            id: 'ebanking-save-window',
            autoHeight: true,
            width: 350,
            title: 'Please Select Payment',
            modal: true,
            stateful: false,
            closeAction: 'close',
            buttons: [
                {
                    text: 'OK',
                    handler: function()
                    {
                        var cb = function(){
                            windowSave.close();
                            that.showAddForm({
                                trano: that.dataAdd.get("trano"),
                                item_type: that.dataAdd.get("jenis_document"),
                                bpv_trano: that.dataAdd.get("voc_trano"),
                                ref_number: that.dataAdd.get("doc_trano"),
                                total: that.dataAdd.get("total_bayar"),
                                val_kode: that.dataAdd.get("val_kode")
                            });
                        };
                        if (alreadyEbanking)
                        {
                            Ext.MessageBox.alert('Error', 'This Payment already transferred with Ebanking!');
                            return false;
//                            Ext.MessageBox.confirm('Confirm', 'This Payment already transferred with Ebanking, Proceed?',
//                                function(btn)
//                                {
//                                    if (btn == 'yes')
//                                    {
//                                        cb();
//                                    }
//                                }, this
//                            );
                        }
                        else
                            cb();

                    }
                },
                {
                    text: 'Cancel',
                    handler: function()
                    {
                        windowSave.close();
                    }
                }
            ],
            items: [
                paymentForm
            ]
        });
        windowSave.show();
    },

    checkExisting: function(data) {
        var indeks = this.store.findBy(function(record,id){

            if (record.get('bank_bi_code') == data.get("bank_bi_code") &&
                record.get('bank_no') == data.get("bank_no") &&
                record.get('bank_transaction_type') == data.get("bank_transaction_type")
                )
            {
                return true;
            }
        });
        if (indeks >= 0)
            return this.store.getAt(indeks);

        return false;
    },

    showAddForm: function(data, rootCallback) {
        if (data == undefined)
        {
            data = {
                bank_name: '',
                bank_city: '',
                bank_branch: '',
                bank_account_name: '',
                bank_no: '',
                supplier_name: '',
                remark_1: '',
                remark_2: '',
                bank_transaction_type: ''
            }
        }

        var that = this;

        addToStore = function(callback){
            if (callback == undefined)
                callback = function(){}

            if (!supForm.getForm().isValid())
            {
                Ext.Msg.alert('Error','There are some Errors on form, Please check it again');
                return false;
            }

            var vals = supForm.getForm().getValues();

            vals.bank_bi_code = Ext.getCmp('ebanking-item').getValue();
            vals.bank_transaction_type = Ext.getCmp('bank_transaction_type').getValue();

            if (!checkSupForm(vals))
            {
                return false;
            }

            var e = new that.recordTrano({
                trano: data.trano,
                ref_number: data.ref_number,
                total: data.total,
                val_kode: data.val_kode,
                bank_name: vals.bank_name,
                bank_city: vals.bank_city,
                bank_branch: vals.bank_branch,
                bank_no: vals.bank_no,
                bank_bi_code: vals.bank_bi_code,
                bank_account_name: vals.bank_account_name,
                bank_transaction_type: vals.bank_transaction_type,
                remark_1: vals.remark_1,
                remark_2: vals.remark_2,
                item_type: data.item_type
            });


            that.paymentType = vals.bank_transaction_type;
            //Check existing payment on grid ebanking
//            var cekExist = that.checkExisting(e);
//            if (cekExist !== false)
//            {
//                //if exist, combine them into 1 payment
//                cekExist.set('total',parseFloat(cekExist.get("total")) + parseFloat(e.get("total")));
//                cekExist.set('ref_number','');
//            }
//            else
//            {
                that.store.add(e);
//            }

            that.dataAdd = null;
            supForm.getForm().reset();

            callback(e);
        };

        checkSupForm = function(data){
            if (data.bank_bi_code == '' || data.bank_bi_code == undefined)
            {
                Ext.Msg.alert("Error","Domestic BI Code not selected yet!");
                return false;
            }
            if (data.bank_transaction_type == '' || data.bank_transaction_type == undefined)
            {
                Ext.Msg.alert("Error","Transaction Type not selected yet!");
                return false;
            }
            return true;
        }

        var addField = {}, addButton = [];
        if (data.item_type == 'RPI')
        {
            addField = {
                xtype: 'textfield',
                fieldLabel: 'Supplier Name',
                readOnly: true,
                name: 'supplier_name',
                width: 200
            };

            addButton = [{
                text: 'Update to Master Supplier',
                handler: function() {

                    var vals = supForm.getForm().getValues();
                    vals.bank_bi_code = Ext.getCmp('ebanking-item').getValue();

                    if (!checkSupForm(vals))
                    {
                        return false;
                    }

                    vals.sup_kode = that.dataSupplier.sup_kode;
                    vals.id = that.dataSupplier.id;
                    Ext.getBody().mask('Please wait...');
                    Ext.Ajax.request({
                        url: '/default/suplier/update-supplier-detail',
                        method: 'POST',
                        params: vals,
                        success: function(result)
                        {
                            Ext.getBody().unmask();
                            var returnData = Ext.util.JSON.decode(result.responseText);
                            if (returnData.success)
                            {
                                Ext.Msg.alert("Success","Master Supplier has been updated");
                            }
                            else
                                Ext.Msg.alert("Error",returnData.msg);

                        },
                        failure: function(action)
                        {
                            Ext.getBody().unmask();
                            if (action.failureType == 'server') {
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                Ext.Msg.alert('Error!', obj.errors.reason);
                            } else {
                                Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                            }
                        }
                    },this);
                }
            }];
        }

        if (supForm)
            supForm.destroy();

        var supForm = new Ext.FormPanel({
            height: 300,
            autoWidth: true,
            frame: true,
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: 'Transaction Type ',
                    name: 'bank_transaction_type',
                    id: 'bank_transaction_type',
                    allowBlank: false,
                    width: 100,
                    store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['RTGS', 'RTGS'],
                            ['LLG', 'LLG'],
                            ['OVB', 'Over Booking']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    emptyText: 'Select a type ...',
                    listeners: {
                        'select' : function(c,n,o) {
                            if(that.paymentType != null)
                            {
                                if (that.paymentType != n.data['nilai'])
                                {
                                    Ext.Msg.alert("Warning", "Transaction Type is different with previous one, please use only " + that.paymentType + " for this Ebanking Payment!");
                                }
                            }
                        }
                    }
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Bank Name',
                    name: 'bank_name',
                    width: 200,
                    allowBlank: false
//                    value: data.bank_name
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Bank City',
                    name: 'bank_city',
                    width: 150,
//                    value: data.bank_city
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Bank Branches',
                    name: 'bank_branch',
                    width: 200,
//                    value: data.bank_branch
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recepient Name',
                    name: 'bank_account_name',
                    width: 150,
                    allowBlank: false
//                    value: data.bank_account_name
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Account No.',
                    name: 'bank_no',
                    vtype: 'numericonly',
                    width: 200,
                    allowBlank: false
//                    value: data.bank_no
                },
                {
                    xtype: 'ebankingselector',
                    Selectid: 'ebanking-item',
                    fieldLabel: 'BI Code',
                    name: 'bank_bi_code',
                    callback: function(rec){

                    }
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Remark 1',
                    name: 'remark_1',
                    width: 150,
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Remark 2',
                    name: 'remark_2',
                    width: 150,
                },
            ],
            buttons: addButton
        });

        if (Object.keys(addField).length !== 0)
        {
            supForm.insert(1,addField);
        }

        supForm.doLayout();

        var windowSave = new Ext.Window({
            id: 'ebanking-save-window',
            autoHeight: true,
            width: 350,
            title: 'Please Fill this form',
            modal: true,
            stateful: false,
            closeAction: 'close',
            buttons: [
                {
                    text: 'OK',
                    handler: function()
                    {
                        var callback = function(rec){
                            windowSave.close();
                            rootCallback(rec);
                        }
                        addToStore(callback);
                    }
                },
                {
                    text: 'Cancel',
                    handler: function()
                    {
                        windowSave.close();
                        rootCallback();
                    }
                }
            ],
            items: [
                supForm
            ]
        });

        windowSave.show();

        this.getSupplierDetail({
            bpv_trano: data.bpv_trano
        },function(rec){
            that.dataSupplier = rec;
            var a = new that.recordTrano(rec);
            supForm.getForm().loadRecord(a);
            if (rec.bi_code != null && rec.bi_code != undefined)
                Ext.getCmp('ebanking-item').setValue(rec.bi_code);
        });
    },
    addNewTrano: function(data, rootCallback)
    {
        if (data != undefined)
        {
            this.dataAdd = data;
            this.showAddForm(data, rootCallback);
        }
        else
            this.showAddForm(null,rootCallback);
    },
    store: null,
    exportJson: function(type)
    {
        var json = this.getJSON();

        if (json == false)
        {
            return false;
        }

        //downloadFile @ header.phtml
        downloadFile('/finance/ebanking/export',{type: type, json: json});
    },
    initComponent: function() {

        this.title = "Ebanking Payment";

//        var summary = new Ext.grid.GroupSummary();
        var that = this;
        this.store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root: 'data',
                totalProperty: 'total',
                fields: that.recordTrano
            })
        });

        this.rowactions = this.initAction();
        this.plugins = [this.rowactions];

        this.columns = [
            new Ext.grid.RowNumberer({
                width: 30
            }),
            this.rowactions,
            {
                header: 'Receiver',
                dataIndex: 'bank_account_name',
                width: 120,
                sortable: true,
            },
            {
                header: 'BI Code',
                dataIndex: 'bank_bi_code',
                width: 100,
                sortable: true,
            },
            {
                header: 'Type',
                dataIndex: 'bank_transaction_type',
                width: 70,
                sortable: true,
            },
            {
                header: 'Bank Receiver',
                dataIndex: 'bank_no',
                width: 200,
                sortable: true,
                renderer: function(v,p,r) {
                    return v + " - " + r.data['bank_name'] + "<br>" + r.data['bank_branch'] + " " + r.data['bank_city'];
                }
            },
            {
                header: 'Trano',
                dataIndex: 'trano',
                width: 120,
                sortable: true
            }, {
                header: 'Ref Number',
                dataIndex: 'ref_number',
                width: 120,
                sortable: true
            }, {
                header: 'Valuta',
                dataIndex: 'val_kode',
                sortable: true
            }, {
                header: 'Total',
                dataIndex: 'total',
                sortable: true,
//                summaryType: 'sum',
                width: 150,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right'
            }
        ];

        var addButtons = new Ext.Button({
            iconCls: 'icon-add-new',
            text: 'Add New'
        });

        if (this.disableNewTrano == true)
            addButtons.disable();
        else
            addButtons.enable();

        addButtons.on('click', function(btn, e) {
            that.showPaymentForm();
        }, this);

        var printButtons = new Ext.Button({
            iconCls: 'silk-printer',
            text: 'Export to XLS'
        });

        printButtons.on('click', function(btn, e) {
            that.exportJson('XLS');
        }, this);

        var printCSVButtons = new Ext.Button({
            iconCls: 'silk-printer',
            text: 'Export to CSV'
        });

        printCSVButtons.on('click', function(btn, e) {
            that.exportJson('CSV');
        }, this);

        var clearButtons = new Ext.Button({
            iconCls: 'silk-delete',
            text: 'Clear All'
        });

        clearButtons.on('click', function(btn, e) {
            that.store.removeAll();
            that.dataAdd = null;
            that.dataPayment = null;
            that.paymentType = null;
        }, this);

        this.tbar = [
            addButtons,
            '-',
            printButtons,
            printCSVButtons,
            '-',
            clearButtons
        ];

        if (this.arrayData != undefined)
        {
            this.store.loadData(this.arrayData);
        }

        Ext.ux.grid.gridEbanking.superclass.initComponent.call(this);
    }
});

Ext.reg('gridEbanking', Ext.ux.grid.gridEbanking);

Ext.ux.form.EbankingSelector = Ext.extend(Ext.form.Field, {
    showItemWindow: function(t) {

        var url = '/finance/ebanking/get-bank-list';

        var proxy = new Ext.data.HttpProxy({
            url: url
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            root: 'data'
        }, [
            {name: 'bank_name'},
            {name: 'bank_branch'},
            {name: 'bank_city'},
            {name: 'clearing_code'},
            {name: 'rtgs_code'},
            {name: 'location_bank'},
        ]);

        var itemStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'ebankingselector-store'
        });
        itemStore.load();

        var bankNameText = new Ext.form.TextField({
            fieldLabel: 'Name',
//            enableKeyEvents: true
        });

        var bankBranchText = new Ext.form.TextField({
            fieldLabel: 'Branch',
//            enableKeyEvents: true
        });

//        bankNameText.on('keyup', function(field, e) {
//            var pname = field.getValue();
//            newUrl = url + '/bank_name/' + pname;
//            itemStore.proxy = new Ext.data.HttpProxy({
//                url: newUrl
//            });
//            itemStore.reload();
//            Ext.getCmp(this.id + '-grid').getView().refresh();
//        }, this);

        var bankCityText = new Ext.form.TextField({
            fieldLabel: 'City',
//            enableKeyEvents: true
        });

//        bankCityText.on('keyup', function(field, e) {
//            var pname = field.getValue();
//            newUrl = url + '/bank_city/' + pname;
//            itemStore.proxy = new Ext.data.HttpProxy({
//                url: newUrl
//            });
//            itemStore.reload();
//            Ext.getCmp(this.id + '-grid').getView().refresh();
//        }, this);

        var gridItem = new Ext.grid.GridPanel({
            store: itemStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                pageSize: 100,
                store: itemStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Clearing Code', width: 100, dataIndex: 'clearing_code', sortable: true},
                {header: 'Bank Name', width: 200, dataIndex: 'bank_name', sortable: true},
                {header: 'Branch', width: 200, dataIndex: 'bank_branch', sortable: true},
                {header: 'City', width: 200, dataIndex: 'bank_city', sortable: true},
                {header: 'Location of Receiving Bank', width: 200, dataIndex: 'location_bank', sortable: true},
            ]
        });

        gridItem.on('rowclick', function(g, rowIndex, e) {
            var rec = g.getStore().getAt(rowIndex);
            Ext.getCmp(this.Selectid).setValue(rec.get('clearing_code'));
            this.setClearingCode(rec.get('clearing_code'));
            this.setRtgsCode(rec.get('rtgs_code'));
            this.setDataRecord(rec);

            this.callback(rec);

            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
        {
            xtype: 'form',
            labelWidth: 120,
            frame: true,
            items: [

                {
                    layout: 'column',
                    frame: true,
                    items: [
                        {columnWidth: .55,
                            layout: 'form',
                            style: 'margin-right: 3px;',
                            items: [
                                bankNameText,
                                bankBranchText,
                                bankCityText,
                            ]
                        },
                        {columnWidth: .45,
                            layout: 'form',
                            style: 'margin-right: 3px;',
                            items: [
                                {
                                    xtype: 'button',
                                    text: 'Search',
                                    handler: function(){
                                        var name = bankNameText.getValue(),
                                            branch = bankBranchText.getValue(),
                                            city = bankCityText.getValue();
                                        newUrl = url + '/bank_name/' + name + '/bank_branch/' + branch + '/bank_city/' + city;
                                        itemStore.proxy = new Ext.data.HttpProxy({
                                            url: newUrl
                                        });
                                        itemStore.reload();
                                    }
                                },
                                {
                                    xtype: 'button',
                                    text: 'Reset',
                                    handler: function(){
                                        bankNameText.setValue('');
                                        bankBranchText.setValue('');
                                        bankCityText.setValue('');
                                    }
                                },
                            ]
                        }
                    ]
                },
                gridItem
            ]
        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 800,
            height: 420,
            title: 'Select Bank',
            items: forms
        });

        if (this.disabled !== true)
        {
            pwindow.show();
        }
    },
    onRender: function(ct, position) {

        this.clearingCode = '';
        this.rtgsCode = '';
        this.recordData = '';

        var select_id = this.Selectid;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.SelectWidth == undefined)
            this.SelectWidth = 80;

        if (this.callback == undefined)
            this.callback = function(rec){};

        if (!this.el) {
            this.selectItem = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.SelectWidth,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectItem.onTriggerClick = this.showItemWindow.createDelegate(this);

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
                    this.selectItem
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectItem]);

        }
        Ext.ux.form.EbankingSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.EbankingSelector.superclass.beforeDestroy.call(this);
    },
    clearData: function()
    {
        this.clearingCode = '';
        this.rtgsCode = '';
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue('');
    },
    getClearingCode: function()
    {
        return this.clearingCode;
    },
    getRtgsCode: function()
    {
        return this.rtgsCode;
    },
    setClearingCode: function(v)
    {
        this.clearingCode = v;
    },
    setDataRecord: function(rec)
    {
        this.recordData = rec;
    },
    getDataRecord: function()
    {
        return this.recordData;
    },
    setRtgsCode: function(v)
    {
        this.rtgsCode = v;
    },
    getValue: function()
    {
        return Ext.getCmp(this.Selectid).getValue();
    },
    setValue: function(v)
    {
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue(v);
    }


});

Ext.reg('ebankingselector', Ext.ux.form.EbankingSelector);