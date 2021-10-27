Ext.ux.workflowChoosePerson = Ext.extend(Ext.Window,{
    // === Ext.Window properties & config ===
    id: 'workflow-choose-next',
//    layout: 'border',
    layout: 'fit',
    width: 300,
    height: 350,
    modal: true,
    resizable: false,
    title: 'Choose Document Receiver',
    style: 'margin-top: 10px',
    labelAlign: 'right',
    stateful: false,
    // === END ====
    //Our custom properties n method...
    chooseCallback: function() { /*please override this*/ },
    userArray: [],
    gridRecord : Ext.data.Record.create([
        {name: 'id', type: 'string'},
        {name: 'workflow_id', type: 'string'},
        {name: 'workflow_item_name', type: 'string'},
        {name: 'workflow_item_id', type: 'string'},
        {name: 'workflow_item_type_id', type: 'string'},
        {name: 'workflow_structure_id', type: 'string'},
        {name: 'next', type: 'string'},
        {name: 'uid_next', type: 'string'},
        {name: 'name', type: 'string'},
        {name: 'trano', type: 'string'},
        {name: 'role_name', type: 'string'},
        {name: 'prj_kode', type: 'string'}
    ]),
    storeUser : new Ext.data.Store({
        reader: new Ext.data.JsonReader({ fields: this.gridRecord })
    }),
    fillGridRecord: function(userArray) {
        var theList = this.gridRecord,
            theStore = this.storeUser;

        theStore.removeAll();
        Ext.each(userArray, function (t, index){
            var d = new theList({
                id: t.id,
                workflow_id: t.workflow_id,
                workflow_item_id: t.workflow_item_id,
                workflow_item_type_id: t.workflow_item_type_id,
                workflow_item_name: t.workflow_item_name,
                workflow_structure_id: t.workflow_structure_id,
                uid_next: t.uid_next,
                next: t.next,
                name: t.name,
                trano: t.trano,
                role_name: t.role_name,
                prj_kode: t.prj_kode
            });
            theStore.add(d);
            d = undefined;
        },this);

    },
    userColumns :  [
        new Ext.grid.RowNumberer(),
        {
            header: "Receiver", dataIndex: 'name', width: 220, renderer: function (v,p,r) {
            return '<div class="personicon"><span class="rolename"><b>' + r.data.name + '</b><br>' + r.data.role_name + '<br>Transaction Type: <b>' + r.data.workflow_item_name + '</b><br>Project : ' + r.data.prj_kode + '</span></div>';
        }
        }
    ],
    // === END ====
    initComponent : function() {

        var that = this;
        this.fillGridRecord(this.userArray);

        var grid = new Ext.grid.GridPanel({
            width: 288,
            height: 330,
            region: 'center',
            loadMask: true,
            columns: this.userColumns,
            store: this.storeUser,
            sm: new Ext.grid.RowSelectionModel({
                singleSelect:true
            }),
            viewConfig: {
                forceFit: true,
                enableRowBody:true,
                showPreview:true,
                getRowClass : function(record, rowIndex, p, ds) {
                    return 'x-grid3-row-collapsed';
                }
            },
            listeners: {
                'rowdblclick': function(g, rowIndex, e){
                    var record = g.getStore().getAt(rowIndex);
//                    Ext.Msg.show({
//                        title:'Confirm',
//                        msg: 'Send this document to ' + record.get("name") + " ?",
//                        buttons: Ext.Msg.OKCANCEL,
//                        fn: function(btn) {
//                            if (btn == 'ok')
//                            {
                                that.close();
                                that.chooseCallback(record);
//                            }
//                        },
//                        animEl: 'elId',
//                        icon: Ext.MessageBox.QUESTION
//                    });
                }
            }
        });

        this.buttons = [
            {
                text: 'OK',
                id: 'ok-next',
                iconCls: 'silk-upd',
                handler: function(btn, ev) {
                    var row = grid.getSelectionModel().getSelections();
                    if (row.length == 0)
                    {
                        Ext.Msg.alert('Error','Please choose document receiver');
                        return false;
                    }
//                    Ext.Msg.show({
//                        title:'Confirm',
//                        msg: 'Send this document to ' + row[0].get("name") + " ?",
//                        buttons: Ext.Msg.OKCANCEL,
//                        fn: function(btn) {
//                            if (btn == 'ok')
//                            {
                                that.close();
                                that.chooseCallback(row[0]);
//                            }
//                        },
//                        animEl: 'elId',
//                        icon: Ext.MessageBox.QUESTION
//                    });
                }

            },
            {
                text: 'Cancel',
                id: 'cancel',
                handler: function(btn, ev) {
                    that.close();
                }
            }
        ],

        this.items = [
            grid
        ];
        Ext.ux.workflowChoosePerson.superclass.initComponent.call(this);

    }
});

