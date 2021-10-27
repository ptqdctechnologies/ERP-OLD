/*!
 * Ext JS Library 3.3.0
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.FileUploadField
 * @extends Ext.form.TextField
 * Creates a file upload field.
 * @xtype fileuploadfield
 */

Ext.ux.form.ProjectSelector = Ext.extend(Ext.form.Field,  {

    showProjectWindow: function(t) {

        var urlProxy = '/default/project/list';
        var urlProxySearch = '/project/listByParams';

        if (this.urlProject != undefined)
        {
            urlProxy = this.urlProject;
        }

        if (this.urlSearchProject != undefined)
        {
            urlProxySearch = this.urlSearchProject;
        }

        if (this.typeOverhead != undefined)
        {
            urlProxy += '/type/overhead';
            urlProxySearch += '/type/overhead';
        }
        if (this.typeProject != undefined)
        {
            urlProxy += '/type/project';
            urlProxySearch += '/type/project';
        }

        if (this.showAll != undefined)
        {
            urlProxy += '/all/true';
            urlProxySearch += '/all/true';
        }
        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'trano',
            root: 'posts'
        }, [
            {name: 'prj_kode', mapping: 'Prj_Kode'},
            {name: 'prj_nama', mapping: 'Prj_Nama'}
        ]);

        var projectStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'projectselector-store'
        });
        projectStore.load();

        var projectKodeText = new Ext.form.TextField({
            fieldLabel: 'Project Code',
            enableKeyEvents:true
        });

        projectKodeText.on('keyup',function(field,e){
            var pname = field.getValue();
            if (this.urlSearchProject == undefined)
            {
                newUrl = urlProxySearch + '/name/Prj_Kode/data/' + pname;
            }
            else
            {
                newUrl = urlProxySearch + '/prj_kode/' + pname;
            }
            projectStore.proxy = new Ext.data.HttpProxy( {
                url: newUrl
                 });
            projectStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        },this);

        var projectNamaText = new Ext.form.TextField({
            fieldLabel: 'Project Code',
            enableKeyEvents:true
        });

        projectNamaText.on('keyup',function(field,e){
            var pname = field.getValue();
            if (this.urlSearchProject == undefined)
            {
                newUrl = urlProxySearch + '/name/Prj_Nama/data/' + pname;
            }
            else
            {
                newUrl = urlProxySearch + '/prj_nama/' + pname;
            }
            projectStore.proxy = new Ext.data.HttpProxy( {
                url: newUrl
                 });
            projectStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        },this);

        var gridProject = new Ext.grid.GridPanel({
            store: projectStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: projectStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Project Code', width: 120, dataIndex: 'prj_kode', sortable: true},
                {header: 'Project Name', width: 200, dataIndex: 'prj_nama', sortable: true}
            ]
        });

        gridProject.on('rowdblclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('prj_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('prj_nama');
            Ext.getCmp(this.Nameid).setValue(name);
            if (pwindow)
                pwindow.close();
        },this);
        gridProject.on('rowclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('prj_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('prj_nama');
            Ext.getCmp(this.Nameid).setValue(name);
            if (pwindow)
                pwindow.close();
        },this)

        var forms =
        {
            xtype: 'form',
            labelWidth: 120,
            frame: true,
            items: [
                projectKodeText,
                projectNamaText,
                gridProject
            ]
        };
        
        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 400,
            title: 'Select Project',
            items: forms
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;
        
        if (this.showName == '' || this.showName == undefined)
            this.showName = true;



        if (!this.el) {
        this.selectProject = new Ext.form.TriggerField({
        id: this.Selectid,
        width: 80,
        triggerClass: 'teropong',
        editable: false
//        onTriggerClick: function(){
//            showProjectWindow(this);
//        }
        });

        if (!this.disabled)
            this.selectProject.onTriggerClick = this.showProjectWindow.createDelegate(this);

        this.projectName = new Ext.form.TextField({
            id: this.Nameid,
            width: 150,
            readOnly:true,
            hideLabel: true
        });
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
            this.selectProject,
            this.projectName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectProject,this.projectName]);
        if (!this.ShowName)
        {
            this.items.items[1].setVisible(false);
        }
        }
        Ext.ux.form.ProjectSelector.superclass.onRender.call(this, ct, position);

    },
    
    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.ProjectSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('projectselector', Ext.ux.form.ProjectSelector);

// backwards compat
Ext.form.SiteSelector = Ext.ux.form.SiteSelector;

