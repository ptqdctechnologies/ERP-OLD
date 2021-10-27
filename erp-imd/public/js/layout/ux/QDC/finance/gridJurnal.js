/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * Date: 3/12/12
 * Time: 8:50 AM
 * To change this template use File | Settings | File Templates.
 */

Ext.ns('Ext.ux.grid');

var Money = function(amount) {
    this.amount = amount;
};
Money.prototype.valueOf = function() {
    return Math.round(this.amount * 100) / 100;
};

Ext.ux.grid.gridJurnal = Ext.extend(Ext.grid.GridPanel, {
    initAction: function() {
        var rowactions = new Ext.ux.grid.RowActions({
            actions: [
                {
                    iconCls: 'icon-edit',
                    qtip: 'Edit',
                    id: 'edit'
                },
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

            if (action == 'icon-edit')
            {
                this.editor.startEditing(row, false);
            }
            else if (action == 'icon-delete')
            {
                Ext.MessageBox.confirm('Confirm', 'This action will delete this item, Proceed?',
                        function(btn)
                        {
                            if (btn == 'yes')
                            {
                                if (record && record.get('tipe') == '')
                                {
                                    grid.getStore().remove(record);
                                    grid.getView().refresh();
                                    this.summaryTotal();
                                }
                                else
                                {
                                    Ext.Msg.alert('Error', 'This item cannot be deleted!');
                                }
                            }
                        }, this
                        );
            }
        }, this);

        return rowactions;
    },
    initEditor: function() {
        var editor = new Ext.ux.grid.RowEditor({
            saveText: 'Update',
            clicksToEdit: ''
        });

//        editor.on('canceledit',function(ed,close){
//            ed.record.cancelEdit();
//            this.summaryTotal();
//        },this);

        editor.on('stopedit', function(ed, fields, rec) {
            if ((rec.data['debit'] == 0 && rec.data['credit'] == 0))
            {
                if (this.enableEditJurnal)
                {
                    Ext.each(fields, function(t, index) {
                        if (t.id == 'credit' || t.id == 'debit')
                        {
                            t.setValue('0');
                        }
                    });
                }
                else
                {
                    Ext.each(fields, function(t, index) {
                        if (t.id == 'credit' || t.id == 'debit')
                        {
                            t.markInvalid('Debit or Credit field must be greater than 0!');
                        }
                    });
                    return false;
                }
            }
            Ext.getCmp('coa-select').setValue('');
            Ext.getCmp('coa-select').setNameValue('');
        }, this);

        editor.on('afteredit', function(ed, obj, rec, index, fields) {
            var recs = this.store.getAt(index);
            var coaKode = Ext.getCmp('coa-select').getValue();
            if (coaKode != '')
            {
                var coaNama = Ext.getCmp('coa-select').getNameValue();
                recs.data['coa_kode'] = coaKode;
                if (coaNama != '')
                recs.data['coa_nama'] = coaNama;

                recs.data['urut'] = this.getUrutCOA();

                ed.record.commit();
                this.summaryTotal();
            }
            else
            {
                ed.record.reject();
                fields[3].markInvalid('COA Code is Blank');
                this.summaryTotal();
            }

        }, this);

        editor.on('filtercell', function(rowEditor, fields, record) {
            if (record.get('tipe') != '' && record.get('tipe') != undefined)
            {
                Ext.each(fields, function(t, index) {
                    if ((t.id == 'debit' || t.id == 'credit') && !this.setEnabledCoa)
                        t.disable();
                    if (t.id == 'ref_number_text' && this.hideRefNumber == false)
                        t.disable();
                }, this);
            }
            else
            {
                Ext.each(fields, function(t, index) {
                    t.enable();
                });
            }

            //Cek kalau jurnal sudah di close..
//            Ext.each(fields, function (t, index){
//                if (record.get('stsclose') == "1" || record.get('stsclose') == 1)
//                    t.disable();
//            },this);

//            var coa_kode = record.get('coa_kode');
//            if (coa_kode != '')
//                Ext.getCmp('coa-select').setValue(coa_kode);

        }, this);

        editor.on('canceledit', function(roweditor, forced) {
            if (forced) {
                var record = this.store.getAt(0);
                if ((record.get('coa_kode') == '' || record.get('coa_kode') == undefined) || ((record.get('debit') == 0 && record.get('credit') == 0)) && !this.enableEditJurnal) {
                    this.store.remove(record);
                    this.getView().refresh();
                    this.summaryTotal();
                }
            }
            roweditor.record.cancelEdit();
        }, this);

        return editor;
    },
    summaryTotal: function(cekBalance, returnValue) {
        var totDebit = 0;
        var totCredit = 0;

        this.store.clearFilter();
        this.store.each(function(items) {

            var debit = new Money(parseFloat(items.data['debit']).toFixed(2));
            var credit = new Money(parseFloat(items.data['credit']).toFixed(2));

            if (debit > 0)
            {
//                if (items.data['is_minus'] == false || items.data['is_minus'] != undefined)
//                    totDebit += ((-1) * debit);
//                else
                totDebit += debit;
            }
            if (credit > 0)
            {
//                if (items.data['is_minus'] == false || items.data['is_minus'] != undefined)
//                    totCredit += ((-1) * credit);
//                else
                totCredit += credit;
            }

        });
       totDebit = totDebit.toFixed(2);
       totCredit = totCredit.toFixed(2);
       
        var tots = this.setRounded(totDebit, totCredit);
        if (cekBalance != undefined && cekBalance !== false)
        {
            if (returnValue !== undefined && returnValue !== false)
                return tots.debit;
            else
                return this.moneyComp(tots.debit, '==', tots.credit, 2);
        }

        this.getBottomToolbar().get(0).setText('Total Debit : ' + Ext.util.Format.number(tots.debit, '0,0.00'));
        this.getBottomToolbar().get(1).setText('Total Credit : ' + Ext.util.Format.number(tots.credit, '0,0.00'));
    },
    setRounded: function(debit, credit)
    {
        var totDebit = new BigDecimal(debit.toString()).setScale(2, BigDecimal.prototype.ROUND_DOWN);
//        console.log(totDebit);
        var totCredit = new BigDecimal(credit.toString()).setScale(2, BigDecimal.prototype.ROUND_DOWN);
//        console.log(['DOWN',Ext.util.Format.number(totDebit, '0,0.00'),Ext.util.Format.number(totCredit, '0,0.00')]);

        if (this.moneyComp(totDebit, '==', totCredit, 2) === false)
        {
            totDebit = new BigDecimal(debit.toString()).setScale(2, BigDecimal.prototype.ROUND_CEILING);
            totCredit = new BigDecimal(credit.toString()).setScale(2, BigDecimal.prototype.ROUND_CEILING);

            if (this.moneyComp(totDebit, '==', totCredit, 2) === false)
            {
                totDebit = new BigDecimal(debit.toString()).setScale(2, BigDecimal.prototype.ROUND_UP);
                totCredit = new BigDecimal(credit.toString()).setScale(2, BigDecimal.prototype.ROUND_UP);

                return {
                    debit: totDebit,
                    credit: totCredit
                };
            }
            else
                return {
                    debit: totDebit,
                    credit: totCredit
                };
        }
        else
            return {
                debit: totDebit,
                credit: totCredit
            };
    },
    getUniqueRefNumber: function(jurnal)
    {
        if (!jurnal)
            return false;
        var refNumber = [];

        Array.prototype.getUnique = function() {
            var u = {}, a = [];
            for (var i = 0, l = this.length; i < l; ++i) {
                if (this[i] in u)
                    continue;
                a.push(this[i]);
                u[this[i]] = 1;
            }
            return a;
        };

        Ext.each(jurnal.data, function(items) {
            refNumber[refNumber.length] = items.ref_number;
        });

        var arrayRef = [];

        Ext.each(refNumber.getUnique(), function(items, index) {
            arrayRef[arrayRef.length] = [index, items];
        });

        return arrayRef;
    },
    getJSONFromStore: function(callback)
    {
        var jsonJurnal = '';

        this.store.each(function(store) {
            if (store.get("coa_kode") == null || store.get("coa_kode") == '')
            {
                this.store.remove(store);
            }
            var encode = Ext.util.JSON.encode(store.data);
            if (encode != undefined)
                jsonJurnal += encode + ',';
        }, this);
        jsonJurnal = '[' + jsonJurnal.substring(0, jsonJurnal.length - 1) + ']';

        if (callback != undefined)
        {
            Ext.Ajax.request({
                url: '/finance/jurnal/cek-jurnal',
                method: 'POST',
                params: {
                    jurnal: jsonJurnal
                },
                success: function(result, request) {
                    var returnData = Ext.util.JSON.decode(result.responseText);
                    if (returnData.success) {
                        callback(jsonJurnal);
                    }
                    else
                        Ext.Msg.alert('Error!', returnData.msg);
                },
                failure: function(action) {
                    if (action.failureType == 'server') {
                        obj = Ext.util.JSON.decode(action.response.responseText);
                        Ext.Msg.alert('Error!', obj.errors.reason);
                    }
                },
                scope: this
            });
        }
        else
        {
            if (this.summaryTotal(true) === true)
            {
                return jsonJurnal;
            }
            else
            {
                Ext.MessageBox.show({
                    title: 'Error',
                    msg: 'Debit & Credit is not Balance yet!',
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
                return false;
            }
        }
    },
    checkClosedJournal: function(obj)
    {
        var closedJurnal = {data: []};
        var isCLosed = false;

        if (this.summaryTotal(true) === true)
        {
            Ext.each(obj.data, function(items, index) {
                if (parseFloat(items.stspost) == 1 || parseFloat(items.stsclose) == 1)
                {
                    items.tipe = '';
                    items.stspost = 0;
                    items.stsclose = 0;
                    if (parseFloat(items.credit) > 0)
                    {
                        items.debit = items.credit;
                        items.credit = 0;
                    }
                    else
                    {
                        items.credit = items.debit;
                        items.debit = 0;
                    }
                    closedJurnal.data[closedJurnal.data.length] = items;
                }
            });
            if (closedJurnal.data.length > 0)
            {
                showWindowJurnalAdj({
                    closedJournalFunction: this.closedJournalFunction
                }, closedJurnal);
            }
            else
            {
                this.closedJournalFunction();
            }
        }
        else
        {
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Debit & Credit is not Balance yet!',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
            return false;
        }
    },
    getStore: function()
    {
        return this.store;
    },
    getCoa: function(coa, addInfo)
    {
        if (!coa)
            return false;

        coa = Ext.util.JSON.encode(coa);
        addInfos = Ext.util.JSON.encode(addInfo);

        Ext.Ajax.request({
            url: '/finance/coa/getcoatransaction',
            method: 'POST',
            params: {
                coa: coa,
                addInfo: addInfos
            },
            success: function(result, request) {
                var returnData = Ext.util.JSON.decode(result.responseText);
                if (returnData.success) {

                    var coas = returnData.data;
                    Ext.each(coas, function(items)
                    {
                        if (items.urut != undefined || items.urut != '')
                            var urut = items.urut;
                        else
                            var urut = this.getUrutCOA();

                        var coaInsert,
                                debit = 0,
                                credit = 0,
                                isMinus = false;

                        if (items.side == 'debit')
                            debit = parseFloat(items.value);
                        else
                            credit = parseFloat(items.value);

//                        if (items.side != items.dk.toLowerCase())
//                            isMinus = true;

                        if (!addInfo)
                        {
                            addInfo = {
                                prj_kode: '',
                                sit_kode: '',
                                trano: '',
                                ref_number: '',
                                ref_number2: '',
                                ket: '',
                                val_kode: '',
                                rateidr: '',
                                job_number: '',
                                stspost: '',
                                stsclose: '',
                                tipe_jurnal: '',
                                memo: '',
                                memo_id: '',
                                status_doc_rpc: '',
                                status_doc_cip: ''

                            }
                        }

                        addInfo.memo = '';
                        addInfo.memo_id = '';

                        if (items.memo != undefined || items.memo != null)
                        {
                            addInfo.memo = items.memo;
                        }
                        if (items.memo_id != undefined || items.memo_id != null)
                        {
                            addInfo.memo_id = items.memo_id;
                        }

                        coaInsert = {
                            coa_kode: items.coa_kode,
                            coa_nama: items.coa_nama,
                            debit: debit,
                            credit: credit,
                            val_kode: addInfo.val_kode,
                            tipe: items.tipe,
                            tipe_jurnal: addInfo.tipe_jurnal,
                            urut: urut,
                            prj_kode: addInfo.prj_kode,
                            sit_kode: addInfo.sit_kode,
                            job_number: addInfo.job_number,
                            trano: addInfo.trano,
                            ref_number: addInfo.ref_number,
                            ref_number2: addInfo.ref_number2,
                            stspost: addInfo.stspost,
                            stsclose: addInfo.stsclose,
                            ket: addInfo.ket,
                            rateidr: addInfo.rateidr,
                            memo: addInfo.memo,
                            memo_id: addInfo.memo_id,
                            status_doc_rpc: addInfo.status_doc_rpc,
                            status_doc_cip: addInfo.status_doc_cip
                        };
                        this.insertCOA(coaInsert);
                    }, this);
                }
                else
                    Ext.Msg.alert('Error!', returnData.msg);
            },
            failure: function(action) {
                if (action.failureType == 'server') {
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                }
            },
            scope: this
        });
    },
    getUrutCOA: function()
    {
        var last = this.store.getCount();
        return (last + 1);
    },
    insertCOA: function(coaObj)
    {
        if (coaObj != undefined)
        {
            if ((coaObj.debit == 0 && coaObj.credit == 0) || (coaObj.debit == '' && coaObj.credit == ''))
            {
                return false;
            }

            var e = new this.recordJurnal({
                coa_kode: coaObj.coa_kode,
                coa_nama: coaObj.coa_nama,
                debit: coaObj.debit,
                credit: coaObj.credit,
                val_kode: coaObj.val_kode,
                tipe: coaObj.tipe,
                tipe_jurnal: coaObj.tipe_jurnal,
                urut: coaObj.urut,
                prj_kode: coaObj.prj_kode,
                sit_kode: coaObj.sit_kode,
                job_number: coaObj.job_number,
                trano: coaObj.trano,
                ref_number: coaObj.ref_number,
                ref_number2: coaObj.ref_number2,
                stspost: coaObj.stspost,
                stsclose: coaObj.stsclose,
                ket: coaObj.ket,
                rateidr: coaObj.rateidr,
                is_minus: false,
                memo: coaObj.memo,
                memo_id: coaObj.memo_id,
                status_doc_rpc: coaObj.status_doc_rpc,
                status_doc_cip: coaObj.status_doc_cip
            });
            this.store.add(e);
            this.getView().refresh();
            this.summaryTotal();
            this.store.sort('urut', 'ASC');
        }
    },
    removeAll: function()
    {
        this.store.removeAll();
        this.summaryTotal();
    },
    getCount: function()
    {
        return this.store.getCount();
    },
    loadData: function(jsonCoa)
    {
        if (!jsonCoa)
            return false;

        this.store.removeAll();
        this.store.loadData(jsonCoa);
        this.getView().refresh();
        this.summaryTotal();
    },
    updateCOA: function(coaObj, urut)
    {
        if (!coaObj)
            return false;

        var rows = this.store.queryBy(function(record, id) {
            return record.get('urut') == urut;
        });

        if (rows.length != 0)
        {

            Ext.each(rows.items, function(items) {
                this.store.remove(items);
            }, this);

            this.getCoa(coaObj);

        }

        this.getView().refresh();
    },
    setClosedJournalFunction: function(fn)
    {
        this.closedJournalFunction = fn;
    },
    moneyComp: function(a, comp, b, decimals)
    {
        if (!decimals)
            decimals = 2;
//        var multiplier = Math.pow(10,decimals);
//        a = Math.round(a * multiplier); // multiply to do integer comparison instead of floating point
//        b = Math.round(b * multiplier);

        a = new BigDecimal(a.toString()).setScale(decimals, BigDecimal.prototype.ROUND_CEILING);
        b = new BigDecimal(b.toString()).setScale(decimals, BigDecimal.prototype.ROUND_CEILING);
        switch (comp) {
            case ">":
//                return (a > b);
                return a.isGreaterThan(b);
            case ">=":
//                return (a >= b);
                return a.isGreaterThanOrEqualTo(b);
            case "<":
//                return (a < b);
                return a.isLessThan(b);
            case "<=":
//                return (a <= b);
                return a.isLessThanOrEqualTo(b);
            case "==":
//                return (a == b);
                return (a.compareTo(b) == 0) ? true : false;
        }
        return null;
    },
    compareValueToJournal: function(value)
    {
        var journalValue = this.summaryTotal(true, true);
        return this.moneyComp(value, '==', journalValue);
    },
    recordJurnal: new Ext.data.Record.create([
        {name: 'coa_kode'},
        {name: 'coa_nama'},
        {name: 'debit'},
        {name: 'credit'},
        {name: 'val_kode'},
        {name: 'tipe'},
        {name: 'urut'},
        {name: 'prj_kode'},
        {name: 'sit_kode'},
        {name: 'job_number'},
        {name: 'trano'},
        {name: 'ref_number'},
        {name: 'ref_number2'},
        {name: 'stspost'},
        {name: 'stsclose'},
        {name: 'ket'},
        {name: 'rateidr'},
        {name: 'tipe_jurnal'},
        {name: 'memo'},
        {name: 'memo_id'},
        {name: 'status_doc_rpc'},
        {name: 'status_doc_cip'}

//        {name:'is_minus'},
    ]),
    printJurnal: function()
    {

        Ext.ux.Printer.print({
            component: this,
            printTitle: this.printTitle,
            additionalHTML: this.additionalHTML
        });
    },
    showSaveWindow: function() {

        var recordSave = new Ext.data.Record.create([
            {name: 'id_save'},
            {name: 'date'},
            {name: 'json'}
        ]);

        var saveStore = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root: 'data',
                fields: recordSave
            })
        });

        var func = this.showSavePreview;
        var callbackPreview = function(record) {
            func(record);
        };

        var rowactions = new Ext.ux.grid.RowActions({
            hideMode: "display",
            actions: [
                {
                    iconCls: 'silk-delete',
                    qtip: 'Delete',
                    id: 'delete',
                    callback: function(grid, record, action, row, col)
                    {
                        var rec = record;
                        Ext.MessageBox.confirm('Confirm', 'Delete this item?',
                                function(btn) {
                                    if (btn == 'yes')
                                    {
                                        var id = rec.get("id_save");
                                        var indeks = jurnalCollection.findIndexBy(function(items) {
                                            return (items.id_save == id);
                                        });
                                        if (indeks >= 0)
                                        {
                                            jurnalCollection.removeAt(indeks);
                                        }
                                        saveStore.removeAt(row);
                                    }
                                }
                        );

                    }
                },
                {
                    iconCls: 'silk-magnifier',
                    qtip: 'Preview',
                    callback: function(grid, record, action, row, col)
                    {
                        callbackPreview(record);
                    }
                }
            ],
            header: '',
            width: 20
        }, this);

        if (jurnalCollection.getCount() > 0)
        {
            jurnalCollection.each(function(item) {
                var a = new recordSave({
                    id_save: item.id_save,
                    date: item.date,
                    json: item.json
                });

                saveStore.add(a);
            });
        }

        var loadButton = new Ext.Button({
            iconCls: 'icon-go',
            text: 'Load this Clipboard'
        });

        loadButton.on('click', function() {
            var choose = grid.getSelectionModel().getSelections();
            if (choose.length > 0)
            {
                var theData = choose[0];
                this.loadData(Ext.util.JSON.decode(theData.data['json']));

            }
        }, this);

        var grid = new Ext.grid.GridPanel({
            id: 'save-grid',
            store: saveStore,
            width: 300,
            height: 200,
            layout: 'fit',
            plugins: [rowactions],
            viewConfig: {
                singleSelect: true,
                forceFit: true
            },
            columns: [
                new Ext.grid.RowNumberer(),
                rowactions,
                {
                    header: 'Date',
                    dataIndex: 'date',
                    width: 150,
                    sortable: true,
                    renderer: function(v, p, r) {
                        return (v) ? Ext.util.Format.date(v, 'd/M/y H:i:s') : '';
                    }
                }
            ]
        });

        var callbackRead = function(data) {
            var theData = Ext.util.JSON.decode(data);
            saveStore.loadData(theData);
            jurnalCollection.clear();

            Ext.each(theData.data, function(item) {
                jurnalCollection.add(item);
            });
        };

        var uploadButton = new Ext.Button({
            text: 'Upload',
            iconCls: 'icon-arrow-up'
        }, this);

        uploadButton.on('click', function() {
            if (formUpload.getForm().isValid()) {
                formUpload.getForm().submit({
                    url: '/default/file/upload',
                    params: {
                        type: 'JURNAL'
                    },
                    scope: this,
                    waitMsg: 'Uploading file...',
                    success: function(form, action) {
                        var returnData = action.result;
                        if (returnData.success) {
                            var params = {
                                filename: returnData.savename
                            };
                            Ext.Ajax.request({
                                url: '/finance/jurnal/load-jurnal-from-file',
                                params: params,
                                method: 'POST',
                                scope: this,
                                success: function(resp) {
                                    var returnData = Ext.util.JSON.decode(resp.responseText);
                                    if (returnData.success)
                                    {
                                        callbackRead(returnData.data);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('Error', returnData.msg);
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
                        else
                        {
                            Ext.Msg.alert('Error', returnData.msg);
                        }
                    },
                    failure: function(form, action) {
                        if (action.result.msg != undefined)
                            Ext.Msg.alert('Error', action.result.msg);
                    }
                });
            }
        });
        var formUpload = new Ext.form.FormPanel({
            id: 'upload-form-json',
            frame: true,
            height: 60,
            hidden: true,
            fileUpload: true,
            items: [
                {
                    xtype: 'compositefield',
                    fieldLabel: 'Upload File',
                    msgTarget: 'under',
                    anchor: '-20',
                    defaults: {
                        flex: 1
                    },
                    items: [
                        {
                            xtype: 'fileuploadfield',
                            emptyText: 'Select a File',
                            allowBlank: false,
                            buttonOnly: true,
                            name: 'file-path',
                            width: 25,
                            buttonCfg: {
                                iconCls: 'upload-icon'
                            }
                        },
                        uploadButton,
//                        {
//                            xtype: 'button',
//                            text: 'Upload',
//                            iconCls: 'icon-arrow-up',
//                            handler: function()
//                            {
//
//                            }
//                        },
                        {
                            xtype: 'button',
                            text: 'Cancel',
                            iconCls: 'icon-delete',
                            handler: function()
                            {
                                formUpload.setVisible(false);
                            }
                        }
                    ]
                }
            ]
        });

        var windowSave = new Ext.Window({
            id: 'save-window',
            modal: true,
//            height: 260,
            autoHeight: true,
            width: 310,
            title: 'Clipboard',
            resizable: false,
            stateful: false,
            closeAction: 'hide',
            closeable: false,
            buttons: [
                {
                    text: 'Close',
                    handler: function()
                    {
                        windowSave.close();
                    }
                }
            ],
            items: [
                formUpload,
                grid
            ],
            tbar: [
                loadButton,
                '-',
                {
                    text: 'File',
                    iconCls: 'menu-drop',
                    menu: {
                        xtype: 'menu',
                        plain: true,
                        items: [
                            {
                                iconCls: 'icon-save',
                                text: 'Save As File',
                                scope: this,
                                handler: function()
                                {
                                    var choose = grid.getSelectionModel().getSelections();
                                    if (choose.length > 0)
                                    {
                                        var theData = choose[0];
                                        var indeks = jurnalCollection.findIndexBy(function(items) {
                                            return (items.id_save == theData.data['id_save']);
                                        });
                                        var json = {};
                                        if (indeks >= 0)
                                        {
                                            var theItem = jurnalCollection.itemAt(indeks);
                                            json = {
                                                data: [{
                                                        id_save: theItem.id_save,
                                                        date: theItem.date,
                                                        json: theItem.json
                                                    }]
                                            };
                                        }

                                        var params = {
                                            json: Ext.util.JSON.encode(json)
                                        };
                                        Ext.Ajax.request({
                                            url: '/finance/jurnal/save-jurnal-as-file',
                                            params: params,
                                            method: 'POST',
                                            success: function(resp) {
                                                var returnData = Ext.util.JSON.decode(resp.responseText);
                                                if (returnData.success)
                                                {
                                                    location.href = '/default/file/download/filename/' + returnData.filename;
                                                }
                                                else
                                                {
                                                    Ext.Msg.alert('Error', returnData.msg);
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
                                        }, this);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('Error', 'Please choose clipboard from list below.');
                                    }
                                }
                            },
                            {
                                iconCls: 'icon-attach',
                                text: 'Load From File',
                                scope: this,
                                handler: function()
                                {
                                    Ext.getCmp('upload-form-json').setVisible(true);
                                }
                            }
                        ]
                    }
                }
            ]
        });

        windowSave.show();
    },
    showSavePreview: function(data) {

        if (Ext.getCmp('save-preview-window'))
        {
            Ext.getCmp('save-preview-window').close();
        }

        var saved = new Ext.data.Record.create([
            {name: 'coa_kode'},
            {name: 'coa_nama'},
            {name: 'debit'},
            {name: 'credit'},
            {name: 'val_kode'},
            {name: 'tipe'},
            {name: 'urut'},
            {name: 'prj_kode'},
            {name: 'sit_kode'},
            {name: 'job_number'},
            {name: 'trano'},
            {name: 'ref_number'},
            {name: 'ref_number2'},
            {name: 'stspost'},
            {name: 'stsclose'},
            {name: 'ket'},
            {name: 'rateidr'},
            {name: 'tipe_jurnal'},
            {name: 'status_doc_rpc'},
            {name: 'status_doc_cip'}
        ]);

        var store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root: 'data',
                fields: saved
            })
        });

        var json = data.data['json'];
        if (json != '')
        {
            var loadData = Ext.util.JSON.decode(json);
            store.loadData(loadData);
        }
        var columns = [
            new Ext.grid.RowNumberer({
                width: 30
            }),
            {
                header: 'Project',
                dataIndex: 'prj_kode',
                width: 70
            },
            {
                header: 'Site',
                dataIndex: 'sit_kode',
                width: 60
            },
            {
                header: 'Ref Number',
                dataIndex: 'ref_number',
                width: 100
            },
            {
                header: 'COA Code',
                dataIndex: 'coa_kode',
                sortable: true,
                width: 60
            }, {
                header: 'COA Name',
                dataIndex: 'coa_nama',
                sortable: true
            }, {
                header: 'Debit',
                dataIndex: 'debit',
                sortable: true,
                summaryType: 'sum',
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right'
            }, {
                header: 'Credit',
                dataIndex: 'credit',
                sortable: true,
                summaryType: 'sum',
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right'
            }, {
                header: 'Job Number',
                dataIndex: 'job_number',
                sortable: true

            }, {
                header: 'Is CIP ?',
                dataIndex: 'status_doc_cip',
                sortable: true, renderer: function(v) {
                    return v == '1' ? 'No' : 'CIP';
            }
            }, {
                header: 'Is RPC ?',
                dataIndex: 'status_doc_rpc',
                sortable: true, renderer: function(v) {
                    return v == '1' ? 'No' : 'RPC';
                }
            }
        ];

        var grid = new Ext.grid.GridPanel({
            id: 'save-preview-grid',
            store: store,
            width: 600,
            height: 300,
            layout: 'fit',
            columns: columns,
            viewConfig: {
                forceFit: true
            }
        });

        var windowSavePreview = new Ext.Window({
            id: 'save-preview-window',
            modal: true,
            height: 340,
            width: 610,
            title: 'Clipboard Preview',
            resizable: false,
            stateful: false,
            closeAction: 'hide',
            closeable: false,
            buttons: [
                {
                    text: 'Close',
                    handler: function()
                    {
                        windowSavePreview.close();
                    }
                }
            ],
            items: [
                grid
            ]
        });

        windowSavePreview.show();
    },
    findByRefNumber: function(ref_number)
    {
        if (ref_number == undefined || ref_number == null)
            return false;

        return (this.store.find('ref_number', ref_number) != -1);
    },
    initComponent: function() {

        var summary = new Ext.grid.GroupSummary();

        this.store = new Ext.data.Store({
//        this.store = new Ext.data.GroupingStore ({
            reader: new Ext.data.JsonReader({
                root: 'data',
                totalProperty: 'total',
                fields: this.recordJurnal
            }),
            sortInfo: {field: 'urut', direction: "ASC"},
//            groupField: 'ref_number'
        });

//        this.view = new Ext.grid.GroupingView({
//            forceFit: true,
//            showGroupName: false,
//            enableNoGroups: false,
//            enableGroupingMenu: false,
//            hideGroupedColumn: true
//        });

        if (this.disableNewJournal == undefined)
            this.disableNewJournal = false;

        if (this.setEnabledCoa != undefined)
            this.setEnabledCoa = true;
        else
            this.setEnabledCoa = false;

        if (this.enableEditJurnal == undefined)
            this.enableEditJurnal = true;

        this.editor = this.initEditor();
        this.rowactions = this.initAction();
//        this.plugins = [this.editor, this.rowactions, summary];
        if (this.enableEditJurnal)
            this.plugins = [this.editor, this.rowactions];
        if (this.refNumberArray != undefined)
        {
            this.refNumberArray = this.getUniqueRefNumber(this.refNumberArray);

            var ref =
                    {
                        header: 'Ref Number',
                        dataIndex: 'ref_number',
                        editor: {
                            xtype: 'combo',
                            id: 'ref_number_text',
                            store: new Ext.data.SimpleStore({
                                fields: ['id', 'ref_number'],
                                data: this.refNumberArray

                            }),
                            valueField: 'ref_number',
                            displayField: 'ref_number',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus: true,
                            emptyText: 'Select Type',
                            listeners: {
                                'disable': function(txttext)
                                {
                                    txttext.getEl().addClass('text-tebal');
                                }
                            }
                        }
                    };
        }
        else
        {
            var ref =
                    {
                        header: 'Ref Number',
                        dataIndex: 'ref_number',
                        editor: {
                            xtype: 'textfield',
                            id: 'ref_number_text',
                            listeners: {
                                'disable': function(txttext)
                                {
                                    txttext.getEl().addClass('text-tebal');
                                }
                            }
                        }
                    };
        }


        this.columns = [
            new Ext.grid.RowNumberer({
                width: 30
            }),
//            this.rowactions,
            ref,
            {
                header: 'COA Code',
                dataIndex: 'coa_kode',
                sortable: true,
                editor: {
                    xtype: 'coaselector',
                    id: 'coa-select',
                    Selectid: 'coa_kode_text',
                    Nameid: 'coa_nama_text',
                    showName: false,
                    editorInstance: this.editor,
                    SelectWidth: 100
                }
            }, {
                header: 'COA Name',
                dataIndex: 'coa_nama',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'coa_nama_text',
                    readOnly: true
                }
            }, {
                header: 'Debit',
                dataIndex: 'debit',
                sortable: true,
                summaryType: 'sum',
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right',
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowNegative: false,
                    id: 'debit',
//                    minValue: 0,
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function(txttext, event)
                        {
                            var debit = parseFloat(txttext.getValue().toString().replace(/\$|\,/g, ''));
                            if (debit > 0)
                            {
                                Ext.getCmp('credit').setValue(0);
                            }
                        },
                        'disable': function(txttext)
                        {
                            txttext.getEl().addClass('text-tebal');
                        }
                    }
                }
            }, {
                header: 'Credit',
                dataIndex: 'credit',
                sortable: true,
                summaryType: 'sum',
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right',
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowNegative: false,
                    id: 'credit',
//                    minValue: 0,
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function(txttext, event)
                        {
                            var credit = parseFloat(txttext.getValue().toString().replace(/\$|\,/g, ''));
                            if (credit > 0)
                            {
                                Ext.getCmp('debit').setValue(0);
                            }
                        },
                        'disable': function(txttext)
                        {
                            txttext.getEl().addClass('text-tebal');
                        }
                    }
                }
            }, {
                header: 'Job Number',
                width: 100,
                dataIndex: 'job_number',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'job_number_text'
                },
                hidden: true
            }, {
                header: 'Memo',
                width: 150,
                dataIndex: 'memo',
                sortable: true,
                editor: {
                    xtype: 'textfield',
                    id: 'memo_text'
                },
                hidden: true

            }, {
                header: 'Is CIP',
                width: 125,
                dataIndex: 'status_doc_cip',
                sortable: true,
                editor: {
                    xtype: 'combo'
                    , editable: false
                    , forceSelection: true
                    , store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['1', 'No'],
                            ['2', 'CIP']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true, 
                    id: 'status_doc_cip'
                },
//                hidden: true,
                renderer: function(v) {
                    return v == '1' ? 'No' : 'CIP';
            }
            },
            {
                header: 'Is RPC',
                width: 125,
                dataIndex: 'status_doc_rpc',
                sortable: true,
                editor: {
                    xtype: 'combo'
                    , editable: false
                    , forceSelection: true
                    , store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['1', 'No'],
                            ['2', 'RPC']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true, 
                    id: 'status_doc_rpc'
                },
//                hidden: true,
                renderer: function(v) {
                    return v == '1' ? 'No' : 'RPC';
                }
            }
        ];

        if (this.enableEditJurnal)
            this.columns.splice(1, 0, this.rowactions);

        if (this.hideRefNumber == true)
        {
            Ext.each(this.columns, function(col) {
                if (col.dataIndex == 'ref_number')
                    col.hidden = true;
            });
        }

        if (this.showJobNumber == undefined)
            this.showJobNumber = false;

        if (this.showJobNumber == true)
        {
            Ext.each(this.columns, function(col) {
                if (col.dataIndex == 'job_number')
                    col.hidden = false;
            });
        }

        if (this.showStatusDocCip == undefined) {
//            this.showStatusDocCip = false;
//        if (this.showStatusDocCip == true)

            Ext.each(this.columns, function(col) {
                if (col.dataIndex == 'status_doc_cip')
                    col.hidden = true;
            });
        }

        if (this.showStatusDocRpc == undefined) {
//            this.showStatusDocRpc = false;
//        if (this.showStatusDocRpc == true)

            Ext.each(this.columns, function(col) {
                if (col.dataIndex == 'status_doc_rpc')
                    col.hidden = true;
            });
        }

        if (this.showMemo == undefined)
            this.showMemo = false;

        if (this.showMemo == true)
        {
            Ext.each(this.columns, function(col) {
                if (col.dataIndex == 'memo')
                    col.hidden = false;
            });
        }
        var addButtons = new Ext.Button({
            iconCls: 'icon-add-new',
            text: 'Add New Journal'
        });

        if (this.disableNewJournal == true)
            addButtons.disable();
        else
            addButtons.enable();

        addButtons.on('click', function(btn, e) {
            var e = new this.recordJurnal({
                coa_kode: '',
                coa_nama: '',
                debit: 0,
                credit: 0,
                val_kode: 'IDR',
                tipe: ''
            });
            this.editor.stopEditing();
            this.store.insert(0, e);
            this.getView().refresh();
            this.getSelectionModel().selectRow(0);
            this.editor.startEditing(0);
        }, this);

        var printButtons = new Ext.Button({
            iconCls: 'silk-printer',
            text: 'Print This Journal'
        });

        printButtons.on('click', function(btn, e) {
            this.printJurnal();
        }, this);

        this.tbar = [
            addButtons,
            '-',
            printButtons,
            '->',
            {
                text: 'Clipboard',
                iconCls: 'menu-drop',
                menu: {
                    xtype: 'menu',
                    plain: true,
                    items: [
                        {
                            iconCls: 'icon-go',
                            text: 'Open Clipboard',
                            scope: this,
                            handler: function()
                            {
                                this.showSaveWindow();
                            }
                        },
                        {
                            iconCls: 'icon-save',
                            text: 'Save Journal To Clipboard',
                            handler: function(btn, e)
                            {
                                var jsonJurnal = '';
                                if (this.store.getCount() == 0)
                                {
                                    Ext.Msg.alert('Error', 'Journal still empty');
                                    return false;
                                }
                                this.store.each(function(store) {
                                    if (store.get("coa_kode") == null || store.get("coa_kode") == '')
                                    {
                                        this.store.remove(store);
                                    }
                                    var encode = Ext.util.JSON.encode(store.data);
                                    if (encode != undefined)
                                        jsonJurnal += encode + ',';
                                }, this);
                                jsonJurnal = '{data : [' + jsonJurnal.substring(0, jsonJurnal.length - 1) + ']}';
                                var hash = Ext.util.MD5(jsonJurnal);

                                var uniq = (new Date()).getTime() + Math.floor((Math.random() * 25) + 65);
                                var col = {
                                    id_save: uniq,
                                    date: new Date(),
                                    json: jsonJurnal
                                };
                                if (jurnalCollection.getCount() > 0)
                                {
                                    var indeks = jurnalCollection.findIndexBy(function(items) {
                                        return (Ext.util.MD5(items.json) == hash);
                                    });
                                    if (indeks < 0)
                                    {
                                        jurnalCollection.add(col);
                                    }
                                }
                                else
                                    jurnalCollection.add(col);

                                this.showSaveWindow();
                            },
                            scope: this
                        }
                    ]
                }
            }
        ];

        this.bbar = new Ext.Toolbar({
            id: 'total-bbar',
            style: "text-align:right",
            items: [{
                    xtype: 'label',
                    ref: '../debitTotal',
                    style: 'color:red;font-weight:bold;margin-right:20px;font-size:12'
                },
                {
                    xtype: 'label',
                    ref: '../creditTotal',
                    style: 'color:red;font-weight:bold;font-size:12;margin-right:10px'
                }],
            layout: 'fit'
        });

        if (this.arrayJurnal != undefined)
        {
            this.store.loadData(this.arrayJurnal);
        }

        Ext.ux.grid.gridJurnal.superclass.initComponent.call(this);
    }

});

Ext.reg('gridJurnal', Ext.ux.grid.gridJurnal);