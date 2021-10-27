function showProgressBar(message)
{
    if (message == undefined)
        message = 'Processing....';
    var bForm =  new Ext.Window({
        id: 'waiting-form',
        layout:'fit',
        width: 300,
        stateful: false,
        modal: true,
        resizable: false,
        style: 'margin-top:10px;',
        title: 'Our server is having Meditation..',
        items: [
            new Ext.ProgressBar({
                id:'pbar'
            })
        ],
        listeners: {
            'show': function(t){
                Ext.getCmp('pbar').on('update', function(val){
                    Ext.getCmp('pbar').updateText(message);
                });

                Ext.getCmp('pbar').wait({
                    interval:200,
                    increment:15
                });
            }

        }
    });

    bForm.show();
}

function closeProgressBar()
{
    Ext.getCmp('waiting-form').close();
}