Ext.ux.form.SiteSelector = Ext.extend(Ext.form.Field,  {

    // private
    onRender : function(ct, position){

        var select_id = this.SiteSelectid;
        var name_id = this.SiteNameid;
        var independent = this.independent; //true bila ingin menampilkan semua list site, false untuk bergantung pada isian projectselector
        var overheadOnly = this.overheadOnly;
        var noOverhead = this.noOverhead;
        var showName = this.ShowName;


        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;
        if (this.showName == '' || this.showName == undefined)
            this.showName = true;
        if (!independent)
            var project_select_id = this.ProjectSelectid;
        if (this.overheadOnly == undefined)
            this.overheadOnly = false;
        if (this.noOverhead == undefined)
            this.noOverhead = false;

        if (!this.el) {
        this.selectSite = new Ext.form.TriggerField({
            id: this.SiteSelectid,
            width: 80,
            triggerClass: 'teropong',
            editable: false
        });

        this.selectSite.onTriggerClick = this.showSiteWindow.createDelegate(this);

        this.siteName = new Ext.form.TextField({
            id: this.SiteNameid,
            width: 150,
            readOnly:true,
            hideLabel: true
        });

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
            this.selectSite,
            this.siteName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectSite,this.siteName]);

        if (!this.ShowName)
        {
            this.items.items[1].setVisible(false);
        }
        }
        Ext.ux.form.SiteSelector.superclass.onRender.call(this, ct, position);

    },
    showSiteWindow : function(t) {
        if (this.disabled)
            return false;

            if (!this.independent)
            {
                var prjKode = Ext.getCmp(this.ProjectSelectid).getValue();
                if (prjKode == "" || prjKode == undefined)
                {
                    Ext.Msg.alert('Error', 'Please select project!');
                    return false;
                }
            }
            var querySearch  = '/byPrj_Kode/';
            var urlProxySearch = '/site/listByParams';

            if (!this.overheadOnly)
            {
                if (this.noOverhead)
                {
                    var url = '/default/site/list/nooverhead/true';
                }
                else
                {
                    var url = '/default/site/list';
                }
            }
            else
            {
                var url = '/default/site/listoverhead';
            }

            if (this.urlSite != undefined)
            {
                url = this.urlSite;
            }

            if (this.urlSearchSite != undefined)
            {
                urlProxySearch = this.urlSearchSite;
                querySearch  = '/prj_kode/';
            }


            if (!this.independent)
            {
                var prjKode = Ext.getCmp(this.ProjectSelectid).getValue();
                url = url + querySearch + prjKode;
            }
            var proxy = new Ext.data.HttpProxy({
                url: url
            });

            var reader = new Ext.data.JsonReader({
                totalProperty: 'count',
                idProperty: 'trano',
                root: 'posts'
            }, [
                {name: 'sit_kode', mapping: 'sit_kode'},
                {name: 'sit_nama', mapping: 'sit_nama'},
                {name: 'prj_kode', mapping: 'prj_kode'}
            ]);

            var siteStore = new Ext.data.Store({
                proxy: proxy,
                reader: reader,
                id: 'siteselector-store'
            });
            siteStore.load();

            var grid = new Ext.grid.GridPanel({
                store: siteStore,
                loadMask: true,
                height: 300,
                id: this.id + '-site-grid',
                bbar:[ new Ext.PagingToolbar({
                    pageSize: 100,
                    store: siteStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
                columns: [
                    new Ext.grid.RowNumberer({width:30}),
                    {header: 'Site Code', width: 80, dataIndex: 'sit_kode', sortable: true},
                    {header: 'Site Name', width: 200, dataIndex: 'sit_nama', sortable: true},
                    {header: 'Project Code', width: 80, dataIndex: 'prj_kode', sortable: true}
                ]
            });
            grid.on('rowdblclick', function(g, rowIndex, e){
                var code = g.getStore().getAt(rowIndex).get('sit_kode');
                Ext.getCmp(this.SiteSelectid).setValue(code);
                var name = g.getStore().getAt(rowIndex).get('sit_nama');
                Ext.getCmp(this.SiteNameid).setValue(name);
                if (pwindow)
                pwindow.close();
            },this);

            grid.on('rowclick', function(g, rowIndex, e){
                var code = g.getStore().getAt(rowIndex).get('sit_kode');
                Ext.getCmp(this.SiteSelectid).setValue(code);
                var name = g.getStore().getAt(rowIndex).get('sit_nama');
                Ext.getCmp(this.SiteNameid).setValue(name);
                if (pwindow)
                pwindow.close();
            },this);

            var siteCodeText = new Ext.form.TextField({
                fieldLabel: 'Site Code',
                enableKeyEvents:true
            });
            siteCodeText.on(
                'keyup', function(field,e){
                    if (field.getValue().toString().length >= 3)
                    {
                        var pname = field.getValue();
                        if (!this.overheadOnly)
                        {
                            if (this.noOverhead)
                                newUrl = urlProxySearch + '/name/sit_kode/nooverhead/true/data/' + pname;
                            else
                                newUrl = urlProxySearch + '/name/sit_kode/data/' + pname;
                        }
                        else
                            newUrl = urlProxySearch + 'overhead/name/sit_kode/data/' + pname;

                        if (urlProxySearch != undefined)
                        {
                            if (this.urlSearchSite != undefined)
                                newUrl = urlProxySearch + '/sit_kode/' + pname;
                            else
                                newUrl = urlProxySearch + '/name/sit_kode/data/' + pname;
                        }
                        if (!this.independent)
                        {
                            var prjKode = Ext.getCmp(this.ProjectSelectid).getValue();
                            newUrl = newUrl + querySearch + prjKode;
                        }
                        siteStore.proxy = new Ext.data.HttpProxy( {
                            url: newUrl
                             });
                        siteStore.reload();
                        Ext.getCmp(this.id + '-site-grid').getView().refresh();
                    }
                },this
            );

            var siteNameText = new Ext.form.TextField({
                fieldLabel: 'Site Name',
                enableKeyEvents:true
            });
            siteNameText.on(
                'keyup', function(field,e){
                    if (field.getValue().toString().length >= 3)
                    {
                        var pname = field.getValue();
                        if (!this.overheadOnly)
                        {
                            if (this.noOverhead)
                                newUrl = urlProxySearch + '/name/sit_nama/nooverhead/true/data/' + pname;
                            else
                                newUrl = urlProxySearch + '/name/sit_nama/data/' + pname;
                        }
                        else
                            newUrl = urlProxySearch + 'overhead/name/sit_nama/data/' + pname;

                        if (urlProxySearch != undefined)
                        {
                            if (this.urlSearchSite != undefined)
                                newUrl = urlProxySearch + '/sit_nama/' + pname;
                            else
                                newUrl = urlProxySearch + '/name/sit_nama/data/' + pname;
                        }
                        if (!this.independent)
                        {
                            var prjKode = Ext.getCmp(this.ProjectSelectid).getValue();
                            newUrl = newUrl + querySearch + prjKode;
                        }
                        siteStore.proxy = new Ext.data.HttpProxy( {
                            url: newUrl
                             });
                        siteStore.reload();
                        Ext.getCmp(this.id + '-site-grid').getView().refresh();
                    }
                },this
            );


            var forms =
            {
                xtype: 'form',
                labelWidth: 120,
                frame: true,
                items: [
                    siteCodeText,
                    siteNameText,
                    grid
                ]
            };
        
            pwindow = new Ext.Window({
                modal: true,
                resizable: false,
                closeAction: 'close',
                width: 400,
                height: 400,
                title: 'Select Site',
                items: forms
            });

            pwindow.show();
    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.SiteSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('siteselector', Ext.ux.form.SiteSelector);

// backwards compat
Ext.form.SiteSelector = Ext.ux.form.SiteSelector;

Ext.ux.form.UserSelector = Ext.extend(Ext.form.Field,  {

    // private
    clearData: function()
    {
        this.uid = '';
        this.userName = '';
    },
    getUid : function()
    {
        return this.uid;
    },
    getUserName : function()
    {
        return this.userName;
    },
    setUid : function(uid)
    {
        this.uid = uid;
    },
    setUserName : function(name)
    {
        this.userName = name;
    },
    showUserWindow : function(t) {

        var url = '/default/user/list';

        var proxy = new Ext.data.HttpProxy({
            url: url
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'trano',
            root: 'posts'
        }, [
            {name: 'id', mapping: 'id'},
            {name: 'uid', mapping: 'uid'},
            {name: 'name', mapping: 'name'}
        ]);

        var userStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'userselector-store'
        });
        userStore.load();
        var grid = new Ext.grid.GridPanel({
            store: userStore,
            loadMask: true,
            height: 300,
            id: this.id + '-user-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 20,
                store: userStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width:30}),
                {header: 'Name', width: 200, dataIndex: 'name', sortable: true}
            ]
        });
        grid.on('rowdblclick', function(g, rowIndex, e){
                var code = g.getStore().getAt(rowIndex).get('name');
                Ext.getCmp(this.UserSelectid).setValue(code);
                this.uid = g.getStore().getAt(rowIndex).get('uid');
                this.userName = code;
                if (pwindow)
                pwindow.close();

                if (isFunction(this.callbackFunc))
                {
                    this.callbackFunc();
                }
            },this);

        grid.on('rowclick', function(g, rowIndex, e){
                var code = g.getStore().getAt(rowIndex).get('name');
                Ext.getCmp(this.UserSelectid).setValue(code);
                this.uid = g.getStore().getAt(rowIndex).get('uid');
                this.userName = code;
                if (pwindow)
                pwindow.close();

                if (isFunction(this.callbackFunc))
                {
                    this.callbackFunc();
                }
            },this);

        var forms =
        {
            xtype: 'form',
            labelWidth: 100,
            frame: true,
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: 'Name',
                    enableKeyEvents:true,
                    listeners: {
                        keyup: function(field,e){
                            if (field.getValue().toString().length >= 3)
                            {
                                searchUserByName(field.getValue());
                            }
                        }
                    }
                },
                grid
            ]
        };
        searchUserByName= function(pname) {
            newUrl = '/default/user/listByParams/name/name/data/' + pname;

            userStore.proxy = new Ext.data.HttpProxy( {
                url: newUrl
                 });
            userStore.reload();
//            Ext.getCmp(this.id + '-user-grid').getView().refresh();
        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 300,
            height: 400,
            title: 'Select User',
            items: forms
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.UserSelectid;

        this.uid = '';
        this.userName = '';
        setFieldValue = function(g,rowIndex){
            
        };

        if  (!this.width)
            this.width = 150;
        if (!this.el) {
        this.selectUser = new Ext.form.TriggerField({
            id: this.UserSelectid,
            width: this.width,
            triggerClass: 'teropong',
            editable: false,
//            msgTarget : 'firstNameError',
//            msgDisplay: 'block',
//            listeners: {
//                render: function(c) {
//                    Ext.QuickTips.register({
//                        target: c,
//                        text: 'this is a test message'
//                    });
//                }
//            }
        });

        if (!this.disabled)
            this.selectUser.onTriggerClick = this.showUserWindow.createDelegate(this);

        this.fieldCt = new Ext.Container({
            autoEl: {
                id: this.id
            },
            renderTo: ct,
            cls: 'ext-project-selector',
            layout: 'table',
            layoutConfig: {
                columns: 1
            },
            defaults: {
                hideParent: true
            },
            items: [
            this.selectUser
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectUser]);
        }
        Ext.ux.form.UserSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.UserSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('userselector', Ext.ux.form.UserSelector);

// backwards compat
Ext.form.UserSelector = Ext.ux.form.UserSelector;


/* CUSTOMER SELECTOR UX!!!!!  */

    Ext.ux.form.CustomerSelector = Ext.extend(Ext.form.Field,  {

        onRender:function(ct,position){
            var select_id = this.Selectid;
            var name_id = this.Nameid;
            var showName = this.ShowName;

            if(this.showName == '' || this.showName == undefined)
            this.showName = true;

            setCustomerFieldValue = function (g,rowIndex)
            {
                var code = g.getStore().getAt(rowIndex).get('cus_kode');
                Ext.getCmp(select_id).setValue(code);
                var name = g.getStore().getAt(rowIndex).get('cus_nama');
                Ext.getCmp(name_id).setValue(name);
            };

            showCustomerWindow =  function(t)
            {
                var customerstore = new Ext.data.Store ({

                url:'/logistic/logisticcustomer/getcustomer',
                autoLoad:true,

                reader: new Ext.data.JsonReader({
                    id: 'customerselector-store',
                    root:'data',
                    totalProperty:'total'
                },[
                    {name:'cus_kode',mapping:'cus_kode'},
                    {name:'cus_nama',mapping:'cus_nama'}
                ])

                })

                var grid = new Ext.grid.GridPanel({

                store:customerstore,
                height: 370,
                width:386,

                viewConfig:{
                    forceFit:true
                },

                columns:[
                    new Ext.grid.RowNumberer({width:30}),
                    {
                        header:'code',
                        width:100,
                        dataIndex:'cus_kode',
                        align:'center',
                        sortable:true
                    },{
                        header:'name',
                        width:200,
                        dataIndex:'cus_nama',
                        align:'center',
                        sortable:true
                    }
                ],listeners:{
                        'rowdblclick': function(g, rowIndex, e){

                                setCustomerFieldValue(g,rowIndex);

                                if (showwindow)
                                    showwindow.close();
                        },
                        'rowclick': function(g, rowIndex, e){

                                setCustomerFieldValue(g,rowIndex);

                                if (showwindow)
                                    showwindow.close();
                        }
                    },
                bbar: new Ext.PagingToolbar ({
                    id: 'paging',
                    pageSize: 20,
                    store: customerstore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                }),
                tbar:[
                    {
                        text:'Customer Name',
                        xtype:'label',
                        style: 'margin-left: 5px'

                    },{
                        xtype: 'textfield',
                        id: 'search',
                        style: 'margin-left: 5px'

                    },{
                        text: 'Search',
                        iconCls: 'search-icon',
                        handler: searchData,
                        scope: this
                    },
                    '-',
                    {
                        text: 'refresh',
                        iconCls: 'icon-refresh',
                        handler: refreshData,
                        scope: this
                    },'-'
                ]

            })

            function searchData ()
            {
                var search = Ext.getCmp('search').getValue();
                customerstore.proxy.setUrl('/logistic/logisticcustomer/getcustomer/search/' + search);
                customerstore.reload();
                grid.getView().refresh();
            }

            function refreshData ()
            {
                customerstore.clearFilter();
                Ext.getCmp('search').setValue('');
                Ext.getCmp('paging').doRefresh();
            }

            showwindow = new Ext.Window({
                modal: true,
                resizable: false,
                closeAction: 'close',
                width: 400,
                height: 400,
                title: 'Select Project',
                items: grid
            });

            showwindow.show();

            };

            if (!this.el) {
                
                this.selectCustomer = new Ext.form.TriggerField({
                id: this.Selectid,
                width: 80,
                triggerClass: 'teropong',
                editable: false,
                onTriggerClick: function(){
                    showCustomerWindow(this);
                }

                });

                this.customerName = new Ext.form.TextField({
                    id: this.Nameid,
                    width: 150,
                    disabled:true,
                    hideLabel: true
                });

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
                    this.selectCustomer,
                    this.customerName
                    ]
                });

                this.fieldCt.ownerCt = this;
                this.el = this.fieldCt.getEl();
                this.items = new Ext.util.MixedCollection();
                this.items.addAll([this.selectCustomer,this.customerName]);
                if (!this.ShowName)
                {
                    this.items.items[1].setVisible(false);
                }
            }
            Ext.ux.form.ProjectSelector.superclass.onRender.call(this, ct, position);


        },

        // private
        preFocus : Ext.emptyFn,

        beforeDestroy: function() {
            Ext.destroy(this.fieldCt);
            Ext.ux.form.SiteSelector.superclass.beforeDestroy.call(this);
        }


    });

    Ext.reg('customerselector', Ext.ux.form.CustomerSelector);


