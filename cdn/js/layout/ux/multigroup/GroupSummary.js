/*
 * Ext JS Library 3.0 RC2
 * Copyright(c) 2006-2009, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.ux.GroupSummary2 = Ext.extend(Ext.util.Observable, {
    constructor: function(config){
        Ext.apply(this, config);
        Ext.ux.GroupSummary2.superclass.constructor.call(this);
    },
    /* show each grouping level in different color ? */
    show_summary_colors: true,
    summary_colors: [ "#EEEEEE", "#FFFFFF", "#EAEAEA"],
    /* show a hovering tooltip over summary row (TR 'title' attribute) */
    show_group_title: true,
    init : function(grid){
        this.grid = grid;
        this.cm = grid.getColumnModel();
        this.view = grid.getView();

        var v = this.view;
        v.doGroupEnd = this.doGroupEnd.createDelegate(this);

        v.afterMethod('onColumnWidthUpdated', this.doWidth, this);
        v.afterMethod('onAllColumnWidthsUpdated', this.doAllWidths, this);
        v.afterMethod('onColumnHiddenUpdated', this.doHidden, this);
        v.afterMethod('onUpdate', this.doUpdate, this);
        v.afterMethod('onRemove', this.doRemove, this);

        if(!this.rowTpl){
            this.rowTpl = new Ext.Template(
                '<div class="x-grid3-summary-row" style="{tstyle}">',
                '<table class="x-grid3-summary-table" border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
                '<tbody><tr {title} style="{trstyle}">{cells}</tr></tbody>',
                '</table></div>'
            );
            this.rowTpl.disableFormats = true;
        }
        this.rowTpl.compile();

        if(!this.cellTpl){
            this.cellTpl = new Ext.Template(
                '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} {css}" style="{style}">',
                '<div class="x-grid3-cell-inner x-grid3-col-{id}" unselectable="on">{value}</div>',
                "</td>"
            );
            this.cellTpl.disableFormats = true;
        }
        this.cellTpl.compile();
    },

    toggleSummaries : function(visible){
        var el = this.grid.getGridEl();
        if(el){
            if(visible === undefined){
                visible = el.hasClass('x-grid-hide-summary');
            }
            el[visible ? 'removeClass' : 'addClass']('x-grid-hide-summary');
        }
    },

    renderSummary : function(o, cs, g){
        cs = cs || this.view.getColumnData();
        var cfg = this.cm.config;

        var buf = [], c, p = {}, cf, last = cs.length-1;
        var grpId = g.groupId.split(':')[0].split('gp-')[1];
        var group_summary_desc = "summary for this " + g.text;
        title_text = this.show_group_title ?
            "title='" + group_summary_desc + "'" : "";
        var colors = this.summary_colors;
        summary_color_text = !this.show_summary_colors ? "" :
            "background-color:" + colors[g.group_level] + ";";
        for (var i = 0, len = cs.length; i < len; i++) {
            c = cs[i];
            cf = cfg[i];
            p.id = c.id;
            p.style = c.style;
            p.css = i == 0 ? 'x-grid3-cell-first ' : (i == last ? 'x-grid3-cell-last ' : '');
            if(cf.summaryType || cf.summaryRenderer){
                p.value = (cf.summaryRenderer || c.renderer)(o.data[c.name], p, o, g);
            }else{
                p.value = '';
            }
            if(p.value == undefined || p.value === "") p.value = "&#160;";
            buf[buf.length] = this.cellTpl.apply(p);
        }

        return this.rowTpl.apply({
            tstyle: 'width:' + this.view.getTotalWidth() + ';',
            trstyle: summary_color_text,
            cells: buf.join(''),
            title: title_text
        });
    },

    calculate : function(rs, cs){
        var data = {}, r, c, cfg = this.cm.config, cf;
        var grp, grpId;
        for(var j = 0, jlen = rs.length; j < jlen; j++){
            r = rs[j];
            if (r._groupId) {
                grp = r._groupId.split(':')[0];
                grpId = grp.split('gp-')[1];
            } else {
            	grpId = '';
            }
            for(var i = 0, len = cs.length; i < len; i++){
                c = cs[i];
                cf = cfg[i];
                if(cf.summaryType){
                    data[c.name] = Ext.ux.GroupSummary2.Calculations[cf.summaryType](data[c.name] || 0, r, c.name, data);
                }
            }
        }
        return data;
    },

    doGroupEnd : function(buf, g, cs, ds, colCount){
        var data = this.calculate(g.rs, cs, g);
        buf.push('</div>', this.renderSummary({data: data}, cs, g), '</div>');
    },

    doWidth : function(col, w, tw){
        var gs = this.view.getGroups(), s;
        for(var i = 0, len = gs.length; i < len; i++){
            s = gs[i].childNodes[2];
            s.style.width = tw;
            s.firstChild.style.width = tw;
            s.firstChild.rows[0].childNodes[col].style.width = w;
        }
    },

    doAllWidths : function(ws, tw){
        var gs = this.view.getGroups(), s, cells, wlen = ws.length;
        for(var i = 0, len = gs.length; i < len; i++){
            s = gs[i].childNodes[2];
            s.style.width = tw;
            s.firstChild.style.width = tw;
            cells = s.firstChild.rows[0].childNodes;
            for(var j = 0; j < wlen; j++){
                cells[j].style.width = ws[j];
            }
        }
    },

    doHidden : function(col, hidden, tw){
        var gs = this.view.getGroups(), s, display = hidden ? 'none' : '';
        for(var i = 0, len = gs.length; i < len; i++){
            s = gs[i].childNodes[2];
            s.style.width = tw;
            s.firstChild.style.width = tw;
            s.firstChild.rows[0].childNodes[col].style.display = display;
        }
    },

    // Note: requires that all (or the first) record in the 
    // group share the same group value. Returns false if the group
    // could not be found.
    refreshSummary : function(groupValue){
        return this.refreshSummaryById(this.view.getGroupId(groupValue));
    },

    getSummaryNode : function(gid){
        var g = Ext.fly(gid, '_gsummary');
        if(g){
            return g.down('.x-grid3-summary-row', true);
        }
        return null;
    },

    refreshSummaryById : function(gid){
        var gElement = document.getElementById(gid);
        if(!gElement){
            return false;
        }
        var g = this.view.getGroupById(gid);
        var rs = g.rs;
        var cs = this.view.getColumnData();
        var data = this.calculate(rs, cs);
        var markup = this.renderSummary({data: data}, cs, g);

        var existing = this.getSummaryNode(gid);
        if(existing){
            gElement.removeChild(existing);
        }
        Ext.DomHelper.append(gElement, markup);
        return true;
    },

    doUpdate : function(ds, record){
        // refresh summary for the groups to which the record belongs
        var member_of_groups = this.view.record_memberships[record.id];
        for (var i = 0; i < member_of_groups.length; i++)
        {
            var group = member_of_groups[i];
            this.refreshSummaryById(group.groupId);
        }
    },

    doRemove : function(ds, record, index, isUpdate){
        if(!isUpdate){
            this.refreshSummaryById(record._groupId);
        }
    },

    showSummaryMsg : function(groupValue, msg){
        var gid = this.view.getGroupId(groupValue);
        var node = this.getSummaryNode(gid);
        if(node){
            node.innerHTML = '<div class="x-grid3-summary-msg">' + msg + '</div>';
        }
    }
});
Ext.grid.GroupSummary = Ext.ux.GroupSummary2;

