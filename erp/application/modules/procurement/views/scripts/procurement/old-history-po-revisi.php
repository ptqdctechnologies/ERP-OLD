<?php if ($this->noData) { ?>No Data to display....<? die;} ?>

<!-- base library -->
<link rel="stylesheet" type="text/css" href="/css/ext-all.css" />
<link rel="stylesheet" type="text/css" href="/images/icons/silk.css" />
<link rel="stylesheet" type="text/css" href="/js/layout/ux/css/GridSummary.css" />

<!-- ** Javascript ** -->

<script type="text/javascript" src="/js/layout/base/config.js"></script>

<!-- ExtJS library: all widgets -->
<script type="text/javascript" src="/js/ext-base.js"></script>
<script type="text/javascript" src="/js/ext-all.js"></script>
<script type="text/javascript" src="/js/layout/base/patchforextjs3-firefoxbugs.js"></script>
<script type="text/javascript" src="/js/layout/ux/gridsummary.js"></script>

<style>
    #first {
        width: 360px;
        border: 1px solid #000;
        padding: 5px;
        margin-right: 10px;
        margin-left: 10px;
        float: left;
    }

    #second {
        width: 360px;
        border: 1px solid #000;
        padding: 5px;
        float: left;
    }
    body {
        font-size: 12px;
    }
    .header {
        /*background-color: #01C9F9;*/
        border: 1px #000000 solid;
        font-weight: bold;
        font-size: 14px;
        text-align: left;
        /*color: #404040;*/
    }
</style>