Ext.ux.form.PeriodeSelector = Ext.extend(Ext.form.Field,  {

    // private
    getperiodeid : function()
    {
        return this.periodeId;
    },
    showPeriodeWindow : function(t) {

        var url = '/humanresource/timesheet/getviewperiode';

        var proxy = new Ext.data.HttpProxy({
            url: url
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            idProperty: 'id',
            root: 'data'
        }, [
            {
                  name: 'id'
            },
            {
                name:'tahun',type:'String'
            },{
                name:'periode'
            },{
                name:'tgl_aw'
            },{
                name:'tglak'
            },{
                name:'periode_act'
            },{
                name:'jumlah_jam_bulan'
            }
        ]);

        var periodeStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'periodeselector-store'
        });
        periodeStore.load();
        var grid = new Ext.grid.GridPanel({
            store: periodeStore,
            loadMask: true,
            height: 300,
            id: this.id + '-periode-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 20,
                store: periodeStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width:30}),
                {
                    header:'Year',
                    dataIndex:'tahun',
                    sortable:true
                },
                {
                    header:'Periode',
                    dataIndex:'periode',
                    sortable:true
                },
                {
                    header:'Start Date',
                    dataIndex:'tgl_aw',
                    sortable:true
                },
                {
                    header:'End Date',
                    dataIndex:'tglak',
                    sortable:true
                },
                {
                    header:'Action',
                    dataIndex:'periode_act',
                    sortable:true
                },
                {
                    header:'Hour per month',
                    dataIndex:'jumlah_jam_bulan',
                    sortable:true
                }
            ]
        })
        grid.on('rowdblclick', function(g, rowIndex, e){
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
            pwindow.close();
        },this);

        grid.on('rowclick', function(g, rowIndex, e){
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
            pwindow.close();
        },this);
        var forms =
        {
            xtype: 'form',
            labelWidth: 100,
            frame: true,
            items: [
//                {
//                    xtype: 'textfield',
//                    fieldLabel: 'Name',
//                    enableKeyEvents:true,
//                    listeners: {
//                        keyup: function(field,e){
//                            if (field.getValue().toString().length >= 3)
//                            {
//                                searchUserByName(field.getValue());
//                            }
//                        }
//                    }
//                },
                grid
            ]
        };
