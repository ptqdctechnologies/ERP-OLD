/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
SampleGrid = function(limitColumns){

    function italic(value){
        return '<i>' + value + '</i>';
    }

    function change(val){
        if(val > 0){
            return '<span style="color:green;">' + val + '</span>';
        }else if(val < 0){
            return '<span style="color:red;">' + val + '</span>';
        }
        return val;
    }

    function pctChange(val){
        if(val > 0){
            return '<span style="color:green;">' + val + '%</span>';
        }else if(val < 0){
            return '<span style="color:red;">' + val + '%</span>';
        }
        return val;
    }


    var columns = [
        {id:'trano',header: "Trano", width: 200, sortable: true, dataIndex: 'trano'},
        {header: "Tgl", width: 100, sortable: true,  dataIndex: 'tgl',renderer: Ext.util.Format.dateRenderer('d/m/Y')},
        {header: "PR no", width: 100, sortable: true, dataIndex: 'pr_no'},
        {header: "Tgl PR", width: 100, sortable: true, dataIndex: 'tglpr',renderer: Ext.util.Format.dateRenderer('d/m/Y')},
        {header: "PrjKode", width: 100, sortable: true, dataIndex: 'prj_kode'}
    ];

    // allow samples to limit columns
    if(limitColumns){
        var cs = [];
        for(var i = 0, len = limitColumns.length; i < len; i++){
            cs.push(columns[limitColumns[i]]);
        }
        columns = cs;
    }

    

    var store = new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({
        url: CFG_CLIENT_SERVER_NAME + '/procurement/list/type/poh'
         }),
            reader: new Ext.data.JsonReader({
        root: 'posts',
        totalProperty: 'count',
        id: 'trano'
    }, [
    {name: 'trano', mapping: 'trano'},
    {name: 'pr_no', mapping: 'pr_no'},
    {name: 'tgl', mapping: 'tgl', type: 'date', dateFormat: 'Y-m-d'},
    {name: 'tglpr', mapping: 'tglpr', type: 'date', dateFormat: 'Y-m-d'},
    {name: 'prj_kode', mapping: 'prj_kode'}
    ])
        });
    
     var gridForm = new Ext.FormPanel({
        id: 'company-form',
        frame: true,
        labelAlign: 'left',
        title: 'Company data',
        bodyStyle:'padding:5px',
        width: 750,
        layout: 'column',    // Specifies that the items will now be arranged in columns
        items: [{
            columnWidth: 0.60,
            layout: 'fit',
            items: {
                xtype: 'grid',
                ds: store,
                cm: columns,
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true,
                    listeners: {
                        rowselect: function(sm, row, rec) {
                            Ext.getCmp("company-form").getForm().loadRecord(rec);
                        }
                    }
                }),
                autoExpandColumn: 'company',
                height: 350,
                title:'Company Data',
                border: true,
                listeners: {
                    viewready: function(g) {
                        g.getSelectionModel().selectRow(0);
                    } // Allow rows to be rendered.
                }
            }
        },{
            columnWidth: 0.4,
            xtype: 'fieldset',
            labelWidth: 90,
            title:'Company details',
            defaults: {width: 140, border:false},    // Default config options for child items
            defaultType: 'textfield',
            autoHeight: true,
            bodyStyle: Ext.isIE ? 'padding:0 0 5px 15px;' : 'padding:10px 15px;',
            border: false,
            style: {
                "margin-left": "10px", // when you add custom margin in IE 6...
                "margin-right": Ext.isIE6 ? (Ext.isStrict ? "-10px" : "-13px") : "0"  // you have to adjust for it somewhere else
            },
            items: [{
                fieldLabel: 'Name',
                name: 'company'
            },{
                fieldLabel: 'Price',
                name: 'price'
            },{
                fieldLabel: '% Change',
                name: 'pctChange'
            },{
                xtype: 'datefield',
                fieldLabel: 'Last Updated',
                name: 'lastChange'
            }, {
                xtype: 'radiogroup',
                columns: 'auto',
                fieldLabel: 'Rating',
                name: 'rating',
// A radio group: A setValue on any of the following 'radio' inputs using the numeric
// 'rating' field checks the radio instance which has the matching inputValue.
                items: [{
                    inputValue: '0',
                    boxLabel: 'A'
                }, {
                    inputValue: '1',
                    boxLabel: 'B'
                }, {
                    inputValue: '2',
                    boxLabel: 'C'
                }]
            }]
        }]
    });
    

    SampleGrid.superclass.constructor.call(this, {
        store: store,
        columns: columns,
        bbar: new Ext.PagingToolbar({
            pageSize: 100,
            store: store,
            displayInfo: true,
            displayMsg: 'Displaying data {0} - {1} of {2}',
            emptyMsg: "No data to display"
        }),
        autoExpandColumn: 'trano',
        height:250,
        width:800
    });
    store.load();

   

/*
 *    Here is where we create the Form
 */
   

}
Ext.extend(SampleGrid, Ext.grid.GridPanel);