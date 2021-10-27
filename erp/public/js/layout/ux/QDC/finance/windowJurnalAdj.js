function showWindowJurnalAdj(obj, jurnal)
{
    var compareDebit,
        compareCredit,
        closeable = false;

    var gridJurnal = new Ext.ux.grid.gridJurnal({
        height: 320,
        autoWidth: true,
        id: 'grid-jurnal-window',
        hideRefNumber: false, // show Ref Number column
        refNumberArray: jurnal,
        arrayJurnal: jurnal
    });

    var windowJurnal = new Ext.Window({
        id: 'jurnal-window',
        modal: true,
        height: 400,
        width: 600,
        title: 'Adjustment Journal Form',
        resizable: false,
        stateful: false,
        closeAction: 'hide',
        closeable: false,
        buttons: [
            {
                text: 'OK',
                iconCls: 'silk-add',
                handler: function()
                {
                    var json = gridJurnal.getJSONFromStore(),
                        jsonJurnal = '';

                    if (json == false)
                        return false;

                    json = Ext.util.JSON.decode(json);
                    Ext.each(json,function(items){
                        items.type = 'ADJ';
                        items.ket = 'Adjusment Journal';
                        encode = Ext.util.JSON.encode(items);
                        jsonJurnal += encode + ',';
                    });
                    jsonJurnal = '[' + jsonJurnal.substring(0, jsonJurnal.length - 1) + ']';

                    Ext.Ajax.request({
                        url: '/finance/adjustingjournal/doinsertadjustingjournal',
                        method:'POST',
                        params: {
                            adjustingjournaldata: jsonJurnal
                        },
                        success: function(result, request){
                                var returnData = Ext.util.JSON.decode(result.responseText);
                                if(returnData.success) {
                                    obj.closedJournalFunction();
                                    windowJurnal.close();
                                }
                            }
                            ,failure:function( action){
                        if(action.failureType == 'server'){
                        obj = Ext.util.JSON.decode(action.response.responseText);
                        Ext.Msg.alert('Error!', obj.errors.reason);
                        }
                        }
                    });
                }
            }
        ],
        items: [
            gridJurnal
        ],
        listeners: {
            'beforeclose': function(win)
            {
                var json = gridJurnal.getJSONFromStore();

                if (json == false)
                    return false;
            }
        }
    });

    windowJurnal.show();
}