//        searchUserByName= function(pname) {
//            newUrl = '/default/user/listByParams/name/name/data/' + pname;
//
//            userStore.proxy = new Ext.data.HttpProxy( {
//                url: newUrl
//                 });
//            userStore.reload();
//            Ext.getCmp(this.id + '-user-grid').getView().refresh();
//        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 350,
            title: 'Select Periode',
            items: forms
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.PeriodeSelectid;

        if (!this.el) {
        this.selectPeriode = new Ext.form.TriggerField({
        id: this.PeriodeSelectid,
        width: this.width,
        triggerClass: 'teropong',
        editable: false
        });

        this.selectPeriode.onTriggerClick = this.showPeriodeWindow.createDelegate(this);

        this.fieldCt = new Ext.Container({
            autoEl: {
                id: this.id
            },
            renderTo: ct,
            cls: 'ext-project-selector',
            layout: 'table',
            layoutConfig: {
                columns: 1
            },
            defaults: {
                hideParent: true
            },
            items: [
            this.selectPeriode
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectPeriode]);
        }
        Ext.ux.form.PeriodeSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodeselector', Ext.ux.form.PeriodeSelector);

Ext.ux.form.CoaSelector = Ext.extend(Ext.form.Field,  {

    showCoaWindow: function(t) {

        var proxy = new Ext.data.HttpProxy({
            url: '/finance/coa/getcoa'
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            idProperty: 'id',
            root: 'data'
        }, [
            {name: 'coa_kode'},
            {name: 'coa_nama'},
            {name: 'tipe'}
        ]);

        var coaStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'coaselector-store'
        });
        coaStore.load();

        var coaKodeText = new Ext.form.TextField({
            fieldLabel: 'COA Code',
            enableKeyEvents:true
        });

        coaKodeText.on('keyup',function(field,e){
            var pname = field.getValue();
            newUrl = '/finance/coa/getcoa/option/coa_kode/search/' + pname;
            coaStore.proxy = new Ext.data.HttpProxy( {
                url: newUrl
                 });
            coaStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        },this);

        var coaNamaText = new Ext.form.TextField({
            fieldLabel: 'COA Name',
            enableKeyEvents:true
        });

        coaNamaText.on('keyup',function(field,e){
            var pname = field.getValue();
            newUrl = '/finance/coa/getcoa/option/coa_nama/search/' + pname;
            coaStore.proxy = new Ext.data.HttpProxy( {
                url: newUrl
                 });
            coaStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        },this);

        var gridCoa = new Ext.grid.GridPanel({
            store: coaStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: coaStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width:30}),
                {header: 'COA Code', width: 120, dataIndex: 'coa_kode', sortable: true},
                {header: 'COA Name', width: 200, dataIndex: 'coa_nama', sortable: true},
                {header: 'COA Type', width: 200, dataIndex: 'tipe', sortable: true}
            ]
        });

        gridCoa.on('rowdblclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('coa_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('coa_nama');
            Ext.getCmp(this.Nameid).setValue(name);
            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentPosition()));
            }
            if (pwindow)
                pwindow.close();
        },this);
        gridCoa.on('rowclick', function (g,rowIndex,e){
            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentRowIndex()));
            }
            var code = g.getStore().getAt(rowIndex).get('coa_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('coa_nama');
            Ext.getCmp(this.Nameid).setValue(name);
            if (pwindow)
                pwindow.close();
        },this)

        var forms =
        {
            xtype: 'form',
            labelWidth: 120,
            frame: true,
            items: [
                coaKodeText,
                coaNamaText,
                gridCoa
            ]
        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 400,
            title: 'Select COA',
            items: forms
        });

        if (this.editorInstance != undefined)
        {
            this.editorInstance.stopEditing();
            this.editorInstance.hide();
        }

        if(this.disabled !== true)
            pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;

        if (this.SelectWidth == undefined)
            this.SelectWidth = 80;

        if (!this.el) {
        this.selectCoa = new Ext.form.TriggerField({
        id: this.Selectid,
        width: this.SelectWidth,
        triggerClass: 'teropong',
        editable: false
//        onTriggerClick: function(){
//            showProjectWindow(this);
//        }
        });

        if (!this.disabled)
            this.selectCoa.onTriggerClick = this.showCoaWindow.createDelegate(this);

        this.coaName = new Ext.form.TextField({
            id: this.Nameid,
            width: 150,
            readOnly:true,
            hideLabel: true
        });
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
            this.selectCoa,
            this.coaName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectCoa,this.coaName]);
        if (!this.ShowName)
        {
            this.items.items[1].setVisible(false);
        }
        }
        Ext.ux.form.CoaSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.CoaSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('coaselector', Ext.ux.form.CoaSelector);