Ext.ux.workflowButton = Ext.extend(Ext.Container,{
    layout: {
        type: 'hbox',
        defaultMargins: ' 5',
        pack : 'end'
    },
    width: 400,
    defaults: {
        xtype: 'button'
    },
    itemType: null,
    isApproval: false,
    showApproveButton: false,
    showEditButton: false,
    showRejectButton: false,
    showCancelButton: false,
    showSubmitButton: false,
    showAskButton: false,
    submitButton: null,
    cancelButton: null,
    approveButton: null,
    askButton: null,
    rejectButton: null,
    editButton: null,
//    submitButton: new Ext.Button({
//        text: 'Submit',
//        iconCls: 'icon-save',
//        scale: 'large'
//    }),
//    cancelButton: new Ext.Button({
//        text: 'Cancel',
//        scale: 'large'
//    }),
//    approveButton: new Ext.Button({
//        text: 'Approve',
//        iconCls: 'icon-add',
//        scale: 'large'
//    }),
//    askButton: new Ext.Button({
//        text: 'Ask Question',
//        iconCls: 'icon-ask',
//        scale: 'large'
//    }),
//    rejectButton: new Ext.Button({
//        text: 'Reject',
//        iconCls: 'icon-cancel',
//        scale: 'large'
//    }),
//    editButton: new Ext.Button({
//        text: 'Goto Edit',
//        iconCls: 'icon-go',
//        scale: 'large'
//    }),
    showComment: function(cb) {
        Ext.MessageBox.show({
            title: 'Comment',
            msg: 'Please enter comment:',
            width:300,
            buttons: Ext.MessageBox.OKCANCEL,
            multiline: true,
            fn: function(btn,txt){
                if (btn == 'ok')
                {
                    cb(txt);
                }
                else
                {
                    this.fireEvent('cancelworkflow',this);
                    return false;
                }
            },
            scope: this
        });
    },
    doSubmit: function(params){
        if (this.urlSubmit == null)
        {
            Ext.Msg.alert('Error','No URL defined');
            return false;
        }
        var that = this;
        this.masking();
        Ext.Ajax.request({
            url: this.urlSubmit,
            method:'POST',
            success: function(resp){
                that.unmasking();
                var returnData = Ext.util.JSON.decode(resp.responseText);
                if (returnData.success)
                {
                    this.parseDataSubmit(this.itemType,returnData);
                }
                else
                {
                    Ext.Msg.alert('Error', returnData.msg);
                }
            },
            failure:function( action){
                that.unmasking();
                if(action.failureType == 'server'){
                    obj = Ext.util.JSON.decode(action.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                }else{
                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                }
            },
            params: params,
            scope: this
        });
    },
    doApprove: function(params){
        var that = this;
        this.masking();
        Ext.Ajax.request({
            url: this.urlApprove,
            method:'POST',
            success: function(resp){
                that.unmasking();
                var returnData = Ext.util.JSON.decode(resp.responseText);
                if (returnData.success)
                {
                    this.parseDataApprove(this.itemType,returnData);
                }
                else
                {
                    Ext.Msg.alert('Error', returnData.msg);
                }
            },
            failure:function( action){
                that.unmasking();
                if(action.failureType == 'server'){
                    obj = Ext.util.JSON.decode(action.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                }else{
                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                }
            },
            params: params,
            scope: this
        });
    },
    doReject: function(params){
        var that = this;
        this.masking();
        Ext.Ajax.request({
            url: this.urlReject,
            method:'POST',
            success: function(resp){
                that.unmasking();
                var returnData = Ext.util.JSON.decode(resp.responseText);
                if (returnData.success)
                {
                    this.rejectSuccessCallback();
                }
                else
                {
                    Ext.Msg.alert('Error', returnData.msg);
                }
            },
            failure:function( action){
                that.unmasking();
                if(action.failureType == 'server'){
                    obj = Ext.util.JSON.decode(action.responseText);
                    Ext.Msg.alert('Error!', obj.errors.reason);
                }else{
                    Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                }
            },
            params: params,
            scope: this
        });
    },
    doCancel: function(params){ /*please override this*/ },
    doAsk: function(params){ /*please override this*/ },
    //before success callback harus null...
    beforeApproveCallback: null,
    rejectSuccessCallback: function(){ /*please override this*/ },
    approveSuccessCallback: function(){ /*please override this*/ },
    editSuccessCallback: function(){ /*please override this*/ },
    cancelSuccessCallback: function(){ /*please override this*/ },
    submitSuccessCallback: function(){ /*please override this*/ },
    setRejectParams: function(v)
    {
        this.rejectParams = v;
    },
    setApproveParams: function(v)
    {
        this.approveParams = v;
    },
//    addToApproveParams: function(v)
//    {
//        if (!Ext.isObject(v))
//            return false;
//        if (this.approveParams == null)
//            this.approveParams = v;
//        else
//        {
//            Ext.each(v,function(items,index){
//                this.approveParams[index] = items;
//            },this);
//        }
//    },
//    addToRejectParams: function(v)
//    {
//        if (!Ext.isObject(v))
//            return false;
//        if (this.rejectParams == null)
//            this.rejectParams = v;
//        else
//        {
//            Ext.each(v,function(items,index){
//                console.log([items,index]);
//                this.rejectParams[index] = items;
//            },this);
//        }
//    },
    approveParams: {},
    rejectParams: {},
    submitParams: {},
    trano: null,
    transId: null,
    urlApprove: '/admin/workflow/approve',
    urlReject: '/admin/workflow/reject',
    urlSubmit: null,
    isEdit: false,
    getButtons: function(itemType){
        var btn = [];

        if (this.showAskButton)
        {
            var ask = this.askButton;
            ask.on('click',function(){
                askQuestion(this.trano);
            },this);
            btn.push(ask);
        }
        if (this.showSubmitButton)
        {
            var sub = this.submitButton,
                that = this;
            sub.on('click',function(){
                var cb = function(comment) {
                    that.submitParams.comment = comment;
                    that.doSubmit(that.submitParams);
                };
                this.showComment(cb);
            },this);
            btn.push(sub);
        }
        if (this.showApproveButton)
        {
            var app = this.approveButton,
                that = this;

            app.on('click',function(){
                if (this.fireEvent('beforeworkflowapprove', this) !== false)
                {
                    if (this.beforeApproveCallback !== null)
                    {
                        this.beforeApproveCallback(that);
                    }
                    else
                    {
                        var cb = function(comment) {
                            //                    that.addToApproveParams({comment:comment});
                            that.approveParams.comment = comment;
                            that.doApprove(that.approveParams);
                        };
                        this.showComment(cb);
                    }
                }
            },this);
            btn.push(app);
        }
        if (this.showRejectButton)
        {
            var rej = this.rejectButton;

            rej.on('click',function(){
                this.fireEvent('beforeworkflowreject', this);
                var that = this,cb = function(comment) {
//                    that.addToRejectParams({comment:comment});
                    that.rejectParams.comment = comment;
                    that.doReject(that.rejectParams);
                };
                this.showComment(cb);
            },this);
            btn.push(rej);
        }
        if (this.showCancelButton)
        {
            var can = this.cancelButton;

            can.on('click',function(){
                this.cancelSuccessCallback();
            },this);
            btn.push(can);
        }
        if (this.showEditButton)
        {
            var ed = this.editButton;

            ed.on('click',function(){
                this.editSuccessCallback();
            },this);
            btn.push(ed);
        }

        return btn;
    },
    initComponent : function() {
        this.addEvents(
            'beforeworkflowsubmit',
            'cancelworkflowsubmit',
            'cancelworkflow',
            'workflowsubmit',
            'workflownotfound',
            'workflowmultinextperson',
            'workflowsubmitsuccess',
            'workflowsubmitfail',
            'beforeworkflowapprove',
            'cancelworkflowapprove',
            'workflowapprovesuccess',
            'beforeworkflowreject',
            'cancelworkflowreject',
            'workflowrejectsuccess'
        );
        this.submitButton= new Ext.Button({
            text: 'Submit',
            iconCls: 'icon-save',
            scale: 'large'
        });
        this.cancelButton= new Ext.Button({
            text: 'Cancel',
            iconCls: 'icon-cancel',
            scale: 'large'
        });
        this.approveButton= new Ext.Button({
            text: 'Approve',
            iconCls: 'icon-add',
            scale: 'large'
        });
        this.askButton= new Ext.Button({
            text: 'Ask Question',
            iconCls: 'icon-ask',
            scale: 'large'
        });
        this.rejectButton= new Ext.Button({
            text: 'Reject',
            iconCls: 'icon-cancel',
            scale: 'large'
        });
        this.editButton= new Ext.Button({
            text: 'Goto Edit',
            iconCls: 'icon-go',
            scale: 'large'
        });

        this.items = this.getButtons(this.itemType);
        Ext.ux.workflowButton.superclass.initComponent.call(this);
    },
    parseDataApprove: function(type, returnData)
    {
        var approveParam = this.approveParams,
            that = this;
        if (type == 'BRF' || type == 'BRFP')
        {
            if (returnData.number != undefined)
            {
                var payTrano = returnData.data.number;
                if ( payTrano != undefined)
                    Ext.Msg.alert('Success', 'BRF Payment has been made, Please create Bank Payment Voucher for this trano : <b>' + payTrano + '</b>');
            }
        }
        else
        {
            if (returnData.number != undefined)
            {
                var trano = returnData.number;
                if ( trano != undefined)
                {
                    var msg = '';
                    if (!this.isEdit)
                        msg = 'Document has been submitted to workflow, Trano : <b>' + trano + '</b>';
                    else
                        msg = 'Document <b>' + trano + '</b> has been re-submitted to workflow';

                    Ext.Msg.alert('Success', msg);
                    that.submitSuccessCallback();
                }
            }

        }

        if (returnData.approval == true)
        {
            this.approveSuccessCallback();
        }

        if (returnData.user != undefined)
        {
            var wChoose = new Ext.ux.workflowChoosePerson({
                chooseCallback: function(rec) {
                    approveParam.next = rec.get("next");
                    approveParam.uid_next = rec.get("uid_next");
                    approveParam.workflow_structure_id = rec.get("workflow_structure_id");
                    approveParam.workflow_id = rec.get("workflow_id");
                    approveParam.workflow_item_id = rec.get("workflow_item_id");
                    approveParam.workflow_item_type_id = rec.get("workflow_item_type_id");
                    approveParam.trano = rec.get("trano");

                    that.doApprove(approveParam);
                },
                userArray: returnData.user
            });

            wChoose.show();
        }
        else
        {
            var msg = '';
            if (this.isEdit)
                msg = 'Document has been re-submitted to workflow';

            if (msg != '')
                Ext.Msg.alert('Success', msg);

            that.submitSuccessCallback();
        }
    },
    parseDataSubmit: function(type, returnData)
    {
        var submitParam = this.submitParams,
            that = this;
        if (type == 'BRFP')
        {
            if (returnData.number != undefined)
            {
                var payTrano = returnData.number;
                if ( payTrano != undefined)
                {
                    var msg = '';
                    if (!this.isEdit)
                        msg = 'BRF Payment has been submitted to workflow, BRF Payment trano : <b>' + payTrano + '</b>';
                    else
                        msg = 'BRF Payment <b>' + payTrano + '</b> has been re-submitted to workflow';

                    Ext.Msg.alert('Success', msg);
                    that.submitSuccessCallback();
                }
            }
        }
        else if (type == 'BRF')
        {
            if (returnData.number != undefined)
            {
                var trano = returnData.number;
                if ( trano != undefined)
                {
                    var msg = '';
                    if (!this.isEdit)
                        msg = 'BRF has been submitted to workflow, BRF trano : <b>' + trano + '</b>';
                    else
                        msg = 'BRF <b>' + trano + '</b> has been re-submitted to workflow';

                    Ext.Msg.alert('Success', msg);
                    that.submitSuccessCallback();
                }
            }
        }
        else if (type == 'BSF')
        {
            if (returnData.number != undefined)
            {
                var trano = returnData.number;
                if ( trano != undefined)
                {
                    var msg = '';
                    if (!this.isEdit)
                        msg = 'BSF has been submitted to workflow, BSF trano : <b>' + trano + '</b>';
                    else
                        msg = 'BSF <b>' + trano + '</b> has been re-submitted to workflow';

                    Ext.Msg.alert('Success', msg);
                    that.submitSuccessCallback();
                }
            }
        }
        else
        {
            if (returnData.number != undefined)
            {
                var trano = returnData.number;
                if ( trano != undefined)
                {
                    var msg = '';
                    if (!this.isEdit)
                        msg = 'Document has been submitted to workflow, Trano : <b>' + trano + '</b>';
                    else
                        msg = 'Document <b>' + trano + '</b> has been re-submitted to workflow';

                    Ext.Msg.alert('Success', msg);
                    that.submitSuccessCallback();
                }
            }
        }

        if (returnData.user != undefined)
        {
            var wChoose = new Ext.ux.workflowChoosePerson({
                chooseCallback: function(rec) {
                    submitParam.next = rec.get("next");
                    submitParam.uid_next = rec.get("uid_next");
                    submitParam.workflow_structure_id = rec.get("workflow_structure_id");
                    submitParam.workflow_id = rec.get("workflow_id");
                    submitParam.workflow_item_id = rec.get("workflow_item_id");
                    submitParam.workflow_item_type_id = rec.get("workflow_item_type_id");
                    submitParam.trano = rec.get("trano");

                    that.doSubmit(submitParam);
                },
                userArray: returnData.user
            });

            wChoose.show();
        }
    },
    masking: function()
    {
        Ext.getBody().mask("Please wait...");
    },
    unmasking: function()
    {
        Ext.getBody().unmask();
    },
    getRandomMsg: function()
    {
        var r = Math.floor((Math.random()*10));
        var m = [
            "Calculating value of Pi :)",
            "The server needs more resources :)",
            "Internet is on the way :)",
            "The server is meditating :)",
            "Our network has been so laggy :)",
            "Trying to figure out whose network is this :)",
            "After this commercial break, stay tuned to see Your Document :)",
            "QDC is stand for Qualitiy, fast Delivery and Cost effeciency :)",
        ];

        var msg = m[r];
        if (msg == undefined)
            msg = "The server is loading :)";
        return msg;
    }
});