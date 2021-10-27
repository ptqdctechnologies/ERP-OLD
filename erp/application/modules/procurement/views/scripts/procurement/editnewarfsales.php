<script type="text/javascript">
<?php echo $this->action('popupgrid','grid'); ?>
   
// ARF LIST CART AREA

var arflist = Ext.data.Record.create([
    {name: 'id',type: 'integer'}, 
    {name: 'boq_id',type: 'integer'}, 
    {name: 'budgetid',type: 'string'},  
    {name: 'budgetname',type: 'string'}, 
    {name: 'nama_brg',type: 'string'}, 
    {name: 'kode_brg',type: 'string'}, 
    {name: 'qty',type: 'float'}, 
    {name: 'harga',type: 'float'},        
    {name: 'ket',type: 'string'},    
    {name: 'val_kode',type: 'string'}, 
    {name: 'uom',type: 'string'},
    {name: 'requester',type: 'string'},
    {name: 'requesterName',type: 'string'}
]);

var storeARF = new Ext.data.Store({
    reader: new Ext.data.JsonReader({fields: arflist})
});

<?php if ($this->json != '') {  ?>
        
    var firstBack = 0;
    var newRequests={};
    var json = <?php echo $this->json; ?>;
    var jsonEtc = <?php echo $this->etc; ?>;
    var jsonFile = <?php echo $this->file; ?>;

    storeARF.loadData(json);
             
<?php }  ?>


// ARF FORM

userForm = new Ext.FormPanel({
    renderTo: 'user-form',
    iconCls: 'silk-user',
    title: 'Add New Advance Request Form (ARF)',
    frame: true,
    width: 750,
    id: 'arf-form',
    labelAlign: 'left',
    items: [
        {
            layout:'column',
            items:[
                {
                    columnWidth:.310,
                    layout: 'form',
                    labelWidth: 120,
                    items: [
                        {
                            fieldLabel : 'Name of Beneficiary',
                            id : 'penerima_text',
                            xtype: 'textfield',
                            disabled: false,

                            width: 100
                        },
                        {
                            fieldLabel : 'Bank Name ',
                            id : 'bank_text',
                            xtype: 'textfield',
                            disabled: false,

                            width: 100
                        },
                        {
                            fieldLabel : 'Account Name ',
                            id : 'bankaccountname_text',
                            xtype: 'textfield',
                            disabled: false,

                            width: 100
                        },
                        {
                            fieldLabel : 'Account Number ',
                            id : 'bankaccountno_text',
                            xtype: 'textfield',
                            disabled: false,

                            width: 100
                        },
                        {
                            fieldLabel : 'Origin Of Budget',
                            id:'arf_origin_text',
                            name:'arf-origin',
                            xtype:'combo',
                            store: new Ext.data.SimpleStore({
                                fields:['nilai', 'ori'],data:[['SALES','SALES']]
                            }),
                            valueField:'ori',
                            displayField:'nilai',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            value: 'Sales',
                            selectOnFocus:true,
                            forceSelection:false,
                            width: 70
                        }

                    ]
                },
                {
                    columnWidth:.270,
                    layout: 'form',
                    style: 'margin-left:10px;',   
                    items: [
                        {
                            fieldLabel: 'Tender Code',
                            id:'prj_kode_text',
                            name:'prj_kode',
                            allowBlank: false,
                            xtype:"trigger",
                            triggerClass: 'teropong',
                            editable: false,
                            width: 80,
                            onTriggerClick:function (){showPrjList();}
			},
                        {
                            fieldLabel: 'Bid Code',
                            id:'sit_kode_text',
                            name:'sit_kode',
                            allowBlank: false,
                            xtype:"trigger",
                            triggerClass: 'teropong',
                            editable: false,
                            width: 80,
                            onTriggerClick:function ()
				{
                                    if (Ext.getCmp('prj_kode_text').getValue() != '')
                                    showSitList();
				}
                        },
                        {
                            fieldLabel: 'Currency',id:'val_kode_text',name: 'val_kode', xtype: 'trigger',triggerClass: 'teropong',value: 'IDR',editable: false,
                            onTriggerClick: function(){valutaPopUphandler();},
                            allowBlank: false,
                            width: 80
                        },
                        {
                            fieldLabel : 'PIC Name',
                            id : 'pic_kode_text',
                            xtype: 'textfield',
                            disabled: true,
                            width: 80
                        },
                        {
                            xtype:'trigger',
                            fieldLabel: 'Manager',
                            id:'mgr_kode_text',
                            name: 'mgr_kode', 
                            triggerClass: 'teropong',
                            onTriggerClick: function( ){showManagerList();},
                            editable: false,
                            allowBlank: false,
                            width: 80
                        },
                        {
                            fieldLabel : 'Finance Staff',
                            id : 'fin_kode_text',
                            xtype: 'trigger',
                            triggerClass: 'teropong',
                            onTriggerClick: function( ){showUserList();},
                            editable: false,
                            allowBlank: false,
                            width: 80
                        }
                            
                    ]
                },
                {
                    columnWidth:.270,
                    style: 'text-align:left',
                    layout: 'form',
                    items: [
			{
                            id:'prj_nama_text',
                            name: 'prj_nama',
                            hideLabel: true,
                            allowBlank: false,
                            xtype: 'textfield',
                            disabled:true,
                            width: 175
			},
                        {
                            id:'sit_nama_text',
                            name: 'sit_nama',
                            hideLabel: true,
                            allowBlank: false,
                            disabled: true,
                            xtype: 'textfield',
                            width: 175
			},
                        {
                            id:'val_nama_text',
                            name: 'val_nama',
                            hideLabel: true,
                            allowBlank: false,
                            disabled: true,
                            xtype: 'textfield',
                            width: 175

                        },
                        {
                            id:'pic_nama_text',
                            name: 'pic_nama',
                            hideLabel: true,
                            allowBlank: false,
                            disabled: true,
                            xtype: 'textfield',
                            width: 175
                        },
                        {
                            id : 'mgr_nama_text',
                            xtype: 'textfield',
                            hideLabel: true,
                            disabled: true,
                            width: 175
                        },
                        {
                            id : 'fin_nama_text',
                            xtype: 'textfield',
                            hideLabel: true,
                            disabled: true,
                            width: 175
                        }
                    ]
                }
            ]
        },
        {
                fieldLabel: 'Internal Notes',
                id: 'ketin_text',
                xtype:'textarea',
                width: 250,
                height: 80
        }
    ],
    buttons: [
        {
            text: 'Submit',
            id: 'boq3-submit',
            iconCls: 'silk-add',
            handler: function() {
      		store.removeAll();
                storeARF.removeAll();
                clearARFForm();              
                submitBoq3();  
            },
            scope: this
        }, 
        {
            text: 'Cancel',
            handler: function(btn, ev){
 	        	myPanel = Ext.getCmp('abs-budget-panel');
 	    	    myPanel.body.load({
 	    	        url: '/procurement/procurement/addnewarf',
 	    	        scripts : true
 	            });
            },
            scope: this
        } 
    ]

});

// FILE FORM

var filelist = Ext.data.Record.create([ 
    {name: 'id',type: 'integer'},
    {name: 'filename',type: 'string'},
    {name: 'savename',type: 'string'},
    {name: 'status',type: 'string'},
    {name: 'path',type: 'string'}
]);

var storeFile = new Ext.data.Store({
    reader: new Ext.data.JsonReader({fields: filelist})
});

var fileColumns = [
    new Ext.grid.RowNumberer(),
    {header: "File Name",width: 130, dataIndex: 'filename'},
    {header:'',width:40,sortable:true,css:'text-align:center;', renderer: function (v,p,r){
        return '<a href="#" onclick="window.open(\'/default/file/download/path/files/filename/' + r.data['savename'] + '\',\'mywin\',\'left=20,top=20,width=100,height=20,toolbar=0,resizable=0\');"><img src="/images/icons/fam/page_find.gif"></a>&nbsp;<a href="#" onclick="deleteFile();"><img src="/images/g_rec_del.png"></a>';
    }}
];