/* CUSTOMER SELECTOR UX!!!!!  */

    Ext.ux.form.CustomerSelector = Ext.extend(Ext.form.Field,  {

        onRender:function(ct,position){
            var select_id = this.Selectid;
            var name_id = this.Nameid;
            var showName = this.ShowName;

            if(this.showName == '' || this.showName == undefined)
            this.showName = true;

            setCustomerFieldValue = function (g,rowIndex)
            {
                var code = g.getStore().getAt(rowIndex).get('cus_kode');
                Ext.getCmp(select_id).setValue(code);
                var name = g.getStore().getAt(rowIndex).get('cus_nama');
                Ext.getCmp(name_id).setValue(name);
            };

            showCustomerWindow =  function(t)
            {
                var customerstore = new Ext.data.Store ({

                url:'/logistic/logisticcustomer/getcustomer',
                autoLoad:true,

                reader: new Ext.data.JsonReader({
                    id: 'customerselector-store',
                    root:'data',
                    totalProperty:'total'
                },[
                    {name:'cus_kode',mapping:'cus_kode'},
                    {name:'cus_nama',mapping:'cus_nama'}
                ])

                })

                var grid = new Ext.grid.GridPanel({

                store:customerstore,
                height: 370,
                width:386,

                viewConfig:{
                    forceFit:true
                },

                columns:[
                    new Ext.grid.RowNumberer({width:30}),
                    {
                        header:'code',
                        width:100,
                        dataIndex:'cus_kode',
                        align:'center',
                        sortable:true
                    },{
                        header:'name',
                        width:200,
                        dataIndex:'cus_nama',
                        align:'center',
                        sortable:true
                    }
                ],listeners:{
                        'rowdblclick': function(g, rowIndex, e){

                                setCustomerFieldValue(g,rowIndex);

                                if (showwindow)
                                    showwindow.close();
                        },
                        'rowclick': function(g, rowIndex, e){

                                setCustomerFieldValue(g,rowIndex);

                                if (showwindow)
                                    showwindow.close();
                        }
                    },
                bbar: new Ext.PagingToolbar ({
                    id: 'paging',
                    pageSize: 20,
                    store: customerstore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                }),
                tbar:[
                    {
                        text:'Customer Name',
                        xtype:'label',
                        style: 'margin-left: 5px'

                    },{
                        xtype: 'textfield',
                        id: 'search',
                        style: 'margin-left: 5px'

                    },{
                        text: 'Search',
                        iconCls: 'search-icon',
                        handler: searchData,
                        scope: this
                    },
                    '-',
                    {
                        text: 'refresh',
                        iconCls: 'icon-refresh',
                        handler: refreshData,
                        scope: this
                    },'-'
                ]

            })

            function searchData ()
            {
                var search = Ext.getCmp('search').getValue();
                customerstore.proxy.setUrl('/logistic/logisticcustomer/getcustomer/search/' + search);
                customerstore.reload();
                grid.getView().refresh();
            }

            function refreshData ()
            {
                customerstore.clearFilter();
                Ext.getCmp('search').setValue('');
                Ext.getCmp('paging').doRefresh();
            }

            showwindow = new Ext.Window({
                modal: true,
                resizable: false,
                closeAction: 'close',
                width: 400,
                height: 400,
                title: 'Select Project',
                items: grid
            });

            showwindow.show();

            };

            if (!this.el) {

                this.selectCustomer = new Ext.form.TriggerField({
                id: this.Selectid,
                width: 80,
                triggerClass: 'teropong',
                editable: false,
                onTriggerClick: function(){
                    showCustomerWindow(this);
                }

                });

                this.customerName = new Ext.form.TextField({
                    id: this.Nameid,
                    width: 150,
                    disabled:true,
                    hideLabel: true
                });

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
                    this.selectCustomer,
                    this.customerName
                    ]
                });

                this.fieldCt.ownerCt = this;
                this.el = this.fieldCt.getEl();
                this.items = new Ext.util.MixedCollection();
                this.items.addAll([this.selectCustomer,this.customerName]);
                if (!this.ShowName)
                {
                    this.items.items[1].setVisible(false);
                }
            }
            Ext.ux.form.ProjectSelector.superclass.onRender.call(this, ct, position);


        },

        // private
        preFocus : Ext.emptyFn,

        beforeDestroy: function() {
            Ext.destroy(this.fieldCt);
            Ext.ux.form.SiteSelector.superclass.beforeDestroy.call(this);
        }


    });

    Ext.reg('customerselector', Ext.ux.form.CustomerSelector);


Ext.ux.form.PeriodeSelector = Ext.extend(Ext.form.Field,  {

    // private
    getperiodeid : function()
    {
        return this.periodeId;
    },
    showPeriodeWindow : function(t) {

        var url = '/humanresource/timesheet/getviewperiode';

        var proxy = new Ext.data.HttpProxy({
            url: url
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            idProperty: 'id',
            root: 'data'
        }, [
            {
                  name: 'id'
            },
            {
                name:'tahun',type:'String'
            },{
                name:'periode'
            },{
                name:'tgl_aw'
            },{
                name:'tglak'
            },{
                name:'periode_act'
            },{
                name:'jumlah_jam_bulan'
            }
        ]);

        var periodeStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'periodeselector-store'
        });
        periodeStore.load();
        var grid = new Ext.grid.GridPanel({
            store: periodeStore,
            loadMask: true,
            height: 300,
            id: this.id + '-periode-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 20,
                store: periodeStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width:30}),
                {
                    header:'Year',
                    dataIndex:'tahun',
                    sortable:true
                },
                {
                    header:'Periode',
                    dataIndex:'periode',
                    sortable:true
                },
                {
                    header:'Start Date',
                    dataIndex:'tgl_aw',
                    sortable:true
                },
                {
                    header:'End Date',
                    dataIndex:'tglak',
                    sortable:true
                },
                {
                    header:'Action',
                    dataIndex:'periode_act',
                    sortable:true
                },
                {
                    header:'Hour per month',
                    dataIndex:'jumlah_jam_bulan',
                    sortable:true
                }
            ]
        })
        grid.on('rowdblclick', function(g, rowIndex, e){
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
            pwindow.close();
        },this);

        grid.on('rowclick', function(g, rowIndex, e){
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
            pwindow.close();
        },this);
        var forms =
        {
            xtype: 'form',
            labelWidth: 100,
            frame: true,
            items: [
//                {
//                    xtype: 'textfield',
//                    fieldLabel: 'Name',
//                    enableKeyEvents:true,
//                    listeners: {
//                        keyup: function(field,e){
//                            if (field.getValue().toString().length >= 3)
//                            {
//                                searchUserByName(field.getValue());
//                            }
//                        }
//                    }
//                },
                grid
            ]
        };
