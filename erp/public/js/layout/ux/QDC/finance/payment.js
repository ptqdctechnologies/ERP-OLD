Ext.ux.form.PaymentSelector = Ext.extend(Ext.form.Field, {
    showItemWindow: function(t) {

        var url = '/finance/payment/get-payment-list/type/' + this.transactionType;

        var proxy = new Ext.data.HttpProxy({
            url: url
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            root: 'data'
        }, [
            {name: 'trano'},
            {name: 'voc_trano'},
            {name: 'doc_trano'},
            {name: 'jenis_document'},
            {name: 'tgl'},
            {name: 'prj_kode'},
            {name: 'sit_kode'},
            {name: 'total_bayar'},
            {name: 'val_kode'},
            {name: 'invoice_no'},
        ]);

        var itemStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'paymentselector-store'
        });
        itemStore.load();

        var tranoText = new Ext.form.TextField({
            fieldLabel: 'Trano',
            enableKeyEvents: true
        });

        tranoText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = url + '/trano/' + pname;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var bpvText = new Ext.form.TextField({
            fieldLabel: 'BPV Trano',
            enableKeyEvents: true
        });

        bpvText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = url + '/bpv_trano/' + pname;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var refText = new Ext.form.TextField({
            fieldLabel: 'Ref Number',
            enableKeyEvents: true
        });

        refText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = url + '/ref_number/' + pname;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

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
                {header: 'Trano', width: 120, dataIndex: 'trano', sortable: true},
                {header: 'BPV Trano', width: 120, dataIndex: 'voc_trano', sortable: true},
                {header: 'Ref Number', width: 120, dataIndex: 'doc_trano', sortable: true},
                {header: 'Type', width: 50, dataIndex: 'jenis_document', sortable: true},
                {header: 'Project', width: 200, dataIndex: 'prj_kode', sortable: true, renderer: function(v,p,r){
                    return v + " - " + r.data['sit_kode'];
                }},
            ]
        });

        gridItem.on('rowclick', function(g, rowIndex, e) {
            var rec = g.getStore().getAt(rowIndex);
            Ext.getCmp(this.Selectid).setValue(rec.get('trano'));
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
                tranoText,
                bpvText,
                refText,
                gridItem
            ]
        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 420,
            title: 'Select Payment',
            items: forms
        });

        if (this.disabled !== true)
        {
            pwindow.show();
        }
    },
    onRender: function(ct, position) {

        this.recordData = '';

        var select_id = this.Selectid;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.SelectWidth == undefined)
            this.SelectWidth = 80;

        if (this.callback == undefined)
            this.callback = function(rec){};

        if (this.transactionType == undefined)
            this.transactionType = 'RPI';

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
        Ext.ux.form.PaymentSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PaymentSelector.superclass.beforeDestroy.call(this);
    },
    clearData: function()
    {
        this.recordData = '';
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue('');
    },

    setDataRecord: function(rec)
    {
        this.recordData = rec;
    },
    getDataRecord: function()
    {
        return this.recordData;
    },
    getValue: function()
    {
        return Ext.getCmp(this.Selectid).getValue();
    },
    setValue: function(v)
    {
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue(v);
    },
    setTransactionType: function(type){
        this.transactionType = type;
    },
    getTransactionType: function(type){
        return this.transactionType;
    }



});

Ext.reg('paymentselector', Ext.ux.form.PaymentSelector);