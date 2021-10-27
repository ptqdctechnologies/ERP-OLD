/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * To change this template use File | Settings | File Templates.
 */

Ext.ns('Ext.ux.grid');

Ext.ux.uploadFile = Ext.extend(Ext.FormPanel,  {
    initAction: function(){
        var rowactions = new Ext.ux.grid.RowActions({
            actions:[
                {
                    iconCls:'silk-magnifier',
                    qtip:'Download',
                    id: 'view'
                },
                {
                    iconCls:'icon-delete',
                    qtip:'Delete',
                    id: 'delete'
                }
            ]
            ,header: ''
        });

        return rowactions;
    },

    getJSONFromStore: function()
    {
        var jsonJurnal = '';
        if (this.store.getCount() > 0)
        {
            this.store.each(function(store){
                var encode = Ext.util.JSON.encode(store.data);
                if (encode != undefined)
                    jsonJurnal += encode + ',';
            });
            jsonJurnal = '[' + jsonJurnal.substring(0, jsonJurnal.length - 1) + ']';
        }
        return jsonJurnal;
    },

    getStore: function()
    {
        return this.store;
    },

    removeAll: function()
    {
        this.store.removeAll();
    },

    getCount: function()
    {
        return this.store.getCount();
    },

    loadData: function(json)
    {
        if (!json)
            return false;

        this.store.removeAll();
        this.store.loadData(json);
        this.getView().refresh();
    },

    recordFile: new Ext.data.Record.create([
        {
            name: 'id',
            type: 'integer'
        },{
            name: 'filename',
            type: 'string'
        },{
            name: 'savename',
            type: 'string'
        },{
            name: 'status',
            type: 'string'
        },{
            name: 'path',
            type: 'string'
        }
    ]),

    store: null,

    initComponent : function() {

        var store = new Ext.data.Store ({
            reader:new Ext.data.JsonReader({
                fields: this.recordFile,
                root: 'data'
            })
        });

        this.store = store;

        var recordFile = this.recordFile;

        var rowactions = this.initAction(),
            columns = [
                new Ext.grid.RowNumberer({
                    width: 30
                }),
                rowactions,
                {header: "File Name",width: 150, dataIndex: 'filename'}
            ];

        rowactions.on('action',function(grid,record,action,row,col){

            if(action == 'icon-delete')
            {
                Ext.MessageBox.confirm('Confirm', 'This action will delete this file, Proceed?',
                    function(btn)
                    {
                        if (btn == 'yes')
                        {
                            if (record && record.get('tipe') == '')
                            {
                                gridFile.getStore().remove(record);
                                gridFile.getView().refresh();
                            }
                            else if(record.get('status') == 'new')
                            {
                                Ext.Ajax.request({
                                    results: 0,
                                    url: '/default/file/delete-new-file',
                                    params: {
                                        savename: record.get("savename")
                                    },
                                    method:'POST',
                                    success: function(result, request){
                                        var returnData = Ext.util.JSON.decode(result.responseText);
                                        if (returnData.success)
                                        {
                                            gridFile.getStore().remove(record);
                                            gridFile.getView().refresh();
                                        }
                                    }
                                });
                            }
                            else
                            {
                                Ext.Msg.alert('Error','This item cannot be deleted!');
                            }
                        }
                    },this
                );
            }
            else if(action == 'silk-magnifier')
            {
                location.href="/default/file/download/path/files/filename/" + record.get("savename");
            }
        },this);

        var gridFile = new Ext.grid.GridPanel ({
            iconCls: 'silk-grid',
            height: 120,
            style: 'margin-left: 5px',
            store: store,
            trackMouseOver: true,
            view : new Ext.grid.GridView({
                forceFit: true
            }),
            columns: columns,
            plugins: rowactions
        });

        this.fileUpload = true;
        var formUpload = this;
        this.items = [
            {
                xtype: 'compositefield',
                fieldLabel: 'Attach File',
                msgTarget : 'under',
                anchor    : '-20',
                defaults: {
                    flex: 1
                },
                items: [
                    {
                        xtype: 'fileuploadfield',
                        emptyText: 'Select a File',
                        allowBlank: false,
                        name: 'file-path',
                        buttonText: '',
                        width: 200,
                        buttonCfg: {
                            iconCls: 'upload-icon'
                        }
                    },
                    {
                        xtype: 'button',
                        text: 'Upload',
                        iconCls: 'icon-arrow-up',
                        handler: function()
                        {
                            if(formUpload.getForm().isValid()){
                                formUpload.getForm().submit({
                                    url: '/default/file/upload',
                                    params: {
                                        type: 'PR'
                                    },
                                    scope: this,
                                    waitMsg: 'Uploading file...',
                                    success: function(form,action){
                                        var returnData = action.result;
                                        if( returnData.success) {
                                            var c = new recordFile({
                                                id:parseFloat(store.getCount() + 1),
                                                filename: returnData.filename,
                                                savename: returnData.savename,
                                                path: returnData.path,
                                                status: 'new'
                                            });
                                            store.add(c);
                                            gridFile.getView().refresh();
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
                        }
                    }
                ]
            },
            gridFile
        ];

//        this.layout = 'form';

//        if (this.arrayJurnal != undefined)
//        {
//            this.store.loadData(this.arrayJurnal);
//        }

        Ext.ux.uploadFile.superclass.initComponent.call(this);
    }

});

Ext.reg('uploadFile', Ext.ux.uploadFile);