//        searchUserByName= function(pname) {
//            newUrl = '/default/user/listByParams/name/name/data/' + pname;
//
//            userStore.proxy = new Ext.data.HttpProxy( {
//                url: newUrl
//                 });
//            userStore.reload();
//            Ext.getCmp(this.id + '-user-grid').getView().refresh();
//        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 350,
            title: 'Select Periode',
            items: forms
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.PeriodeSelectid;

        if (!this.el) {
        this.selectPeriode = new Ext.form.TriggerField({
        id: this.PeriodeSelectid,
        width: this.width,
        triggerClass: 'teropong',
        editable: false
        });

        this.selectPeriode.onTriggerClick = this.showPeriodeWindow.createDelegate(this);

        this.fieldCt = new Ext.Container({
            autoEl: {
                id: this.id
            },
            renderTo: ct,
            cls: 'ext-project-selector',
            layout: 'table',
            layoutConfig: {
                columns: 1
            },
            defaults: {
                hideParent: true
            },
            items: [
            this.selectPeriode
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectPeriode]);
        }
        Ext.ux.form.PeriodeSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodeselector', Ext.ux.form.PeriodeSelector);


// Supplier Selector UX

Ext.ux.form.SupplierSelector = Ext.extend(Ext.form.Field,  {

    showSupplierWindow: function(t) {

        var proxy = new Ext.data.HttpProxy({
            url: '/default/suplier/getsupplier'
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            idProperty: 'id',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'sup_kode'},
            {name: 'sup_nama'},
            {name:'alamat'},
            {name:'tlp'},
            {name:'email'},
            {name:'fax'},
            {name:'ket'},
            {name:'master_kota'},
            {name:'negara'},
            {name:'orang'},
            {name:'statussupplier'}
        ]);

        var supplierStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'supplierselector-store'
        });
        supplierStore.load();

//        var supKodeText = new Ext.form.TextField({
//            fieldLabel: 'Supplier Code',
//            enableKeyEvents:true
//        });
//
//        supKodeText.on('keyup',function(field,e){
//            var pname = field.getValue();
//            newUrl = '/finance/coa/getcoa/option/coa_kode/search/' + pname;
//            supplierStore.proxy = new Ext.data.HttpProxy( {
//                url: newUrl
//                 });
//            supplierStore.reload();
//            Ext.getCmp(this.id + '-grid').getView().refresh();
//        },this);
//
//        var supNamaText = new Ext.form.TextField({
//            fieldLabel: 'Supplier Name',
//            enableKeyEvents:true
//        });
//
//        supNamaText.on('keyup',function(field,e){
//            var pname = field.getValue();
//            newUrl = '/finance/coa/getcoa/option/coa_nama/search/' + pname;
//            supplierStore.proxy = new Ext.data.HttpProxy( {
//                url: newUrl
//                 });
//            supplierStore.reload();
//            Ext.getCmp(this.id + '-grid').getView().refresh();
//        },this);

        var gridSupplier = new Ext.grid.GridPanel({
            store: supplierStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            viewConfig:{
                forceFit:true
            },
            bbar:[ new Ext.PagingToolbar({
                pageSize: 100,
                store: supplierStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width:30}),
                {header: 'Supplier Code', width: 120, dataIndex: 'sup_kode', sortable: true},
                {header: 'Supplier Name', width: 200, dataIndex: 'sup_nama', sortable: true},
                {header: 'Supplier City', width: 200, dataIndex: 'master_kota', sortable: true}
            ],
            tbar:[{
                    text:'Search By',
                    xtype:'label',
                    style:'margin-left:5px'
                },'-',{
                    xtype:'combo',
                    id:'option',
                    width:100,
                    store: new Ext.data.SimpleStore({
                        fields:['nilai','name'],
                        data:[
                                ['sup_kode','Supplier Code'],
                                ['sup_nama','Supplier Name'],
                                ['master_kota','Supplier City']
                            ]
                    }),
                    valueField:'nilai',
                    displayField:'name',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    value:'sup_kode'
                },'-',{
                    xtype:'textfield',
                    id:'search',
                    enableKeyEvents:true,
                    listeners:{
                        'keyup': function (txttext,event)
                        {
                            var txttext = txttext.getValue();
                            if (txttext != "" && txttext.toString().length >= 3)
                            {
                                var option = Ext.getCmp('option').getValue();
                                var search = Ext.getCmp('search').getValue();

                                supplierStore.proxy.url= '/default/suplier/getsupplier/search/' + search + '/option/' + option;
                                supplierStore.proxy.setUrl('/default/suplier/getsupplier/search/' + search + '/option/' + option);
                                supplierStore.proxy.api.read['url']= '/default/suplier/getsupplier/search/' + search + '/option/' + option;
                                supplierStore.load();
                                gridSupplier.getView().refresh();
                            }
                        }
                    }
                }]
        });

        gridSupplier.on('rowdblclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('sup_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            if (this.Namaid != undefined)
            {
                var name = g.getStore().getAt(rowIndex).get('sup_nama');
                Ext.getCmp(this.Namaid).setValue(name);
            }
            if (this.Alamatid != undefined)
            {
                var alamat = g.getStore().getAt(rowIndex).get('alamat');
                Ext.getCmp(this.Alamatid).setValue(alamat);
            }
            if (this.Cityid != undefined)
            {
                var city = g.getStore().getAt(rowIndex).get('master_kota');
                Ext.getCmp(this.Cityid).setValue(city);
            }
            if (this.Phoneid != undefined)
            {
                var phone = g.getStore().getAt(rowIndex).get('tlp');
                Ext.getCmp(this.Phoneid).setValue(phone);
            }
            if (this.Faxid != undefined)
            {
                var fax = g.getStore().getAt(rowIndex).get('fax');
                Ext.getCmp(this.Faxid).setValue(fax);
            }
            if (this.Emailid != undefined)
            {
                var email = g.getStore().getAt(rowIndex).get('email');
                Ext.getCmp(this.Emailid).setValue(email);
            }
//            var name = g.getStore().getAt(rowIndex).get('sup_nama');
//            Ext.getCmp('sup_nama').setValue(name);
            if (pwindow)
                pwindow.close();
        },this);
        gridSupplier.on('rowclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('sup_kode');
            Ext.getCmp(this.Selectid).setValue(code);
//            var name = g.getStore().getAt(rowIndex).get('sup_nama');
//            Ext.getCmp('sup_nama').setValue(name);
            if (this.Namaid != undefined)
            {
                var name = g.getStore().getAt(rowIndex).get('sup_nama');
                Ext.getCmp(this.Namaid).setValue(name);
            }
            if (this.Alamatid != undefined)
            {
                var alamat = g.getStore().getAt(rowIndex).get('alamat');
                Ext.getCmp(this.Alamatid).setValue(alamat);
            }
            if (this.Cityid != undefined)
            {
                var city = g.getStore().getAt(rowIndex).get('master_kota');
                Ext.getCmp(this.Cityid).setValue(city);
            }
            if (this.Phoneid != undefined)
            {
                var phone = g.getStore().getAt(rowIndex).get('tlp');
                Ext.getCmp(this.Phoneid).setValue(phone);
            }
            if (this.Faxid != undefined)
            {
                var fax = g.getStore().getAt(rowIndex).get('fax');
                Ext.getCmp(this.Faxid).setValue(fax);
            }
            if (this.Emailid != undefined)
            {
                var email = g.getStore().getAt(rowIndex).get('email');
                Ext.getCmp(this.Emailid).setValue(email);
            }
            if (pwindow)
                pwindow.close();
        },this)

