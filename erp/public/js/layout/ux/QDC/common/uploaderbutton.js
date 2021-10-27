var showAuthWindow = function(url,callback)
{
    if (callback == undefined)
        callback = function() { };
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
            }
        ]
    });

    var dFormMsg =  new Ext.Window({
        id: 'auth_window',
        layout:'fit',
        width: 300,
        height: 150,
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
                    var params = {
                        username: Ext.getCmp('username').getValue(),
                        password: Ext.getCmp('password').getValue()
                    };

                    var select = this.Selectid;

                    Ext.Ajax.request({
                        url:url,
                        method:'POST',
                        params:params,
                        success:function(result){
                            obj = Ext.util.JSON.decode(result.responseText);

                            if (obj.success)
                            {
                                callback();
                                dFormMsg.close();
                            }
                            else
                            {
                                Ext.Msg.alert('Error', obj.msg);
                            }
                        },
                        failure:function( action){
                            if(action.failureType == 'server'){
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                Ext.Msg.alert('Error!', obj.errors.reason);
                            }else{
                                Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                            }
                        }

                    });
                },scope: this
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
};

var showUploaderWindow = function(url,params,jsonFile,callback)
{
    if (callback == undefined)
        callback = function() { };

    var uploadFile = new Ext.ux.uploadFile({
        width: 500,
        height: 200,
        frame: true
    });

    if (jsonFile != undefined)
        uploadFile.store.loadData(jsonFile);

    var dFormMsg =  new Ext.Window({
        id: 'uploader_window',
        layout:'fit',
        width: 510,
        height: 250,
        title: 'Upload Document',
        stateful: false,
        modal: true,
        resizable: true,
        items: [
            uploadFile
        ],
        buttons: [
            {
                text: 'OK',
                handler: function()
                {
                    var json = uploadFile.getJSONFromStore();

                    params.file = json;

                    Ext.Ajax.request({
                        url:url,
                        method:'POST',
                        params:params,
                        success:function(result){
                            obj = Ext.util.JSON.decode(result.responseText);

                            if (obj.success)
                            {
                                callback();
                                dFormMsg.close();
                            }
                            else
                            {
                                Ext.Msg.alert('Error', obj.msg);
                            }
                        },
                        failure:function( action){
                            if(action.failureType == 'server'){
                                obj = Ext.util.JSON.decode(action.response.responseText);
                                Ext.Msg.alert('Error!', obj.errors.reason);
                            }else{
                                Ext.Msg.alert('Warning!', 'Server is unreachable : ' + action.response.responseText);
                            }
                        }

                    });
                },scope: this
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
};