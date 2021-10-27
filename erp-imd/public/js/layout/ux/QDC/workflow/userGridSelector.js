Ext.ux.userGridSelector = Ext.extend(Ext.grid.GridPanel,  {
    stateful: false,
    useCheckbox: false,
    personSelected: [],
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

    getPerson: function()
    {
        var person = [];
        if (this.useCheckbox)
        {
            var s = this.getSelectionModel().getSelections();
            Ext.each(s, function(p){
                person.push(p.data);
            });
        }

        this.personSelected = person;

        return person;
    },
    refresh: function()
    {
        this.getSelectionModel().clearSelections();
    },
    getPersonJSON: function()
    {
        var json = '';

        this.getPerson();

        Ext.each(this.personSelected,function(p){
            json += Ext.util.JSON.encode(p) + ',';
        });

        if (json != '')
            json = '[' + json.substring(0, json.length - 1) + ']'; //JSON format fix

        return json;
    },

    store: null,
    callback: function(r) {

    },
    loadMask: true,
    bbar: [],
    columns: [],
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

        var that = this;
        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            root: 'posts'
        }, [
            {name: 'id', mapping: 'id'},
            {name: 'uid', mapping: 'uid'},
            {name: 'name', mapping: 'name'}
        ]);

        var storeUser = new Ext.data.Store({
            id: 'user',
            url : '/admin/userrole/listuser',
            reader:reader,
            autoLoad: true
        });

        this.store = storeUser;
        var sm = new Ext.grid.CheckboxSelectionModel();

        var userColumns =  [
            new Ext.grid.RowNumberer(),
            {header: "Full Name", width: 120, sortable: true, dataIndex: 'name'},
            {header: "UID", width: 100, sortable: true, dataIndex: 'uid'},
        ];

        if (this.useCheckbox)
        {
            this.sm = sm;
            userColumns = [
                new Ext.grid.RowNumberer(),
                sm,
                {header: "Full Name", width: 120, sortable: true, dataIndex: 'name'},
                {header: "UID", width: 100, sortable: true, dataIndex: 'uid'},
            ];
        }

        this.columns = userColumns;


        this.bbar = [ new Ext.PagingToolbar({
            pageSize: 100,
            store: this.store,
            displayInfo: true,
            displayMsg: 'Displaying data {0} - {1} of {2}',
            emptyMsg: "No data to display"
        })];

        Ext.ux.userGridSelector.superclass.initComponent.call(this);
    },
    height: 310
});

Ext.reg('usergridselector', Ext.ux.userGridSelector);