<script type="text/javascript">
    Ext.onReady(function(){
        Ext.QuickTips.init();

        function loadFirst(n)
        {
            var firstData = data[n];
            storeFirst.loadData({posts: firstData['detail']});
            gridFirst.getView().refresh();

            var html = '';
            if (n > 0)
            {
                html = 'Editor : ' + firstData['uid'] + ' (' + firstData['action'] + ')';
            }
            document.getElementById('header1').innerHTML = html;
        }

        function loadSecond(n)
        {

            var secondData = data[n];
            storeSecond.loadData({posts: secondData['detail']});
            gridSecond.getView().refresh();

            var html = '';
            if (n > 0)
            {
                html = 'Editor : ' + secondData['uid'] + ' (' + secondData['action'] + ')';
            }
            document.getElementById('header2').innerHTML = html;
        }

        var readerCombo = new Ext.data.JsonReader(
            {
                root : 'posts'
            },
            [
                {name: 'id'},
                {name: 'name'}
            ]
        );

        var storeCombo = new Ext.data.Store({
            id: 'store-combo',
            reader: readerCombo
        });
        storeCombo.loadData(<?=$this->jsonCombo?>);

        var data = <?=$this->json?>;

        var readerData = new Ext.data.JsonReader(
                {
                    root : 'posts'
                },
                [
                    {name: 'id'},
                    {name: 'prj_kode'},
                    {name: 'sit_kode'},
                    {name: 'kode_brg'},
                    {name: 'nama_brg'},
                    {name: 'workid'},
                    {name: 'qtyspl'},
                    {name: 'hargaspl'},
                    {name: 'totalspl'}
                ]
        );

        var storeFirst = new Ext.data.Store({
            id: 'store-first',
            reader: readerData,
            sortInfo: { field: 'kode_brg', direction: 'ASC'}
        });

        var storeSecond = new Ext.data.Store({
            id: 'store-second',
            reader: readerData,
            sortInfo: { field: 'kode_brg', direction: 'ASC'}
        });

        var summary = new Ext.ux.grid.GridSummary();
        var summary2 = new Ext.ux.grid.GridSummary();

        var columnsFirst = [
            new Ext.grid.RowNumberer(),
            {header: "Project-Site",width: 100, dataIndex: 'prj_kode', renderer: function(v,p,r){
                return v + ' - ' + r.get("sit_kode");
            }},
            {header: "Product Code",width: 100, dataIndex: 'kode_brg'},
            {header: "Name",width: 100, dataIndex: 'nama_brg'},
            {header: "Qty",width: 80, align: 'right',dataIndex: 'qtyspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            },summaryType: 'sum'},
            {header: "Price",width: 120, align: 'right',dataIndex: 'hargaspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            }},
            {header: "Total",width: 120, align: 'right',dataIndex: 'totalspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            },summaryType: 'sum'},
        ];

        var columnsSecond = [
            new Ext.grid.RowNumberer(),
            {header: "Project-Site",width: 100, dataIndex: 'prj_kode', renderer: function(v,p,r){
                return v + ' - ' + r.get("sit_kode");
            }},
            {header: "Product Code",width: 100, dataIndex: 'kode_brg'},
            {header: "Name",width: 100, dataIndex: 'nama_brg'},
            {header: "Qty",width: 80, align: 'right',dataIndex: 'qtyspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            },summaryType: 'sum'},
            {header: "Price",width: 120, align: 'right',dataIndex: 'hargaspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            }},
            {header: "Total",width: 120, align: 'right',dataIndex: 'totalspl', renderer: function(v, p, r){
                return Ext.util.Format.number(v, '?0,000.00?');
            },summaryType: 'sum'},
        ];

        var gridFirst = new Ext.grid.GridPanel ({
            id:'grid-first',
            height: 400,
            store: storeFirst,
            trackMouseOver: true,
            autoWidth: true,
            view : new Ext.grid.GridView({
//                forceFit: true
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect:true,
                listeners: {
                    'rowselect': function(t, rowIndex, rec){
                        var kode_brg = rec.get("kode_brg"),
                            secondRow = gridSecond.getStore().findExact('kode_brg',kode_brg);

                        gridSecond.getSelectionModel().clearSelections();
                        if (secondRow >= 0)
                        {
                            gridSecond.getSelectionModel().selectRow(secondRow,false);
                        }

                    }
                }
            }),
            columns: columnsFirst,
            renderTo: 'grid1',
            plugins: [summary]
        });

        var gridSecond = new Ext.grid.GridPanel ({
            id:'grid-second',
            height: 400,
            store: storeSecond,
            trackMouseOver: true,
            autoWidth: true,
            view : new Ext.grid.GridView({
//                forceFit: true
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect:true,
                listeners: {
                    'rowselect': function(t, rowIndex, rec){
//                        var kode_brg = rec.get("kode_brg"),
//                            firstRow = gridFirst.getStore().findExact('kode_brg',kode_brg);
//
//                        gridFirst.getSelectionModel().clearSelections();
//                        if (firstRow >= 0)
//                        {
//                            gridFirst.getSelectionModel().selectRow(firstRow,false);
//                        }

                    }
                }
            }),
            columns: columnsSecond,
            renderTo: 'grid2',
            plugins: [summary2]
        });

        var formFirst = new Ext.form.FormPanel({
            id: 'form-first',
            frame: false,
            border: false,
            renderTo: 'form1',
            items: [
                {
                    id: 'combo-first',
                    width: 200,
                    xtype: 'combo',
                    triggerAction: 'all',
                    mode: 'local',
                    fieldLabel: 'Revision ',
                    editable: false,
                    displayField: 'name',
                    valueField: 'id',
                    store: storeCombo,
                    value: 0,
                    listeners: {
                        'select': function(t,n,o){
                            loadFirst(n.get("id"));
                        }
                    }
                }
            ]
        });


        var formSecond = new Ext.form.FormPanel({
            id: 'form-second',
            frame: false,
            border: false,
            renderTo: 'form2',
            items: [
                {
                    id: 'combo-second',
                    xtype: 'combo',
                    width: 200,
                    triggerAction: 'all',
                    mode: 'local',
                    fieldLabel: 'Revision ',
                    editable: false,
                    displayField: 'name',
                    valueField: 'id',
                    value: <?=$this->lastRev?>,
                    store: storeCombo,
                    listeners: {
                        'select': function(t,n,o){
                            loadSecond(n.get("id"));
                        }
                    }
                }
            ]
        });

        loadFirst(0);
        loadSecond(<?=$this->lastRev?>);
    });
</script>

<center><h1 style="font-size: 20px;">Revision History for PO : <?=$this->trano?></h1></center>
<br>
<div id="first">
    <div id="form1"></div>
    <div id="header1" class="header"></div><br>
    <div id="grid1"></div>
</div>
<div id="second">
    <div id="form2"></div>
    <div id="header2" class="header"></div><br>
    <div id="grid2"></div>

</div>