//        var forms =
//        {
//            xtype: 'form',
//            labelWidth: 120,
//            frame: true,
//            items: [
////                supKodeText,
////                supNamaText,
//                gridSupplier
//            ]
//        };

        pwindow = new Ext.Window({
            title:'Select Supplier',
            id:'select-supplier',
            layout:'absolute',
            minHeight: 200,
            stateful:false,
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 330,
            loadMask:true,
            items:[gridSupplier]
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

//        if (this.showName == '' || this.showName == undefined)
//            this.showName = true;

        if (!this.el) {
        this.selectSupplier = new Ext.form.TriggerField({
        id: this.Selectid,
        width: 80,
        triggerClass: 'teropong',
        editable: false
//        onTriggerClick: function(){
//            showProjectWindow(this);
//        }
        });

        if (!this.disabled)
            this.selectSupplier.onTriggerClick = this.showSupplierWindow.createDelegate(this);

//        this.supplierName = new Ext.form.TextField({
//            id: this.Nameid,
//            width: 150,
//            readOnly:true,
//            hideLabel: true
//        });
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
            this.selectSupplier
//            this.supplierName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectSupplier]);
//        this.items.addAll([this.selectSupplier,this.supplierName]);
//        if (!this.ShowName)
//        {
//            this.items.items[1].setVisible(false);
//        }
        }
        Ext.ux.form.SupplierSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.SupplierSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('supplierselector', Ext.ux.form.SupplierSelector);

/*
    Fungsi untuk memilih COA jika user memilih tipe pembayaran Petty Cash
    coaPC : record coa yang dihasilkan dari request ke server
    callbackFUnc : fungsi yang akan dijalankan setelah user meng-klik COA yang dipilih,
    default parameter yang dikirim hanya coa_kode
    returnAllField : jika true akan mengisi parameter callbackFunc dengan field record yg lengkap (coa_nama, coa_kode, etc),

 */
function selectCoaPC(coaPC,callbackFunc,returnAllField)
{
    if (returnAllField == undefined)
        returnAllField = false;

    var reader = new Ext.data.JsonReader({
        idProperty: 'id',
        root: 'data'
    }, [
        {name: 'id'},
        {name:'coa_kode'},
        {name: 'coa_nama'}
    ]);

    var store = new Ext.data.Store({
        reader: reader,
        id: 'coa-store'
    });

    store.loadData(coaPC);

    var gridCoa = new Ext.grid.GridPanel({
        store: store,
        loadMask: true,
        height: 300,
        id: 'coa-grid',
        viewConfig:{
            forceFit:true
        },
        columns: [
            new Ext.grid.RowNumberer({width:30}),
            {header: 'Coa Code', width: 120, dataIndex: 'coa_kode', sortable: true},
            {header: 'Coa Name', width: 200, dataIndex: 'coa_nama', sortable: true}
        ]
    });

    gridCoa.on('rowclick', function (g,rowIndex,e){
        var rec = g.getStore().getAt(rowIndex);
        var code = rec.get('coa_kode');
        if (pwindow)
            pwindow.close();

        if (!returnAllField)
            callbackFunc(code);
        else
            callbackFunc(rec);
    },this);

    pwindow = new Ext.Window({
        title:'Select COA for Petty Cash Transaction',
        id:'select-coa',
        layout:'absolute',
        minHeight: 200,
        stateful:false,
        modal: true,
        resizable: false,
        closeAction: 'close',
        width: 500,
        height: 330,
        loadMask:true,
        items:[gridCoa]
    });

    pwindow.show();
}

// Trano Selector UX

Ext.ux.form.TranoSelector = Ext.extend(Ext.form.Field,  {

    showTranoWindow: function(t) {

        var reader = new Ext.data.JsonReader({
                idProperty: 'id',
                totalProperty: 'count',
                root : 'posts'},
            [
                {name: 'id'},
                {name: 'trano'},
                {name: 'prj_kode'},
                {name: 'sit_kode'}
            ]
        );

        var proxy = new Ext.data.HttpProxy({
            url : '/default/home/getlistdocumentbytype'
        });

        var store = new Ext.data.Store({
            id: 'store-doc-msg',
            reader: reader,
            proxy: proxy
        });

        var grids = new Ext.grid.GridPanel({
            loadMask: true,
            frame:true,
            width: 280,
            id: 'grid-trano',
            store: store,
            loadMask: {msg:'Loading...'},
            sm: new Ext.grid.RowSelectionModel({
                singleSelect:true
            }),
            viewConfig: {
                forceFit:true
            },
            bbar: [
                new Ext.PagingToolbar({
                    pageSize: 20,
                    store: store,
                    displayInfo: true,
                    displayMsg: 'Displaying document {0} - {1} of {2}',
                    emptyMsg: "No document to display"
                })
            ],
            columns: [
                {
                    id: 'trano',
                    header: "Trano",
                    dataIndex: 'trano',
                    width: 100
                },
                {
                    id: 'prj_kode',
                    header: "Project Code",
                    dataIndex: 'prj_kode',
                    width: 50
                },
                {
                    id: 'sit_kode',
                    header: "Site Code",
                    dataIndex: 'sit_kode',
                    width: 50
                }
            ]
        });

        grids.on('rowclick',function(grid, index, e){
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp(this.Selectid).setValue(trano);
            Ext.getCmp('doc-form-panel').close();

            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc();
            }

        },this);

        var viewportsMsg = ({
            layout: 'border',
            stateful: false,
            loadMask: true,
            bodyCfg : { cls:'xpanel-body-table' , style: {'overflow':'auto'}},
            bodyStyle: 'padding:15px;',
            items: [

                {
                    region: 'center',
                    id: 'detail', // see Ext.getCmp() below
                    title: '',
                    width: 320,
                    layout: 'fit',
                    items: [
                        grids
                    ]

                },
                {
                    region: 'north',
                    id: 'south2',
                    height: 125,
                    stateful: false,
                    items: [
                        {
                            layout:'column',
                            frame: true,
                            items:[
                                {columnWidth:.9,
                                    layout: 'form',
                                    items: [
                                        {
                                            id: 'combo-type',
                                            fieldLabel: 'Transaction',
                                            hiddenName : 'workflow_item_type_id',
                                            width: 100,
                                            xtype: 'combo',
                                            triggerAction: 'all',
                                            mode: 'remote',
                                            editable: false,
                                            displayField: 'name',
                                            style: 'font-weight: bold; color: black',
                                            valueField: 'workflow_item_type_id',
                                            store: new Ext.data.JsonStore({
                                                url: '/admin/workflow/listworkflowitemtype',
                                                root: 'posts',
                                                fields:[
                                                    { name: "name"},{ name: "workflow_item_type_id"}
                                                ]
                                            }),
                                            listeners: {
                                                'select': function(t,n,o){
                                                    store.proxy = new Ext.data.HttpProxy({
                                                        url : '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue()
                                                    });
                                                    store.reload();
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            layout:'column',
                            frame: true,
                            items:[
                                {columnWidth:.55,
                                    layout: 'form',
                                    style : 'margin-right: 3px;',
                                    items: [
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'Trano',
                                            width: 80,
                                            id: 'search_trano'
                                        },
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'Project Code',
                                            width: 80,
                                            id: 'search_prj'
                                        },
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'Site Code',
                                            width: 80,
                                            id: 'search_site'
                                        }
                                    ]
                                },
                                {columnWidth:.3,
                                    layout: 'form',
                                    style : 'margin-left: 3px;',
                                    items: [
                                        new Ext.Button({
                                            text: 'Search',
                                            id: 'search-button',
                                            style: 'margin-top: 12px;',
                                            handler: function (){
                                                var type = Ext.getCmp('combo-type').getValue();
                                                if (type == "" || type == null)
                                                {
                                                    Ext.Msg.alert('Error!', "Please select Transaction!");
                                                    return false;
                                                }
                                                else
                                                {
                                                    var trano = Ext.getCmp('search_trano').getValue();
                                                    var prj = Ext.getCmp('search_prj').getValue();
                                                    var site = Ext.getCmp('search_site').getValue();
                                                    store.proxy = new Ext.data.HttpProxy({
                                                        url : '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + '/trano/' + trano + '/prj_kode/' + prj + '/sit_kode/' + site
                                                    });
                                                    store.reload();
                                                }
                                            }
                                        }),
                                        new Ext.Button({
                                            text: 'Clear',
                                            id: 'clear-button',
                                            style: 'margin-top: 5px;',
                                            handler: function (){
                                                Ext.getCmp('search_trano').setValue('');
                                                Ext.getCmp('search_prj').setValue('');
                                                Ext.getCmp('search_site').setValue('');
                                            }
                                        })
                                    ]
                                }
                            ]
                        }
                    ]
                }

            ]

        });

        var dFormMsg =  new Ext.Window({
            id: 'doc-form-panel',
            layout:'fit',
            width: 400,
            height: 400,
            title: 'Select Document',
            stateful: false,
            modal: true,
            resizable: false,
            items: [
                viewportsMsg
            ]
        });

        dFormMsg.show();
        if (this.Tranotype != undefined)
        {
            Ext.getCmp('combo-type').setValue(this.Tranotype);
            Ext.getCmp('combo-type').setRawValue(this.Tranotype);
            store.proxy = new Ext.data.HttpProxy({
                url : '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue()
            });
            store.reload();

            if (this.Disabletype == true)
                Ext.getCmp('combo-type').disable();
        }
    },
    onRender : function(ct, position){

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 100;

        if (this.Enableeditable == undefined)
            this.Enableeditable = false;

        if (!this.el) {
            this.selectTrano = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: this.Enableeditable
            });

            if (!this.disabled)
                this.selectTrano.onTriggerClick = this.showTranoWindow.createDelegate(this);

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
                    this.selectTrano
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectTrano]);

        }
        Ext.ux.form.TranoSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('tranoselector', Ext.ux.form.TranoSelector);

Ext.ux.form.PeriodeFinanceSelector = Ext.extend(Ext.form.Field,  {

    showPeriodeWindow: function(t) {

        var urlProxy = '/finance/periode/getviewperiode';

        if (this.accounting != undefined)
        {
            urlProxy += '/type/accounting';
        }
        if (this.inventory != undefined)
        {
            urlProxy += '/type/inventory';
        }

        if (this.showAll != undefined)
        {
            urlProxy += '/all/true';
        }
        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'id',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'perkode'},
            {name: 'bulan'},
            {name: 'tahun'},
            {name: 'tgl_awal'},
            {name: 'tgl_akhir'},
            {name: 'aktif'},
            {name: 'stsclose'},
            {name: 'tglclose'},
            {name: 'stscloseinventory'},
            {name: 'tglcloseinventory'}
        ]);

        var periodeStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'periodeselector-store'
        });
        periodeStore.load();

        var periodeKodeText = new Ext.form.TextField({
            fieldLabel: 'Periode Code',
            enableKeyEvents:true
        });

        periodeKodeText.on('keyup',function(field,e){
            var pname = field.getValue();

            periodeStore.proxy = new Ext.data.HttpProxy( {
                url: urlProxy + '/perkode/' + pname
            });
            periodeStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        },this);

        var gridPeriode = new Ext.grid.GridPanel({
            store: periodeStore,
            loadMask: true,
            height: 200,
            id: this.id + '-grid',
            bbar:[ new Ext.PagingToolbar({
                pageSize: 20,
                store: periodeStore,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Periode Code', width: 80, dataIndex: 'perkode', sortable: true},
                {header: 'Periode', width: 90, dataIndex: 'bulan', sortable: true, renderer: function(v,p,r){
                    return v + " - " + r.data['tahun'];
                }},
                {header: 'Start Date', width: 90, dataIndex: 'tgl_awal', sortable: true},
                {header: 'End Date', width: 90, dataIndex: 'tgl_akhir', sortable: true},
                {header: 'Active', width: 90, dataIndex: 'aktif', sortable: true, renderer: function(v,p,r){
                    if (v == 'ACTIVE')
                    {
                        return 'CURRENT PERIODE';
                    }
                    else
                        return 'PREVIOUS PERIODE';
                }}
            ]
        });

        gridPeriode.on('rowclick', function (g,rowIndex,e){
            var code = g.getStore().getAt(rowIndex).get('perkode');
            Ext.getCmp(this.Selectid).setValue(code);
            if (pwindow)
                pwindow.close();
        },this);

        var forms =
        {
            xtype: 'form',
            labelWidth: 120,
            frame: true,
            items: [
                periodeKodeText,
                gridPeriode
            ]
        };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 300,
            title: 'Select Periode',
            items: forms
        });

        pwindow.show();
    },
    onRender : function(ct, position){

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 120;

        if (!this.el) {
            this.selectPeriode = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectPeriode.onTriggerClick = this.showPeriodeWindow.createDelegate(this);

            this.fieldCt = new Ext.Container({
                autoEl: {
                    id: this.id
                },
                renderTo: ct,
                cls: 'ext-project-selector',
                layout: 'table',
                layoutConfig: {
                    columns: 1
                },
                defaults: {
                    hideParent: true
                },
                items: [
                    this.selectPeriode
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectPeriode]);
        }
        Ext.ux.form.PeriodeFinanceSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeFinanceSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodefinanceselector', Ext.ux.form.PeriodeFinanceSelector);