var fileUploads = new Ext.FormPanel({
    renderTo: 'form_file',
    fileUpload: true,
    autoHeight: true,
    frame: true,
    style: 'margin-top: 10px',
    width: 700,
    defaults: {
        anchor: '95%',
        allowBlank: false,
        msgTarget: 'side'
    },
    items:[
        {
            layout : 'column',
            items:[
                {
                    columnWidth:.46,
                    layout :'form',
                    items:[
                        {
                            xtype: 'fileuploadfield',
                            id: 'po-file',
                            emptyText: 'Select a File',
                            fieldLabel: 'Attach File',
                            allowBlank: false,
                            name: 'file-path',
                            buttonText: '',
                            buttonCfg: {iconCls: 'upload-icon'}
                        },
                        {
                            xtype: 'button',
                            text: 'Upload',
                            style: 'float: right',
                            handler: function(){
                                if(fileUploads.getForm().isValid()){
                                    form_action=1;
                                    fileUploads.getForm().submit({
                                        url: '/procurement/procurement/uploadfile/type/ARF',
                                        waitMsg: 'Uploading file...',
                                        success: function(form,action){
                                            var returnData = action.result;
                                            if( returnData.success) {
                                                var c = new filelist({
                                                    id:parseFloat(storeFile.getCount() + 1),
                                                    filename: returnData.filename,
                                                    savename: returnData.savename,
                                                    path: returnData.path,
                                                    status: 'new'
                                                });
                                                storeFile.add(c);
                                                Ext.getCmp('files-grid').getView().refresh();
                                                Ext.getCmp('po-file').setValue('');
                                            }
                                            else
                                            {
                                                Ext.Msg.alert('Error', returnData.msg);
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    ]                  
                },
                {
                    columnWidth:.5,
                    layout :'form',
                    items:[
                        new Ext.grid.GridPanel ({
                            id:'files-grid',
                            iconCls: 'silk-grid',
                            height: 100,
                            style: 'margin-left: 5px',
                            store: storeFile,
                            trackMouseOver: true,
                            view : new Ext.grid.GridView({
                                forceFit: true
                            }),
                            columns: fileColumns
                        })
                    ]
                }
            ]
        }
    ],
    buttons: []
});

// Budget DETAIL

var proxy = new Ext.data.HttpProxy({
    url: '/procurement/procurement/getboq3forproject'
});
    
var reader = new Ext.data.JsonReader({
    totalProperty: 'count',
    idProperty: 'id',
    root: 'posts'
}, [
    {name: 'id', allowBlank: false},
    {name: 'budgetid', allowBlank: false},
    {name: 'budgetname', allowBlank: false}, 
    {name: 'totalPrice', allowBlank: false},
    {name: 'val_kode', allowBlank: false},
    {name: 'totalRequests', allowBlank: false},
    {name: 'totalAFE', allowBlank: false},
    {name: 'tranoAFE', allowBlank: false}
]);
    
var store = new Ext.data.Store({
    id: 'boq3',
    proxy: proxy,
    reader: reader
});

var userColumns = [
    new Ext.grid.RowNumberer(),

    {header: "", width: 30, dataIndex: 'id', renderer: function(v, p, r) {
              
            var total = r.data['tranoAFE']=='' ? parseFloat(r.data['totalPrice']):parseFloat(r.data['totalAFE']);
                      
            <?php if ($this->json != '') {  ?>
                 r.data['totalRequests'] = firstBack == 0 && newRequests[v] !=undefined ? parseFloat(r.data['totalRequests']) + parseFloat(newRequests[v]) : r.data['totalRequests'];       
            <?php }  ?>
                
             var percent = total == 0 ? 100 : parseFloat((r.data['totalRequests'] / total) * 100);
                
            if (percent.toFixed(2) < 100)
            {
                return '<a href="#" onclick="addToARF(' + v + ');"><img src="/images/g_rec_add.png"></a>';
            }
            else
            {
                p.attr = 'ext:qtip="This item (' + r.data['kode_brg'] + ') must be created by AFE"';
                return '<img src="/images/icons/fam/page_tag_red.gif">';
            }
    }},
    
    {header: "Applied", width: 100, renderer: function(v, p, r) {

            var total = r.data['tranoAFE']=='' ? parseFloat(r.data['totalPrice']):parseFloat(r.data['totalAFE']);
                      
            <?php if ($this->json != '') {  ?>
                 r.data['totalRequests'] = firstBack == 0 && newRequests[v] !=undefined ? parseFloat(r.data['totalRequests']) + parseFloat(newRequests[v]) : r.data['totalRequests'];       
            <?php }  ?>
                
            var percent = total == 0 ? 100 : parseFloat((r.data['totalRequests'] / total) * 100);        
            
            var warna = '#0a0';

            if (percent.toFixed(2) > 50)
            {
                    warna = '#FFDA2F';
            }
               
            if (percent.toFixed(2) > 75)
            {
                    warna = '#FF3F7D';
            }

            return '<div class="meter-wrap"><div class="meter-value" style="background-color: ' + warna + '; width: ' + percent.toFixed(2)+ '%;"><div class="meter-text"><b>' + percent.toFixed(2) + '%</b></div></div></div>';
    }},
    {header: "Budget Id", width: 70, sortable: true, dataIndex: 'budgetid'},
    {header: "Budget Name", width: 150, sortable: true, dataIndex: 'budgetname'},
    {header: "Currency", width: 55, sortable: true, dataIndex: 'val_kode'},
    {header: "Total", width: 80, sortable: true, dataIndex: 'totalPrice', css: 'text-align:right;', renderer: function(v, p, r) {
        return CommaFormatted(r.data['totalPrice']);
    }}
];

userGrid = Ext.extend(Ext.grid.GridPanel, {
    renderTo: 'user-grid',
    iconCls: 'silk-grid',
    id: 'boq3-grid',
    frame: true,
    title: 'Budget Detail',
    height: 250,
    width: 530,
    stateful: false,
    style: 'margin-top: 10px',
    initComponent : function() {

        this.buttons = this.buildUI();

        userGrid.superclass.initComponent.call(this);
    },
    buildUI : function() {},
});

var userGrids = new userGrid({
    renderTo: 'user-grid',
    id: 'boq3-grid',
    store: store,
    columns : userColumns,
    loadMask: true,
    bbar: new Ext.PagingToolbar({
        id: 'paging',
        pageSize: 100,
        store: store,
        displayInfo: true,
        displayMsg: 'Displaying data {0} - {1} of {2}',
        emptyMsg: "No data to display"
    })
});

// DETAIL ARF FORM

userForm2 = new Ext.FormPanel({
    renderTo: 'user-form2',
    id: 'arf-form',
    iconCls: 'silk-user',
    labelAlign: 'left',
    title: 'Detail Advance Request Form(ARF)',
    frame: true,
    width: 530,
    style: 'margin-top: 10px',
    items: [
        {
            layout: 'column',
            items: [
                    {
                        layout: 'form',
                        items: [ 
                            {
                                hideLabel: true,
                                id: 'arf-id',
                                xtype: 'textfield',
                                disabled: true,
                                width: 20,
                                hidden:true 
                            },
                            {
                                hideLabel: true,
                                id: 'boq-id',
                                xtype: 'textfield',
                                disabled: true,
                                width: 20,
                                hidden:true 
                            },
                            {
                                hideLabel: true,
                                id: 'arf-status',
                                xtype: 'textfield',
                                disabled: true,
                                width: 20,
                                hidden:true 
                            },
                            {
                                fieldLabel : 'Requester Name',
                                id : 'user_selector',
                                xtype: 'userselector',
                                ShowName: false,
                                UserSelectid: 'requester_text',
                                width: 200
                            },
                            {
                                layout: 'column',
                                items: 
                                [
                                    {
                                        columnWidth: .49,
                                        layout: 'form',
                                        items: [
                                            {
                                                fieldLabel: 'Budget ID',
                                                id: 'budgetid_text',
                                                xtype: 'textfield',
                                                disabled: true,
                                                width: 55
                                            }
                                        ]
                                    },
                                    {
                                        columnWidth: .500,
                                        layout: 'form',
                                        items: [
                                            {
                                                hideLabel: true,
                                                id: 'budgetname_text',
                                                xtype: 'textfield',
                                                disabled: true,
                                                width: 200
                                            }
                                        ]
                                    }
                                ]
                            },
                            {
                                xtype: 'itemselector',
                                fieldLabel: 'Product ID',
                                id: 'item-select',
                                Selectid: 'kode_brg_text',
                                Nameid: 'nama_brg_text',
                                ShowName: true,
                                SelectWidth: 70,
                                prjKodeField: 'prj_kode_text'
                            },
                            {
                                layout: 'column',
                                items: [
                                    {
                                        columnWidth: .49,
                                        layout: 'form',
                                        items: [
                                            {
                                                fieldLabel: 'Qty Request',
                                                xtype: 'textfield',
                                                id: 'arf-qty',
                                                allowBlank: false,
                                                width: 55,
                                                style: "text-align:right",
                                                enableKeyEvents: true,
                                                listeners: {
                                                    'blur': function(t) {
                                                        if (!isNaN(t.getValue()))
                                                        {
                                                            t.setValue(CommaFormatted(t.getValue()));
                                                        }
                                                    },
                                                    'focus': function(t) {
                                                        if (t.getValue().search(",") > 0)
                                                            t.setValue(t.getValue().toString().replace(/\$|\,/g, ''));
                                                    },
                                                    'keyup': function(t, e) {
                                                        if (parseFloat(t.getValue().toString().replace(/\$|\,/g, '')) > 0)
                                                        {
                                                            cekQty(t.getValue());
                                                        }
                                                    }
                                                }
                                            }]
                                    },
                                    {
                                        columnWidth: .3,
                                        layout: 'form',
                                        items: [
                                            {
                                                hideLabel: true,
                                                id: 'uom_text',
                                                xtype: 'textfield',
                                                disabled: true,
                                                width: 30
                                            }
                                        ]
                                    }
                                ]
                            },
                            {
                                layout: 'column',
                                items: [
                                    {
                                        columnWidth: .65,
                                        layout: 'form',
                                        items: [
                                            {
                                                fieldLabel: 'Unit Price',
                                                id: 'price_text',
                                                name: 'price',
                                                allowBlank: false,
                                                xtype: 'textfield',
                                                style: "text-align:right",
                                                enableKeyEvents: true, listeners: {
                                                    'blur': function(t) {
                                                        if (!isNaN(t.getValue()))
                                                        {
                                                            t.setValue(CommaFormatted(t.getValue()));
                                                        }
                                                    },
                                                    'focus': function(t) {
                                                        if (t.getValue().search(",") > 0)
                                                            t.setValue(t.getValue().toString().replace(/\$|\,/g, ''));
                                                    },
                                                    'keyup': function(t, e) {                                                        
                                                        cekPrice(t.getValue());
                                                    }
                                                },
                                                width: 100,
                                                disabled: true
                                            }
                                        ]
                                    },
                                    {
                                        columnWidth: .170,
                                        layout: 'form',
                                        items: [
                                            {
                                                hideLabel: true,
                                                id: 'arf-val',
                                                xtype: 'textfield',
                                                disabled: true,
                                                width: 40
                                            }
                                        ]
                                    }
                                ]
                            }


                        ]
                    }
                ]
            },
            {
                fieldLabel: 'Remark',
                id: 'ket-arf',
                xtype: 'textarea',
                width: 315, 
                enableKeyEvents: true,
                listeners: {
                    'keyup': function(t, e) {
                        oldtext = t.getValue();
                        new_text = oldtext.replace(/\r?\n/g, " ");
                        this.setValue(new_text);
                    }
                }
            }
        ],
        buttons: [
            {
                text: 'Add to ARF List(Cart)',
                id: 'save-to-arf',
                iconCls: 'icon-add',
                handler: function(btn, ev) {insertToARF();},
                scope: this
            },
            {
                text: 'Cancel',
                id: 'cancel-to-arf',
                iconCls: 'icon-cancel',
                handler: function(btn, ev) {
                    clearARFForm();
                    refreshGrid();
                    
                },
                scope: this
            }
        ]
});

// ARF LIST CART AREA

var userColumns2 = [
    new Ext.grid.RowNumberer(),
    {header: "Edit", width: 40, dataIndex: 'id', css: 'text-align:center;', renderer: function(v, p, r) {
        return '<a href="#" onclick="editToARF('+ v +');"><img src="/images/g_rec_upd.png"></a>';
    }},
    {header: "Delete", width: 50, dataIndex: 'id', css: 'text-align:center;', renderer: function(v, p, r) {
        return '<a href="#" onclick="delToARF(' + v + ','+ parseInt(r.data['boq_id'])+');"><img src="/images/g_rec_del.png"></a>';
    }},
    {header: "Budget Id", width: 60, sortable: true, dataIndex: 'budgetid'},
    {header: "Budget Name", width: 150, sortable: true, dataIndex: 'budgetname'},
    {header: "Product Id", width: 65, sortable: true, dataIndex: 'kode_brg'},
    {header: "Description", width: 200, sortable: true, dataIndex: 'nama_brg'},
    {header: "Qty", width: 40, sortable: true, dataIndex: 'qty', css: 'text-align:right;',renderer: function(v, p, r) {
        return CommaFormatted(r.data['qty']);
    }},
    {header: "Uom", width: 50, sortable: true, dataIndex: 'uom'},
    {header: "Price", width: 60, sortable: true, css: 'text-align:right;', renderer: function(v, p, r) {
        return CommaFormatted(r.data['harga']);
    }},
    {header: "Total", width: 100, sortable: true, css: 'text-align:right;', renderer: function(v, p, r) {
        return CommaFormatted((parseFloat(r.data['harga']) * parseFloat(r.data['qty'])).toString());
    }},
    {header: "Currency", width: 100, sortable: true, dataIndex: 'val_kode'},
    {header: "Remark", width: 100, sortable: true, dataIndex: 'ket'}
];

userGrid2 = Ext.extend(Ext.grid.GridPanel, {
    renderTo: 'user-grid2',
    iconCls: 'silk-grid',
    id: 'arf-grid',
    frame: true,
    title: 'ARF List(Cart)',
    height: 250,
    width: 750,
    stateful: false,
    style: 'margin-top: 10px',
    initComponent : function() {

        this.buttons = this.buildUI();

        userGrid.superclass.initComponent.call(this);
    },

    buildUI : function() {},

    onSave : function(btn, ev) {

        var json = '';
    	this.store.each(function(store){
    	json += Ext.util.JSON.encode(store.data) + ',';
    	});
    	json = json.substring(0, json.length - 1);
    	params = {posts:[json]};
    	Ext.Ajax.request({
            url: '/procurement/procurement/insertarf',
            method:'POST',
            success: function(resp){
    		Ext.Msg.alert('Success', 'Data has been saved!');
    		isEdited = false;
            },
            failure:function( action){
          	if(action.failureType == 'server'){
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
          	}else{
                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
          	}
            },
            params: params
        });
    }
});

showAddARF = function(){
    myPanel = Ext.getCmp('abs-budget-panel');
    myPanel.body.load({
	url: '/procurement/procurement/addnewarf',
	scripts : true
    });
};

var userGrids2 = new userGrid2({
    renderTo: 'user-grid2',
    id: 'arf-grid',
    store: storeARF,
    columns : userColumns2,
    loadMask: true,
    bbar: [
        '->',
        {
        xtype: 'button',
	text: 'Save ARF List(Cart)',
	iconCls: 'icon-save',
	handler: function()
	{
	    if (storeARF.getCount() == 0){return false;}

	    var json = '';
            var fileJson = '';
	    var parameter = '';
	    Ext.getCmp('arf-grid').getStore().each(function(store){json += Ext.util.JSON.encode(store.data) + ',';});
            
            json = '[' + json.substring(0, json.length - 1) + ']'; //JSON format fix

            storeFile.each(function(store){fileJson += Ext.util.JSON.encode(store.data) + ',';});
            fileJson = '[' + fileJson.substring(0, fileJson.length - 1) + ']'; //JSON format fix
              
            var prjKode = Ext.getCmp('prj_kode_text').getValue();
            var sitKode = Ext.getCmp('sit_kode_text').getValue();
            var prjNama = Ext.getCmp('prj_nama_text').getValue();
            var sitNama = Ext.getCmp('sit_nama_text').getValue();
            var budgetType = Ext.getCmp('arf_origin_text').getValue();
            var requester2 = Ext.getCmp('mgr_kode_text').getValue();
            var penerima = Ext.getCmp('penerima_text').getValue();
            var bank = Ext.getCmp('bank_text').getValue();
            var bankaccountname = Ext.getCmp('bankaccountname_text').getValue();
            var bankaccountno = Ext.getCmp('bankaccountno_text').getValue();
            var valuta = Ext.getCmp('val_kode_text').getValue();
            var finance = Ext.getCmp('fin_kode_text').getValue();
            var financeName =Ext.getCmp('fin_nama_text').getValue();
            var managerKode = Ext.getCmp('mgr_kode_text').getValue();
            var managerNama = Ext.getCmp('mgr_nama_text').getValue();
            var picKode = Ext.getCmp('pic_kode_text').getValue();
            var picNama = Ext.getCmp('pic_nama_text').getValue();
            var ketin = Ext.getCmp('ketin_text').getValue().replace(/\"|\'|\r|\n/g,' ');
            
            parameter = '[{"prj_kode":"' + prjKode + '","prj_nama":"' + prjNama + '", "sit_kode":"' + sitKode + '", "sit_nama":"' + sitNama + '",  "requester2":"' + requester2 + '","penerima":"' + penerima + '", "bank":"' + bank + '", "bankaccountname":"' + bankaccountname + '", "bankaccountno":"' + bankaccountno + '", "valuta":"' + valuta + '", "pic_kode":"' + picKode + '", "pic_nama":"' + picNama + '", "mgr_kode":"' + managerKode + '", "mgr_nama":"' + managerNama + '", "finance":"' + finance + '",  "financeName":"' + financeName + '", "budgettype":"' + budgetType + '", "ketin":"' + ketin + '"}]';
            params = {posts:json, etc:parameter , file:fileJson};
            myPanel = Ext.getCmp('abs-budget-panel'); //Load the panel
            myPanel.body.load({
                url: '/procurement/procurement/apparfbudget/sales/true', //the url
                scripts : true,
                params: params //our params goes here
            });

	},
	scope: this
    },
    {
        xtype: 'button',
	text: 'Cancel ARF List(Cart)',
	iconCls: 'icon-cancel',
	handler:showAddARF,
	scope: this
    }
    ]
});


//POP UP AREA

function showPrjList()
{
    var columns = [
                    {header:'Budget Code',width:100,sortable:true,dataIndex:'prj_kode'},
                    {header:'Budget Name',width:150,sortable:true,dataIndex:'prj_nama'},
                ];

    data =  [

            {name: 'prj_kode', mapping: 'Prj_Kode'},
            {name: 'prj_nama', mapping: 'Prj_Nama'},
            ];


    urlJson = '/project/list/type/overhead';

    primaryKey = 'prj_kode';
    Pk = primaryKey;
    widthGrid = 380;
    heightGrid = 250;

    var stores = new Ext.data.Store({
        proxy:new Ext.data.HttpProxy({
        url: urlJson
         }),
            reader: new Ext.data.JsonReader({
        root: 'posts',
        totalProperty: 'count'
    },data)
        });

    yAxis=100;
    grids = function()
    {
        grids.superclass.constructor.call(this, {
        store: stores,
        columns: columns,
        x:0,
        y:yAxis,
        id:primaryKey+'_grid',
        loadMask: true,
        bbar:[ new Ext.PagingToolbar({
            pageSize: 100,
            store: stores,
            displayInfo: true,
            displayMsg: 'Displaying data {0} - {1} of {2}',
            emptyMsg: "No data to display"
        })],
        height:heightGrid,
        width:widthGrid,
        listeners: {
            'rowdblclick': function(g, rowIndex, e){
				        	objectName = 'prj_kode';
				            txtBoxChange = 'prj_kode_text';
				            closeForm = true;
				            formId = 'a-form-panel-pjr_kode_button';
				            prox = 'trano_proxy';
				            gri = 'trano_grid';

				            var record = g.getStore().getAt(rowIndex).get(objectName);
				            var record2 = g.getStore().getAt(rowIndex).get("prj_nama");
                            Ext.getCmp('prj_kode_text').setValue(record);

                            if (Ext.getCmp('prj_nama_text') != undefined)
                            {
                            	Ext.getCmp('prj_nama_text').setValue(record2);
                            }
                            if (Ext.getCmp('sit_kode_text') != undefined)
                            {
                            	Ext.getCmp('sit_kode_text').setValue('');
                            }
                            if (Ext.getCmp('sit_nama_text') != undefined)
                            {
                            	Ext.getCmp('sit_nama_text').setValue('');
                            }


                            if (closeForm)
                            {
                                Ext.getCmp(formId).close();
                            }

                            window.setTimeout(function(){
                                    isDblClick = false;
                            }, 0);
                    }
            }
    });
    }
    stores.load();

    Ext.extend(grids, Ext.grid.GridPanel);
    Ext.extend(txtboks,Ext.form.TextField);//PR
    searchPrjKode = function(field,e){
        newUrl = '/default/project/listByParams/name/Prj_Kode/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();

    }
    searchPrjName = function(field,e){
        newUrl = '/default/project/listByParams/name/Prj_Nama/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();
    }

    var aForm =  new Ext.Window({
        id: 'a-form-panel-pjr_kode_button',
        layout: 'absolute',
        minWidth: 300,
        minHeight: 200,
        stateful:false,
        modal: true,
        resizable: false,
        width: 400,
        height: 400,
        items : [
            {
            x: 10,
            y: 12,
            xtype: 'label',
            text: 'Budget Code:'
            },
             new txtboks(80,10,80,'project_code',searchPrjKode),
             {
            x: 170,
            y: 12,
            xtype: 'label',
            text: 'Budget Name:'
            },
            new txtboks(240,10,80,'project_name',searchPrjName),
             new grids('project_list')
        ]

      });
      aForm.title = 'Choose Project';
      aForm.show();
}

function showSitList()
{
    var columns = [
                   {header:'Site Code',width:100,sortable:true,dataIndex:'sit_kode'},
                    {header:'Project Code',width:100,sortable:true,dataIndex:'prj_kode'},
                    {header:'Site Name',width:150,sortable:true,dataIndex:'sit_nama'},
                ];

    data =  [
            {name: 'prj_kode', mapping: 'prj_kode'},
            {name: 'sit_kode', mapping: 'sit_kode'},
            {name: 'sit_nama', mapping: 'sit_nama'},
            ];


    urlJson = '/site/list/type/true/byPrj_Kode/'+Ext.getCmp('prj_kode_text').getValue();

    primaryKey = 'sit_kode';
    Pk = primaryKey;
    widthGrid = 380;
    heightGrid = 250;

    var stores = new Ext.data.Store({
        proxy:new Ext.data.HttpProxy({
        url: urlJson
         }),
            reader: new Ext.data.JsonReader({
        root: 'posts',
        totalProperty: 'count'
    },data)
        });

    yAxis=100;
    grids = function()
    {
        grids.superclass.constructor.call(this, {
        store: stores,
        columns: columns,
        x:0,
        y:yAxis,
        id:primaryKey+'_grid',
        loadMask: true,
        bbar:[ new Ext.PagingToolbar({
            pageSize: 100,
            store: stores,
            displayInfo: true,
            displayMsg: 'Displaying data {0} - {1} of {2}',
            emptyMsg: "No data to display"
        })],
        height:heightGrid,
        width:widthGrid,
        listeners: {
            'rowdblclick': function(g, rowIndex, e){
				        	objectName = 'sit_kode';
				            txtBoxChange = 'sit_kode_text';
				            closeForm = true;
				            formId = 'a-form-panel-site_kode_button';
				            var prjNama = g.getStore().getAt(rowIndex).get('sit_nama');
				            var prjKode = g.getStore().getAt(rowIndex).get('prj_kode');
				            var sitKode = g.getStore().getAt(rowIndex).get(objectName);
				            newUrl = '/procurement/listByParams/name/sit_kode/joinToPod/true/data/' + sitKode + '/Prj_Kode/' + prjKode;
				            prox = 'trano_proxy';
				            gri = 'trano_grid';
				            var record = g.getStore().getAt(rowIndex).get(objectName);
				            var record2 = g.getStore().getAt(rowIndex).get("sit_nama");
				            Ext.getCmp('sit_kode_text').setValue(record);
				            if (Ext.getCmp('sit_nama_text') != undefined)
				            {
				            	Ext.getCmp('sit_nama_text').setValue(record2);
				            }


                            if (closeForm)
                            {
                                Ext.getCmp(formId).close();
                            }

                            window.setTimeout(function(){
                                    isDblClick = false;
                            }, 0);
                    }
            }
    });
    }
    stores.load();

    Ext.extend(grids, Ext.grid.GridPanel);
    Ext.extend(txtboks,Ext.form.TextField);//PR
    searchSiteName = function(field,e){
        newUrl = '/default/site/listbyproject/sit_nama/' + field.getValue() + '/prj_kode/' + Ext.getCmp('prj_kode_text').getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();

    }
    searchSiteKode = function(field,e){
        newUrl = '/default/site/listbyproject/sit_kode/' + field.getValue() + '/prj_kode/' + Ext.getCmp('prj_kode_text').getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();

    }

    var aForm =  new Ext.Window({
        id: 'a-form-panel-site_kode_button',
        layout: 'absolute',
        minWidth: 300,
        minHeight: 200,
        stateful:false,
        modal: true,
        resizable: false,
        width: 400,
        height: 400,
        items : [
             {
            x: 10,
            y: 12,
            xtype: 'label',
            text: 'Site Code:'
            },
             new txtboks(80,10,80,'site_code',searchSiteKode),
             {
            x: 170,
            y: 12,
            xtype: 'label',
            text: 'Site Name:'
            },
            new txtboks(240,10,80,'site_name',searchSiteName),
             new grids('site_list')
        ]

      });
      aForm.title = 'Pop Up Window';
      aForm.show();
}

function showManagerList()
{
    var columns = [
        {header:'Uid',width:100,sortable:true,dataIndex:'uid'},
        {header:'Manager Name',width:150,sortable:true,dataIndex:'nama'},
    ];

    data =  [{name: 'uid', mapping: 'uid'},{name: 'nama', mapping: 'nama'},];

    urlJson = '/default/manager/list';

    primaryKey = 'uid';
    Pk = primaryKey;
    widthGrid = 380;
    heightGrid = 250;

    var stores = new Ext.data.Store({
        proxy:new Ext.data.HttpProxy({
            url: urlJson
        }),
        reader: new Ext.data.JsonReader({
            root: 'posts',
            totalProperty: 'count'
        },data)
    });

    yAxis=100;
    grids = function()
    {
        grids.superclass.constructor.call(this, {
            store: stores,
            columns: columns,
            x:0,
            y:yAxis,
            id:primaryKey+'_grid',
            loadMask: true,
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: stores,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            height:heightGrid,
            width:widthGrid,
            listeners: {
                'rowdblclick': function(g, rowIndex, e){
		
                    objectName = 'uid';
                    txtBoxChange = 'mgr_kode_text';
                    closeForm = true;
                    formId = 'a-form-panel-mgr_kode_button';
                    var uid = g.getStore().getAt(rowIndex).get(objectName);
                    newUrl = '/default/manager/dblclick/name/uid/data/' + uid;
                    prox = 'trano_proxy';
                    gri = 'trano_grid';

                    var record = g.getStore().getAt(rowIndex).get(objectName);
                    var record2 = g.getStore().getAt(rowIndex).get("nama");
                    Ext.getCmp('mgr_kode_text').setValue(record);

                    if (Ext.getCmp('mgr_nama_text') != undefined)
                    {
                        Ext.getCmp('mgr_nama_text').setValue(record2);
                    }

                    if (closeForm)
                    {
                        Ext.getCmp(formId).close();
                    }

                    window.setTimeout(function(){
                        isDblClick = false;
                    }, 0);
                }
            }
        });
    }
    stores.load();

    Ext.extend(grids, Ext.grid.GridPanel);
    Ext.extend(txtboks,Ext.form.TextField);
    searchMgrKode = function(field,e){
        newUrl = '/default/manager/dblclick/name/uid/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();

    }
    
    searchMgrName = function(field,e){
        newUrl = '/default/manager/dblclick/name/Name/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();
    }

    var aForm =  new Ext.Window({
        id: 'a-form-panel-mgr_kode_button',
        layout: 'absolute',
        minWidth: 300,
        minHeight: 200,
        stateful:false,
        modal: true,
        resizable: false,
        width: 400,
        height: 400,
        items : [
            {
                x: 10,
                y: 12,
                xtype: 'label',
                text: 'Manager Uid:'
            },
            new txtboks(90,10,80,'manager_code',searchMgrKode),
            {
                x: 180,
                y: 12,
                xtype: 'label',
                text: 'Manager Name:'
            },
            new txtboks(260,10,80,'manager_name',searchMgrName),
             new grids('manager_list')
        ]

    });
    aForm.title = 'Choose Manager';
    aForm.show();
}

function showUserList()
{
    var columns =[
        new Ext.grid.RowNumberer({width: 30}),
        {header:'User Name',width:250,sortable:true,dataIndex:'name'}
    ];

    data =  [
        {name: 'id', mapping: 'id'},
        {name: 'uid', mapping: 'uid'},
        {name: 'name', mapping: 'name'}
    ];

    urlJson = '/default/user/list';

    primaryKey = 'id';
    Pk = primaryKey;
    widthGrid = 380;
    heightGrid = 250;

    var stores = new Ext.data.Store({
            proxy:new Ext.data.HttpProxy({
            url: urlJson
         }),
        reader: new Ext.data.JsonReader({
            root: 'posts',
            totalProperty: 'count'
        },data)
    });

    yAxis=100;
    grids = function()
    {
        grids.superclass.constructor.call(this, {
            store: stores,
            columns: columns,
            x:0,
            y:yAxis,
            id:primaryKey+'_grid',
            loadMask: true,
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: stores,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            height:heightGrid,
            width:widthGrid,
            listeners: {
                'rowdblclick': function(g, rowIndex, e){
                    objectName = 'id';
                    txtBoxChange = 'mgr_kode_text';
                    closeForm = true;
                    formId = 'a-form-panel-user_button';
                    var id = g.getStore().getAt(rowIndex).get(objectName);
                    newUrl = '/default/user/listByParams/name/id/data/' + id;
                    prox = 'trano_proxy';
                    gri = 'trano_grid';

                    var record = g.getStore().getAt(rowIndex).get("uid");
                    var record2 = g.getStore().getAt(rowIndex).get("name");
                    Ext.getCmp('fin_kode_text').setValue(record);

                    if (Ext.getCmp('fin_nama_text') != undefined)
                    {
                        Ext.getCmp('fin_nama_text').setValue(record2);
                    }
                
                    if (closeForm)
                    {
                        Ext.getCmp(formId).close();
                    }

                    window.setTimeout(function(){
                        isDblClick = false;
                    }, 0);
                }
            }
        });
    }
    
    stores.load({
        params: {
            start: 0,
            limit: 100
        }
    });

    Ext.extend(grids, Ext.grid.GridPanel);
    Ext.extend(txtboks,Ext.form.TextField);
    searchUserLogin = function(field,e){
        newUrl = '/default/user/listByParams/name/master_login/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();

    }
    searchUserName = function(field,e){
        newUrl = '/default/user/listByParams/name/Name/data/' + field.getValue();    prox = Pk+'_proxy';
        gri = Pk+'_grid';
        proxies = Ext.getCmp(gri).getStore();
        proxies.proxy = new Ext.data.HttpProxy({
            url: newUrl
             });
        Ext.getCmp(gri).getStore().reload();
    }

    var aForm =  new Ext.Window({
        id: 'a-form-panel-user_button',
        layout: 'absolute',
        minWidth: 300,
        minHeight: 200,
        stateful:false,
        modal: true,
        resizable: false,
        width: 400,
        height: 400,
        items : [
            {
            x: 10,
            y: 12,
            xtype: 'label',
            text: 'User Login:'
            },
             new txtboks(100,10,80,'master_login',searchUserLogin),
             {
            x: 190,
            y: 12,
            xtype: 'label',
            text: 'User Name:'
            },
            new txtboks(260,10,80,'Name',searchUserName),
             new grids('user_list')
        ]

      });
      aForm.title = 'Choose User';
      aForm.show();
}


//FUNCTIONS AREA

function refreshGrid()
{
    <?php if ($this->json != '') {  ?>
         firstBack ++;
    <?php }  ?>
    Ext.getCmp('boq3-grid').enable();
    Ext.getCmp('arf-grid').enable();
    Ext.getCmp('arf-grid').getSelectionModel().clearSelections();
    Ext.getCmp('boq3-grid').getSelectionModel().clearSelections();
    Ext.getCmp('arf-grid').getView().refresh();
    Ext.getCmp('boq3-grid').getView().refresh();
}

function clearARFForm()
{
    Ext.getCmp('arf-form').getForm().reset();
    Ext.getCmp('requester_text').setValue('');
    Ext.getCmp('user_selector').clearData();
    
    Ext.getCmp('save-to-arf').setText('Add to ARF List(Cart)');
    Ext.getCmp('nama_brg_text').setValue();
    
    if (document.getElementById('boq3') != undefined)
        document.getElementById('boq3').innerHTML = '0';
    if (document.getElementById('totalRequest') != undefined)
        document.getElementById('totalRequest').innerHTML = '0';
    if (document.getElementById('balance') != undefined)
        document.getElementById('balance').innerHTML = '0';
    if (document.getElementById('newbalance') != undefined)
        document.getElementById('newbalance').innerHTML = '0';
	
    Ext.getCmp('kode_brg_text').disable();
    Ext.getCmp('nama_brg_text').setValue();
}

function init()
{
    Ext.getCmp('kode_brg_text').disable();
    Ext.getCmp('price_text').enable();
    Ext.getCmp('pic_kode_text').setValue('<?php echo $this->uid; ?>');
    Ext.getCmp('pic_nama_text').setValue('<?php echo $this->nama; ?>');
    Ext.getCmp('boq3-submit').enable();
    Ext.Ajax.request({
	scope: this,
	results: 0,
        url: '/default/home/whoami',
        method:'POST',
        success: function(result, request){
            var returnData = Ext.util.JSON.decode(result.responseText);
            if(returnData.user.id != '') {}
	}
    });
    
    clearARFForm();
}

function cekTotal()
{
    var boq3 = document.getElementById('boq3').innerHTML;
    var  totalRequest = document.getElementById(' totalRequest').innerHTML;

    totalRequest = parseFloat( totalRequest.toString().replace(/\$|\,/g,''));
    boq3 = parseFloat(boq3.toString().replace(/\$|\,/g,''));
    var unitQty = parseFloat(Ext.getCmp('arf-qty').getValue().replace(/\$|\,/g,''));
    var newPrice = Ext.getCmp('price_text').getValue().replace(/\$|\,/g,'');
    var newBalance = parseFloat( totalRequest) + (parseFloat(newPrice) * parseFloat(unitQty));
    if (moneycomp(newBalance,'>',boq3))
    {
        Ext.MessageBox.show({
            title: 'Error',
            msg: 'Request Quantity is over the Budget!',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR
        });
        return false;
    }
    else
    {
        return true;
    }
}

function insertToARF()
{
    var idBoq = Ext.getCmp('boq-id').getValue();
    
    var requesterUID = Ext.getCmp('user_selector').getUid();
    
    if (requesterUID == undefined || requesterUID == '')
    {
        Ext.MessageBox.show({
            title: 'Error',
            msg: 'Please choose Requester for this Item!',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR
        });
        return false;
    }
        
    var kode_brg = Ext.getCmp('kode_brg_text').getValue();
    if (kode_brg == undefined || kode_brg == '')
    {
        Ext.MessageBox.show({
            title: 'Error',
            msg: 'Please Select Product!',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR
        });
        return false;
    }
                    
    var qty = parseFloat(Ext.getCmp('arf-qty').getValue().toString().replace(/\$|\,/g, ''));
    var price = parseFloat(Ext.getCmp('price_text').getValue().toString().replace(/\$|\,/g, ''));
                    
    if (parseFloat(qty) <= 0 || parseFloat(price) <= 0)
    {
        Ext.MessageBox.show({
            title: 'Error',
            msg: 'Quantity and Price should be more than 0!',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR
        });
        return false;
    }
        
    var boq3 = document.getElementById('boq3').innerHTML;
    boq3 = parseFloat(boq3.toString().replace(/\$|\,/g, ''));
       
    var totalRequest = document.getElementById('totalRequest').innerHTML;
    totalRequest = parseFloat(totalRequest.toString().replace(/\$|\,/g, ''));
                
    var unitPrice = parseFloat(Ext.getCmp('price_text').getValue().replace(/\$|\,/g, ''));
    var newQty = parseFloat(Ext.getCmp('arf-qty').getValue().replace(/\$|\,/g, ''));
    var newBalance = parseFloat(totalRequest) + (parseFloat(newQty) * parseFloat(unitPrice));          
        
    if (newBalance > boq3)
    {
        Ext.getCmp('save-to-arf').disable();
        Ext.MessageBox.show({
            title: 'Error',
            msg: 'Your Request Is Over Budget!',
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR
        });
        return false;
    }
        
    var arf = storeARF.queryBy(function(record2,id2){
        return record2.get('budgetid') == Ext.getCmp('budgetid_text').getValue()  && record2.get('kode_brg') == Ext.getCmp('kode_brg_text').getValue();
    });
    
    var ori = store.getAt(store.findExact('id', idBoq.toString()));
        
    if(arf.length !=0)
    {
        if(Ext.getCmp('arf-status').getValue()=='edit')
        {
            ori.data['totalRequests'] = parseFloat(ori.data['totalRequests']) - parseFloat(arf.items[0].data['qty'] * arf.items[0].data['harga']);
            arf.items[0].data['qty'] = parseFloat(qty);
            arf.items[0].data['harga'] = parseFloat(price);
            arf.items[0].data['ket'] = Ext.getCmp('ket-arf').getValue();
            arf.items[0].data['requester'] = requesterUID;
            arf.items[0].data['requesterName'] = Ext.getCmp('requester_text').getValue();
        }
        else
        {
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'You have selected this item',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
            return false;
        }
    }
    else
    {
        var e = new arflist({
            id: storeARF.getCount()+1,
            boq_id: parseInt(Ext.getCmp('boq-id').getValue()),
            budgetid: Ext.getCmp('budgetid_text').getValue(),
            budgetname: Ext.getCmp('budgetname_text').getValue(),
            kode_brg: Ext.getCmp('kode_brg_text').getValue(),
            nama_brg: Ext.getCmp('nama_brg_text').getValue(),
            qty: qty,
            harga: price,
            ket: Ext.getCmp('ket-arf').getValue(),
            val_kode: Ext.getCmp('arf-val').getValue(),   
            uom: Ext.getCmp('uom_text').getValue(),  
            requester: requesterUID,
            requesterName: Ext.getCmp('requester_text').getValue(),
        });
                            
        storeARF.insert(0, e);
    }
    
    Ext.getCmp('save-to-arf').disable();
                    
    ori.data['totalRequests'] = parseFloat(ori.data['totalRequests']) + parseFloat(qty * price);
    
    clearARFForm();
    refreshGrid();
}

function editToARF(id)
{
    Ext.getCmp('save-to-arf').setText('Update to ARF List(Cart)');
    var arf = storeARF.getAt(storeARF.findExact('id', parseInt(id)));
        
    Ext.getCmp('arf-id').setValue(parseInt(arf.data['id']));
    Ext.getCmp('boq-id').setValue(parseInt(arf.data['boq_id']));
    Ext.getCmp('arf-status').setValue('edit');
    Ext.getCmp('budgetid_text').setValue(arf.data['budgetid']);
    Ext.getCmp('budgetname_text').setValue(arf.data['budgetname']);
    Ext.getCmp('requester_text').setValue(arf.data['requesterName']);
    Ext.getCmp('user_selector').setUid(arf.data['requester']);
    Ext.getCmp('kode_brg_text').setValue(arf.data['kode_brg']);
    Ext.getCmp('nama_brg_text').setValue(arf.data['nama_brg']);
    Ext.getCmp('arf-val').setValue(arf.data['val_kode']);
    Ext.getCmp('uom_text').setValue(arf.data['uom']);
    Ext.getCmp('price_text').setValue(CommaFormatted(arf.data['harga']));
    Ext.getCmp('arf-qty').setValue(CommaFormatted(arf.data['qty']));
    Ext.getCmp('ket-arf').setValue(arf.data['ket']);

    Ext.getCmp('kode_brg_text').disable();
    Ext.getCmp('arf-grid').disable();
    Ext.getCmp('boq3-grid').disable();
    Ext.getCmp('save-to-arf').enable();
    Ext.getCmp('cancel-to-arf').enable();
    Ext.getCmp('price_text').enable();
        
    var idBoq = arf.data['boq_id'];
    var ori = store.getAt(store.findExact('id', idBoq.toString()));
        
    var valuta = arf.data['val_kode'];
    var total = ori.data['tranoAFE']=='' ? parseFloat(ori.data['totalPrice']):parseFloat(ori.data['totalAFE']);
    var totalRequest = parseFloat(ori.data['totalRequests']) - parseFloat(arf.data['qty'] * arf.data['harga']);
    var balance = parseFloat(total - totalRequest);
    document.getElementById('arf-available').innerHTML = '<table class="tablebox"><tr><td>Budget after AFE</td><td>:</td><td align="right"><b id="boq3">' + CommaFormatted(total.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>Requests Total</td><td>:</td><td align="right"><b id="totalRequest">' + CommaFormatted(totalRequest.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>Balance</td><td>:</td><td align="right"><b id="balance" style="color:#FF3F7D">' + CommaFormatted(balance.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>New Balance</td><td>:</td><td align="right"><b id="newbalance" style="color:#FF3F7D">0</b></td></tr></table>';

}

function delToARF(id,boqId)
{ 
    Ext.MessageBox.confirm('Confirm', 'Are you sure want to delete this?', function(btn) {
        if (btn == 'yes')
        {            
            var ori = store.getAt(store.findExact('id', boqId.toString()));
            var arf = storeARF.getAt(storeARF.findExact('id', parseInt(id)));
                
            ori.data['totalRequests'] = parseFloat(ori.data['totalRequests']) - parseFloat(arf.data['qty'] * arf.data['harga']);
            storeARF.remove(arf);
                
            refreshGrid();
        }

    });
}

function addToARF(idBoq)
{
    var ori = store.getAt(store.findExact('id', idBoq.toString()));
        
    if (ori != undefined)
    {
        Ext.getCmp('boq-id').setValue(idBoq);
        Ext.getCmp('arf-status').setValue('new');
        Ext.getCmp('save-to-arf').setText('Add to ARF List(Cart)');
        Ext.getCmp('budgetid_text').setValue(ori.data['budgetid']);
	Ext.getCmp('budgetname_text').setValue(ori.data['budgetname']);
	Ext.getCmp('arf-val').setValue(ori.data['val_kode']);
	Ext.getCmp('price_text').setValue(CommaFormatted(ori.data['price']));
	Ext.getCmp('kode_brg_text').enable();
	Ext.getCmp('arf-qty').setValue('0');

        Ext.getCmp('arf-grid').disable();
	Ext.getCmp('boq3-grid').disable();

        if (isMscWorkid(ori.data['workid']))
        {
            Ext.getCmp('kode_brg_text').setValue('');
            Ext.getCmp('nama_brg_text').setValue('');
            Ext.getCmp('price_text').setValue('0');
            Ext.getCmp('kode_brg_text').enable();
            Ext.getCmp('arf-qty').enable('0');   
        }
            
        var valuta = ori.data['val_kode'];
        var total = ori.data['tranoAFE']=='' ? parseFloat(ori.data['totalPrice']):parseFloat(ori.data['totalAFE']);
        var totalRequest = parseFloat(ori.data['totalRequests']);
        var balance = parseFloat(total - totalRequest);
        document.getElementById('arf-available').innerHTML = '<table class="tablebox"><tr><td>Budget after AFE</td><td>:</td><td align="right"><b id="boq3">' + CommaFormatted(total.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>Requests Total </td><td>:</td><td align="right"><b id="totalRequest">' + CommaFormatted(totalRequest.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>Balance</td><td>:</td><td align="right"><b id="balance" style="color:#FF3F7D">' + CommaFormatted(balance.toFixed(4)) + ' ' + valuta + '</b></td></tr><tr><td>New Balance </td><td>:</td><td align="right"><b id="newbalance" style="color:#FF3F7D">0</b></td></tr></table>';
            
    }
    else
    {
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Error while fetching data...',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
            return false;
    }
}

function cekQty(values)
{
    if (!isNaN(values))
    {
        var newQty = parseFloat(values);

        var boq3 = document.getElementById('boq3').innerHTML;
        var totalRequest = document.getElementById('totalRequest').innerHTML;
        totalRequest = parseFloat(totalRequest.toString().replace(/\$|\,/g, ''));
        boq3 = parseFloat(boq3.toString().replace(/\$|\,/g, ''));
                
        var unitPrice = parseFloat(Ext.getCmp('price_text').getValue().replace(/\$|\,/g, ''));
        var newBalance = parseFloat(totalRequest) + (parseFloat(newQty) * parseFloat(unitPrice));    
                
        if (moneycomp(newBalance, '>', boq3, 4))
        {
            Ext.getCmp('save-to-arf').disable();
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Your Request Is Over Budget!',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
            return false;
        }
        else
        {
            temp = boq3 - newBalance;
            document.getElementById('newbalance').innerHTML = CommaFormatted(temp.toFixed(2)) + ' ' + Ext.getCmp('arf-val').getValue();
            Ext.getCmp('save-to-arf').enable();
        }

        return true;
    }
}

function cekPrice(values)
{
    if (!isNaN(values))
    {
        var newPrice = parseFloat(values);
        var boq3 = document.getElementById('boq3').innerHTML;
        var totalRequest = document.getElementById('totalRequest').innerHTML;
            
        totalRequest = parseFloat(totalRequest.toString().replace(/\$|\,/g, ''));
        boq3 = parseFloat(boq3.toString().replace(/\$|\,/g, ''));
                
        var qty = parseFloat(Ext.getCmp('arf-qty').getValue().replace(/\$|\,/g, ''));
        var newBalance = parseFloat(totalRequest) + (parseFloat(newPrice) * parseFloat(qty));
                
        if (moneycomp(newBalance, '>', boq3, 4))
        {
            Ext.getCmp('save-to-arf').disable();
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Your Request Is Over Budget!',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
            return false;
        }
        else
        {
            temp = boq3 - newBalance;
            document.getElementById('newbalance').innerHTML = CommaFormatted(temp.toFixed(2)) + ' ' + Ext.getCmp('arf-val').getValue();
            Ext.getCmp('save-to-arf').enable();
        }

        return true;
    }
}


var submitBoq3 = function (){

    var valuta = Ext.getCmp('val_kode_text').getValue();
    var prjKode = Ext.getCmp('prj_kode_text').getValue();
    var sitKode = Ext.getCmp('sit_kode_text').getValue();
    var manager = Ext.getCmp('mgr_kode_text').getValue();
    var finance = Ext.getCmp('fin_kode_text').getValue();

    if (valuta == '' )
    {
    	Ext.MessageBox.show({
	    title: 'Error',
	    msg: 'Please Select Currency Code !',
	    buttons: Ext.MessageBox.OK,
	    icon: Ext.MessageBox.ERROR
	});
        return false;
    }
    
    if (manager == '' )
    {
    	Ext.MessageBox.show({
	    title: 'Error',
	    msg: 'Please Select Manager !',
	    buttons: Ext.MessageBox.OK,
	    icon: Ext.MessageBox.ERROR
	});
        return false;
    }
    
    if (finance == '' )
    {
    	Ext.MessageBox.show({
	    title: 'Error',
	    msg: 'Please Select Finance Staff !',
	    buttons: Ext.MessageBox.OK,
	    icon: Ext.MessageBox.ERROR
	});
        return false;
    }

    if (prjKode == '' || sitKode == '')
    {
    	Ext.MessageBox.show({
	    title: 'Error',
	    msg: 'Please Select Project/Site Code !',
	    buttons: Ext.MessageBox.OK,
	    icon: Ext.MessageBox.ERROR
	});
        return false;
    }

    boq3Reload(prjKode,sitKode,valuta);

};

function boq3Reload(prjKode,sitKode)
{
    var params = {};
    var col;
         
    storeARF.each(function(stores) {
                
            if(isMscWorkid(stores.get('workid')))
            {
                params = {prj_kode:prjKode, sit_kode:sitKode, workid: stores.data['workid']};
            }
            else
            {
                params = {prj_kode:prjKode, sit_kode:sitKode, workid: stores.data['workid'], kode_brg: stores.data['kode_brg']};
            }
            
            
            Ext.Ajax.request({
                
                url: '/procurement/procurement/getboq3nonproject',
                method: 'POST',
                params: params,
                success: function(resp) {
                    
                    var returnData = Ext.util.JSON.decode(resp.responseText); 
                    var totalRequests =parseFloat(returnData.posts[0]['totalRequests'])  + parseFloat(stores.data['qty']*stores.data['harga']);
                                  
                    var s = new boq3list({
                            'id': returnData.posts[0]['id'],
                            'workid': returnData.posts[0]['workid'],
                            'workname': returnData.posts[0]['workname'],
                            'kode_brg': returnData.posts[0]['kode_brg'],
                            'nama_brg': returnData.posts[0]['nama_brg'],
                            'cfs_kode': returnData.posts[0]['cfs_kode'],
                            'cfs_nama': returnData.posts[0]['cfs_nama'],
                            'qty': returnData.posts[0]['qty'],
                            'uom': returnData.posts[0]['uom'],
                            'price': returnData.posts[0]['price'],
                            'totalPrice': returnData.posts[0]['totalPrice'],
                            'val_kode': returnData.posts[0]['val_kode'],
                            'totalRequests':totalRequests ,
                            'totalAFE': returnData.posts[0]['totalAFE'],
                            'tranoAFE': returnData.posts[0]['tranoAFE']
                    });
                    
                    if (!isMscWorkid(stores.data['workid']))
                    {
                        col = store.queryBy(function(record, id) {
                            return record.get('workid') == stores.data['workid'] && record.get('kode_brg') == stores.data['kode_brg'];
                        });
                
                    }
                    else
                    {
                        col = store.queryBy(function(record, id) {
                            return record.get('workid').toString() == stores.data['workid'];
                        });
                
                    }
                       
                    if(col.length == 0){store.add(s);}

                    var strARF = storeARF.getAt(storeARF.findExact('id', stores.data['id']));
                    strARF.data['boq_id'] = returnData.posts[0]['id'] ;
                        
                }
            });

    });
    
}

function deleteFile()
{
    var rec = Ext.getCmp('files-grid').getSelectionModel().getSelections();
	theFile = storeFile.getAt(storeFile.findExact('id', rec[0].data['id']));

    if (theFile != undefined)
    {
        Ext.MessageBox.confirm('Confirm', 'Are you sure want to delete this file?', function(btn){
            if (btn== 'yes')
            {
                if (theFile.data['status'] == 'new')
                    var params = {filename:theFile.data['savename']};
                    Ext.Ajax.request({
                        url: '/procurement/procurement/deletefile',
                        method:'POST',
                        success: function(result, request){
                            var returnData = Ext.util.JSON.decode(result.responseText);
                            if (returnData.success)
                            {
                                storeFile.remove(theFile);
                                Ext.getCmp('files-grid').getView().refresh();
                            }
                            else
                            {
                                Ext.Msg.alert('Error!', returnData.msg);
                                return false;
                            }
                        },
                        failure:function( action){
                        if(action.failureType == 'server'){
                          obj = Ext.util.JSON.decode(action.response.responseText);
                          Ext.Msg.alert('Error!', obj.errors.reason);
                        }else{
                          Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                        }
                        },
                        params: params
                    });
            }
	    });
    }
};

Ext.onReady(function() {
    Ext.QuickTips.init();
    init();
    
    <?php if ($this->json != '') {  ?>
    
        if(jsonFile != null)
        {
            storeFile.loadData(jsonFile);
        }
        
        Ext.getCmp('prj_kode_text').setValue(jsonEtc[0]['prj_kode']);
        Ext.getCmp('prj_nama_text').setValue(jsonEtc[0]['prj_nama']);
        Ext.getCmp('sit_kode_text').setValue(jsonEtc[0]['sit_kode']);
        Ext.getCmp('sit_nama_text').setValue(jsonEtc[0]['sit_nama']);
        Ext.getCmp('mgr_kode_text').setValue(jsonEtc[0]['requester2']);
        Ext.getCmp('penerima_text').setValue(jsonEtc[0]['penerima']);
        Ext.getCmp('bank_text').setValue(jsonEtc[0]['bank']);
        Ext.getCmp('bankaccountname_text').setValue(jsonEtc[0]['bankaccountname']);
        Ext.getCmp('bankaccountno_text').setValue(jsonEtc[0]['bankaccountno']);
        Ext.getCmp('val_kode_text').setValue(jsonEtc[0]['valuta']);
        Ext.getCmp('fin_kode_text').setValue(jsonEtc[0]['finance']);
        Ext.getCmp('fin_nama_text').setValue(jsonEtc[0]['financeName']);
        Ext.getCmp('mgr_kode_text').setValue(jsonEtc[0]['mgr_kode']);
        Ext.getCmp('mgr_nama_text').setValue(jsonEtc[0]['mgr_nama']);
        Ext.getCmp('pic_kode_text').setValue(jsonEtc[0]['pic_kode']);
        Ext.getCmp('pic_nama_text').setValue(jsonEtc[0]['pic_nama']);
        Ext.getCmp('ketin_text').setValue(jsonEtc[0]['ketin']);
            
        submitBoq3();

    <?php }  ?>
});


</script>

<div id="user-form"></div>
<div id="form_file"></div>
<div id="user-grid"></div>
<div id="separator">
<div id="user-form2" style="float:left;"></div>
<div class="sidebox" style="float:left;width:40em;">
	<div class="boxhead"><h2>Available Total</h2></div>
	<div class="boxbody" id="arf-available">

	</div>
</div>
</div>
<div id="user-grid2" style="margin-top:10px;float:left;"></div>

