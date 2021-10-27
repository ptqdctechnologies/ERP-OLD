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

Ext.ux.form.ProjectSelectorReport = Ext.extend(Ext.form.Field, {
    showProjectWindow: function(t) {

        var urlProxy = '/project/listReport';
        var urlProxySearch = '/project/listByParamsProject';

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
            id: 'projectselectorreport-store'
        });
        projectStore.load();

        var projectKodeText = new Ext.form.TextField({
            fieldLabel: 'Project Code',
            enableKeyEvents: true
        });

        projectKodeText.on('keyup', function(field, e) {
            var pname = field.getValue();
            if (this.urlSearchProject == undefined)
            {
                newUrl = urlProxySearch + '/name/Prj_Kode/data/' + pname;
            }
            else
            {
                newUrl = urlProxySearch + '/prj_kode/' + pname;
            }
            projectStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            projectStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var projectNamaText = new Ext.form.TextField({
            fieldLabel: 'Project Name',
            enableKeyEvents: true
        });

        projectNamaText.on('keyup', function(field, e) {
            var pname = field.getValue();
            if (this.urlSearchProject == undefined)
            {
                newUrl = urlProxySearch + '/name/Prj_Nama/data/' + pname;
            }
            else
            {
                newUrl = urlProxySearch + '/prj_nama/' + pname;
            }
            projectStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            projectStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridProject = new Ext.grid.GridPanel({
            store: projectStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
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

        gridProject.on('rowdblclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('prj_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('prj_nama');
            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    prj_kode: code,
                    prj_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this);
        gridProject.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('prj_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('prj_nama');
            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    prj_kode: code,
                    prj_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this)

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
    onRender: function(ct, position) {

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

            this.selectProject.on('enable', function(t) {
                this.selectProject.onTriggerClick = this.showProjectWindow.createDelegate(this);
            }, this);

            this.selectProject.on('disable', function(t) {
                this.selectProject.onTriggerClick = function() {
                };
            }, this);

            if (!this.disabled)
                this.selectProject.onTriggerClick = this.showProjectWindow.createDelegate(this);

            this.projectName = new Ext.form.DisplayField({
                id: this.Nameid,
                width: 150,
                style: 'font-size: 12px;',
//            readOnly:true,
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
            this.items.addAll([this.selectProject, this.projectName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.ProjectSelectorReport.superclass.onRender.call(this, ct, position);

    },
    setTypeOverhead: function(value) {
        this.typeOverhead = value;
        this.typeProject = null;
    },
    setTypeProject: function(value) {
        this.typeProject = value;
        this.typeOverhead = null;
    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.ProjectSelectorReport.superclass.beforeDestroy.call(this);
    },
    reset: function() {
        Ext.getCmp(this.Selectid).setValue('');
        if (this.ShowName === true)
            Ext.getCmp(this.Nameid).setValue('');
    }


});

Ext.reg('projectselectorreport', Ext.ux.form.ProjectSelectorReport);

// backwards compat
Ext.form.SiteSelector = Ext.ux.form.SiteSelector;

Ext.ux.form.SiteSelector = Ext.extend(Ext.form.Field, {
    // private
    onRender: function(ct, position) {

        var select_id = this.SiteSelectid;
        var name_id = this.SiteNameid;
        var independent = this.independent; //true bila ingin menampilkan semua list site, false untuk bergantung pada isian projectselector
        var overheadOnly = this.overheadOnly;
        var noOverhead = this.noOverhead;
        var showName = this.ShowName;
        var showAll = this.ShowAll;


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
        if (this.showAll == undefined)
            this.showAll = false;

        if (!this.el) {
            this.selectSite = new Ext.form.TriggerField({
                id: this.SiteSelectid,
                width: 80,
                triggerClass: 'teropong',
                editable: false
            });

            this.selectSite.onTriggerClick = this.showSiteWindow.createDelegate(this);

            this.siteName = new Ext.form.DisplayField({
                id: this.SiteNameid,
                width: 150,
                style: 'font-size:12px;',
//            readOnly:true,
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
            this.items.addAll([this.selectSite, this.siteName]);

            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.SiteSelector.superclass.onRender.call(this, ct, position);

    },
    clearAllValue: function() {
        if (this.selectSite)
            this.selectSite.setValue('');
        if (this.siteName)
            this.siteName.setValue('');
    },
    showSiteWindow: function(t) {
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
        var querySearch = '/byPrj_Kode/';
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
            querySearch = '/prj_kode/';
        }


        if (!this.independent)
        {
            var prjKode = Ext.getCmp(this.ProjectSelectid).getValue();
            url = url + querySearch + prjKode;
        }

        if (this.showAll)
            url = url + '/all/true';

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
            bbar: [new Ext.PagingToolbar({
                    pageSize: 100,
                    store: siteStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Site Code', width: 80, dataIndex: 'sit_kode', sortable: true},
                {header: 'Site Name', width: 200, dataIndex: 'sit_nama', sortable: true},
                {header: 'Project Code', width: 80, dataIndex: 'prj_kode', sortable: true}
            ]
        });
        grid.on('rowdblclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('sit_kode');
            Ext.getCmp(this.SiteSelectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('sit_nama');
            Ext.getCmp(this.SiteNameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    sit_kode: code,
                    sit_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this);

        grid.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('sit_kode');
            Ext.getCmp(this.SiteSelectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('sit_nama');
            if (this.ShowName == true)
                Ext.getCmp(this.SiteNameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    sit_kode: code,
                    sit_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this);

        var siteCodeText = new Ext.form.TextField({
            fieldLabel: 'Site Code',
            enableKeyEvents: true
        });
        siteCodeText.on(
                'keyup', function(field, e) {
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
                        siteStore.proxy = new Ext.data.HttpProxy({
                            url: newUrl
                        });
                        siteStore.reload();
                        Ext.getCmp(this.id + '-site-grid').getView().refresh();
                    }
                    else if (field.getValue() == '') {
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
                        siteStore.proxy = new Ext.data.HttpProxy({
                            url: newUrl
                        });
                        siteStore.reload();
                        Ext.getCmp(this.id + '-site-grid').getView().refresh();
                    }
                }, this
                );

        var siteNameText = new Ext.form.TextField({
            fieldLabel: 'Site Name',
            enableKeyEvents: true
        });
        siteNameText.on(
                'keyup', function(field, e) {
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
                        siteStore.proxy = new Ext.data.HttpProxy({
                            url: newUrl
                        });
                        siteStore.reload();
                        Ext.getCmp(this.id + '-site-grid').getView().refresh();
                    }
                }, this
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
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.SiteSelector.superclass.beforeDestroy.call(this);
    },
    reset: function() {
        Ext.getCmp(this.SiteSelectid).setValue('');
        if (this.ShowName === true)
            Ext.getCmp(this.SiteNameid).setValue('');
    }


});

Ext.reg('siteselector', Ext.ux.form.SiteSelector);

// backwards compat
Ext.form.SiteSelector = Ext.ux.form.SiteSelector;

Ext.ux.form.UserSelector = Ext.extend(Ext.form.Field, {
    // private
    clearData: function()
    {
        this.uid = '';
        this.userName = '';
    },
    getUid: function()
    {
        return this.uid;
    },
    getUserName: function()
    {
        return this.userName;
    },
    setUid: function(uid)
    {
        this.uid = uid;
    },
    setUserName: function(name)
    {
        this.userName = name;
    },
    getRoleByPrjctUid: function(prj_kode, id_user, jobTitleid, callback)
    {

        Ext.Ajax.request({
            url: '/admin/Userrole/listuserrole',
            method: 'POST',
            params: {prj_kode: prj_kode, id: id_user},
            success: function(resp) {
                var returnData = Ext.util.JSON.decode(resp.responseText);
                if (returnData.count > 0) {

                    id_role = returnData.posts[0].id_role;
                    role_name = returnData.posts[0].role_name;

                    if (jobTitleid != undefined)
                        Ext.getCmp(jobTitleid).setValue(id_role);


                    if (callback != undefined)
                    {
                        callback({
                            role_name: role_name
                        });
                    }
                }
                else {
                    Ext.getCmp(jobTitleid).setValue('');
                    if (callback != undefined)
                    {
                        callback({
                            role_name: ''
                        });
                    }
                }
            }
            , failure: function(action) {
                if (action.failureType == 'server') {
                    obj = Ext.util.JSON.decode(action.response.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                }
            }
        });
    },
    showUserWindow: function(t) {

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
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: userStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Name', width: 200, dataIndex: 'name', sortable: true}
            ]
        });
        grid.on('rowdblclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('name');
            Ext.getCmp(this.UserSelectid).setValue(code);
            this.uid = g.getStore().getAt(rowIndex).get('uid');
            Ext.getCmp('uid').setValue(this.uid);
            var id_user = g.getStore().getAt(rowIndex).get('id');
            Ext.getCmp('userid').setValue(id_user);
            this.userName = code;


            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentPosition()));
            }
            if (this.chainWithJob != undefined || this.chainWithJob != '')
            {
                if (this.chainWithJob) {
                    if (this.projectCode != undefined || this.projectCode != '')
                        this.getRoleByPrjctUid(this.projectCode, id_user, this.jobTitleId, this.callback);
                    else
                        this.getRoleByPrjctUid('', id_user, this.jobTitleId, this.callback);

                }


            }

            if (pwindow)
                pwindow.close();

            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc();
            }
        }, this);

        grid.on('rowclick', function(g, rowIndex, e) {

            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentRowIndex()));
            }
            var code = g.getStore().getAt(rowIndex).get('name');
            Ext.getCmp(this.UserSelectid).setValue(code);
            this.uid = g.getStore().getAt(rowIndex).get('uid');
            Ext.getCmp('uid').setValue(this.uid);
            var id_user = g.getStore().getAt(rowIndex).get('id');
            Ext.getCmp('userid').setValue(id_user);
            this.userName = code;
            if (this.chainWithJob != undefined || this.chainWithJob != '')
            {
                if (this.chainWithJob) {
                    if (this.projectCode != undefined || this.projectCode != '')
                        this.getRoleByPrjctUid(this.projectCode, id_user, this.jobTitleId, this.callback);
                    else
                        this.getRoleByPrjctUid('', id_user, this.jobTitleId, this.callback);
                }


            }

            if (pwindow)
                pwindow.close();

            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc();
            }
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 100,
                    frame: true,
                    items: [
                        {
                            xtype: 'textfield',
                            fieldLabel: 'Name',
                            enableKeyEvents: true,
                            listeners: {
                                keyup: function(field, e) {
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
        searchUserByName = function(pname) {
            newUrl = '/default/user/listByParams/name/name/data/' + pname;

            userStore.proxy = new Ext.data.HttpProxy({
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

        if (this.editorInstance != undefined)
        {
            this.editorInstance.stopEditing();
            this.editorInstance.hide();
        }

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.UserSelectid;
        var chainWithJob = this.chainWithJob;
        var projectCode = this.projectCode;
        var jobTitleId = this.jobTitleId;
//        var idRole = this.idRole;

        this.uid = '';
        this.userName = '';
        this.idRole = '';
        setFieldValue = function(g, rowIndex) {

        };

        if (!this.width)
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
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.UserSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('userselector', Ext.ux.form.UserSelector);

// backwards compat
Ext.form.UserSelector = Ext.ux.form.UserSelector;


///* CUSTOMER SELECTOR UX!!!!!  */
//
//    Ext.ux.form.CustomerSelector = Ext.extend(Ext.form.Field,  {
//
//        onRender:function(ct,position){
//            var select_id = this.Selectid;
//            var name_id = this.Nameid;
//            var showName = this.ShowName;
//
//            if(this.showName == '' || this.showName == undefined)
//            this.showName = true;
//
//            setCustomerFieldValue = function (g,rowIndex)
//            {
//                var code = g.getStore().getAt(rowIndex).get('cus_kode');
//                Ext.getCmp(select_id).setValue(code);
//                if (name_id != '' || name_id != undefined)
//                {
//                    var name = g.getStore().getAt(rowIndex).get('cus_nama');
//                    Ext.getCmp(name_id).setValue(name);
//                }
//            };
//
//            showCustomerWindow =  function(t)
//            {
//                var customerstore = new Ext.data.Store ({
//
//                url:'/logistic/logisticcustomer/getcustomer',
//                autoLoad:true,
//
//                reader: new Ext.data.JsonReader({
//                    id: 'customerselector-store',
//                    root:'data',
//                    totalProperty:'total'
//                },[
//                    {name:'cus_kode',mapping:'cus_kode'},
//                    {name:'cus_nama',mapping:'cus_nama'}
//                ])
//
//                })
//
//                var grid = new Ext.grid.GridPanel({
//
//                store:customerstore,
//                height: 370,
//                width:386,
//
//                viewConfig:{
//                    forceFit:true
//                },
//
//                columns:[
//                    new Ext.grid.RowNumberer({width:30}),
//                    {
//                        header:'code',
//                        width:100,
//                        dataIndex:'cus_kode',
//                        align:'center',
//                        sortable:true
//                    },{
//                        header:'name',
//                        width:200,
//                        dataIndex:'cus_nama',
//                        align:'center',
//                        sortable:true
//                    }
//                ],listeners:{
//                        'rowdblclick': function(g, rowIndex, e){
//
//                                setCustomerFieldValue(g,rowIndex);
//
//                                if (showwindow)
//                                    showwindow.close();
//                        },
//                        'rowclick': function(g, rowIndex, e){
//
//                                setCustomerFieldValue(g,rowIndex);
//
//                                if (showwindow)
//                                    showwindow.close();
//                        }
//                    },
//                bbar: new Ext.PagingToolbar ({
//                    id: 'paging',
//                    pageSize: 20,
//                    store: customerstore,
//                    displayInfo: true,
//                    displayMsg: 'Displaying data {0} - {1} of {2}',
//                    emptyMsg: "No data to display"
//                }),
//                tbar:[
//                    {
//                        text:'Customer Name',
//                        xtype:'label',
//                        style: 'margin-left: 5px'
//
//                    },{
//                        xtype: 'textfield',
//                        id: 'search',
//                        style: 'margin-left: 5px'
//
//                    },{
//                        text: 'Search',
//                        iconCls: 'search-icon',
//                        handler: searchData,
//                        scope: this
//                    },
//                    '-',
//                    {
//                        text: 'refresh',
//                        iconCls: 'icon-refresh',
//                        handler: refreshData,
//                        scope: this
//                    },'-'
//                ]
//
//            })
//
//            function searchData ()
//            {
//                var search = Ext.getCmp('search').getValue();
//                customerstore.proxy.setUrl('/logistic/logisticcustomer/getcustomer/search/' + search);
//                customerstore.reload();
//                grid.getView().refresh();
//            }
//
//            function refreshData ()
//            {
//                customerstore.clearFilter();
//                Ext.getCmp('search').setValue('');
//                Ext.getCmp('paging').doRefresh();
//            }
//
//            showwindow = new Ext.Window({
//                modal: true,
//                resizable: false,
//                closeAction: 'close',
//                width: 400,
//                height: 400,
//                title: 'Select Project',
//                items: grid
//            });
//
//            showwindow.show();
//
//            };
//
//            if (!this.el) {
//
//                this.selectCustomer = new Ext.form.TriggerField({
//                id: this.Selectid,
//                width: 80,
//                triggerClass: 'teropong',
//                editable: false,
//                onTriggerClick: function(){
//                    showCustomerWindow(this);
//                }
//
//                });
//
//                this.customerName = new Ext.form.TextField({
//                    id: this.Nameid,
//                    width: 150,
//                    disabled:true,
//                    hideLabel: true
//                });
//
//                this.fieldCt = new Ext.Container({
//                    autoEl: {
//                        id: this.id
//                    },
//                    renderTo: ct,
//                    cls: 'ext-project-selector',
//                    layout: 'table',
//                    layoutConfig: {
//                        columns: 2
//                    },
//                    defaults: {
//                        hideParent: true
//                    },
//                    items: [
//                    this.selectCustomer,
//                    this.customerName
//                    ]
//                });
//
//                this.fieldCt.ownerCt = this;
//                this.el = this.fieldCt.getEl();
//                this.items = new Ext.util.MixedCollection();
//                this.items.addAll([this.selectCustomer,this.customerName]);
//                if (!this.ShowName)
//                {
//                    this.items.items[1].setVisible(false);
//                }
//            }
//            Ext.ux.form.ProjectSelector.superclass.onRender.call(this, ct, position);
//
//
//        },
//
//        // private
//        preFocus : Ext.emptyFn,
//
//        beforeDestroy: function() {
//            Ext.destroy(this.fieldCt);
//            Ext.ux.form.SiteSelector.superclass.beforeDestroy.call(this);
//        }
//
//
//    });
//
//    Ext.reg('customerselector', Ext.ux.form.CustomerSelector);


Ext.ux.form.PeriodeSelector = Ext.extend(Ext.form.Field, {
    // private
    getperiodeid: function()
    {
        return this.periodeId;
    },
    showPeriodeWindow: function(t) {

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
                name: 'tahun', type: 'String'
            }, {
                name: 'periode'
            }, {
                name: 'tgl_aw'
            }, {
                name: 'tglak'
            }, {
                name: 'periode_act'
            }, {
                name: 'jumlah_jam_bulan'
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
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: periodeStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {
                    header: 'Year',
                    dataIndex: 'tahun',
                    sortable: true
                },
                {
                    header: 'Periode',
                    dataIndex: 'periode',
                    sortable: true
                },
                {
                    header: 'Start Date',
                    dataIndex: 'tgl_aw',
                    sortable: true
                },
                {
                    header: 'End Date',
                    dataIndex: 'tglak',
                    sortable: true
                },
                {
                    header: 'Action',
                    dataIndex: 'periode_act',
                    sortable: true
                },
                {
                    header: 'Hour per month',
                    dataIndex: 'jumlah_jam_bulan',
                    sortable: true
                }
            ]
        })
        grid.on('rowdblclick', function(g, rowIndex, e) {
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
                pwindow.close();
        }, this);

        grid.on('rowclick', function(g, rowIndex, e) {
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
                pwindow.close();
        }, this);
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
    onRender: function(ct, position) {

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
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodeselector', Ext.ux.form.PeriodeSelector);

Ext.ux.form.CoaSelector = Ext.extend(Ext.form.Field, {
    selectCoa: null,
    coaName: null,
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
            enableKeyEvents: true
        });

        coaKodeText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = '/finance/coa/getcoa/option/coa_kode/search/' + pname;
            coaStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            coaStore.reload();
            gridCoa.getView().refresh();
        }, this);

        var coaNamaText = new Ext.form.TextField({
            fieldLabel: 'COA Name',
            enableKeyEvents: true
        });

        coaNamaText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = '/finance/coa/getcoa/option/coa_nama/search/' + pname;
            coaStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            coaStore.reload();
            gridCoa.getView().refresh();
        }, this);

        var gridCoa = new Ext.grid.GridPanel({
            store: coaStore,
            loadMask: true,
            height: 300,
//            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 100,
                    store: coaStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'COA Code', width: 120, dataIndex: 'coa_kode', sortable: true},
                {header: 'COA Name', width: 200, dataIndex: 'coa_nama', sortable: true},
                {header: 'COA Type', width: 200, dataIndex: 'tipe', sortable: true}
            ]
        });

//        gridCoa.on('rowdblclick', function (g,rowIndex,e){
//            var code = g.getStore().getAt(rowIndex).get('coa_kode');
//            Ext.getCmp(this.Selectid).setValue(code);
//            var name = g.getStore().getAt(rowIndex).get('coa_nama');
//            Ext.getCmp(this.Nameid).setValue(name);
//            if (this.editorInstance != undefined)
//            {
//                this.editorInstance.startEditing((this.editorInstance.getCurrentPosition()));
//            }
//            if (pwindow)
//                pwindow.close();
//        },this);
        gridCoa.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('coa_kode');
            var name = g.getStore().getAt(rowIndex).get('coa_nama');

            if (this.editorInstance != undefined)
            {

                var sel = Ext.getCmp(this.selectCoa.id),
                        nam = Ext.getCmp(this.coaName.id);
                this.editorInstance.setAddData({
                    coa_kode: code,
                    coa_nama: name
                });
                this.editorInstance.startEditing((this.editorInstance.getCurrentRowIndex()));

                sel.setValue(code);
                nam.setValue(name);

                if (isFunction(this.callbackFunc))
                {
                    this.callbackFunc(code);
                }
            }
            else
            {
                Ext.getCmp(this.Selectid).setValue(code);
                Ext.getCmp(this.Nameid).setValue(name);
                if (isFunction(this.callbackFunc))
                {
                    this.callbackFunc(code);
                }
            }
            if (pwindow)
                pwindow.close();
        }, this);

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

        if (this.disabled !== true)
            pwindow.show();
    },
    onRender: function(ct, position) {

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

            if (this.editorInstance == undefined)
            {
                this.selectCoa = new Ext.form.TriggerField({
                    id: this.Selectid,
                    width: this.SelectWidth,
                    triggerClass: 'teropong',
                    editable: false
                });
                if (!this.disabled)
                    this.selectCoa.onTriggerClick = this.showCoaWindow.createDelegate(this);

                this.coaName = new Ext.form.TextField({
                    id: this.Nameid,
                    width: 150,
                    readOnly: true,
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
            }
            else
            {
                this.selectCoa = new Ext.form.TriggerField({
                    width: this.SelectWidth,
                    triggerClass: 'teropong',
                    editable: false
                });
                if (!this.disabled)
                    this.selectCoa.onTriggerClick = this.showCoaWindow.createDelegate(this);

                this.coaName = new Ext.form.TextField({
                    width: 150,
                    readOnly: true,
                    hideLabel: true
                });

                this.fieldCt = new Ext.Container({
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
            }

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectCoa, this.coaName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.CoaSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.CoaSelector.superclass.beforeDestroy.call(this);
    },
    getValue: function()
    {
        if (this.editorInstance != undefined)
        {
            var f = this.editorInstance.getFieldsByXtype('coaselector');

            for (i = 0; i < f.length; i++)
            {
                var sel = Ext.getCmp(f[i].id).getSelectCoa();
                if (sel != null)
                {
                    return sel.getValue();
                    break;
                }
            }
        }
    },
    getNameValue: function()
    {
        if (this.editorInstance != undefined)
        {
            var f = this.editorInstance.getFieldsByXtype('coaselector');

            for (i = 0; i < f.length; i++)
            {
                var sel = Ext.getCmp(f[i].id).getNameCoa();
                if (sel != null)
                {
                    return sel.getValue();
                    break;
                }
            }
        }
        else
        {
            return this.getNameCoa().getValue();
        }
    },
    setValue: function(val)
    {
        if (this.editorInstance != undefined)
        {
            var f = this.editorInstance.getFieldsByXtype('coaselector');

            for (i = 0; i < f.length; i++)
            {
                var sel = Ext.getCmp(f[i].id).getSelectCoa();
                if (sel != null)
                    sel.setValue(val);
            }
        }
        else
        {
//            this.
        }
    },
    setNameValue: function(val)
    {
        if (this.editorInstance != undefined)
        {
            var f = this.editorInstance.getFieldsByXtype('coaselector');

            for (i = 0; i < f.length; i++)
            {
                var sel = Ext.getCmp(f[i].id).getNameCoa();
                if (sel != null)
                    sel.setValue(val);
            }
        }
    },
    getSelectCoa: function()
    {
        return this.selectCoa;
    },
    getNameCoa: function()
    {
        return this.coaName;
    },
    clearValue: function()
    {
        this.getSelectCoa().setValue('');
        this.getNameCoa().setValue('');
    }


});

Ext.reg('coaselector', Ext.ux.form.CoaSelector);


/* CUSTOMER SELECTOR UX!!!!!  */

Ext.ux.form.CustomerSelector = Ext.extend(Ext.form.Field, {
    onRender: function(ct, position) {
        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;

        setCustomerFieldValue = function(g, rowIndex)
        {
            var code = g.getStore().getAt(rowIndex).get('cus_kode');
            Ext.getCmp(select_id).setValue(code);
            if (name_id != undefined && name_id != '')
            {
                var name = g.getStore().getAt(rowIndex).get('cus_nama');
                Ext.getCmp(name_id).setValue(name);
            }
        };

        showCustomerWindow = function(t)
        {
            var customerstore = new Ext.data.Store({
                url: '/logistic/logisticcustomer/getcustomer',
                autoLoad: true,
                reader: new Ext.data.JsonReader({
                    id: 'customerselector-store',
                    root: 'data',
                    totalProperty: 'total'
                }, [
                    {name: 'cus_kode'},
                    {name: 'cus_nama'}
                ])

            })

            var grid = new Ext.grid.GridPanel({
                store: customerstore,
                height: 370,
                width: 386,
                viewConfig: {
                    forceFit: true
                },
                columns: [
                    new Ext.grid.RowNumberer({width: 30}),
                    {
                        header: 'code',
                        width: 100,
                        dataIndex: 'cus_kode',
                        align: 'center',
                        sortable: true
                    }, {
                        header: 'name',
                        width: 200,
                        dataIndex: 'cus_nama',
                        align: 'center',
                        sortable: true
                    }
                ], listeners: {
                    'rowdblclick': function(g, rowIndex, e) {

                        setCustomerFieldValue(g, rowIndex);

                        if (showwindow)
                            showwindow.close();
                    },
                    'rowclick': function(g, rowIndex, e) {

                        setCustomerFieldValue(g, rowIndex);

                        if (showwindow)
                            showwindow.close();
                    }
                },
                bbar: new Ext.PagingToolbar({
                    id: 'paging',
                    pageSize: 20,
                    store: customerstore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                }),
                tbar: [
                    {
                        text: 'Customer Name',
                        xtype: 'label',
                        style: 'margin-left: 5px'

                    }, {
                        xtype: 'textfield',
                        id: 'search',
                        style: 'margin-left: 5px'

                    }, {
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
                    }, '-'
                ]

            })

            function searchData()
            {
                var search = Ext.getCmp('search').getValue();
                customerstore.proxy.setUrl('/logistic/logisticcustomer/getcustomer/search/' + search);
                customerstore.reload();
                grid.getView().refresh();
            }

            function refreshData()
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
                onTriggerClick: function() {
                    showCustomerWindow(this);
                }

            });

            this.customerName = new Ext.form.TextField({
                id: this.Nameid,
                width: 150,
                disabled: true,
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
            this.items.addAll([this.selectCustomer, this.customerName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.CustomerSelector.superclass.onRender.call(this, ct, position);


    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.CustomerSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('customerselector', Ext.ux.form.CustomerSelector);


Ext.ux.form.PeriodeSelector = Ext.extend(Ext.form.Field, {
    // private
    getperiodeid: function()
    {
        return this.periodeId;
    },
    showPeriodeWindow: function(t) {

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
                name: 'tahun', type: 'String'
            }, {
                name: 'periode'
            }, {
                name: 'tgl_aw'
            }, {
                name: 'tglak'
            }, {
                name: 'periode_act'
            }, {
                name: 'jumlah_jam_bulan'
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
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: periodeStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {
                    header: 'Year',
                    dataIndex: 'tahun',
                    sortable: true
                },
                {
                    header: 'Periode',
                    dataIndex: 'periode',
                    sortable: true
                },
                {
                    header: 'Start Date',
                    dataIndex: 'tgl_aw',
                    sortable: true
                },
                {
                    header: 'End Date',
                    dataIndex: 'tglak',
                    sortable: true
                },
                {
                    header: 'Action',
                    dataIndex: 'periode_act',
                    sortable: true
                },
                {
                    header: 'Hour per month',
                    dataIndex: 'jumlah_jam_bulan',
                    sortable: true
                }
            ]
        })
        grid.on('rowdblclick', function(g, rowIndex, e) {
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
                pwindow.close();
        }, this);

        grid.on('rowclick', function(g, rowIndex, e) {
            var tahun = g.getStore().getAt(rowIndex).get('tahun');
            var periode = g.getStore().getAt(rowIndex).get('periode');
            Ext.getCmp(this.PeriodeSelectid).setValue(tahun + ' - ' + periode);
            this.periodeId = g.getStore().getAt(rowIndex).get('id');
            if (pwindow)
                pwindow.close();
        }, this);
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
    onRender: function(ct, position) {

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
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodeselector', Ext.ux.form.PeriodeSelector);


// Supplier Selector UX

Ext.ux.form.SupplierSelector = Ext.extend(Ext.form.Field, {
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
            {name: 'alamat'},
            {name: 'tlp'},
            {name: 'email'},
            {name: 'fax'},
            {name: 'ket'},
            {name: 'master_kota'},
            {name: 'negara'},
            {name: 'orang'},
            {name: 'statussupplier'}
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
            viewConfig: {
                forceFit: true
            },
            bbar: [new Ext.PagingToolbar({
                    pageSize: 100,
                    store: supplierStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Supplier Code', width: 120, dataIndex: 'sup_kode', sortable: true},
                {header: 'Supplier Name', width: 200, dataIndex: 'sup_nama', sortable: true},
                {header: 'Supplier City', width: 200, dataIndex: 'master_kota', sortable: true}
            ],
            tbar: [{
                    text: 'Search By',
                    xtype: 'label',
                    style: 'margin-left:5px'
                }, '-', {
                    xtype: 'combo',
                    id: 'option',
                    width: 100,
                    store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['sup_kode', 'Supplier Code'],
                            ['sup_nama', 'Supplier Name'],
                            ['master_kota', 'Supplier City']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    value: 'sup_kode'
                }, '-', {
                    xtype: 'textfield',
                    id: 'search',
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function(txttext, event)
                        {
                            var txttext = txttext.getValue();
                            if (txttext != "" && txttext.toString().length >= 3)
                            {
                                var option = Ext.getCmp('option').getValue();
                                var search = Ext.getCmp('search').getValue();

                                supplierStore.proxy.url = '/default/suplier/getsupplier/search/' + search + '/option/' + option;
                                supplierStore.proxy.setUrl('/default/suplier/getsupplier/search/' + search + '/option/' + option);
                                supplierStore.proxy.api.read['url'] = '/default/suplier/getsupplier/search/' + search + '/option/' + option;
                                supplierStore.load();
                                gridSupplier.getView().refresh();
                            }
                        }
                    }
                }]
        });

        gridSupplier.on('rowdblclick', function(g, rowIndex, e) {
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
        }, this);
        gridSupplier.on('rowclick', function(g, rowIndex, e) {
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
        }, this)

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
            title: 'Select Supplier',
            id: 'select-supplier',
            layout: 'absolute',
            minHeight: 200,
            stateful: false,
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 330,
            loadMask: true,
            items: [gridSupplier]
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

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
    preFocus: Ext.emptyFn,
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
function selectCoaPC(coaPC, callbackFunc, returnAllField)
{
    if (returnAllField == undefined)
        returnAllField = false;

    var reader = new Ext.data.JsonReader({
        idProperty: 'id',
        root: 'data'
    }, [
        {name: 'id'},
        {name: 'coa_kode'},
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
        viewConfig: {
            forceFit: true
        },
        columns: [
            new Ext.grid.RowNumberer({width: 30}),
            {header: 'Coa Code', width: 120, dataIndex: 'coa_kode', sortable: true},
            {header: 'Coa Name', width: 200, dataIndex: 'coa_nama', sortable: true}
        ]
    });

    gridCoa.on('rowclick', function(g, rowIndex, e) {
        var rec = g.getStore().getAt(rowIndex);
        var code = rec.get('coa_kode');
        if (pwindow)
            pwindow.close();

        if (!returnAllField)
            callbackFunc(code);
        else
            callbackFunc(rec);
    }, this);

    pwindow = new Ext.Window({
        title: 'Select COA for Petty Cash Transaction',
        id: 'select-coa',
        layout: 'absolute',
        minHeight: 200,
        stateful: false,
        modal: true,
        resizable: false,
        closeAction: 'close',
        width: 500,
        height: 330,
        loadMask: true,
        items: [gridCoa]
    });

    pwindow.show();
}

// Trano Selector UX

Ext.ux.form.TranoSelectorJournal = Ext.extend(Ext.form.Field, {
    showTranoWindow: function(t) {

        var url = group = '';
        if (this.detailType != '' && this.detailType != undefined)
            url = '/detailType/' + this.detailType;

        if (this.groupBy == true)
            group = '/grouped/true';

        var reader = new Ext.data.JsonReader({
            idProperty: 'id',
            totalProperty: 'count',
            root: 'posts'},
        [
            {name: 'id'},
            {name: 'trano'},
            {name: 'prj_kode'},
            {name: 'sit_kode'},
            {name: 'prj_nama'},
            {name: 'sit_nama'}
        ]
                );

        var proxy = new Ext.data.HttpProxy({
            url: '/default/home/getlistdocumentbytype'
        });

        var store = new Ext.data.Store({
            id: 'store-doc-msg',
            reader: reader,
            proxy: proxy
        });

        var grids = new Ext.grid.GridPanel({
            loadMask: true,
            frame: true,
            width: 280,
            id: 'grid-trano',
            store: store,
            loadMask: {msg: 'Loading...'},
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
            }),
            viewConfig: {
                forceFit: true
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

        grids.on('rowclick', function(grid, index, e) {
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp('prj_kode_text').setValue(rec.data['prj_kode']);
            Ext.getCmp('prj_nama_text').setValue(rec.data['prj_nama']);
            Ext.getCmp('sit_kode_text').setValue(rec.data['sit_kode']);
            Ext.getCmp('sit_nama_text').setValue(rec.data['sit_nama']);
            Ext.getCmp(this.Selectid).setValue(trano);
            var itemType = Ext.getCmp('combo-type').getRawValue();
            Ext.getCmp('doc-form-panel').close();
            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc(trano, itemType);
            }

        }, this);

        var isArrayType = false,
                jsonType = '';
        if (Object.prototype.toString.call(this.Tranotype) === '[object Array]')
        {
            isArrayType = true;
            jsonType = Ext.util.JSON.encode(this.Tranotype);
        }

        var viewportsMsg = ({
            layout: 'border',
            stateful: false,
            loadMask: true,
            bodyCfg: {cls: 'xpanel-body-table', style: {'overflow': 'auto'}},
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
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .9,
                                    layout: 'form',
                                    items: [
                                        {
                                            id: 'combo-type',
                                            fieldLabel: 'Transaction',
                                            hiddenName: 'workflow_item_type_id',
                                            width: 100,
                                            xtype: 'combo',
                                            triggerAction: 'all',
                                            mode: 'remote',
                                            editable: false,
                                            displayField: 'name',
                                            style: 'font-weight: bold; color: black',
                                            valueField: 'workflow_item_type_id',
                                            store: new Ext.data.JsonStore({
                                                url: '/admin/workflow/listworkflowitemtype/all/true',
                                                root: 'posts',
                                                fields: [
                                                    {name: "name"}, {name: "workflow_item_type_id"}
                                                ],
                                                listeners: {
                                                    'beforeload': function(t, o) {
                                                        o.params.type = jsonType;
                                                    }
                                                }
                                            }),
                                            listeners: {
                                                'select': function(t, n, o) {
                                                    store.proxy = new Ext.data.HttpProxy({
                                                        url: '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + url + group
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
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .55,
                                    layout: 'form',
                                    style: 'margin-right: 3px;',
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
                                {columnWidth: .3,
                                    layout: 'form',
                                    style: 'margin-left: 3px;',
                                    items: [
                                        new Ext.Button({
                                            text: 'Search',
                                            id: 'search-button',
                                            style: 'margin-top: 12px;',
                                            handler: function() {
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
                                                        url: '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + '/trano/' + trano + '/prj_kode/' + prj + '/sit_kode/' + site + url + group
                                                    });
                                                    store.reload();
                                                }
                                            }
                                        }),
                                        new Ext.Button({
                                            text: 'Clear',
                                            id: 'clear-button',
                                            style: 'margin-top: 5px;',
                                            handler: function() {
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
        var dFormMsg = new Ext.Window({
            id: 'doc-form-panel',
            layout: 'fit',
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
            if (Object.prototype.toString.call(this.Tranotype) !== '[object Array]')
            {
                Ext.getCmp('combo-type').setValue(this.Tranotype);
                Ext.getCmp('combo-type').setRawValue(this.Tranotype);
                store.proxy = new Ext.data.HttpProxy({
                    url: '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + url + group
                });
                store.reload();

                if (this.Disabletype == true)
                    Ext.getCmp('combo-type').disable();
            }
        }
    },
    onRender: function(ct, position) {

        var detailType = this.detailType;

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
        Ext.ux.form.TranoSelectorJournal.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoSelectorJournal.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('tranoselectorJournal', Ext.ux.form.TranoSelectorJournal);

Ext.ux.form.TranoSelectorOverHead = Ext.extend(Ext.form.Field, {
    showTranoWindow: function(t) {

        var url = group = '';
        if (this.detailType != '' && this.detailType != undefined)
            url = '/detailType/' + this.detailType;

        if (this.groupBy == true)
            group = '/grouped/true';

        var reader = new Ext.data.JsonReader({
            idProperty: 'id',
            totalProperty: 'count',
            root: 'posts'},
        [
            {name: 'id'},
            {name: 'trano'},
            {name: 'prj_kode'},
            {name: 'sit_kode'}
        ]
                );

        var proxy = new Ext.data.HttpProxy({
            url: '/default/home/getlistdocumentbytype'
        });

        var store = new Ext.data.Store({
            id: 'store-doc-msg',
            reader: reader,
            proxy: proxy
        });

        var grids = new Ext.grid.GridPanel({
            loadMask: true,
            frame: true,
            width: 280,
            id: 'grid-trano',
            store: store,
            loadMask: {msg: 'Loading...'},
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
            }),
            viewConfig: {
                forceFit: true
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

        grids.on('rowclick', function(grid, index, e) {
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp(this.Selectid).setValue(trano);
            var itemType = Ext.getCmp('combo-type').getRawValue();
            Ext.getCmp('doc-form-panel').close();
            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc(trano, itemType);
            }

        }, this);

        var isArrayType = false,
                jsonType = '';
        if (Object.prototype.toString.call(this.Tranotype) === '[object Array]')
        {
            isArrayType = true;
            jsonType = Ext.util.JSON.encode(this.Tranotype);
        }

        var viewportsMsg = ({
            layout: 'border',
            stateful: false,
            loadMask: true,
            bodyCfg: {cls: 'xpanel-body-table', style: {'overflow': 'auto'}},
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
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .9,
                                    layout: 'form',
                                    items: [
                                        {
                                            id: 'combo-type',
                                            fieldLabel: 'Transaction',
                                            hiddenName: 'workflow_item_type_id',
                                            width: 100,
                                            xtype: 'combo',
                                            triggerAction: 'all',
                                            mode: 'remote',
                                            editable: false,
                                            displayField: 'name',
                                            style: 'font-weight: bold; color: black',
                                            valueField: 'workflow_item_type_id',
                                            store: new Ext.data.JsonStore({
                                                url: '/admin/workflow/listworkflowitemtype/all/true',
                                                root: 'posts',
                                                fields: [
                                                    {name: "name"}, {name: "workflow_item_type_id"}
                                                ],
                                                listeners: {
                                                    'beforeload': function(t, o) {
                                                        o.params.type = jsonType;
                                                    }
                                                }
                                            }),
                                            listeners: {
                                                'select': function(t, n, o) {
                                                    store.proxy = new Ext.data.HttpProxy({
                                                        url: '/default/home/getlistdocumentbytype/type/ARF' //+ Ext.getCmp('combo-type').getRawValue() + url + group
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
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .55,
                                    layout: 'form',
                                    style: 'margin-right: 3px;',
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
                                {columnWidth: .3,
                                    layout: 'form',
                                    style: 'margin-left: 3px;',
                                    items: [
                                        new Ext.Button({
                                            text: 'Search',
                                            id: 'search-button',
                                            style: 'margin-top: 12px;',
                                            handler: function() {
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
                                                        url: '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + '/trano/' + trano + '/prj_kode/' + prj + '/sit_kode/' + site + url + group
                                                    });
                                                    store.reload();
                                                }
                                            }
                                        }),
                                        new Ext.Button({
                                            text: 'Clear',
                                            id: 'clear-button',
                                            style: 'margin-top: 5px;',
                                            handler: function() {
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
        var dFormMsg = new Ext.Window({
            id: 'doc-form-panel',
            layout: 'fit',
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
            if (Object.prototype.toString.call(this.Tranotype) !== '[object Array]')
            {
                Ext.getCmp('combo-type').setValue(this.Tranotype);
                Ext.getCmp('combo-type').setRawValue(this.Tranotype);
                store.proxy = new Ext.data.HttpProxy({
                    url: '/default/home/getlistdocumentbytype/type/' + Ext.getCmp('combo-type').getRawValue() + url + group
                });
                store.reload();

                if (this.Disabletype == true)
                    Ext.getCmp('combo-type').disable();
            }
        }
    },
    onRender: function(ct, position) {

        var detailType = this.detailType;

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
        Ext.ux.form.TranoSelectorOverHead.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoSelectorOverHead.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('tranoselectoroverhead', Ext.ux.form.TranoSelectorOverHead);

Ext.ux.form.PeriodeFinanceSelector = Ext.extend(Ext.form.Field, {
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
            enableKeyEvents: true
        });

        periodeKodeText.on('keyup', function(field, e) {
            var pname = field.getValue();

            periodeStore.proxy = new Ext.data.HttpProxy({
                url: urlProxy + '/perkode/' + pname
            });
            periodeStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridPeriode = new Ext.grid.GridPanel({
            store: periodeStore,
            loadMask: true,
            height: 200,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: periodeStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Periode Code', width: 80, dataIndex: 'perkode', sortable: true},
                {header: 'Periode', width: 90, dataIndex: 'bulan', sortable: true, renderer: function(v, p, r) {
                        return v + " - " + r.data['tahun'];
                    }},
                {header: 'Start Date', width: 90, dataIndex: 'tgl_awal', sortable: true},
                {header: 'End Date', width: 90, dataIndex: 'tgl_akhir', sortable: true},
                {header: 'Active', width: 90, dataIndex: 'aktif', sortable: true, renderer: function(v, p, r) {
                        if (v == 'ACTIVE')
                        {
                            return 'CURRENT PERIODE';
                        }
                        else
                            return 'PREVIOUS PERIODE';
                    }}
            ]
        });

        gridPeriode.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('perkode');
            Ext.getCmp(this.Selectid).setValue(code);

            if (this.callbackFunc != undefined)
            {
                this.callbackFunc();
            }

            if (pwindow)
                pwindow.close();
        }, this);

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
    onRender: function(ct, position) {

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
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodeFinanceSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodefinanceselector', Ext.ux.form.PeriodeFinanceSelector);

Ext.ux.form.WarehouseSelector = Ext.extend(Ext.form.Field, {
    clearData: function()
    {
        this.warehouse = '';
        this.warehouseName = '';
    },
    getWarehouse: function()
    {
        return this.warehouse;
    },
    getWarehouseName: function()
    {
        return this.warehouseName;
    },
    setWarehouse: function(warehouse)
    {
        this.warehouse = warehouse;
    },
    setWarehouseName: function(name)
    {
        this.warehouseName = name;
        if (this.warehouseName != undefined)
            Ext.getCmp(this.Selectid).setValue(this.warehouseName);
    },
    showWarehouseWindow: function(t) {

        var urlProxy = '/delivery/list';

        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'id',
            root: 'posts'
        }, [
            {name: 'id'},
            {name: 'gdg_kode'},
            {name: 'gdg_nama'},
            {name: 'alamat1'}
        ]);

        var warehouseStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'warehouseselector-store'
        });
        warehouseStore.load();

        var warehouseNamaText = new Ext.form.TextField({
            fieldLabel: 'Warehouse Name',
            enableKeyEvents: true
        });

        warehouseNamaText.on('keyup', function(field, e) {
            var pname = field.getValue();

            warehouseStore.proxy = new Ext.data.HttpProxy({
                url: '/delivery/listbyparams/name/gdg_nama/data/' + pname
            });
            warehouseStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridWarehouse = new Ext.grid.GridPanel({
            store: warehouseStore,
            loadMask: true,
            height: 200,
            viewConfig:
                    {
                        forceFit: true
                    },
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: warehouseStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Warehouse', width: 80, dataIndex: 'gdg_nama', sortable: true},
                {header: 'Address', width: 90, dataIndex: 'alamat1', sortable: true}
            ]
        });

        gridWarehouse.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('gdg_kode');
            this.warehouse = code;
            this.warehouseName = g.getStore().getAt(rowIndex).get('gdg_nama');
            Ext.getCmp(this.Selectid).setValue(this.warehouseName);

            if (this.callbackFunc != undefined)
            {
                this.callbackFunc();
            }

            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        warehouseNamaText,
                        gridWarehouse
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 300,
            title: 'Select Warehouse',
            items: forms
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 120;

        if (!this.el) {
            this.selectWarehouse = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectWarehouse.onTriggerClick = this.showWarehouseWindow.createDelegate(this);

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
                    this.selectWarehouse
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectWarehouse]);
        }
        Ext.ux.form.WarehouseSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.WarehouseSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('warehouseselector', Ext.ux.form.WarehouseSelector);

Ext.ux.form.ItemSelector = Ext.extend(Ext.form.Field, {
    showItemWindow: function(t) {

        var urlProxy = '',
                theProject = null,
                useProject = false;

        if (this.prjKodeField != undefined)
        {
            useProject = true;
            var cek = Ext.getCmp(this.prjKodeField);
            if (cek)
            {
                urlProxy = '/prj_kode/' + cek.getValue();
                theProject = cek.getValue();
            }
        }

        if (this.prjKode != undefined)
        {
            useProject = true;
            theProject = this.prjKode;
            urlProxy = '/prj_kode/' + this.prjKode;
        }

        if (this.showAll != undefined)
        {
            urlProxy += '/all/true';
        }

        var proxy = new Ext.data.HttpProxy({
            url: '/default/barang/list' + urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'id',
            root: 'posts'
        }, [
            {name: 'kode_brg'},
            {name: 'nama_brg'},
            {name: 'sat_kode'},
            {name: 'stspmeal'}
        ]);

        var itemStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'itemselector-store'
        });
        itemStore.load();

        var kodeBrgText = new Ext.form.TextField({
            fieldLabel: 'Product Code',
            enableKeyEvents: true
        });

        kodeBrgText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = '/default/barang/list/code/' + pname + urlProxy;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var namaBrgText = new Ext.form.TextField({
            fieldLabel: 'Product Name',
            enableKeyEvents: true
        });

        namaBrgText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = '/default/barang/list/name/' + pname + urlProxy;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var checkPulsa = new Ext.form.Checkbox({
            boxLabel: 'Show Item Pulsa'
        });

        checkPulsa.on('check', function(check, value) {
            if (value == true)
            {
                newUrl = '/default/barang/list/pulsa/true' + urlProxy;

            }
            else
            {
                newUrl = '/default/barang/list' + urlProxy;
            }
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
                {header: 'Product Code', width: 120, dataIndex: 'kode_brg', sortable: true},
                {header: 'Product Name', width: 200, dataIndex: 'nama_brg', sortable: true},
                {header: 'UOM', width: 200, dataIndex: 'sat_kode', sortable: true},
                {header: 'Piece Meal', width: 200, dataIndex: 'stspmeal', sortable: true}
            ]
        });
//
//        gridItem.on('rowdblclick', function (g,rowIndex,e){
//            var code = g.getStore().getAt(rowIndex).get('kode_brg');
//            Ext.getCmp(this.Selectid).setValue(code);
//            this.setKode(code);
//            var name = g.getStore().getAt(rowIndex).get('nama_brg');
//            if (Ext.getCmp(this.Nameid))
//            {
//                Ext.getCmp(this.Nameid).setValue(name);
//                this.setName(name);
//            }
//            if (this.editorInstance != undefined)
//            {
//                this.editorInstance.startEditing((this.editorInstance.getCurrentPosition()));
//            }
//            if (pwindow)
//                pwindow.close();
//        },this);
        gridItem.on('rowclick', function(g, rowIndex, e) {
            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentRowIndex()));
            }
            var code = g.getStore().getAt(rowIndex).get('kode_brg');
            Ext.getCmp(this.Selectid).setValue(code);
            this.setKode(code);
            var name = g.getStore().getAt(rowIndex).get('nama_brg');
            if (Ext.getCmp(this.Nameid))
            {
                Ext.getCmp(this.Nameid).setValue(name);
                this.setName(name);
            }
            
            var uom = g.getStore().getAt(rowIndex).get('sat_kode');
            Ext.getCmp('uom_text').setValue(uom);
            
            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        kodeBrgText,
                        namaBrgText,
                        checkPulsa,
                        gridItem
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 420,
            title: 'Select Product',
            items: forms
        });

        if (this.editorInstance != undefined)
        {
            this.editorInstance.stopEditing();
            this.editorInstance.hide();
        }

        if (this.disabled !== true)
        {
            if (!useProject)
                pwindow.show();
            else
            {
                if (theProject != '')
                    pwindow.show();
            }
        }
    },
    onRender: function(ct, position) {

        this.kodeBrg = '';
        this.namaBrg = '';

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;

        if (this.useDisplayField == '' || this.useDisplayField == undefined)
            this.useDisplayField = false;

        if (this.SelectWidth == undefined)
            this.SelectWidth = 80;

        if (!this.el) {
            this.selectItem = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.SelectWidth,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectItem.onTriggerClick = this.showItemWindow.createDelegate(this);

            if (this.useDisplayField)
            {
                this.itemName = new Ext.form.DisplayField({
                    id: this.Nameid,
                    width: 150,
                    style: 'font-size: 12px;',
                    hideLabel: true
                });
            }
            else
            {
                this.itemName = new Ext.form.TextField({
                    id: this.Nameid,
                    width: 150,
                    readOnly: true,
                    hideLabel: true
                });
            }
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
                    this.selectItem,
                    this.itemName
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectItem, this.itemName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.ItemSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.ItemSelector.superclass.beforeDestroy.call(this);
    },
    clearData: function()
    {
        this.kodeBrg = '';
        this.namaBrg = '';
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue('');
        if (Ext.getCmp(this.Nameid))
            Ext.getCmp(this.Nameid).setValue('');
    },
    getKode: function()
    {
        return this.kodeBrg;
    },
    getName: function()
    {
        return this.namaBrg;
    },
    setKode: function(v)
    {
        this.kodeBrg = v;
    },
    setName: function(v)
    {
        this.namaBrg = v;
    },
    getValue: function()
    {
        return Ext.getCmp(this.Selectid).getValue();
    },
    setValue: function(v)
    {
        if (Ext.getCmp(this.Selectid))
            Ext.getCmp(this.Selectid).setValue(v);
    }


});

Ext.reg('itemselector', Ext.ux.form.ItemSelector);


Ext.ux.form.TransactionSelector = Ext.extend(Ext.form.Field, {
    showWindow: function(t) {

        var proxy = new Ext.data.HttpProxy({
            url: this.url
        });

        var itemList = [];
        Ext.each(this.storeItemList, function(item) {
            var type = 'string';
            if (item.type != '')
                type = item.type;
            itemList.push({
                name: item.name,
                type: type
            });
        });

        itemList = Ext.data.Record.create(itemList);

        var reader = new Ext.data.JsonReader({
            totalProperty: this.total,
            idProperty: 'id',
            root: this.root,
            fields: itemList
        });

        var itemStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'transactionselector-store'
        });
        itemStore.load();

        var kodeText = new Ext.form.TextField({
            fieldLabel: 'Transaction No',
            enableKeyEvents: true
        });

        kodeText.on('keyup', function(field, e) {
            var pname = field.getValue();
            newUrl = this.urlKodeSearch + pname;
            itemStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            itemStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridItem = new Ext.grid.GridPanel({
            store: itemStore,
            loadMask: true,
            height: this.gridHeight,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: this.storePageSize,
                    store: itemStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: this.gridColumns
        });

        gridItem.on('rowclick', function(g, rowIndex, e) {
            if (this.editorInstance != undefined)
            {
                this.editorInstance.startEditing((this.editorInstance.getCurrentRowIndex()));
            }
            if (this.callbackGridClick != undefined)
                this.callbackGridClick(g, rowIndex);
            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        kodeText,
                        gridItem
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: this.windowsHeight,
            title: this.title,
            items: forms,
            buttons: [
                {
                    text: 'Close',
                    handler: function() {
                        pwindow.close();
                    }
                }
            ]
        });

        if (this.editorInstance != undefined)
        {
            this.editorInstance.stopEditing();
            this.editorInstance.hide();
        }

        if (this.disabled !== true)
            pwindow.show();
    },
    onRender: function(ct, position) {

        this.kodeTrans = '';

        var select_id = this.Selectid;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.SelectWidth == undefined)
            this.SelectWidth = 80;

        if (this.windowsOnly == undefined)
            this.windowsOnly = false;

        if (this.gridHeight == undefined)
            this.gridHeight = 300;

        if (this.windowsHeight == undefined)
            this.windowsHeight = 400;

        if (!this.windowOnly)
        {
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
                        columns: 1
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
            Ext.ux.form.TransactionSelector.superclass.onRender.call(this, ct, position);
        }
        else
        {
            this.showWindow.createDelegate(this);
        }
    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TransactionSelector.superclass.beforeDestroy.call(this);
    },
    clearData: function()
    {
        this.kodeTrans = '';
    },
    getKode: function()
    {
        return this.kodeTrans;
    },
    setKode: function(v)
    {
        this.kodeTrans = v;
    }
});

Ext.reg('transactionselector', Ext.ux.form.TransactionSelector);

// Trano Jurnal Selector UX

Ext.ux.form.TranoJurnalSelector = Ext.extend(Ext.form.Field, {
    showTranoWindow: function(t) {

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            root: 'data'},
        [
            {name: 'trano'},
            {name: 'ref_number'},
            {name: 'ref_number_myob'},
            {name: 'tgl'},
            {name: 'tglpost'},
            {name: 'tglclose'},
            {name: 'debit'},
            {name: 'credit'}
        ]
                );

        var proxy = new Ext.data.HttpProxy({
            url: '/finance/jurnal/get-trano-jurnal'
        });

        var store = new Ext.data.Store({
            id: 'store-tranojurnal',
            reader: reader,
            proxy: proxy
        });

        var grids = new Ext.grid.GridPanel({
            frame: true,
            width: 350,
            id: 'grid-trano',
            store: store,
            loadMask: {msg: 'Loading...'},
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
            }),
            viewConfig: {
                forceFit: true
            },
            bbar: [
                new Ext.PagingToolbar({
                    pageSize: 30,
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
                    width: 120
                },
                {
                    id: 'ref_number',
                    header: "Ref Number",
                    dataIndex: 'ref_number',
                    width: 100
                },
                {
                    id: 'ref_number',
                    header: "MYOB Ref Number",
                    dataIndex: 'ref_number_myob',
                    width: 100
                },
                {
                    id: 'debit',
                    header: " Total Debit",
                    dataIndex: 'debit',
                    width: 100
                },
                {
                    id: 'credit',
                    header: " Total Credit",
                    dataIndex: 'credit',
                    width: 100
                },
            ]
        });

        grids.on('rowclick', function(grid, index, e) {
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp(this.Selectid).setValue(trano);
            Ext.getCmp('doc-form-panel').close();

            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc(trano);
            }

        }, this);

        var isArrayType = false,
                jsonType = '';
        if (Object.prototype.toString.call(this.Tranotype) === '[object Array]')
        {
            isArrayType = true;
            jsonType = Ext.util.JSON.encode(this.Tranotype);
        }

        var viewportsMsg = ({
            layout: 'border',
            stateful: false,
            loadMask: true,
            bodyCfg: {cls: 'xpanel-body-table', style: {'overflow': 'auto'}},
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
                    height: 140,
                    stateful: false,
                    items: [
                        {
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .9,
                                    layout: 'form',
                                    items: [
                                        {
                                            id: 'combo-type',
                                            fieldLabel: 'Journal Type',
                                            width: 120,
                                            xtype: 'combo',
                                            store: new Ext.data.SimpleStore({
                                                fields: ['name', 'nilai'],
                                                data: [
                                                    ['Adjusting Journal', 'ADJ'],
                                                    ['Voucher Journal', 'JV'],
                                                    ['Sales Journal', 'SJ'],
                                                    ['Settlement Journal', 'JS'],
                                                    ['Accrual Journal', 'ACJ']
                                                ]
                                            }),
                                            valueField: 'nilai',
                                            displayField: 'name',
                                            mode: 'local',
                                            typeAhead: true,
                                            forceSelection: true,
                                            editable: false,
                                            style: 'font-weight: bold; color: black',
                                            listeners: {
                                                'select': function(t, n, o) {
                                                    store.load({
                                                        params: {
                                                            type: Ext.getCmp('combo-type').getValue()
                                                        }
                                                    });
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            layout: 'column',
                            frame: true,
                            items: [
                                {columnWidth: .55,
                                    layout: 'form',
                                    style: 'margin-right: 3px;',
                                    items: [
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'Trano',
                                            width: 80,
                                            id: 'search_trano'
                                        },
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'Ref Number',
                                            width: 80,
                                            id: 'search_ref_number'
                                        },
                                        {
                                            xtype: 'textfield',
                                            fieldLabel: 'MYOB Ref Number',
                                            width: 80,
                                            id: 'search_myob_ref_number'
                                        }
                                    ]
                                },
                                {columnWidth: .3,
                                    layout: 'form',
                                    style: 'margin-left: 3px;',
                                    items: [
                                        new Ext.Button({
                                            text: 'Search',
                                            id: 'search-button',
                                            style: 'margin-top: 12px;',
                                            handler: function() {
                                                var type = Ext.getCmp('combo-type').getValue();
                                                if (type == "" || type == null)
                                                {
                                                    Ext.Msg.alert('Error!', "Please select Transaction!");
                                                    return false;
                                                }
                                                else
                                                {
                                                    var trano = Ext.getCmp('search_trano').getValue();
                                                    var ref_number = Ext.getCmp('search_ref_number').getValue();
                                                    var myob_ref_number = Ext.getCmp('search_myob_ref_number').getValue();

                                                    store.load({
                                                        params: {
                                                            type: Ext.getCmp('combo-type').getValue(),
                                                            trano: trano,
                                                            ref_number: ref_number,
                                                            myob_ref_number: myob_ref_number
                                                        }
                                                    });
                                                }
                                            }
                                        }),
                                        new Ext.Button({
                                            text: 'Clear',
                                            id: 'clear-button',
                                            style: 'margin-top: 5px;',
                                            handler: function() {
                                                Ext.getCmp('search_trano').setValue('');
                                                Ext.getCmp('search_ref_number').setValue('');
                                                Ext.getCmp('search_myob_ref_number').setValue('');
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
        var dFormMsg = new Ext.Window({
            id: 'doc-form-panel',
            layout: 'fit',
            width: 450,
            height: 430,
            title: 'Select Journal',
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
            if (Object.prototype.toString.call(this.Tranotype) !== '[object Array]')
            {
                Ext.getCmp('combo-type').setValue(this.Tranotype);
                Ext.getCmp('combo-type').setRawValue(this.Tranotype);
                store.proxy = new Ext.data.HttpProxy({
                    url: '/finance/jurnal/get-trano-jurnal/type/' + Ext.getCmp('combo-type').getValue()
                });
                store.reload();

                if (this.Disabletype == true)
                    Ext.getCmp('combo-type').disable();
            }
        }
    },
    onRender: function(ct, position) {

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
        Ext.ux.form.TranoJurnalSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoJurnalSelector.superclass.beforeDestroy.call(this);
    }

});

Ext.reg('tranojurnalselector', Ext.ux.form.TranoJurnalSelector);


// Trano Date Changer UX

Ext.ux.form.TranoDateChanger = Ext.extend(Ext.form.Field, {
    showAuthWindow: function(t) {

        var formAuth = new Ext.FormPanel({
            layout: 'form',
            frame: true,
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: 'Username',
                    id: 'username',
                    width: 120
                },
                {
                    xtype: 'textfield',
                    inputType: 'password',
                    fieldLabel: 'Password',
                    id: 'password',
                    width: 120
                },
                {
                    xtype: 'datefield',
                    fieldLabel: 'New Date',
                    id: 'new_date',
                    width: 100,
                    format: 'd M Y'
                }
            ]
        });

        var dFormMsg = new Ext.Window({
            id: 'auth_window',
            layout: 'fit',
            width: 300,
            height: 170,
            title: 'Authentication Required',
            stateful: false,
            modal: true,
            resizable: true,
            items: [
                formAuth
            ],
            buttons: [
                {
                    text: 'OK',
                    handler: function()
                    {
                        var date = Ext.getCmp('new_date').getValue();
                        if (date == '')
                        {
                            Ext.Msg.alert('Error', 'Please select New Date');
                            return false;
                        }

                        var params = {
                            username: Ext.getCmp('username').getValue(),
                            password: Ext.getCmp('password').getValue()
                        };

                        var select = this.Selectid;

                        Ext.Ajax.request({
                            url: '/finance/jurnal/cek-auth-date-changer',
                            method: 'POST',
                            params: params,
                            success: function(result) {
                                obj = Ext.util.JSON.decode(result.responseText);

                                if (obj.success)
                                {
                                    if (obj.auth)
                                    {
                                        var currDate = new Date();

                                        if (date.format('Y-m-d') != currDate.format('Y-m-d'))
                                        {
                                            Ext.Ajax.request({
                                                url: '/finance/banktransaction/checkdatetransaction',
                                                method: 'POST',
                                                params: {
                                                    date: date,
                                                },
                                                success: function(result) {
                                                    obj = Ext.util.JSON.decode(result.responseText);

                                                    if (obj.success)
                                                    {
                                                        Ext.getCmp(select).setValue(date.format('Y-m-d'));
                                                        dFormMsg.close();
                                                    } else
                                                        Ext.Msg.alert('Sorry!', 'Please select another date');
                                                },
                                                failure: function(action) {
                                                    if (action.failureType == 'server') {
                                                        obj = Ext.util.JSON.decode(action.response.responseText);
                                                        Ext.Msg.alert('Error!', obj.errors.reason);
                                                    } else {
                                                        Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                                                    }
                                                }

                                            });
                                        } else {
                                            Ext.getCmp(select).setValue(date.format('Y-m-d'));
                                            dFormMsg.close();
                                        }
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('Error', obj.msg);
                                    }
                                }
                                else
                                {
                                    Ext.Msg.alert('Error', obj.msg);
                                }
                            },
                            failure: function(action) {
                                if (action.failureType == 'server') {
                                    obj = Ext.util.JSON.decode(action.response.responseText);
                                    Ext.Msg.alert('Error!', obj.errors.reason);
                                } else {
                                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                                }
                            }

                        });
                    }, scope: this
                },
                {
                    text: 'Cancel',
                    handler: function()
                    {
                        dFormMsg.close();
                    }
                }
            ]
        });

        dFormMsg.show();

    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 100;

        if (this.Enableeditable == undefined)
            this.Enableeditable = false;

        if (this.selectValue == undefined)
            this.selectValue = '';

        if (!this.el) {
            this.selectDate = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: this.Enableeditable,
                value: this.selectValue
            });

            if (!this.disabled)
                this.selectDate.onTriggerClick = this.showAuthWindow.createDelegate(this);

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
                    this.selectDate
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectDate]);

        }
        Ext.ux.form.TranoDateChanger.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoDateChanger.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('tranodatechanger', Ext.ux.form.TranoDateChanger);

// Trano All Jurnal Selector UX

Ext.ux.form.TranoAllJurnalSelector = Ext.extend(Ext.form.Field, {
    storeJurnal: null,
    setJurnalType: function()
    {
        if (this.jurnalType != undefined)
        {
            if (Object.prototype.toString.call(this.jurnalType) !== '[object Array]')
            {
                this.storeJurnal.proxy = new Ext.data.HttpProxy({
                    url: this.url + '/jurnal_type/' + this.jurnalType
                });
            }
            else
            {
                this.storeJurnal.setBaseParam('jurnal_type', Ext.util.JSON.encode(this.jurnalType));
                jurnalType = Ext.util.JSON.encode(this.jurnalType);
            }
        }
    },
    showTranoWindow: function(t) {
        var jurnalType = '';
        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            root: 'data'},
        [
            {name: 'trano'},
            {name: 'ref_number'},
            {name: 'tgl'},
            {name: 'tglpost'},
            {name: 'tglclose'},
            {name: 'debit'},
            {name: 'credit'}
        ]
                );

        var proxy = new Ext.data.HttpProxy({
            url: this.url
        });

        this.storeJurnal = new Ext.data.Store({
            id: 'store-alltranojurnal',
            reader: reader,
            proxy: proxy
        });

        var store = this.storeJurnal;

        var grids = new Ext.grid.GridPanel({
            frame: true,
            width: 350,
            id: 'grid-all-jurnal-trano',
            store: store,
            loadMask: {msg: 'Loading...'},
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true
            }),
            viewConfig: {
                forceFit: true
            },
            bbar:
                    new Ext.PagingToolbar({
                        pageSize: 30,
                        store: store,
                        displayInfo: true,
                        displayMsg: 'Displaying document {0} - {1} of {2}',
                        emptyMsg: "No document to display"
                    })
            ,
            columns: [
                new Ext.grid.RowNumberer(),
                {
                    id: 'trano',
                    header: "Trano",
                    dataIndex: 'trano',
                    width: 120
                },
                {
                    id: 'ref_number',
                    header: "Ref Number",
                    dataIndex: 'ref_number',
                    width: 100
                },
                {
                    id: 'debit',
                    header: " Total Debit",
                    dataIndex: 'debit',
                    width: 100
                },
                {
                    id: 'credit',
                    header: " Total Credit",
                    dataIndex: 'credit',
                    width: 100
                }
            ]
        });

        grids.on('rowclick', function(grid, index, e) {
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp(this.Selectid).setValue(trano);
            Ext.getCmp('doc-form-panel').close();

            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc(trano);
            }

        }, this);

        this.setJurnalType();

        if (this.addBaseParams != undefined)
        {
            Ext.iterate(this.addBaseParams, function(k, v) {
                store.setBaseParam(k, v);
            });
        }

        store.load();

        var isArrayType = false,
                jsonType = '';
        if (Object.prototype.toString.call(this.Tranotype) === '[object Array]')
        {
            isArrayType = true;
            jsonType = Ext.util.JSON.encode(this.Tranotype);
        }

        var viewportsMsg = ({
            layout: 'border',
            stateful: false,
            loadMask: true,
            bodyCfg: {cls: 'xpanel-body-table', style: {'overflow': 'auto'}},
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
                    height: 100,
                    stateful: false,
                    items: [
                        {
                            layout: 'form',
                            frame: true,
                            border: false,
                            items: [
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Trano',
                                    width: 150,
                                    id: 'search_trano'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Ref Number',
                                    width: 150,
                                    id: 'search_ref_number'
                                }
//                                {columnWidth:.55,
//                                    layout: 'form',
//                                    style : 'margin-right: 3px;',
//                                    items: [
//
//                                    ]
//                                },
//                                {columnWidth:.3,
//                                    layout: 'form',
//                                    style : 'margin-left: 3px;',
//                                    items: [
//
//                                    ]
//                                }
                            ]
                        }
                    ],
                    buttons: [
                        new Ext.Button({
                            text: 'Search',
                            id: 'search-button',
                            handler: function() {
                                var trano = Ext.getCmp('search_trano').getValue();
                                var ref_number = Ext.getCmp('search_ref_number').getValue();
//                                store.setBaseParam('jurnal_type',jurnalType);
                                store.setBaseParam('trano', trano);
                                store.setBaseParam('ref_number', ref_number);
                                store.load();
                            }
                        }),
                        new Ext.Button({
                            text: 'Clear',
                            id: 'clear-button',
                            handler: function() {
                                Ext.getCmp('search_trano').setValue('');
                                Ext.getCmp('search_ref_number').setValue('');
                            }
                        })
                    ]
                }

            ]

        });
        var dFormMsg = new Ext.Window({
            id: 'doc-form-panel',
            layout: 'fit',
            width: 450,
            height: 430,
            title: 'Select Journal',
            stateful: false,
            modal: true,
            resizable: false,
            items: [
                viewportsMsg
            ]
        });

        dFormMsg.show();


    },
    onRender: function(ct, position) {

        if (this.url == '' || this.url == undefined || this.url == null)
            this.url = '/finance/jurnal/get-trano-jurnal';

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
        Ext.ux.form.TranoAllJurnalSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.TranoAllJurnalSelector.superclass.beforeDestroy.call(this);
    }

});

Ext.reg('tranoalljurnalselector', Ext.ux.form.TranoAllJurnalSelector);

Ext.ux.form.BudgetTypeSelector = Ext.extend(Ext.form.Field, {
    onRender: function(ct, position) {
        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 100;

        this.comboBudget = new Ext.form.ComboBox({
            id: this.Selectid,
            width: this.width,
            store: new Ext.data.SimpleStore({
                fields: ['nilai', 'name'],
                data: [
                    ['project', 'Project'],
                    ['overhead', 'Overhead']
                ]
            }),
            valueField: 'nilai',
            displayField: 'name',
            typeAhead: true,
            forceSelection: true,
            editable: false,
            mode: 'local',
            triggerAction: 'all',
            selectOnFocus: true,
            emptyText: 'Select...'
        });

        this.comboBudget.on('select', function(combo, record, index)
        {
            if (this.callback != undefined)
            {
                this.callback(record.get("nilai"));
            }
        }, this);

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
                this.comboBudget
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.comboBudget]);

        Ext.ux.form.BudgetTypeSelector.superclass.onRender.call(this, ct, position);

    },
    getBudgetType: function() {
        return this.comboBudget.getValue();
    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.BudgetTypeSelector.superclass.beforeDestroy.call(this);
    },
    reset: function() {
        Ext.getCmp(this.Selectid).setValue('');
    }


});

Ext.reg('budgettypeselector', Ext.ux.form.BudgetTypeSelector);

// BPV Selector UX

Ext.ux.form.BPVSelector = Ext.extend(Ext.form.Field, {
    store: null,
    bpvType: null,
    showTranoWindow: function(t) {

        var filterBPV = function(searchTxt) {
            var option = Ext.getCmp('option').getValue(),
                    option_type = Ext.getCmp('option_type').getValue(),
                    search = Ext.getCmp('search').getValue();
            var type = 'RPI';

            store.proxy.setUrl('/finance/payment/getvoucher/search/' + search + '/option/' + option + '/type/' + type + '/option_type/' + option_type);
            store.reload();
            grids.getView().refresh();
        };

        var store = new Ext.data.Store({
            url: '/finance/payment/getvoucher/type/' + this.bpvType,
            autoLoad: true,
            reader: new Ext.data.JsonReader({
                root: 'data',
                totalProperty: 'total',
                fields: [{
                        name: 'trano'
                    }, {
                        name: 'tgl'
                    }, {
                        name: 'item_type'
                    }, {
                        name: 'prj_kode'
                    }, {
                        name: 'valuta'
                    }, {
                        name: 'bpv_type'
                    }]
            })
        });

        var grids = new Ext.grid.GridPanel({
            store: store,
            height: 300,
            width: 600,
            viewConfig: {
                forceFit: true
            },
            columns: [{
                    header: 'Trano',
                    dataIndex: 'trano',
                    sortable: true
                }, {
                    header: 'Date',
                    dataIndex: 'tgl',
                    width: 120,
                    sortable: true
                }, {
                    header: 'From',
                    dataIndex: 'item_type',
                    sortable: true
                }, {
                    header: 'Project Code',
                    dataIndex: 'prj_kode',
                    sortable: true
                }, {
                    header: 'BPV Type',
                    dataIndex: 'bpv_type',
                    sortable: true
                }], bbar: new Ext.PagingToolbar({
                pageSize: 20,
                store: store,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            }), tbar: [
                {
                    text: 'Type',
                    xtype: 'label',
                    style: 'margin-left:5px'
                },
                '-',
                {
                    xtype: 'combo',
                    id: 'option_type',
                    width: 100,
                    store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['ALL', 'ALL'],
                            ['AP', 'AP'],
                            ['PPN', 'PPN'],
                            ['WHT', 'With Holding Tax']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    value: 'ALL',
                    listeners: {
                        'select': function() {
                            filterBPV();
                        }
                    }
                }, '-',
                {
                    text: 'Search By',
                    xtype: 'label',
                    style: 'margin-left:5px'
                }, '-',
                {
                    xtype: 'combo',
                    id: 'option',
                    width: 120,
                    store: new Ext.data.SimpleStore({
                        fields: ['nilai', 'name'],
                        data: [
                            ['trano', 'BPV Trano'],
                            ['item_type', 'Type'],
                            ['prj_kode', 'Project Code'],
                            ['val_kode', 'Valuta']
                        ]
                    }),
                    valueField: 'nilai',
                    displayField: 'name',
                    typeAhead: true,
                    forceSelection: true,
                    editable: false,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus: true,
                    value: 'trano',
                    listeners: {
                        'select': function() {
                            filterBPV();
                        }
                    }
                }, '-', {
                    xtype: 'textfield',
                    id: 'search',
                    enableKeyEvents: true,
                    listeners: {
                        'keyup': function(txttext, event)
                        {
                            var txttext = txttext.getValue();
                            if (txttext != "" && txttext.toString().length >= 3)
                            {
                                filterBPV();
                            }
                        }
                    }
                },
                {
                    xtype: 'tbbutton',
                    text: 'Search',
                    cls: "x-btn-text-icon",
                    icon: "/images/icons/fam/magnifier.png",
                    handler: function()
                    {
                        filterBPV();
                    }
                }
            ]
        });

        grids.on('rowclick', function(grid, index, e) {
            var rec = store.getAt(index);
            var trano = rec.data['trano'];
            Ext.getCmp(this.Selectid).setValue(trano);
            var itemType = Ext.getCmp('option_type').getRawValue();
            Ext.getCmp('doc-form-panel').close();
            if (isFunction(this.callbackFunc))
            {
                this.callbackFunc(trano, itemType);
            }

        }, this);

        var dFormMsg = new Ext.Window({
            id: 'doc-form-panel',
            layout: 'fit',
            width: 630,
            height: 330,
            title: 'Select BPV Document',
            stateful: false,
            modal: true,
            resizable: false,
            items: [
                grids
            ]
        });

        dFormMsg.show();
    },
    onRender: function(ct, position) {

        if (this.bpvType == undefined || this.bpvType == '')
            this.bpvType = 'ALL';

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 120;

        if (!this.el) {
            this.selectBPV = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: this.Enableeditable
            });

            if (!this.disabled)
                this.selectBPV.onTriggerClick = this.showTranoWindow.createDelegate(this);

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
                    this.selectBPV
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectBPV]);

        }
        Ext.ux.form.BPVSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.BPVSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('bpvselector', Ext.ux.form.BPVSelector);


// BPV Selector UX

Ext.ux.grid.BOQ3 = Ext.extend(Ext.grid.GridPanel, {
    initAction: function() {
        var rowactions = new Ext.ux.grid.RowActions({
            actions: [
                {
                    iconCls: 'icon-add-new',
                    qtip: 'Add',
                    id: 'add'
                }
            ]
            , index: 'actions'
            , header: ''
        });

        rowactions.on('action', function(grid, record, action, row, col) {
            if (action == 'icon-add-new')
            {
                if (this.addCallback != undefined)
                {
                    this.addCallback(record);
                }
            }
        }, this);

        return rowactions;
    },
    getJSONFromStore: function(callback)
    {
        var json = '';

        this.store.each(function(store) {
            var encode = Ext.util.JSON.encode(store.data);
            if (encode != undefined)
                json += encode + ',';
        }, this);
        json = '[' + json.substring(0, json.length - 1) + ']';

        if (callback != undefined)
        {
            callback(json);
        }
        else
        {
            return json;
        }
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
    loadData: function(jsonCoa)
    {
        if (!jsonCoa)
            return false;

        this.store.removeAll();
        this.store.loadData(jsonCoa);
        this.getView().refresh();
    },
    recordBOQ3: new Ext.data.Record.create([
        {name: 'prj_kode'},
        {name: 'prj_nama'},
        {name: 'sit_kode'},
        {name: 'sit_nama'},
        {name: 'workid'},
        {name: 'workname'},
        {name: 'kode_brg'},
        {name: 'nama_brg'},
        {name: 'qty'},
        {name: 'harga'},
        {name: 'total'},
        {name: 'val_kode'},
        {name: 'invalid'}

    ]),
    initComponent: function() {

        if (this.hideProjectColumn == undefined)
            this.hideProjectColumn = true;
        if (this.hideSiteColumn == undefined)
            this.hideSiteColumn = true;

        if (this.urlStore == undefined)
            this.urlStore = '/projectmanagement/budget/get-boq3';

        if (this.rootStore == undefined)
            this.rootStore = 'data';

        if (this.totalStore == undefined)
            this.totalStore = 'total';

        var proxy = new Ext.data.HttpProxy({
            url: this.urlStore
        });

        this.store = new Ext.data.Store({
            proxy: proxy,
            reader: new Ext.data.JsonReader({
                root: this.rootStore,
                totalProperty: this.totalStore,
                fields: this.recordBOQ3
            })
        });

        this.rowactions = this.initAction();
        this.plugins = [this.rowactions];

        this.columns = [
            new Ext.grid.RowNumberer({
                width: 30
            }),
            this.rowactions,
            {
                header: 'Project',
                dataIndex: 'prj_kode',
                sortable: true,
                hidden: this.hideProjectColumn,
                width: 150,
            }, {
                header: 'Site',
                dataIndex: 'site_kode',
                sortable: true,
                hidden: this.hideSiteColumn,
                width: 150,
            }, {
                header: 'Workid',
                dataIndex: 'workid',
                sortable: true,
                renderer: function(v, p, r) {
                    return v + "<br>" + r.data['workname'];
                },
                width: 150,
            }, {
                header: 'Product',
                dataIndex: 'kode_brg',
                sortable: true,
                renderer: function(v, p, r) {
                    return v + "<br>" + r.data['nama_brg'];
                },
                width: 250,
            }, {
                header: 'Qty',
                dataIndex: 'qty',
                sortable: true,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.0000') : '';
                },
                align: 'right',
                width: 150,
            }, {
                header: 'Price',
                dataIndex: 'harga',
                sortable: true,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right',
                width: 150,
            }, {
                header: 'Total',
                dataIndex: 'total',
                sortable: true,
                renderer: function(v) {
                    return v ? Ext.util.Format.number(v, '0,0.00') : '';
                },
                align: 'right',
                width: 150,
            }
        ];

        var printButtons = new Ext.Button({
            iconCls: 'silk-printer',
            text: 'Print'
        });

        printButtons.on('click', function(btn, e) {
            this.printJurnal();
        }, this);

        this.tbar = [
            printButtons
        ];

        this.bbar = [
            new Ext.PagingToolbar({
                pageSize: 100,
                store: this.store,
                displayInfo: true,
                displayMsg: 'Displaying data {0} - {1} of {2}',
                emptyMsg: "No data to display"
            })
        ];


        if (this.arrayBOQ3 != undefined)
        {
            this.store.loadData(this.arrayBOQ3);
        }

        Ext.ux.grid.BOQ3.superclass.initComponent.call(this);
    }

});
Ext.reg('boq3selector', Ext.ux.grid.BOQ3);


Ext.ux.form.WorkidSelector = Ext.extend(Ext.form.Field, {
    clearData: function()
    {
        this.Workid = '';
        this.WorkidName = '';
    },
    getWorkid: function()
    {
        return this.Workid;
    },
    getWorkidName: function()
    {
        return this.WorkidName;
    },
    setWorkid: function(Workid)
    {
        this.Workid = Workid;
    },
    setWorkidName: function(name)
    {
        this.WorkidName = name;
        if (this.WorkidName != undefined)
            Ext.getCmp(this.Selectid).setValue(this.WorkidName);
    },
    showWorkidWindow: function(t) {

        var urlProxy = '/work/list';

        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'id',
            root: 'posts'
        }, [
            {name: 'id'},
            {name: 'workid'},
            {name: 'workname'}
        ]);

        var workidStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'workidselector-store'
        });
        workidStore.load();

        var workidText = new Ext.form.TextField({
            fieldLabel: 'Work ID',
            enableKeyEvents: true
        });

        var workidNamaText = new Ext.form.TextField({
            fieldLabel: 'Work Name',
            enableKeyEvents: true
        });

        workidNamaText.on('keyup', function(field, e) {
            var pname = field.getValue();

            workidStore.proxy = new Ext.data.HttpProxy({
                url: '/work/listbyparams/name/workname/data/' + pname
            });
            workidStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        workidText.on('keyup', function(field, e) {
            var pname = field.getValue();

            workidStore.proxy = new Ext.data.HttpProxy({
                url: '/work/listbyparams/name/workid/data/' + pname
            });
            workidStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridWorkid = new Ext.grid.GridPanel({
            store: workidStore,
            loadMask: true,
            height: 200,
            viewConfig:
                    {
                        forceFit: true
                    },
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: workidStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Workid', width: 80, dataIndex: 'workid', sortable: true},
                {header: 'Work Name', width: 90, dataIndex: 'workname', sortable: true}
            ]
        });

        gridWorkid.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('workid');
            this.workid = code;
            this.workidName = g.getStore().getAt(rowIndex).get('workname');
            Ext.getCmp(this.Selectid).setValue(this.workid);

            if (this.callbackFunc != undefined)
            {
                this.callbackFunc();
            }

            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        workidText,
                        workidNamaText,
                        gridWorkid
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 300,
            title: 'Select Workid',
            items: forms
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 120;

        if (!this.el) {
            this.selectWorkid = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectWorkid.onTriggerClick = this.showWorkidWindow.createDelegate(this);

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
                    this.selectWorkid
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectWorkid]);
        }
        Ext.ux.form.WorkidSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.WorkidSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('workidselector', Ext.ux.form.WorkidSelector);

Ext.ux.form.RoleSelector = Ext.extend(Ext.form.Field, {
    clearData: function()
    {
        this.roleId = '';
        this.roleName = '';
    },
    getRoleId: function()
    {
        return this.roleId;
    },
    getRoleName: function()
    {
        return this.roleName;
    },
    setRoleId: function(roleId)
    {
        this.roleId = roleId;
    },
    setRoleName: function(name)
    {
        this.roleName = name;
    },
    showRoleWindow: function(t) {

        var urlProxy = '/admin/userrole/get-role';

        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'total',
            root: 'data'
        }, [
            {name: 'id'},
            {name: 'display_name'},
            {name: 'role_name'}
        ]);

        var roleStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'roleselector-store'
        });
        roleStore.load();

        var roleNamaText = new Ext.form.TextField({
            fieldLabel: 'Role Name',
            enableKeyEvents: true
        });

        roleNamaText.on('keyup', function(field, e) {
            var pname = field.getValue();

            roleStore.proxy = new Ext.data.HttpProxy({
                url: urlProxy,
                baseParams: {
                    display_name: pname
                }
            });
            roleStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridRole = new Ext.grid.GridPanel({
            store: roleStore,
            loadMask: true,
            height: 200,
            viewConfig:
                    {
                        forceFit: true
                    },
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 20,
                    store: roleStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [
                new Ext.grid.RowNumberer({width: 30}),
                {header: 'Role Name', width: 80, dataIndex: 'display_name', sortable: true}
            ]
        });

        gridRole.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('id');
            this.roleId = code;
            this.roleName = g.getStore().getAt(rowIndex).get('display_name');
            Ext.getCmp(this.Selectid).setValue(this.roleName);

            if (this.callbackFunc != undefined)
            {
                this.callbackFunc();
            }

            if (pwindow)
                pwindow.close();
        }, this);

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        roleNamaText,
                        gridRole
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 500,
            height: 300,
            title: 'Select Role',
            items: forms
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.width == undefined)
            this.width = 120;

        if (!this.el) {
            this.selectRole = new Ext.form.TriggerField({
                id: this.Selectid,
                width: this.width,
                triggerClass: 'teropong',
                editable: false
            });

            if (!this.disabled)
                this.selectRole.onTriggerClick = this.showRoleWindow.createDelegate(this);

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
                    this.selectRole
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectRole]);
        }
        Ext.ux.form.RoleSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.RoleSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('roleselector', Ext.ux.form.RoleSelector);


//customer selector
Ext.ux.form.CustomerSelector = Ext.extend(Ext.form.Field, {
    showCustomerWindow: function(t) {

        var urlProxy = '/customer/list';
        var urlProxySearch = '/customer/listByParams';

        if (this.urlCustomer != undefined)
        {
            urlProxy = this.urlCustomer;
        }

        if (this.urlSearchCustomer != undefined)
        {
            urlProxySearch = this.urlSearchCustomer;
        }


        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'trano',
            root: 'posts'
        }, [
            {name: 'cus_kode', mapping: 'cus_kode'},
            {name: 'cus_nama', mapping: 'cus_nama'},
            {name: 'ket', mapping: 'ket'},
            {name: 'id', mapping: 'id'}
        ]);

        var customerStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'customerselector-store'
        });
        customerStore.load();

        var customerKodeText = new Ext.form.TextField({
            fieldLabel: 'Customer Code',
            enableKeyEvents: true
        });

        customerKodeText.on('keyup', function(field, e) {
            var cname = field.getValue();
            if (this.urlSearchCustomer == undefined)
            {
                newUrl = urlProxySearch + '/name/cus_kode/data/' + cname;
            }
            else
            {
                newUrl = urlProxySearch + '/cus_kode/' + cname;
            }
            customerStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            customerStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var customerNamaText = new Ext.form.TextField({
            fieldLabel: 'Customer Name',
            enableKeyEvents: true
        });

        customerNamaText.on('keyup', function(field, e) {
            var cname = field.getValue();
            if (this.urlSearchCustomer == undefined)
            {
                newUrl = urlProxySearch + '/name/cus_nama/data/' + cname;
            }
            else
            {
                newUrl = urlProxySearch + '/cus_nama/' + cname;
            }
            customerStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            customerStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridCustomer = new Ext.grid.GridPanel({
            store: customerStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 100,
                    store: customerStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [new Ext.grid.RowNumberer({width: 30}),
                {header: 'Customer Code', width: 100, sortable: true, dataIndex: 'cus_kode'},
                {header: 'Customer Name', width: 150, sortable: true, dataIndex: 'cus_nama'}
            ]
        });

        gridCustomer.on('rowdblclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('cus_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('cus_nama');
            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    cus_kode: code,
                    cus_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this);
        gridCustomer.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('cus_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('cus_nama');
            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    cus_kode: code,
                    cus_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this)

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        customerKodeText,
                        customerNamaText,
                        gridCustomer
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 400,
            title: 'Select Customer',
            items: forms
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;
//        if (this.selectValue == '' || this.selectValue == undefined)
//                this.selectValue = 'IDR';

        if (!this.el) {
            this.selectCustomer = new Ext.form.TriggerField({
                id: this.Selectid,
                width: 80,
                triggerClass: 'teropong',
                editable: false,
                value: this.selectValue
            });

            if (!this.disabled)
                this.selectCustomer.onTriggerClick = this.showCustomerWindow.createDelegate(this);

            this.customerName = new Ext.form.TextField({
                id: this.Nameid,
                width: 150,
                readOnly: true,
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
            this.items.addAll([this.selectCustomer, this.customerName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }
        }
        Ext.ux.form.CustomerSelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.CustomerSelector.superclass.beforeDestroy.call(this);
    },
    reset: function() {
        Ext.getCmp(this.Selectid).setValue('');
        if (this.ShowName === true)
            Ext.getCmp(this.Nameid).setValue('');
    }


});

Ext.reg('customerselector', Ext.ux.form.CustomerSelector);

//currency selector
Ext.ux.form.CurrencySelector = Ext.extend(Ext.form.Field, {
    setRate: function(currency, callback, dontUpdateRate)
    {

        if (currency != 'IDR') {

            Ext.Ajax.request({
                url: '/default/valuta/getexchangerate/val_kode/' + currency,
                method: 'POST',
                success: function(resp) {
                    var returnData = Ext.util.JSON.decode(resp.responseText);

                    if (Ext.getCmp('val_rate_text') != undefined)
                    {
                        if (dontUpdateRate == undefined || !dontUpdateRate)
                            Ext.getCmp('val_rate_text').setValue(returnData['rate']);

                        if (callback != undefined)
                        {
                            callback();
                        }

                    }

                    if (returnData['rate'] == 0)
                    {
                        Ext.MessageBox.show({
                            title: 'Error',
                            msg: 'Please Call Accounting Staffs (Ext. 1101 - 1104). Ask Them to Input Current IDR Rate. Thank You.',
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR,
                            fn: function() {
                                window.location = '';
                            }
                        });
                    }
                }
                , failure: function(action) {
                    if (action.failureType == 'server') {
                        obj = Ext.util.JSON.decode(action.response.responseText);
                        Ext.Msg.alert('Error!', obj.errors.reason);
                    }
                }

            });

        }
        else
        {
            if (Ext.getCmp('val_rate_text') != undefined)
            {
                Ext.getCmp('val_rate_text').setValue(0);
                if (callback != undefined)
                {
                    callback();
                }
            }

        }

    },
    showCurrencyWindow: function(t) {

        var urlProxy = '/valuta/list';
        var urlProxySearch = '/valuta/listByParams';

        if (this.urlCurrency != undefined)
        {
            urlProxy = this.urlCurrency;
        }

        if (this.urlSearchCurrency != undefined)
        {
            urlProxySearch = this.urlSearchCurrency;
        }


        var proxy = new Ext.data.HttpProxy({
            url: urlProxy
        });

        var reader = new Ext.data.JsonReader({
            totalProperty: 'count',
            idProperty: 'trano',
            root: 'posts'
        }, [
            {name: 'val_kode', mapping: 'val_kode'},
            {name: 'val_nama', mapping: 'val_nama'},
            {name: 'ket', mapping: 'ket'},
            {name: 'id', mapping: 'id'}
        ]);

        var currencyStore = new Ext.data.Store({
            proxy: proxy,
            reader: reader,
            id: 'currencyselector-store'
        });
        currencyStore.load();

        var currencyKodeText = new Ext.form.TextField({
            fieldLabel: 'Currency Code',
            enableKeyEvents: true
        });

        currencyKodeText.on('keyup', function(field, e) {
            var vname = field.getValue();
            if (this.urlSearchCurrency == undefined)
            {
                newUrl = urlProxySearch + '/name/val_kode/data/' + vname;
            }
            else
            {
                newUrl = urlProxySearch + '/val_kode/' + vname;
            }
            currencyStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            currencyStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var currencyNamaText = new Ext.form.TextField({
            fieldLabel: 'Currency Name',
            enableKeyEvents: true
        });

        currencyNamaText.on('keyup', function(field, e) {
            var vname = field.getValue();
            if (this.urlSearchCurrency == undefined)
            {
                newUrl = urlProxySearch + '/name/val_nama/data/' + vname;
            }
            else
            {
                newUrl = urlProxySearch + '/val_nama/' + vname;
            }
            currencyStore.proxy = new Ext.data.HttpProxy({
                url: newUrl
            });
            currencyStore.reload();
            Ext.getCmp(this.id + '-grid').getView().refresh();
        }, this);

        var gridCurrency = new Ext.grid.GridPanel({
            store: currencyStore,
            loadMask: true,
            height: 300,
            id: this.id + '-grid',
            bbar: [new Ext.PagingToolbar({
                    pageSize: 100,
                    store: currencyStore,
                    displayInfo: true,
                    displayMsg: 'Displaying data {0} - {1} of {2}',
                    emptyMsg: "No data to display"
                })],
            columns: [new Ext.grid.RowNumberer({width: 30}),
                {header: 'Valuta Code', width: 100, sortable: true, dataIndex: 'val_kode'},
                {header: 'Valuta Name', width: 150, sortable: true, dataIndex: 'val_nama'}
            ]
        });

        gridCurrency.on('rowdblclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('val_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('val_nama');

            if (code != 'IDR') {

                Ext.Ajax.request({
                    url: '/default/valuta/getexchangerate/val_kode/' + code,
                    method: 'POST',
                    success: function(resp) {
                        var returnData = Ext.util.JSON.decode(resp.responseText);

                        if (Ext.getCmp('val_rate_text') != undefined)
                        {
                            Ext.getCmp('val_rate_text').setValue(returnData['rate']);
                        }

                        if (returnData['rate'] == 0)
                        {
                            Ext.MessageBox.show({
                                title: 'Error',
                                msg: 'Please Call Accounting Staffs (Ext. 1101 - 1104). Ask Them to Input Current IDR Rate. Thank You.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR,
                                fn: function() {
                                    window.location = '';
                                }
                            });
                        }
                    }
                    , failure: function(action) {
                        if (action.failureType == 'server') {
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            Ext.Msg.alert('Error!', obj.errors.reason);
                        }
                    }
                });

            }
            else
            {
                if (Ext.getCmp('val_rate_text') != undefined)
                {
                    Ext.getCmp('val_rate_text').setValue(0);

                }

            }

            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    val_kode: code,
                    val_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this);
        gridCurrency.on('rowclick', function(g, rowIndex, e) {
            var code = g.getStore().getAt(rowIndex).get('val_kode');
            Ext.getCmp(this.Selectid).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('val_nama');

            if (code != 'IDR') {

                Ext.Ajax.request({
                    url: '/default/valuta/getexchangerate/val_kode/' + code,
                    method: 'POST',
                    success: function(resp) {
                        var returnData = Ext.util.JSON.decode(resp.responseText);

                        if (Ext.getCmp('val_rate_text') != undefined)
                        {
                            Ext.getCmp('val_rate_text').setValue(returnData['rate']);
                        }

                        if (returnData['rate'] == 0)
                        {
                            Ext.MessageBox.show({
                                title: 'Error',
                                msg: 'Please Call Accounting Staffs (Ext. 1101 - 1104). Ask Them to Input Current IDR Rate. Thank You.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR,
                                fn: function() {
                                    window.location = '';
                                }
                            });
                        }
                    }
                    , failure: function(action) {
                        if (action.failureType == 'server') {
                            obj = Ext.util.JSON.decode(action.response.responseText);
                            Ext.Msg.alert('Error!', obj.errors.reason);
                        }
                    }
                });

            }
            else
            {
                if (Ext.getCmp('val_rate_text') != undefined)
                {
                    Ext.getCmp('val_rate_text').setValue(0);

                }

            }

            if (this.ShowName === true)
                Ext.getCmp(this.Nameid).setValue(name);

            if (this.callback != undefined)
            {
                this.callback({
                    val_kode: code,
                    val_nama: name
                });
            }

            if (pwindow)
                pwindow.close();
        }, this)

        var forms =
                {
                    xtype: 'form',
                    labelWidth: 120,
                    frame: true,
                    items: [
                        currencyKodeText,
                        currencyNamaText,
                        gridCurrency
                    ]
                };

        pwindow = new Ext.Window({
            modal: true,
            resizable: false,
            closeAction: 'close',
            width: 400,
            height: 400,
            title: 'Select Currency',
            items: forms
        });

        pwindow.show();
    },
    onRender: function(ct, position) {

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;

        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;
        if (this.selectValue == '' || this.selectValue == undefined)
            this.selectValue = 'IDR';
        if (this.size == '' || this.size == undefined)
            this.size = 150;

        if (!this.el) {
            this.selectCurrency = new Ext.form.TriggerField({
                id: this.Selectid,
                width: 80,
                triggerClass: 'teropong',
                editable: false,
                value: this.selectValue
            });

            if (!this.disabled)
                this.selectCurrency.onTriggerClick = this.showCurrencyWindow.createDelegate(this);

            this.currencyName = new Ext.form.TextField({
                id: this.Nameid,
                width: this.size,
                readOnly: true,
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
                    this.selectCurrency,
                    this.currencyName
                ]
            });

            this.fieldCt.ownerCt = this;
            this.el = this.fieldCt.getEl();
            this.items = new Ext.util.MixedCollection();
            this.items.addAll([this.selectCurrency, this.currencyName]);
            if (!this.ShowName)
            {
                this.items.items[1].setVisible(false);
            }


        }
        Ext.ux.form.CurrencySelector.superclass.onRender.call(this, ct, position);

    },
    // private
    preFocus: Ext.emptyFn,
    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.CurrencySelector.superclass.beforeDestroy.call(this);
    },
    reset: function() {
        Ext.getCmp(this.Selectid).setValue('');
        if (this.ShowName === true)
            Ext.getCmp(this.Nameid).setValue('');
    }
});

Ext.reg('currencyselector', Ext.ux.form.CurrencySelector);