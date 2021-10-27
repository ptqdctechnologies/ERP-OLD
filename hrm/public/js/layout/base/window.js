/* 
 * Base window class for Ext JS
 * Created by Bherly 10032010
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
        x:0,
        y:100,
        loadMask: true,
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

}
Ext.extend(SampleGrid, Ext.grid.GridPanel);

var buttonHandler2 = function(button,event) {
alert(button.id.toString());
  var aForm =  new Ext.Window({
    
    id: 'a-form-panel',
    layout: 'fit',
    bodyStyle: 'padding:15px;',
    minWidth: 300,
        minHeight: 200
  });
  aForm.title = 'Pop Up Window';
  aForm.show();
 };
 
var procurementPOForm = new Ext.form.FormPanel({
    baseCls: 'x-plain2',
    layout:'absolute',
    url: CFG_CLIENT_SERVER_NAME + '/procurement/save',
    border: true,
    items: [
        {
        x: 0,
        y: 0,
        xtype: 'label',
        text: 'Project Code:'
    },
         new Ext.form.TextField({
            id:"project_code",
            x:80,
            y:0,
            width:150,
            allowBlank:false,
            blankText:"Please enter the project code"
         }),{
    	// The button is not a Field subclass, so it must be
    	// wrapped in a panel for proper positioning to work
    	xtype: 'panel',
    	x: 232,
    	y: 0,
    	items: {
	    	xtype: 'button',
                id: 'pjr_kode_button',
	    	text: '>',
                handler:buttonHandler2
    	}
    },
         {
        x: 0,
        y: 40,
        xtype: 'label',
        text: 'Site Code:'
    },
    
         new Ext.form.TextField({
            id:"site_code",
            x:80,
            y:40,
            width:150,
            allowBlank:false,
            blankText:"Please enter the site code"
         }),
         {
    	// The button is not a Field subclass, so it must be
    	// wrapped in a panel for proper positioning to work
    	xtype: 'panel',
    	x: 232,
    	y: 40,
    	items: {
	    	xtype: 'button',
                id: 'site_kode_button',
	    	text: '>',
                handler:buttonHandler2
    	}
    },new SampleGrid()
      ],
      buttons: [
         {text:"Cancel"},
         {text:"Save"}
      ]
//    defaultType: 'textfield',
//
//    items: [{
//        x: 0,
//        y: 5,
//        xtype: 'label',
//        text: 'Project Code:'
//    },{
//        x: 80,
//        y: 0,
//        name: 'pjr_kode',
//        anchor:'100%'// anchor width by %
//    },{
//        x: 0,
//        y: 32,
//        xtype: 'label',
//        text: 'Site Code:'
//    },{
//    	// The button is not a Field subclass, so it must be
//    	// wrapped in a panel for proper positioning to work
//    	xtype: 'panel',
//    	x: 55,
//    	y: 27,
//    	items: {
//	    	xtype: 'button',
//	    	text: '>',
//                handler:buttonHandler2
//    	}
//    },{
//        x: 80,
//        y: 27,
//        name: 'to',
//        anchor: '100%'  // anchor width by %
//    }]
});

var procurementForm =  ({

    title: 'Procurement Form',
    id: 'abs-form-po-panel',
    layout: 'fit',
    bodyStyle: 'padding:15px;',
    minWidth: 300,
    minHeight: 200,
    loadMask: true,
    items: {
    	title: 'Purchase Order',
    	cls: 'email-form2',
	    layout: 'fit',
	    frame: true,
	    bodyStyle: 'padding:10px 5px 5px;',
	    items:procurementPOForm
            
            }
});

