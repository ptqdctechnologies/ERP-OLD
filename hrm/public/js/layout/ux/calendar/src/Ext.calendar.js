/*!
 * Ext JS Library 3.3.0
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.calendar');

 (function() {
    Ext.apply(Ext.calendar, {
        Date: {
            diffDays: function(start, end) {
                day = 1000 * 60 * 60 * 24;
                diff = end.clearTime(true).getTime() - start.clearTime(true).getTime();
                return Math.ceil(diff / day);
            },

            copyTime: function(fromDt, toDt) {
                var dt = toDt.clone();
                dt.setHours(
                fromDt.getHours(),
                fromDt.getMinutes(),
                fromDt.getSeconds(),
                fromDt.getMilliseconds());

                return dt;
            },

            compare: function(dt1, dt2, precise) {
                if (precise !== true) {
                    dt1 = dt1.clone();
                    dt1.setMilliseconds(0);
                    dt2 = dt2.clone();
                    dt2.setMilliseconds(0);
                }
                return dt2.getTime() - dt1.getTime();
            },

            // private helper fn
            maxOrMin: function(max) {
                var dt = (max ? 0: Number.MAX_VALUE),
                i = 0,
                args = arguments[1],
                ln = args.length;
                for (; i < ln; i++) {
                    dt = Math[max ? 'max': 'min'](dt, args[i].getTime());
                }
                return new Date(dt);
            },

            max: function() {
                return this.maxOrMin.apply(this, [true, arguments]);
            },

            min: function() {
                return this.maxOrMin.apply(this, [false, arguments]);
            },

            formatDate: function (formatDate, formatString) {
                if(formatDate instanceof Date) {
                    var months = new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
                    var yyyy = formatDate.getFullYear();
                    var yy = yyyy.toString().substring(2);
                    var m = formatDate.getMonth();
                    var bulan = m + 1;
                    var mm = bulan < 10 ? "0" + bulan : bulan;
                    var mmm = months[m];
                    var d = formatDate.getDate();
                    var dd = d < 10 ? "0" + d : d;

                    var h = formatDate.getHours();
                    var hh = h < 10 ? "0" + h : h;
                    var n = formatDate.getMinutes();
                    var nn = n < 10 ? "0" + n : n;
                    var s = formatDate.getSeconds();
                    var ss = s < 10 ? "0" + s : s;

                    formatString = formatString.replace(/yyyy/, yyyy);
                    formatString = formatString.replace(/yy/, yy);
                    formatString = formatString.replace(/mmm/, mmm);
                    formatString = formatString.replace(/mm/, mm);
                    formatString = formatString.replace(/m/, m);
                    formatString = formatString.replace(/dd/, dd);
                    formatString = formatString.replace(/d/, d);
                    formatString = formatString.replace(/hh/, hh);
                    formatString = formatString.replace(/h/, h);
                    formatString = formatString.replace(/nn/, nn);
//                    formatString = formatString.replace(/n/, n);
                    formatString = formatString.replace(/ss/, ss);
//                    formatString = formatString.replace(/s/, s);

                    return formatString;
                } else {
                    return "";
                }
            },

            //Cek tgl diantara start & end
            dateWithin: function(start,end,check) {
                var b,e,c;

                end = end.add(Date.SECOND, -1);

                b = Date.parse(this.formatDate(start,'dd mmm yyyy hh:nn:ss'));
                e = Date.parse(this.formatDate(end,'dd mmm yyyy hh:nn:ss'));
                c = Date.parse(this.formatDate(check,'dd mmm yyyy hh:nn:ss'));
                if (c <= e && c >= b)
                    return true;
                return false;
            }
        }
    });
})();