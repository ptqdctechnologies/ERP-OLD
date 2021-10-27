Ext.ux.customRecord = Ext.extend(Ext.data.Record,{
    getRecord: function(type){
        if (type == 'BRF' || type == 'BRFP')
        {
            return [
                { name: "id" },
                { name: "kode_brg" },
                { name: "nama_brg" },
                { name: "prj_kode" },
                { name: "sit_kode" },
                { name: "workid" },
                { name: "sequence" },
                { name: "requester" },
                { name: "trano" },
                { name: "trano_ref" },
                { name: "qty", type: 'float' },
                { name: "harga", type: 'float' },
                { name: "total", type: 'float' },
                { name: "val_kode" },
                { name: "original_qty", type: 'float' },
                { name: "original_harga", type: 'float' },
                { name: "original_total", type: 'float' },
                { name: "tgl" },
                { name: 'persen' }
            ];
        }
    },

    initComponent: function() {

        Ext.ux.customRecord.superclass.initComponent.call(this);
    }
});

Ext.ux.customGrid = Ext.extend(Ext.grid.GridPanel,{
    initColumns: function(type) {
        if (type == 'BRF' || type == 'BRFP')
        {
            return [
                new Ext.grid.RowNumberer({
                    width: 30
                }),
                this.rowactions,
                {header: "Sequence",width: 80, dataIndex: 'sequence'},
                {header: "Name",width: 150, dataIndex: 'nama_brg'},
                {header: "Valuta",width: 40, dataIndex: 'val_kode'},
                {header: "Ori. Total",width: 150, dataIndex: 'original_total',
                    renderer: function(v){
                        return v ? Ext.util.Format.number(v, '0,0.00') : '';
                    },
                    align:'right'
                },
                {header: "Percentage",width: 80, dataIndex: 'persen',
                    renderer: function(v, p, r){
//                        var p = (parseFloat(r.get("total")) / parseFloat(r.get("original_total"))) * 100;
//                        return p ? Ext.util.Format.number(p, '0,0.00') +   "%" : '0 %';
                        var p = v;
                        return p ? Ext.util.Format.number(p, '0,0.00') +   "%" : '0 %';
                    },
                    align:'right',
                    editor: {
                        xtype: 'numberfield',
                        allowBlank: false,
                        allowNegative: false,
                        enableKeyEvents: true,
                        listeners: {
                            'disable': function(txttext)
                            {
                                txttext.getEl().addClass('text-tebal');
                            }
                        }
                    }
                },
                {
                    header: "New Total",width: 150, dataIndex: 'harga',
                    editor: {
                        xtype: 'numberfield',
                        allowBlank: false,
                        allowNegative: false,
                        enableKeyEvents: true,
                        listeners: {
                            'disable': function(txttext)
                            {
                                txttext.getEl().addClass('text-tebal');
                            }
                        }
                    },renderer: function(v){
                        return v ? Ext.util.Format.number(v, '0,0.00') : '';
                    },
                    align:'right'
                }
            ];
        }
    },
    initAction: function(type){
        var rowactions = null;

        if (type == 'BRF' || type == 'BRFP')
        {
            rowactions = new Ext.ux.grid.RowActions({
                actions:[
                    {
                        iconCls:'icon-edit',
                        qtip:'Edit',
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
            },this);
        }
        return rowactions;
    },

    initEditor: function(type){

        var editor = null;
        if (type == 'BRF' || type == 'BRFP')
        {
            editor = new Ext.ux.grid.RowEditor({
                saveText: 'Update',
                clicksToEdit: ''
            });
            //Triggered after Update button is clicked, record not committed YET...
            editor.on('stopedit', function(ed,fields,rec) {
                if ((rec.data['qty'] > 0 && rec.data['harga'] > 0) && rec.data['original_qty'] > 0)
                {
                    var totalEdited = parseFloat(rec.get("qty")) * parseFloat(rec.get("harga")),
                        totalOriginal = parseFloat(rec.get("original_total"));

                    if (moneycomp(totalEdited,'>',totalOriginal,2))
                    {
//                        Ext.each(fields, function (t, index){
//                            if (t.id == 'credit' || t.id == 'debit')
//                            {
//                                t.markInvalid('Debit or Credit field must be greater than 0!');
//                            }
//                        });

                        Ext.Msg.alert('Error','Total Edited is greater than Total Original<br>If You want to change this value greater than it\'s original, please Reject this BT to the Submitter for Editing.');
                        return false;
                    }
                }

            },this);

            editor.on('afteredit', function(ed,obj,rec,index,fields){
                var recs = this.store.getAt(index);


//                var newTotal = parseFloat(recs.get("qty")) * parseFloat(recs.get("harga")),
//                    persen = (newTotal / parseFloat(recs.get("original_total"))) * 100;

                var newTotal = recs.get("original_total");

                ed.record.beginEdit();

                if (obj.persen != undefined && obj.harga == undefined)
                    newTotal = (parseFloat(recs.get("persen")) / 100) * parseFloat(recs.get("original_total"));
                else
                {
                    newTotal = parseFloat(recs.get("qty")) * parseFloat(recs.get("harga"));
                    recs.data['persen'] = (newTotal / parseFloat(recs.get("original_total"))) * 100;
                }

                recs.data["total"] = parseFloat(recs.get("qty")) * newTotal;
                recs.data["harga"] = newTotal;
                ed.record.endEdit();
                ed.record.commit();
            },this);

            editor.on('canceledit', function(roweditor, forced){
                roweditor.record.cancelEdit();
            },this);
        }
        return editor;
    },
    getStore: function()
    {
        return this.store;
    },
    loadData: function(json)
    {
        if (!json)
            return false;

        this.store.removeAll();
        this.store.loadData(json);
        this.getView().refresh();
    },
    initLoadData: function(type) {
        if (type == 'BRF' || type == 'BRFP')
        {
            Ext.Ajax.request({
                url: this.urlLoadData,
                params: this.paramsLoadData,
                method:'POST',
                scope: this,
                success: function(resp){
                    var returnData = Ext.util.JSON.decode(resp.responseText);
                    if (returnData.success)
                    {
                        var data = returnData.payment,i=0;
                        Ext.each(data,function(d){
                            data[i].original_qty = d.qty;
                            data[i].original_harga = d.harga;
                            data[i].original_total = d.total;
                            data[i].persen = (parseFloat(d.total) / parseFloat(data[i].original_total)) * 100;
                            i++;
                        });
                        this.loadData(data);
                    }
                    else
                    {
                        Ext.Msg.alert('Error', returnData.msg);
                    }
                },
                failure:function( action){
                    if(action.failureType == 'server'){
                        obj = Ext.util.JSON.decode(action.response.responseText);
                        Ext.Msg.alert('Error!', obj.errors.reason);
                    }else{
                        Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                    }
                }
            });
        }
    },
    getJSONFromStore: function()
    {
        var json = '';
        if (this.store.getCount() > 0)
        {
            this.store.each(function(store){
                var encode = Ext.util.JSON.encode(store.data);
                if (encode != undefined)
                    json += encode + ',';
            });
            json = '[' + json.substring(0, json.length - 1) + ']';
        }
        return json;
    },
    initComponent : function() {

        var cr =  new Ext.ux.customRecord();
        this.records = cr.getRecord(this.type);
        this.store = new Ext.data.Store ({
            reader:new Ext.data.JsonReader({
                root: this.rootLoadData,
                fields: this.records
            })
        });

        var plugins = [];
        this.editor = this.initEditor(this.type);
        if (this.editor != null)
            plugins.push(this.editor);
        this.rowactions = this.initAction(this.type);
        if (this.rowactions != null)
            plugins.push(this.rowactions);

        if (plugins.length > 0)
            this.plugins = plugins;

        this.columns = this.initColumns(this.type);

        if (this.urlLoadData != undefined)
        {
            if (this.paramsLoadData == undefined)
                this.paramsLoadData = {};

            this.initLoadData(this.type);
        }

        Ext.ux.grid.gridJurnal.superclass.initComponent.call(this);
    }
});