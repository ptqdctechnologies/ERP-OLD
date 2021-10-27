Ext.ux.userroleSelector = Ext.extend(Ext.Window,  {
    stateful: false,
    modal: true,
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
    callback: function(r) {

    },
    searchGrid: function(search)
    {
        var storeUser = this.store;

        storeUser.baseParams = {};
        storeUser.setBaseParam('role_name',search.role_name);
        storeUser.setBaseParam('prj_kode',search.prj_kode);
        storeUser.setBaseParam('uid',search.uid);
        storeUser.currentPage = 1;
        storeUser.reload({
            params: {
                start: 0
            }
        });
    },

    initComponent : function() {

        var prjKode = this.prjKodeSelect,
            that = this;
        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'id',
            root: 'posts'
        }, [
            {name: 'id'},
            {name: 'fullname'},
            {name: 'role_name'},
            {name: 'display_name'},
            {name: 'prj_kode'},
            {name: 'sit_kode'}
        ]);

        var storeUser = new Ext.data.Store({
            id: 'user',
            url : '/admin/workflow/listuserrole',
//            proxy: new Ext.data.HttpProxy({
//                url : '/admin/workflow/listuserrole'
//            }),
            reader:reader
        });

        this.store = storeUser;

        var storeRole = new Ext.data.Store({
            proxy: new Ext.data.HttpProxy({
                url: '/default/home/getroletypelist'
            }),
            reader: new Ext.data.JsonReader({
                id: 'id_role',
                totalProperty: 'count',
                root: 'posts'
            }, [{
                name: 'role_name'
            },{
                name: 'id_role'
            }
            ]),
            autoLoad: true
        });

        var comboSelectRole = new Ext.form.ComboBox({
            store: storeRole,
            valueField:'id_role',
            displayField:'role_name',
            typeAhead: true,
            forceSelection: true,
            mode: 'remote',
            id: 'select_role',
            triggerAction: 'all',
            selectOnFocus: true,
            emptyText: 'Select Role Type',
            width: 200,
            listeners: {
                'select': function(combo, selected){
                    var params = {
                        role_name: selected.get("role_name"),
                        prj_kode: prjKode
                    };

                    that.searchGrid(params);
                }
            }
        });


        var userColumns =  [
            new Ext.grid.RowNumberer(),
            {header: "Full Name", width: 140, sortable: true, dataIndex: 'fullname'},
            {header: "Role Name", width: 140, sortable: true, dataIndex: 'display_name'},
            {header: "Project Code", width: 80, sortable: true, dataIndex: 'prj_kode'},
            {header: "Site Code", width: 80, sortable: true, dataIndex: 'sit_kode'}
        ];

        var comboSelectUser = new Ext.form.ComboBox({
            store: new Ext.data.ArrayStore({
                fields: ['name', 'value'],
                data : [
                    ['Person Name','uid'],
//                    ['Project Code','prj_kode']
                ]
            }),
            displayField:'name',
            valueField: 'value',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            selectOnFocus:true,
            name:'option',
            width:100,
            style: 'margin-left: 5px',
            value:'uid',
            id:'combo_select_user'
        });

        var textSearch = new Ext.form.TextField({
            style:'margin-left:10px',
            id:'text_search',
            enableKeyEvents: true,
            listeners:{
                'keyup' : function(txttext,event){
                    var txttext = txttext.getValue(),
                        roleName = Ext.getCmp('select_role').getRawValue();
                    if (roleName == '' )
                        return true;
                    storeUser.baseParams = {};
                    if (txttext != "" && txttext.toString().length >= 2){
                        var option = Ext.getCmp('combo_select_user').getValue(),
                            params = {
                                uid: txttext,
                                role_name: roleName,
                                prj_kode: prjKode
                            };

                        that.searchGrid(params);
                    }
                    if (txttext == "")
                    {
                        var params = {
                            role_name: roleName,
                            prj_kode: prjKode
                        };

                        that.searchGrid(params);
                    }
                }
            }
        });

        var userGrid = new Ext.grid.GridPanel({
            store: storeUser,
            columns: userColumns,
            id:'role_grid',
            loadMask: true,
            forceFit: true,
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: storeUser,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            height:250,
            tbar: [],
            listeners: {
                'rowclick': function(g, rowIndex, e){
                    var rec = g.getStore().getAt(rowIndex);
                    that.callback(rec);
                    that.close();
                }
            }
        });

        var tbar = userGrid.getTopToolbar();
        tbar.add(new Ext.Toolbar({
            items: [
                comboSelectRole
            ]
        }));
        tbar.add(new Ext.Toolbar({
            items: [
                comboSelectUser,
                textSearch
            ]
        }));

        this.items = [
            userGrid
        ];

        Ext.ux.userroleSelector.superclass.initComponent.call(this);
    },
    layout: 'fit',
    resizable: false,
    height: 310
});

Ext.reg('userroleselector', Ext.ux.userroleSelector);