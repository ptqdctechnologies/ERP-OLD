Ext.ns('Ext.ux.grid');
Ext.ux.grid.RowNumberer = Ext.extend(Ext.grid.RowNumberer, {
    renderer: function(v, p, record, rowIndex) {
        if (this.rowspan) {
            p.cellAttr = 'rowspan="'+this.rowspan+'"';
        }
        var st = record.store;
        if (st.lastOptions.params && st.lastOptions.params.start != undefined && st.lastOptions.params.limit != undefined) {
            var page = Math.floor(st.lastOptions.params.start/st.lastOptions.params.limit);
            var limit = st.lastOptions.params.limit;
            return limit*page + rowIndex+1;
        }else{
            return rowIndex+1;
        }
    }
});