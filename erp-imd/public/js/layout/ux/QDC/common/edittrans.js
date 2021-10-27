/**
 * Created by JetBrains PhpStorm.
 * User: bherly
 * To change this template use File | Settings | File Templates.
 */

Ext.ux.editTrans = Ext.extend(Ext.Window,  {
    stateful: false,
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
    },

    store: null,

    initComponent : function() {

        this.grid = new Ext.ux.customGrid({
            type: this.type,
            width: 600,
            height: 300,
            layout: 'fit',
            urlLoadData: this.urlLoadData,
            paramsLoadData: this.paramsLoadData,
            allowedID: this.allowedID,
            viewConfig: {
                forceFit: true
            },
            loadMask: true
        });

        this.items = [
            this.grid
        ];

        this.buttons= [
            {
                xtype: 'button',
                text: 'OK',
                handler: function() {
                    if (this.callback != undefined)
                        this.callback(this.grid.getJSONFromStore());
                },
                scope: this
            },
            {
                xtype: 'button',
                text: 'Cancel',
                handler: function() {
                    this.close();
                },
                scope: this
            }
        ];

        Ext.ux.editTrans.superclass.initComponent.call(this);
    },
    layout: 'fit',
    resizable: false,
    height: 310,
    width: 610

});

Ext.reg('edittrans', Ext.ux.editTrans);