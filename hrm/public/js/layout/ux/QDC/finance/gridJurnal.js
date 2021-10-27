/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * Date: 3/12/12
 * Time: 8:50 AM
 * To change this template use File | Settings | File Templates.
 */

Ext.ns('Ext.ux.grid');

Ext.ux.grid.gridJurnal = Ext.extend(Ext.grid.GridPanel,  {
    initAction: function(){
        var rowactions = new Ext.ux.grid.RowActions({
            actions:[
                {
                    iconCls:'icon-edit',
                    qtip:'Edit',
                    id: 'edit'
                },
                {
                    iconCls:'icon-delete',
                    qtip:'Delete',
                    id: 'edit'
                }
            ]
            ,index: 'actions'
            ,header: ''
        });

        rowactions.on('action',function(grid,record,action,row,col){

            if(action == 'icon-edit')
            {
                this.editor.startEditing(row,false);
            }
            else if(action == 'icon-delete')
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
                            Ext.Msg.alert('Error','This item cannot be deleted!');
                        }
                    }
                },this
                );
            }
        },this);

        return rowactions;
    },

    initEditor: function(){
        var editor = new Ext.ux.grid.RowEditor({
            saveText: 'Update',
            clicksToEdit: 1
        });

        editor.on('canceledit',function(ed,close){
            ed.record.cancelEdit();
        },this);

        editor.on('stopedit', function(ed,fields,rec) {
            if (rec.data['debit'] == 0 && rec.data['credit'] == 0)
            {
                Ext.each(fields, function (t, index){
                    if (t.id == 'credit' || t.id == 'debit')
                    {
                        t.markInvalid('Debit or Credit field must be greater than 0!');
                    }
                });
                return false;
            }
            Ext.getCmp('coa_kode_text').setValue('');
        },this);

        editor.on('afteredit', function(ed,obj,rec,index){
            var recs = this.store.getAt(index);
            var coaKode = Ext.getCmp('coa_kode_text').getValue();
            if (coaKode != '')
                recs.data['coa_kode'] = coaKode;

            recs.data['urut'] = this.getUrutCOA();

            ed.record.commit();
            this.summaryTotal();

        },this);

        editor.on('filtercell', function(rowEditor, fields, record){
            if (record.get('tipe') != '')
            {
                Ext.each(fields, function (t, index){
                    if ((t.id == 'debit' || t.id == 'credit') && !this.setEnabledCoa)
                        t.disable();
                    if (t.id == 'ref_number_text' && this.hideRefNumber == false)
                        t.disable();
                },this);
            }
            else
            {
                Ext.each(fields, function (t, index){
                    t.enable();
                });
            }

            //Cek kalau jurnal sudah di close..
            Ext.each(fields, function (t, index){
                if (record.get('stsclose') == "1" || record.get('stsclose') == 1)
                    t.disable();
            },this);

            var coa_kode = record.get('coa_kode');
            if (coa_kode != '')
                Ext.getCmp('coa_kode_text').setValue(coa_kode);

        },this);

        editor.on('canceledit', function(roweditor, forced){
            if(forced){
                var record = this.store.getAt(0);
                if(record.get('coa_kode') == '' || (record.get('debit') == 0 && record.get('credit') == 0)){
                    this.store.remove(record);
                    this.getView().refresh();
                }
            }
        },this);

        return editor;
    },

    summaryTotal: function(cekBalance,returnValue) {
        var totDebit = 0;
        var totCredit = 0;

        this.store.each(function(items){

            var debit = parseFloat(items.data['debit']);
            var credit = parseFloat(items.data['credit']);

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

        if(cekBalance != undefined && cekBalance !== false)
        {
            if (returnValue !== undefined && returnValue !== false)
                return totDebit;
            else
                return moneycomp(totDebit,'==',totCredit,2);
        }

        this.getBottomToolbar().get(0).setText('Total Debit : ' + Ext.util.Format.number(totDebit, '0,0.00'));
        this.getBottomToolbar().get(1).setText('Total Credit : ' + Ext.util.Format.number(totCredit, '0,0.00'));
    },

    getUniqueRefNumber: function(jurnal)
    {
        if (!jurnal)
           return false;
        var refNumber = [];

        Array.prototype.getUnique = function(){
           var u = {}, a = [];
           for(var i = 0, l = this.length; i < l; ++i){
              if(this[i] in u)
                 continue;
              a.push(this[i]);
              u[this[i]] = 1;
           }
           return a;
        };

        Ext.each(jurnal.data,function(items){
            refNumber[refNumber.length] = items.ref_number;
        });

        var arrayRef = [];

        Ext.each(refNumber.getUnique(),function(items,index){
            arrayRef[arrayRef.length] = [index,items];
        });

        return arrayRef;
    },

    getJSONFromStore: function()
    {
        var jsonJurnal = '';
        if (this.summaryTotal(true) === true)
        {
            this.store.each(function(store){
                var encode = Ext.util.JSON.encode(store.data);
                if (encode != undefined)
                    jsonJurnal += encode + ',';
            });
            jsonJurnal = '[' + jsonJurnal.substring(0, jsonJurnal.length - 1) + ']';

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
    },

    checkClosedJournal: function(obj)
    {
        var closedJurnal = {data: []};
        var isCLosed = false;

        if (this.summaryTotal(true) === true)
        {
            Ext.each(obj.data,function(items,index){
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
                },closedJurnal);
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

    getCoa: function(coa,addInfo)
    {
        if (!coa)
         return false;

        coa = Ext.util.JSON.encode(coa);
        addInfos = Ext.util.JSON.encode(addInfo);

        Ext.Ajax.request({
            url: '/finance/coa/getcoatransaction',
            method:'POST',
            params: {
                coa: coa,
                addInfo: addInfos
            },
            success: function(result, request){
                var returnData = Ext.util.JSON.decode(result.responseText);
                if(returnData.success) {

                    var coas = returnData.data;
                    Ext.each(coas,function(items)
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
                            }
                        }

                        coaInsert = {
                            coa_kode: items.coa_kode,
                            coa_nama: items.coa_nama,
                            debit: debit,
                            credit: credit,
                            tipe: items.tipe,
                            prj_kode: addInfo.prj_kode,
                            sit_kode: addInfo.sit_kode,
                            trano: addInfo.trano,
                            ref_number: addInfo.ref_number,
                            urut: urut,
//                            is_minus: isMinus
                        };
                        this.insertCOA(coaInsert);
                    },this);
                }
                else
                    Ext.Msg.alert('Error!',returnData.msg);
            },
            failure:function( action){
                if(action.failureType == 'server'){
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
        return (last+1);
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
                urut: coaObj.urut,
                prj_kode: coaObj.prj_kode,
                sit_kode: coaObj.sit_kode,
                trano: coaObj.trano,
                ref_number: coaObj.ref_number,
                is_minus: false
            });
            this.store.add(e);
            this.getView().refresh();
            this.summaryTotal();
            this.store.data.sort('ASC');
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
    },

    updateCOA: function(coaObj,urut)
    {
        if (!coaObj)
         return false;

        var rows = this.store.queryBy(function(record,id){
             return record.get('urut') == urut;
        });

        if (rows.length != 0)
        {

            Ext.each(rows.items,function(items){
                this.store.remove(items);
            },this);

            this.getCoa(coaObj);

        }

        this.getView().refresh();
    },

    setClosedJournalFunction: function(fn)
    {
        this.closedJournalFunction = fn;
    },

    moneyComp: function(a,comp,b,decimals)
    {
        if (!decimals)
            decimals = 2;
        var multiplier = Math.pow(10,decimals);
        a = Math.round(a * multiplier); // multiply to do integer comparison instead of floating point
        b = Math.round(b * multiplier);
        switch (comp) {
            case ">":
                return (a > b);
            case ">=":
                return (a >= b);
            case "<":
                return (a < b);
            case "<=":
                return (a <= b);
            case "==":
                return (a == b);
        }
        return null;
    },

    compareValueToJournal: function(value)
    {
        var journalValue = this.summaryTotal(true,true);
        return this.moneyComp(value,'==',journalValue);
    },

    recordJurnal: new Ext.data.Record.create([
        {name:'coa_kode'},
        {name:'coa_nama'},
        {name:'debit'},
        {name:'credit'},
        {name:'val_kode'},
        {name:'tipe'},
        {name:'urut'},
        {name:'prj_kode'},
        {name:'sit_kode'},
        {name:'trano'},
        {name:'ref_number'},
        {name:'stspost'},
        {name:'stsclose'},
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

    initComponent : function() {

        var summary = new Ext.grid.GroupSummary();

//        this.store = new Ext.data.Store ({
        this.store = new Ext.data.GroupingStore ({
            reader:new Ext.data.JsonReader({
                root:'data',
                totalProperty:'total',
                fields: this.recordJurnal
            }),
            sortInfo:{field: 'urut', direction: "ASC"},
            groupField: 'ref_number'
        });

        this.view = new Ext.grid.GroupingView({
            forceFit: true,
            showGroupName: false,
            enableNoGroups: false,
            enableGroupingMenu: false,
            hideGroupedColumn: true
        });

        if (this.setEnabledCoa != undefined)
            this.setEnabledCoa = true;
        else
            this.setEnabledCoa = false;

        this.editor = this.initEditor();
        this.rowactions = this.initAction();
        this.plugins = [this.editor, this.rowactions, summary];

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
                        fields:['id','ref_number'],
                        data: this.refNumberArray

                    }),
                    valueField:'ref_number',
                    displayField:'ref_number',
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
            this.rowactions,
            ref,
            {
                header:'COA Code',
                dataIndex:'coa_kode',
                sortable:true,
                editor: {
                    xtype: 'coaselector',
                    id: 'coa-select',
                    Selectid: 'coa_kode_text',
                    Nameid: 'coa_nama_text',
                    showName: false,
                    editorInstance: this.editor,
                    SelectWidth: 100
                }
            },{
                header:'COA Name',
                dataIndex:'coa_nama',
                sortable:true,
                editor: {
                    xtype: 'textfield',
                    id: 'coa_nama_text',
                    readOnly: true
                }
            },{
                header:'Debit',
                dataIndex:'debit',
                sortable:true,
                summaryType: 'sum',
                renderer: function(v){
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align:'right',
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowNegative: false,
                    id:'debit',
                    minValue: 0,
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function (txttext,event)
                        {
                            var debit = parseFloat(txttext.getValue().toString().replace(/\$|\,/g,''));
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
            },{
                header:'Credit',
                dataIndex:'credit',
                sortable:true,
                summaryType: 'sum',
                renderer: function(v){
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align:'right',
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowNegative: false,
                    id:'credit',
                    minValue: 0,
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function (txttext,event)
                        {
                            var credit = parseFloat(txttext.getValue().toString().replace(/\$|\,/g,''));
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
            }
        ];

        if (this.hideRefNumber == true)
        {
            Ext.each(this.columns,function(col){
                if (col.dataIndex == 'ref_number')
                    col.hidden = true;
            });
        }

        var addButtons = new Ext.Button({
            iconCls: 'icon-add-new',
            text: 'Add New Journal'
        });

        addButtons.on('click',function(btn, e){
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
        },this);

        var printButtons = new Ext.Button({
            iconCls: 'silk-printer',
            text: 'Print This Journal'
        });

        printButtons.on('click',function(btn, e){
            this.printJurnal();
        },this);

        this.tbar = [
            addButtons,
            '-',
            printButtons
        ];
        this.bbar = new Ext.Toolbar({
            id: 'total-bbar',
            style:"text-align:right",
            items: [{
                        xtype: 'label',
                        ref: '../debitTotal',
                        style:'color:red;font-weight:bold;margin-right:20px;font-size:12'
                    },
                    {
                        xtype: 'label',
                        ref: '../creditTotal',
                        style:'color:red;font-weight:bold;font-size:12;margin-right:10px'
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