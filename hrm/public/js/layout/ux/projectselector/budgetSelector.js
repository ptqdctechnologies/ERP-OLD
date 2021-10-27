Ext.ux.form.BudgetSelector = Ext.extend(Ext.form.Field,  {

    // private
    onRender : function(ct, position){

        var select_id = this.Selectid;
        var name_id = this.Nameid;
        var showName = this.ShowName;
        
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;

        if (this.showName == '' || this.showName == undefined)
            this.showName = true;

        setBudgetFieldValue = function(g,rowIndex){
            var code = g.getStore().getAt(rowIndex).get('prj_kode');
            Ext.getCmp(select_id).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('prj_nama');
            Ext.getCmp(name_id).setValue(name);

        };

        showBudgetWindow= function(t) {

            var proxy = new Ext.data.HttpProxy({
                url: '/default/project/list/type/overhead'
            });

            var reader = new Ext.data.JsonReader({
                totalProperty: 'count',
                idProperty: 'trano',
                root: 'posts'
            }, [
                {name: 'prj_kode', mapping: 'Prj_Kode'},
                {name: 'prj_nama', mapping: 'Prj_Nama'}
            ]);

            var budgetStore = new Ext.data.Store({
                proxy: proxy,
                reader: reader,
                id: 'budgetselector-store'
            });
            budgetStore.load();

            var forms =
            {
                xtype: 'form',
                labelWidth: 120,
                frame: true,
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Budget Code',
                        enableKeyEvents:true,
                        listeners: {
                            keyup: function(field,e){
                                if (field.getValue().toString().length >= 3)
                                {
                                    searchBudgetByCode(field.getValue());
                                }
                            }
                        }
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Budget Name',
                        enableKeyEvents:true,
                        listeners: {
                            keyup: function(field,e){
                                if (field.getValue().toString().length >= 3)
                                {
                                    searchBudgetByName(field.getValue());
                                }
                            }
                        }
                    },
                    new Ext.grid.GridPanel({
                        store: budgetStore,
                        loadMask: true,
                        height: 300,
                        id: this.id + '-grid',
                        bbar:[ new Ext.PagingToolbar({
                            pageSize: 100,
                            store: budgetStore,
                            displayInfo: true,
                            displayMsg: 'Displaying data {0} - {1} of {2}',
                            emptyMsg: "No data to display"
                        })],
                        columns: [
                            new Ext.grid.RowNumberer(),
                            {header: 'Budget Code', width: 120, dataIndex: 'prj_kode', sortable: true},
                            {header: 'Budget Name', width: 200, dataIndex: 'prj_nama', sortable: true}
                        ],
                        listeners: {
                            'rowdblclick': function(g, rowIndex, e){

                                setBudgetFieldValue(g,rowIndex);

                                if (pwindow)
                                    pwindow.close();
                            },
                            'rowclick': function(g, rowIndex, e){

                                setBudgetFieldValue(g,rowIndex);

                                if (pwindow)
                                    pwindow.close();
                            }
                        }
                    })
                ]
            };
            searchBudgetByCode= function(pname) {
                newUrl = '/project/listByParams/type/overhead/name/Prj_Kode/data/' + pname;
                budgetStore.proxy = new Ext.data.HttpProxy( {
                    url: newUrl
                     });
                budgetStore.reload();
                Ext.getCmp(this.id + '-grid').getView().refresh();
            };

            searchBudgetByName= function(pname) {
                newUrl = '/project/listByParams/type/overhead/name/Prj_Nama/data/' + pname;
                budgetStore.proxy = new Ext.data.HttpProxy( {
                    url: newUrl
                     });
                budgetStore.reload();
                Ext.getCmp(this.id + '-grid').getView().refresh();
            };

            pwindow = new Ext.Window({
                modal: true,
                resizable: false,
                closeAction: 'close',
                width: 400,
                height: 400,
                title: 'Select Budget',
                items: forms
            });

            pwindow.show();
        };

        if (!this.el) {
        this.selectBudget = new Ext.form.TriggerField({
        id: this.Selectid,
        width: 80,
        triggerClass: 'teropong',
        editable: false,
        onTriggerClick: function(){
            if (!this.disabled)
                showBudgetWindow(this);
        }

        });
        this.budgetName = new Ext.form.TextField({
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
            cls: 'ext-budget-selector',
            layout: 'table',
            layoutConfig: {
                columns: 2
            },
            defaults: {
                hideParent: true
            },
            items: [
            this.selectBudget,
            this.budgetName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectBudget,this.budgetName]);
        if (!this.ShowName)
        {
            this.items.items[1].setVisible(false);
        }
        }
        Ext.ux.form.BudgetSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.BudgetSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('budgetselector', Ext.ux.form.BudgetSelector);

Ext.ux.form.PeriodSelector = Ext.extend(Ext.form.Field,  {

    // private
    onRender : function(ct, position){

        var select_id = this.PeriodSelectid;
        var name_id = this.PeriodNameid;
        var independent = this.independent; //true bila ingin menampilkan semua list site, false untuk bergantung pada isian projectselector

        var showName = this.ShowName;
        
        if (this.disabled == '' || this.disabled == undefined)
            this.disabled = false;
        if (this.showName == '' || this.showName == undefined)
            this.showName = true;
        if (!independent)
            var project_select_id = this.BudgetSelectid;

        setPeriodFieldValue = function(g,rowIndex){
            var code = g.getStore().getAt(rowIndex).get('sit_kode');
            Ext.getCmp(select_id).setValue(code);
            var name = g.getStore().getAt(rowIndex).get('sit_nama');
            Ext.getCmp(name_id).setValue(name);
        };

        showPeriodWindow= function(t) {

            var url = '/default/site/list';
            if (!independent)
            {
                var prjKode = Ext.getCmp(project_select_id).getValue();
                url = url + '/byPrj_Kode/' + prjKode;
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

            var periodStore = new Ext.data.Store({
                proxy: proxy,
                reader: reader,
                id: 'periodselector-store'
            });
            periodStore.load();

            var forms =
            {
                xtype: 'form',
                labelWidth: 120,
                frame: true,
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Period Code',
                        enableKeyEvents:true,
                        listeners: {
                            keyup: function(field,e){
                                if (field.getValue().toString().length >= 3)
                                {
                                    searchPeriodByCode(field.getValue());
                                }
                            }
                        }
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Period Name',
                        enableKeyEvents:true,
                        listeners: {
                            keyup: function(field,e){
                                if (field.getValue().toString().length >= 3)
                                {
                                    searchPeriodByName(field.getValue());
                                }
                            }
                        }
                    },
                    new Ext.grid.GridPanel({
                        store: periodStore,
                        loadMask: true,
                        height: 300,
                        id: this.id + '-period-grid',
                        bbar:[ new Ext.PagingToolbar({
                            pageSize: 100,
                            store: periodStore,
                            displayInfo: true,
                            displayMsg: 'Displaying data {0} - {1} of {2}',
                            emptyMsg: "No data to display"
                        })],
                        columns: [
                            new Ext.grid.RowNumberer(),
                            {header: 'Period Code', width: 80, dataIndex: 'sit_kode', sortable: true},
                            {header: 'Period Name', width: 200, dataIndex: 'sit_nama', sortable: true},
                            {header: 'Budget Code', width: 80, dataIndex: 'prj_kode', sortable: true}
                        ],
                        listeners: {
                            'rowdblclick': function(g, rowIndex, e){

                                setPeriodFieldValue(g,rowIndex);
                                if (pwindow)
                                    pwindow.close();
                            }
                        }
                    })
                ]
            };
            searchPeriodByCode= function(pname) {
                newUrl = '/site/listByParams/name/sit_kode/data/' + pname;

                if (!independent)
                {
                    var prjKode = Ext.getCmp(project_select_id).getValue();
                    newUrl = newUrl + '/byPrj_Kode/' + prjKode;
                }
                periodStore.proxy = new Ext.data.HttpProxy( {
                    url: newUrl
                     });
                periodStore.reload();
                Ext.getCmp(this.id + '-period-grid').getView().refresh();
            };

            searchPeriodByName= function(pname) {
                newUrl = '/site/listByParams/name/sit_nama/data/' + pname;
                if (!independent)
                {
                    var prjKode = Ext.getCmp(project_select_id).getValue();
                    newUrl = newUrl + '/byPrj_Kode/' + prjKode;
                }
                periodStore.proxy = new Ext.data.HttpProxy( {
                    url: newUrl
                     });
                periodStore.reload();
                Ext.getCmp(this.id + '-period-grid').getView().refresh();
            };

            pwindow = new Ext.Window({
                modal: true,
                resizable: false,
                closeAction: 'close',
                width: 400,
                height: 400,
                title: 'Select Period',
                items: forms
            });

            pwindow.show();
        };

        if (!this.el) {
        this.selectPeriod = new Ext.form.TriggerField({
        id: this.PeriodSelectid,
        width: 80,
        triggerClass: 'teropong',
        editable: false,
        onTriggerClick: function(){
            if (!this.disabled)
            {
                if (!independent)
                {
                    var prjKode = Ext.getCmp(project_select_id).getValue();
                    if (prjKode == "" || prjKode == undefined)
                    {
                        Ext.Msg.alert('Error', 'Please select project!');
                        return false;
                    }
                }
                showPeriodWindow(this);
            }
        }

        });

        this.periodName = new Ext.form.TextField({
            id: this.PeriodNameid,
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
            this.selectPeriod,
            this.periodName
            ]
        });

        this.fieldCt.ownerCt = this;
        this.el = this.fieldCt.getEl();
        this.items = new Ext.util.MixedCollection();
        this.items.addAll([this.selectPeriod,this.periodName]);

        if (!this.ShowName)
        {
            this.items.items[1].setVisible(false);
        }
        }
        Ext.ux.form.PeriodSelector.superclass.onRender.call(this, ct, position);

    },

    // private
    preFocus : Ext.emptyFn,

    beforeDestroy: function() {
        Ext.destroy(this.fieldCt);
        Ext.ux.form.PeriodSelector.superclass.beforeDestroy.call(this);
    }


});

Ext.reg('periodselector', Ext.ux.form.PeriodSelector);