Ext.ux.GroupSummary2.Calculations = {
    'sum' : function(v, record, field){
        var new_sum = v + (parseFloat(record.data[field]) || 0);
        return new_sum;
    },
	'sum2dec' : function(v, record, field){
        return Math.round((v + (record.data[field]||0))*100)/100;
    },
    'count' : function(v, record, field, data){
        return data[field+'count'] ? ++data[field+'count'] : (data[field+'count'] = 1);
    },

    'max' : function(v, record, field, data){
        var rec_val = record.data[field];
        var max = data[field+'max'] === undefined ?
            (data[field+'max'] = rec_val) : data[field+'max'];
        return rec_val > max ? (data[field+'max'] = v) : max;
    },

    'min' : function(v, record, field, data){
        var rec_val = record.data[field];
        var min = data[field+'min'] === undefined ?
            (data[field+'min'] = rec_val) : data[field+'min'];
        return rec_val < min ? (data[field+'min'] = rec_val) : min;
    },

    'average' : function(v, record, field, data){
        var c = data[field+'count'] ? ++data[field+'count'] : (data[field+'count'] = 1);
        var t = (data[field+'total'] = ((data[field+'total']||0) + (record.data[field]||0)));
        return t === 0 ? 0 : parseFloat(t) / parseFloat(c);
    }
};
Ext.grid.GroupSummary.Calculations = Ext.ux.GroupSummary2.Calculations;

Ext.ux.HybridSummary = Ext.extend(Ext.ux.GroupSummary2, {
    calculate : function(rs, cs){
        var gcol = this.view.getGroupField();
        var gvalue = rs[0].data[gcol];
        var gdata = this.getSummaryData(gvalue);
        return gdata || Ext.ux.HybridSummary.superclass.calculate.call(this, rs, cs);
    },

    updateSummaryData : function(groupValue, data, skipRefresh){
        var json = this.grid.store.reader.jsonData;
        if(!json.summaryData){
            json.summaryData = {};
        }
        json.summaryData[groupValue] = data;
        if(!skipRefresh){
            this.refreshSummary(groupValue);
        }
    },

    getSummaryData : function(groupValue){
        var json = this.grid.store.reader.jsonData;
        if(json && json.summaryData){
            return json.summaryData[groupValue];
        }
        return null;
    }
});
Ext.grid.HybridSummary = Ext.ux.HybridSummary;
