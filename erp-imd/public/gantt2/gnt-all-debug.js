Ext.define("Sch.util.Mixin", {
	statics: {
		iterate: function (c, b, a) {
			Ext.iterate(c, b, a);
			if (Ext.enumerables) {
				Ext.each(Ext.enumerables, function (d) {
					if (c.hasOwnProperty(d)) {
						b.call(a || window, d, c[d])
					}
				})
			}
		},
		getSafeMixinObject: function (c, b) {
			var a = b || {};
			this.iterate(c, function (d, e) {
				if (Ext.isFunction(e)) {
					a[d] = function () {
						return e.apply(this, arguments)
					}
				} else {
					a[d] = e
				}
			});
			return a
		},
		define: function (b, a, c) {
			return Ext.define(b, this.getSafeMixinObject(a, c))
		}
	}
});
Ext.define("Sch.util.Date", {
	requires: "Ext.Date",
	singleton: true,
	constructor: function () {
		Ext.apply(this, {
			MILLI: Ext.Date.MILLI,
			SECOND: Ext.Date.SECOND,
			MINUTE: Ext.Date.MINUTE,
			HOUR: Ext.Date.HOUR,
			DAY: Ext.Date.DAY,
			WEEK: "w",
			MONTH: Ext.Date.MONTH,
			QUARTER: "q",
			YEAR: Ext.Date.YEAR
		});
		var b = this,
			a = [b.MILLI, b.SECOND, b.MINUTE, b.HOUR, b.DAY, b.WEEK, b.MONTH, b.QUARTER, b.YEAR];
		Ext.apply(this, {
			compareUnits: function (e, d) {
				var c = Ext.Array.indexOf(a, e),
					f = Ext.Array.indexOf(a, d);
				return c > f ? 1 : (c < f ? -1 : 0)
			}
		})
	},
	betweenLesser: function (b, d, a) {
		var c = b.getTime();
		return d.getTime() <= c && c < a.getTime()
	},
	constrain: function (b, c, a) {
		return this.min(this.max(b, c), a)
	},
	add: function (b, c, e) {
		var f = Ext.Date.clone(b);
		if (!c || e === 0) {
			return f
		}
		switch (c.toLowerCase()) {
		case this.MILLI:
			f = new Date(b.getTime() + e);
			break;
		case this.SECOND:
			f = new Date(b.getTime() + (e * 1000));
			break;
		case this.MINUTE:
			f = new Date(b.getTime() + (e * 60000));
			break;
		case this.HOUR:
			f = new Date(b.getTime() + (e * 3600000));
			break;
		case this.DAY:
			f.setDate(b.getDate() + e);
			break;
		case this.WEEK:
			f.setDate(b.getDate() + e * 7);
			break;
		case this.MONTH:
			var a = b.getDate();
			if (a > 28) {
				a = Math.min(a, Ext.Date.getLastDateOfMonth(this.add(Ext.Date.getFirstDateOfMonth(b), this.MONTH, e)).getDate())
			}
			f.setDate(a);
			f.setMonth(f.getMonth() + e);
			break;
		case this.QUARTER:
			f = this.add(b, this.MONTH, 3);
			break;
		case this.YEAR:
			f.setFullYear(b.getFullYear() + e);
			break
		}
		return f
	},
	getMeasuringUnit: function (a) {
		switch (a) {
		case this.WEEK:
			return this.DAY;
			break;
		default:
			return a;
			break
		}
	},
	getDurationInUnit: function (d, a, c) {
		var b;
		switch (c) {
		case this.QUARTER:
			b = Math.round(this.getDurationInMonths(d, a) / 3);
			break;
		case this.MONTH:
			b = Math.round(this.getDurationInMonths(d, a));
			break;
		case this.WEEK:
			b = Math.round(this.getDurationInDays(d, a)) / 7;
			break;
		case this.DAY:
			b = Math.round(this.getDurationInDays(d, a));
			break;
		case this.HOUR:
			b = Math.round(this.getDurationInHours(d, a));
			break;
		case this.MINUTE:
			b = Math.round(this.getDurationInMinutes(d, a));
			break;
		case this.SECOND:
			b = Math.round(this.getDurationInSeconds(d, a));
			break;
		case this.MILLI:
			b = Math.round(this.getDurationInMilliseconds(d, a));
			break
		}
		return b
	},
	getUnitToBaseUnitRatio: function (b, a) {
		if (b === a) {
			return 1
		}
		switch (b) {
		case this.YEAR:
			switch (a) {
			case this.QUARTER:
				return 1 / 4;
				break;
			case this.MONTH:
				return 1 / 12;
				break
			}
			break;
		case this.QUARTER:
			switch (a) {
			case this.YEAR:
				return 4;
				break;
			case this.MONTH:
				return 1 / 3;
				break
			}
			break;
		case this.MONTH:
			switch (a) {
			case this.YEAR:
				return 12;
				break;
			case this.QUARTER:
				return 3;
				break
			}
			break;
		case this.WEEK:
			switch (a) {
			case this.DAY:
				return 1 / 7;
				break;
			case this.HOUR:
				return 1 / 168;
				break
			}
			break;
		case this.DAY:
			switch (a) {
			case this.WEEK:
				return 7;
				break;
			case this.HOUR:
				return 1 / 24;
				break;
			case this.MINUTE:
				return 1 / 1440;
				break
			}
			break;
		case this.HOUR:
			switch (a) {
			case this.DAY:
				return 24;
				break;
			case this.MINUTE:
				return 1 / 60;
				break
			}
			break;
		case this.MINUTE:
			switch (a) {
			case this.HOUR:
				return 60;
				break;
			case this.SECOND:
				return 1 / 60;
				break;
			case this.MILLI:
				return 1 / 60000;
				break
			}
			break;
		case this.SECOND:
			switch (a) {
			case this.MILLI:
				return 1 / 1000;
				break
			}
			break;
		case this.MILLI:
			switch (a) {
			case this.SECOND:
				return 1000;
				break
			}
			break
		}
		return -1
	},
	getDurationInMilliseconds: function (b, a) {
		return (a - b)
	},
	getDurationInSeconds: function (b, a) {
		return (a - b) / 1000
	},
	getDurationInMinutes: function (b, a) {
		return (a - b) / 60000
	},
	getDurationInHours: function (b, a) {
		return (a - b) / 3600000
	},
	getDurationInDays: function (b, a) {
		return (a - b) / 86400000
	},
	getDurationInBusinessDays: function (g, b) {
		var c = Math.round((b - g) / 86400000),
			a = 0,
			f;
		for (var e = 0; e < c; e++) {
			f = this.add(g, this.DAY, e).getDay();
			if (f !== 6 && f !== 0) {
				a++
			}
		}
		return a
	},
	getDurationInMonths: function (b, a) {
		return ((a.getFullYear() - b.getFullYear()) * 12) + (a.getMonth() - b.getMonth())
	},
	getDurationInYears: function (b, a) {
		return this.getDurationInMonths(b, a) / 12
	},
	min: function (b, a) {
		return b < a ? b : a
	},
	max: function (b, a) {
		return b > a ? b : a
	},
	intersectSpans: function (c, d, b, a) {
		return this.betweenLesser(c, b, a) || this.betweenLesser(b, c, d)
	},
	getNameOfUnit: function (a) {
		switch (a.toLowerCase()) {
		case this.YEAR:
			return "YEAR";
		case this.QUARTER:
			return "QUARTER";
		case this.MONTH:
			return "MONTH";
		case this.WEEK:
			return "WEEK";
		case this.DAY:
			return "DAY";
		case this.HOUR:
			return "HOUR";
		case this.MINUTE:
			return "MINUTE";
		case this.SECOND:
			return "SECOND";
		case this.MILLI:
			return "MILLI"
		}
		throw "Incorrect UnitName"
	},
	getReadableNameOfUnit: function (b, a) {
		switch (b.toLowerCase()) {
		case this.YEAR:
			return a ? "years" : "year";
		case this.QUARTER:
			return a ? "quarters" : "quarter";
		case this.MONTH:
			return a ? "months" : "month";
		case this.WEEK:
			return a ? "weeks" : "week";
		case this.DAY:
			return a ? "days" : "day";
		case this.HOUR:
			return a ? "hours" : "hour";
		case this.MINUTE:
			return a ? "mins" : "min";
		case this.SECOND:
			return "sec";
		case this.MILLI:
			return "ms"
		}
		throw "Incorrect UnitName"
	},
	getUnitByName: function (a) {
		a = a.toUpperCase();
		if (!this[a]) {
			Ext.Error.raise("Unknown unit name")
		}
		return this[a]
	},
	getNext: function (c, f, a, e) {
		var d = Ext.Date.clone(c);
		a = a || 1;
		switch (f) {
		case this.DAY:
			Ext.Date.clearTime(d);
			d = this.add(d, this.DAY, a);
			break;
		case this.WEEK:
			var b = d.getDay();
			d = this.add(d, this.DAY, (7 * (a - 1)) + (b < e ? (e - b) : (7 - b + e)));
			break;
		case this.MONTH:
			d = this.add(d, this.MONTH, a);
			d.setDate(1);
			break;
		case this.QUARTER:
			d = this.add(d, this.MONTH, ((a - 1) * 3) + (3 - (d.getMonth() % 3)));
			break;
		case this.YEAR:
			d = new Date(d.getFullYear() + a, 0, 1);
			break;
		default:
			d = this.add(c, f, a);
			break
		}
		return d
	},
	getNumberOfMsFromTheStartOfDay: function (a) {
		return a - Ext.Date.clearTime(a, true) || 86400000
	},
	getNumberOfMsTillTheEndOfDay: function (a) {
		return this.getStartOfNextDay(a, true) - a
	},
	getStartOfNextDay: function (a, b) {
		return this.add(Ext.Date.clearTime(a, b), this.DAY, 1)
	},
	getEndOfPreviousDay: function (b) {
		var a = Ext.Date.clearTime(b, true);
		if (a - b) {
			return a
		} else {
			return this.add(a, this.DAY, -1)
		}
	}
});
Ext.define("Sch.util.DragTracker", {
	extend: "Ext.dd.DragTracker",
	xStep: 1,
	yStep: 1,
	setXStep: function (a) {
		this.xStep = a
	},
	setYStep: function (a) {
		this.yStep = a
	},
	getRegion: function () {
		var e = this.startXY,
			d = this.getXY(),
			b = Math.min(e[0], d[0]),
			f = Math.min(e[1], d[1]),
			c = Math.abs(e[0] - d[0]),
			a = Math.abs(e[1] - d[1]);
		return new Ext.util.Region(f, b + c, f + a, b)
	},
	onMouseDown: function (f, d) {
		if (this.disabled || f.dragTracked) {
			return
		}
		var c = f.getXY(),
			g, b, a = c[0],
			h = c[1];
		if (this.xStep > 1) {
			g = this.el.getX();
			a -= g;
			a = Math.round(a / this.xStep) * this.xStep;
			a += g
		}
		if (this.yStep > 1) {
			b = this.el.getY();
			h -= b;
			h = Math.round(h / this.yStep) * this.yStep;
			h += b
		}
		this.dragTarget = this.delegate ? d : this.handle.dom;
		this.startXY = this.lastXY = [a, h];
		this.startRegion = Ext.fly(this.dragTarget).getRegion();
		if (this.fireEvent("mousedown", this, f) === false || this.fireEvent("beforedragstart", this, f) === false || this.onBeforeStart(f) === false) {
			return
		}
		this.mouseIsDown = true;
		f.dragTracked = true;
		if (this.preventDefault !== false) {
			f.preventDefault()
		}
		Ext.getDoc().on({
			scope: this,
			mouseup: this.onMouseUp,
			mousemove: this.onMouseMove,
			selectstart: this.stopSelect
		});
		if (this.autoStart) {
			this.timer = Ext.defer(this.triggerStart, this.autoStart === true ? 1000 : this.autoStart, this, [f])
		}
	},
	onMouseMove: function (g, f) {
		if (this.active && Ext.isIE && !g.browserEvent.button) {
			g.preventDefault();
			this.onMouseUp(g);
			return
		}
		g.preventDefault();
		var d = g.getXY(),
			b = this.startXY;
		if (!this.active) {
			if (Math.max(Math.abs(b[0] - d[0]), Math.abs(b[1] - d[1])) > this.tolerance) {
				this.triggerStart(g)
			} else {
				return
			}
		}
		var a = d[0],
			h = d[1];
		if (this.xStep > 1) {
			a -= this.startXY[0];
			a = Math.round(a / this.xStep) * this.xStep;
			a += this.startXY[0]
		}
		if (this.yStep > 1) {
			h -= this.startXY[1];
			h = Math.round(h / this.yStep) * this.yStep;
			h += this.startXY[1]
		}
		var c = this.xStep > 1 || this.yStep > 1;
		if (!c || a !== d[0] || h !== d[1]) {
			this.lastXY = [a, h];
			if (this.fireEvent("mousemove", this, g) === false) {
				this.onMouseUp(g)
			} else {
				this.onDrag(g);
				this.fireEvent("drag", this, g)
			}
		}
	}
});
Ext.define("Sch.util.HeaderRenderers", {
	singleton: true,
	requires: ["Sch.util.Date", "Ext.XTemplate"],
	constructor: function () {
		var b = Ext.create("Ext.XTemplate", '<table class="sch-nested-hdr-tbl ' + Ext.baseCSSPrefix + 'column-header-text" cellpadding="0" cellspacing="0"><tr><tpl for="."><td style="width:{[100/xcount]}%" class="{cls} sch-dayheadercell-{dayOfWeek}">{text}</td></tpl></tr></table>').compile();
		var a = Ext.create("Ext.XTemplate", '<table class="sch-nested-hdr-tbl" cellpadding="0" cellspacing="0"><tr><tpl for="."><td style="width:{[100/xcount]}%" class="{cls}">{text}</td></tpl></tr></table>').compile();
		return {
			quarterMinute: function (f, d, c, e) {
				c.headerCls = "sch-nested-hdr-pad";
				return '<table class="sch-nested-hdr-tbl" cellpadding="0" cellspacing="0"><tr><td>00</td><td>15</td><td>30</td><td>45</td></tr></table>'
			},
			dateCells: function (d, c, e) {
				return function (j, g, f) {
					f.headerCls = "sch-nested-hdr-nopad";
					var i = [],
						h = Ext.Date.clone(j);
					while (h < g) {
						i.push({
							text: Ext.Date.format(h, e)
						});
						h = Sch.util.Date.add(h, d, c)
					}
					i[0].cls = "sch-nested-hdr-cell-first";
					i[i.length - 1].cls = "sch-nested-hdr-cell-last";
					return a.apply(i)
				}
			},
			dateNumber: function (g, d, c) {
				c.headerCls = "sch-nested-hdr-nopad";
				var f = [],
					e = Ext.Date.clone(g);
				while (e < d) {
					f.push({
						dayOfWeek: e.getDay(),
						text: e.getDate()
					});
					e = Sch.util.Date.add(e, Sch.util.Date.DAY, 1)
				}
				return b.apply(f)
			},
			dayLetter: function (g, d, c) {
				c.headerCls = "sch-nested-hdr-nopad";
				var f = [],
					e = g;
				while (e < d) {
					f.push({
						dayOfWeek: e.getDay(),
						text: Ext.Date.dayNames[e.getDay()].substr(0, 1)
					});
					e = Sch.util.Date.add(e, Sch.util.Date.DAY, 1)
				}
				f[0].cls = "sch-nested-hdr-cell-first";
				f[f.length - 1].cls = "sch-nested-hdr-cell-last";
				return b.apply(f)
			},
			dayStartEndHours: function (e, d, c) {
				c.headerCls = "sch-hdr-startend";
				return Ext.String.format('<span class="sch-hdr-start">{0}</span><span class="sch-hdr-end">{1}</span>', Ext.Date.format(e, "G"), Ext.Date.format(d, "G"))
			}
		}
	}
});
Ext.define("Sch.model.Range", {
	extend: "Ext.data.Model",
	requires: ["Sch.util.Date"],
	fields: [{
		name: "StartDate",
		type: "date",
		dateFormat: "c"
	}, {
		name: "EndDate",
		type: "date",
		dateFormat: "c"
	}, {
		name: "Cls",
		defaultValue: "sch-daterange"
	}],
	getDates: function () {
		var c = [],
			b = this.get("EndDate");
		for (var a = Ext.Date.clearTime(this.get("StartDate"), true); a < b; a = Sch.util.Date.add(a, Sch.util.Date.DAY, 1)) {
			c.push(a)
		}
		return c
	},
	forEachDate: function (b, a) {
		return Ext.each(this.getDates(), b, a)
	}
});
Ext.define("Sch.data.TimeAxis", {
	extend: "Ext.util.Observable",
	requires: ["Ext.data.JsonStore", "Sch.util.Date"],
	reverse: false,
	continuous: true,
	autoAdjust: true,
	constructor: function (a) {
		Ext.apply(this, a);
		this.originalContinuous = this.continuous;
		this.addEvents("reconfigure");
		this.tickStore = new Ext.data.JsonStore({
			sorters: {
				property: "start",
				direction: this.reverse ? "DESC" : "ASC"
			},
			fields: [{
				name: "start",
				type: "date"
			}, {
				name: "end",
				type: "date"
			}]
		});
		this.tickStore.on("datachanged", function () {
			this.fireEvent("reconfigure", this)
		}, this)
	},
	reconfigure: function (a) {
		Ext.apply(this, a);
		var c = this.tickStore,
			b = this.generateTicks(this.start, this.end, this.unit, this.increment || 1, this.mainUnit);
		c.suspendEvents(true);
		c.loadData(b);
		if (c.getCount() === 0) {
			Ext.Error.raise("Invalid time axis configuration or filter, please check your input data.")
		}
		c.resumeEvents()
	},
	setTimeSpan: function (b, a) {
		this.reconfigure({
			start: b,
			end: a
		})
	},
	getIncrement: function () {
		return this.increment
	},
	filterBy: function (b, a) {
		this.continuous = false;
		a = a || this;
		var c = this.tickStore;
		c.clearFilter(true);
		c.suspendEvents(true);
		c.filter([{
			filterFn: function (e, d) {
				return b.call(a, e.data, d)
			}
		}]);
		if (c.getCount() === 0) {
			Ext.Error.raise("Invalid time axis filter - no columns passed through the filter. Please check your filter method.");
			this.clearFilter()
		}
		c.resumeEvents()
	},
	isContinuous: function () {
		return this.continuous && !this.tickStore.isFiltered()
	},
	clearFilter: function () {
		this.continuous = this.originalContinuous;
		this.tickStore.clearFilter()
	},
	generateTicks: function (f, b, e, a) {
		var d = [],
			c;
		e = e || this.unit;
		a = a || this.increment;
		if (this.autoAdjust) {
			f = this.floorDate(f || this.getStart(), false);
			b = this.ceilDate(b || Sch.util.Date.add(f, this.mainUnit, this.defaultSpan), false)
		}
		while (f < b) {
			c = this.getNext(f, e, a);
			d.push({
				start: f,
				end: c
			});
			f = c
		}
		return d
	},
	getTickFromDate: function (c) {
		if (this.getStart() > c || this.getEnd() < c) {
			return -1
		}
		var f = this.tickStore.getRange(),
			e, a, d, b;
		if (this.reverse) {
			for (d = f.length - 1; d >= 0; d--) {
				a = f[d].data.end;
				if (c <= a) {
					e = f[d].data.start;
					return d + (c < a ? (1 - (c - e) / (a - e)) : 0)
				}
			}
		} else {
			for (d = 0, b = f.length; d < b; d++) {
				a = f[d].data.end;
				if (c <= a) {
					e = f[d].data.start;
					return d + (c > e ? (c - e) / (a - e) : 0)
				}
			}
		}
		return -1
	},
	getDateFromTick: function (d, f) {
		var g = this.tickStore.getCount();
		if (d === g) {
			return this.reverse ? this.getStart() : this.getEnd()
		}
		var a = Math.floor(d),
			e = d - a,
			c = this.getAt(a);
		var b = Sch.util.Date.add(c.start, Sch.util.Date.MILLI, (this.reverse ? (1 - e) : e) * (c.end - c.start));
		if (f) {
			b = this[f + "Date"](b)
		}
		return b
	},
	getAt: function (a) {
		return this.tickStore.getAt(a).data
	},
	getCount: function () {
		return this.tickStore.getCount()
	},
	getTicks: function () {
		var a = [];
		this.tickStore.each(function (b) {
			a.push(b.data)
		});
		return a
	},
	getStart: function () {
		return Ext.Date.clone(this.tickStore[this.reverse ? "last" : "first"]().data.start)
	},
	getEnd: function () {
		return Ext.Date.clone(this.tickStore[this.reverse ? "first" : "last"]().data.end)
	},
	roundDate: function (r) {
		var l = Ext.Date.clone(r),
			b = this.getStart(),
			s = this.resolutionIncrement;
		switch (this.resolutionUnit) {
		case Sch.util.Date.MILLI:
			var e = Sch.util.Date.getDurationInMilliseconds(b, l),
				d = Math.round(e / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.MILLI, d);
			break;
		case Sch.util.Date.SECOND:
			var i = Sch.util.Date.getDurationInSeconds(b, l),
				q = Math.round(i / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.MILLI, q * 1000);
			break;
		case Sch.util.Date.MINUTE:
			var n = Sch.util.Date.getDurationInMinutes(b, l),
				a = Math.round(n / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.SECOND, a * 60);
			break;
		case Sch.util.Date.HOUR:
			var m = Sch.util.Date.getDurationInHours(this.getStart(), l),
				j = Math.round(m / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.MINUTE, j * 60);
			break;
		case Sch.util.Date.DAY:
			var c = Sch.util.Date.getDurationInDays(b, l),
				f = Math.round(c / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.DAY, f);
			break;
		case Sch.util.Date.WEEK:
			Ext.Date.clearTime(l);
			var o = l.getDay() - this.weekStartDay,
				t;
			if (o < 0) {
				o = 7 + o
			}
			if (Math.round(o / 7) === 1) {
				t = 7 - o
			} else {
				t = -o
			}
			l = Sch.util.Date.add(l, Sch.util.Date.DAY, t);
			break;
		case Sch.util.Date.MONTH:
			var p = Sch.util.Date.getDurationInMonths(b, l) + (l.getDate() / Ext.Date.getDaysInMonth(l)),
				h = Math.round(p / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.MONTH, h);
			break;
		case Sch.util.Date.QUARTER:
			Ext.Date.clearTime(l);
			l.setDate(1);
			l = Sch.util.Date.add(l, Sch.util.Date.MONTH, 3 - (l.getMonth() % 3));
			break;
		case Sch.util.Date.YEAR:
			var k = Sch.util.Date.getDurationInYears(b, l),
				g = Math.round(k / s) * s;
			l = Sch.util.Date.add(b, Sch.util.Date.YEAR, g);
			break
		}
		return l
	},
	floorDate: function (s, d) {
		d = d !== false;
		var m = Ext.Date.clone(s),
			b = d ? this.getStart() : null,
			t = this.resolutionIncrement;
		switch (d ? this.resolutionUnit : this.mainUnit) {
		case Sch.util.Date.MILLI:
			if (d) {
				var f = Sch.util.Date.getDurationInMilliseconds(b, m),
					e = Math.floor(f / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.MILLI, e)
			}
			break;
		case Sch.util.Date.SECOND:
			if (d) {
				var j = Sch.util.Date.getDurationInSeconds(b, m),
					r = Math.floor(j / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.MILLI, r * 1000)
			} else {
				m.setMilliseconds(0)
			}
			break;
		case Sch.util.Date.MINUTE:
			if (d) {
				var o = Sch.util.Date.getDurationInMinutes(b, m),
					a = Math.floor(o / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.SECOND, a * 60)
			} else {
				m.setSeconds(0);
				m.setMilliseconds(0)
			}
			break;
		case Sch.util.Date.HOUR:
			if (d) {
				var n = Sch.util.Date.getDurationInHours(this.getStart(), m),
					k = Math.floor(n / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.MINUTE, k * 60)
			} else {
				m.setMinutes(0);
				m.setSeconds(0);
				m.setMilliseconds(0)
			}
			break;
		case Sch.util.Date.DAY:
			if (d) {
				var c = Sch.util.Date.getDurationInDays(b, m),
					g = Math.floor(c / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.DAY, g)
			} else {
				Ext.Date.clearTime(m)
			}
			break;
		case Sch.util.Date.WEEK:
			var p = m.getDay();
			Ext.Date.clearTime(m);
			if (p !== this.weekStartDay) {
				m = Sch.util.Date.add(m, Sch.util.Date.DAY, -(p > this.weekStartDay ? (p - this.weekStartDay) : (7 - p - this.weekStartDay)))
			}
			break;
		case Sch.util.Date.MONTH:
			if (d) {
				var q = Sch.util.Date.getDurationInMonths(b, m),
					i = Math.floor(q / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.MONTH, i)
			} else {
				Ext.Date.clearTime(m);
				m.setDate(1)
			}
			break;
		case Sch.util.Date.QUARTER:
			Ext.Date.clearTime(m);
			m.setDate(1);
			m = Sch.util.Date.add(m, Sch.util.Date.MONTH, -(m.getMonth() % 3));
			break;
		case Sch.util.Date.YEAR:
			if (d) {
				var l = Sch.util.Date.getDurationInYears(b, m),
					h = Math.floor(l / t) * t;
				m = Sch.util.Date.add(b, Sch.util.Date.YEAR, h)
			} else {
				m = new Date(s.getFullYear(), 0, 1)
			}
			break
		}
		return m
	},
	ceilDate: function (c, b) {
		var e = Ext.Date.clone(c);
		b = b !== false;
		var a = b ? this.resolutionIncrement : 1,
			d = b ? this.resolutionUnit : this.mainUnit,
			f = false;
		switch (d) {
		case Sch.util.Date.DAY:
			if (e.getMinutes() > 0 || e.getSeconds() > 0 || e.getMilliseconds() > 0) {
				f = true
			}
			break;
		case Sch.util.Date.WEEK:
			Ext.Date.clearTime(e);
			if (e.getDay() !== this.weekStartDay) {
				f = true
			}
			break;
		case Sch.util.Date.MONTH:
			Ext.Date.clearTime(e);
			if (e.getDate() !== 1) {
				f = true
			}
			break;
		case Sch.util.Date.QUARTER:
			Ext.Date.clearTime(e);
			if (e.getMonth() % 3 !== 0) {
				f = true
			}
			break;
		case Sch.util.Date.YEAR:
			Ext.Date.clearTime(e);
			if (e.getMonth() !== 0 && e.getDate() !== 1) {
				f = true
			}
			break;
		default:
			break
		}
		if (f) {
			return this.getNext(e, d, a)
		} else {
			return e
		}
	},
	getNext: function (b, c, a) {
		return Sch.util.Date.getNext(b, c, a, this.weekStartDay)
	},
	getResolution: function () {
		return {
			unit: this.resolutionUnit,
			increment: this.resolutionIncrement
		}
	},
	setResolution: function (b, a) {
		this.resolutionUnit = b;
		this.resolutionIncrement = a || 1
	},
	shiftNext: function (a) {
		a = a || this.getShiftIncrement();
		var b = this.getShiftUnit();
		this.setTimeSpan(Sch.util.Date.add(this.getStart(), b, a), Sch.util.Date.add(this.getEnd(), b, a))
	},
	shiftPrevious: function (a) {
		a = -(a || this.getShiftIncrement());
		var b = this.getShiftUnit();
		this.setTimeSpan(Sch.util.Date.add(this.getStart(), b, a), Sch.util.Date.add(this.getEnd(), b, a))
	},
	getShiftUnit: function () {
		return this.shiftUnit || this.getMainUnit()
	},
	getShiftIncrement: function () {
		return this.shiftIncrement || 1
	},
	getUnit: function () {
		return this.unit
	},
	getIncrement: function () {
		return this.increment
	},
	timeSpanInAxis: function (b, a) {
		if (this.continuous) {
			return Sch.util.Date.intersectSpans(b, a, this.getStart(), this.getEnd())
		} else {
			return a > b && this.getTickFromDate(b) !== this.getTickFromDate(a)
		}
	}
});
Ext.define("Sch.preset.Manager", {
	extend: "Ext.util.MixedCollection",
	requires: ["Sch.util.Date", "Sch.util.HeaderRenderers"],
	singleton: true,
	constructor: function () {
		this.callParent(arguments);
		this.registerDefaults()
	},
	registerPreset: function (b, a) {
		if (a) {
			var c = a.headerConfig;
			var d = Sch.util.Date;
			for (var e in c) {
				if (c.hasOwnProperty(e)) {
					if (d[c[e].unit]) {
						c[e].unit = d[c[e].unit.toUpperCase()]
					}
				}
			}
			if (!a.timeColumnWidth) {
				a.timeColumnWidth = 50
			}
			if (a.timeResolution && d[a.timeResolution.unit]) {
				a.timeResolution.unit = d[a.timeResolution.unit.toUpperCase()]
			}
			if (a.shiftUnit && d[a.shiftUnit]) {
				a.shiftUnit = d[a.shiftUnit.toUpperCase()]
			}
		}
		if (this.isValidPreset(a)) {
			if (this.containsKey(b)) {
				this.removeAtKey(b)
			}
			this.add(b, a)
		} else {
			throw "Invalid preset, please check your configuration"
		}
	},
	isValidPreset: function (a) {
		var d = Sch.util.Date,
			b = true,
			c = [d.MILLI, d.SECOND, d.MINUTE, d.HOUR, d.DAY, d.WEEK, d.MONTH, d.QUARTER, d.YEAR];
		for (var e in a.headerConfig) {
			if (a.headerConfig.hasOwnProperty(e)) {
				b = b && Ext.Array.indexOf(c, a.headerConfig[e].unit) >= 0
			}
		}
		if (a.timeResolution) {
			b = b && Ext.Array.indexOf(c, a.timeResolution.unit) >= 0
		}
		if (a.shiftUnit) {
			b = b && Ext.Array.indexOf(c, a.shiftUnit) >= 0
		}
		return b
	},
	getPreset: function (a) {
		return this.get(a)
	},
	deletePreset: function (a) {
		this.removeKey(a)
	},
	registerDefaults: function () {
		var b = this,
			a = this.defaultPresets;
		for (var c in a) {
			b.registerPreset(c, a[c])
		}
	},
	defaultPresets: {
		hourAndDay: {
			timeColumnWidth: 60,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "G:i",
			shiftIncrement: 1,
			shiftUnit: "DAY",
			defaultSpan: 12,
			timeResolution: {
				unit: "MINUTE",
				increment: 15
			},
			headerConfig: {
				middle: {
					unit: "HOUR",
					dateFormat: "G:i"
				},
				top: {
					unit: "DAY",
					dateFormat: "D d/m"
				}
			}
		},
		dayAndWeek: {
			timeColumnWidth: 100,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d G:i",
			shiftUnit: "DAY",
			shiftIncrement: 1,
			defaultSpan: 5,
			timeResolution: {
				unit: "HOUR",
				increment: 1
			},
			headerConfig: {
				middle: {
					unit: "DAY",
					dateFormat: "D d M"
				},
				top: {
					unit: "WEEK",
					renderer: function (c, b, a) {
						return "w." + Ext.Date.format(c, "W M Y")
					}
				}
			}
		},
		weekAndDay: {
			timeColumnWidth: 100,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftUnit: "WEEK",
			shiftIncrement: 1,
			defaultSpan: 1,
			timeResolution: {
				unit: "DAY",
				increment: 1
			},
			headerConfig: {
				bottom: {
					unit: "DAY",
					increment: 1,
					dateFormat: "d/m"
				},
				middle: {
					unit: "WEEK",
					dateFormat: "D d M",
					align: "left"
				}
			}
		},
		weekAndMonth: {
			timeColumnWidth: 100,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftUnit: "WEEK",
			shiftIncrement: 5,
			defaultSpan: 6,
			timeResolution: {
				unit: "DAY",
				increment: 1
			},
			headerConfig: {
				middle: {
					unit: "WEEK",
					renderer: function (c, b, a) {
						a.align = "left";
						return Ext.Date.format(c, "d M")
					}
				},
				top: {
					unit: "MONTH",
					dateFormat: "M Y"
				}
			}
		},
		monthAndYear: {
			timeColumnWidth: 110,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftIncrement: 3,
			shiftUnit: "MONTH",
			defaultSpan: 12,
			timeResolution: {
				unit: "DAY",
				increment: 1
			},
			headerConfig: {
				middle: {
					unit: "MONTH",
					dateFormat: "M Y"
				},
				top: {
					unit: "YEAR",
					dateFormat: "Y"
				}
			}
		},
		year: {
			timeColumnWidth: 100,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftUnit: "YEAR",
			shiftIncrement: 1,
			defaultSpan: 1,
			timeResolution: {
				unit: "MONTH",
				increment: 1
			},
			headerConfig: {
				bottom: {
					unit: "QUARTER",
					renderer: function (c, b, a) {
						return Ext.String.format("Q{0}", Math.floor(c.getMonth() / 3) + 1)
					}
				},
				middle: {
					unit: "YEAR",
					dateFormat: "Y"
				}
			}
		},
		weekAndDayLetter: {
			timeColumnWidth: 140,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftUnit: "WEEK",
			shiftIncrement: 1,
			defaultSpan: 10,
			timeResolution: {
				unit: "DAY",
				increment: 1
			},
			headerConfig: {
				bottom: {
					unit: "WEEK",
					increment: 1,
					renderer: function () {
						return Sch.util.HeaderRenderers.dayLetter.apply(this, arguments)
					}
				},
				middle: {
					unit: "WEEK",
					dateFormat: "D d M Y",
					align: "left"
				}
			}
		},
		weekDateAndMonth: {
			timeColumnWidth: 30,
			rowHeight: 24,
			resourceColumnWidth: 100,
			displayDateFormat: "Y-m-d",
			shiftUnit: "WEEK",
			shiftIncrement: 1,
			defaultSpan: 10,
			timeResolution: {
				unit: "DAY",
				increment: 1
			},
			headerConfig: {
				middle: {
					unit: "WEEK",
					dateFormat: "d"
				},
				top: {
					unit: "MONTH",
					dateFormat: "Y F",
					align: "left"
				}
			}
		}
	}
});
Ext.define("Sch.feature.AbstractTimeSpan", {
	schedulerView: null,
	timeAxis: null,
	containerEl: null,
	disabled: false,
	cls: null,
	template: null,
	store: null,
	constructor: function (a) {
		this.cls = this.cls || ("sch-timespangroup-" + Ext.id());
		Ext.apply(this, a)
	},
	setDisabled: function (a) {
		if (a) {
			this.removeElements()
		}
		this.disabled = a
	},
	getElements: function () {
		return this.containerEl.select("." + this.cls)
	},
	removeElements: function () {
		return this.getElements().remove()
	},
	init: function (a) {
		this.timeAxis = a.getTimeAxis();
		this.schedulerView = a.getSchedulingView();
		if (!this.store) {
			throw "Without a store, there's not much use for this plugin"
		}
		this.schedulerView.on({
			afterrender: this.onAfterRender,
			destroy: this.onDestroy,
			scope: this
		})
	},
	onAfterRender: function (b) {
		var a = this.schedulerView;
		this.containerEl = a.el;
		this.store.on({
			load: this.renderElements,
			datachanged: this.renderElements,
			add: this.renderElements,
			update: this.renderElements,
			remove: this.renderElements,
			clear: this.renderElements,
			scope: this
		});
		if (a.store instanceof Ext.data.NodeStore) {
			a.store.on({
				expand: this.renderElements,
				collapse: this.renderElements,
				scope: this
			})
		}
		a.on({
			itemupdate: this.renderElements,
			refresh: this.renderElements,
			scope: this
		});
		this.renderElements()
	},
	renderElements: function () {
		if (this.disabled || this.schedulerView.headerCt.getColumnCount() === 0) {
			return
		}
		Ext.Function.defer(this.renderElementsInternal, 10, this)
	},
	renderElementsInternal: function () {
		if (this.disabled || this.schedulerView.headerCt.getColumnCount() === 0) {
			return
		}
		this.removeElements();
		var e = this.timeAxis.getStart(),
			b = this.timeAxis.getEnd(),
			d = this.getElementData(e, b);
		for (var c = 0, a = d.length; c < a; c++) {
			d[c].uniquecls = this.cls
		}
		this.template.insertFirst(this.containerEl, d)
	},
	getElementData: function (c, a, b) {
		throw "Abstract method call"
	},
	onDestroy: function () {
		if (this.store.autoDestroy) {
			this.store.destroy()
		}
	}
});
Ext.define("Sch.plugin.Lines", {
	extend: "Sch.feature.AbstractTimeSpan",
	init: function (b) {
		this.callParent(arguments);
		var a = this.schedulerView;
		if (!this.template) {
			this.template = new Ext.XTemplate('<tpl for=".">', '<div title="{[this.getTipText(values)]}" class="sch-timeline {uniquecls} {Cls}" style="left:{left}px;top:{top}px;height:{height}px;width:{width}px"></div>', "</tpl>", {
				getTipText: function (c) {
					return a.getFormattedDate(c.Date) + " " + (c.Text || "")
				}
			})
		}
	},
	getElementData: function (j, m) {
		var o = this.store,
			h = this.schedulerView,
			e = o.getRange(),
			g = [],
			n = this.containerEl.getHeight(),
			a, c, k, b;
		for (var f = 0, d = o.getCount(); f < d; f++) {
			a = e[f];
			c = a.get("Date");
			if (Ext.Date.between(c, j, m)) {
				k = h.getTimeSpanRegion(c);
				g[g.length] = Ext.apply({
					left: k.left,
					top: k.top,
					width: Math.max(1, k.right - k.left),
					height: k.bottom - k.top
				}, a.data)
			}
		}
		return g
	}
});
Ext.define("Sch.plugin.Zones", {
	extend: "Sch.feature.AbstractTimeSpan",
	requires: ["Sch.model.Range"],
	init: function (a) {
		if (!this.template) {
			this.template = new Ext.XTemplate('<tpl for=".">', '<div class="sch-zone {uniquecls} {Cls}" style="left:{left}px;top:{top}px;height:{height}px;width:{width}px"></div>', "</tpl>")
		}
		this.callParent(arguments)
	},
	getElementData: function (h, m) {
		var n = this.store,
			g = this.schedulerView,
			d = n.getRange(),
			f = [],
			a, k, b, j;
		for (var e = 0, c = n.getCount(); e < c; e++) {
			a = d[e];
			k = a.get("StartDate");
			b = a.get("EndDate");
			if (Sch.util.Date.intersectSpans(k, b, h, m)) {
				j = g.getTimeSpanRegion(Sch.util.Date.max(k, h), Sch.util.Date.min(b, m));
				j.left = j.left - 1;
				f[f.length] = Ext.apply({
					left: j.left,
					top: j.top,
					width: j.right - j.left,
					height: j.bottom - j.top
				}, a.data)
			}
		}
		return f
	}
});
Ext.define("Sch.plugin.Pan", {
	alias: "plugin.pan",
	enableVerticalPan: true,
	panel: null,
	constructor: function (a) {
		Ext.apply(this, a)
	},
	init: function (a) {
		this.panel = a.lockedGrid || a;
		this.view = a.getSchedulingView();
		this.view.on("afterrender", this.onRender, this)
	},
	onRender: function (a) {
		this.view.el.on("mousedown", this.onMouseDown, this, {
			delegate: ".sch-timetd"
		})
	},
	onMouseDown: function (b, a) {
		if (b.getTarget(".sch-timetd") && !b.getTarget(".sch-gantt-item") && !b.getTarget(".sch-event")) {
			this.mouseX = b.getPageX();
			this.mouseY = b.getPageY();
			Ext.getBody().on("mousemove", this.onMouseMove, this);
			Ext.getDoc().on("mouseup", this.onMouseUp, this)
		}
	},
	onMouseMove: function (d) {
		d.stopEvent();
		var a = d.getPageX(),
			f = d.getPageY(),
			c = a - this.mouseX,
			b = f - this.mouseY;
		this.panel.scrollByDeltaX(-c);
		this.mouseX = a;
		this.mouseY = f;
		if (this.enableVerticalPan) {
			this.panel.scrollByDeltaY(-b)
		}
	},
	onMouseUp: function (a) {
		Ext.getBody().un("mousemove", this.onMouseMove, this);
		Ext.getDoc().un("mouseup", this.onMouseUp, this)
	}
});
Ext.define("Sch.feature.Scheduling", {
	extend: "Ext.grid.feature.Feature",
	alias: "feature.scheduling",
	mutateMetaRowTpl: function (a) {
		a[2] = a[2].replace("text-align: {align};", "")
	},
	getMetaRowTplFragments: function () {
		return {
			embedRowAttr: function () {
				return 'style="height:{rowHeight}px"'
			}
		}
	}
});
Ext.define("Sch.view.Locking", {
	extend: "Ext.grid.LockingView",
	scheduleEventRelayRe: /^(schedule|event|beforeevent|afterevent|dragcreate|beforedragcreate|afterdragcreate|beforetooltipshow)/,
	constructor: function (b) {
		this.callParent(arguments);
		var e = this,
			g = [],
			a = e.scheduleEventRelayRe,
			f = b.normal.getView(),
			c = f.events,
			d;
		for (d in c) {
			if (c.hasOwnProperty(d) && a.test(d)) {
				g.push(d)
			}
		}
		e.relayEvents(f, g)
	},
	getElementFromEventRecord: function (a) {
		return this.normal.getView().getElementFromEventRecord(a)
	}
});
Ext.define("Sch.column.Time", {
	extend: "Ext.grid.column.Column",
	alias: "timecolumn",
	draggable: false,
	groupable: false,
	hideable: false,
	sortable: false,
	fixed: true,
	align: "center",
	tdCls: "sch-timetd",
	menuDisabled: true,
	renderTpl: '<div id="{id}-titleContainer" class="sch-timeheader {headerCls} ' + Ext.baseCSSPrefix + 'column-header-inner"><div id="{id}-textEl" class="' + Ext.baseCSSPrefix + 'column-header-text">{text}</div></div>',
	initComponent: function () {
		this.addEvents("timeheaderdblclick");
		this.enableBubble("timeheaderdblclick");
		this.callParent()
	},
	initRenderData: function () {
		var a = this;
		Ext.applyIf(a.renderData, {
			headerCls: a.headerCls,
			id: a.id
		});
		return a.callParent(arguments)
	},
	onElDblClick: function (b, a) {
		this.callParent(arguments);
		this.fireEvent("timeheaderdblclick", this, this.startDate, this.endDate, b)
	}
});
Ext.define("Sch.column.timeAxis.Horizontal", {
	extend: "Ext.grid.column.Column",
	alias: "widget.timeaxiscolumn",
	requires: ["Ext.Date", "Sch.column.Time", "Sch.preset.Manager"],
	cls: "sch-timeaxiscolumn",
	timeAxis: null,
	renderTpl: '<div id="{id}-titleContainer" class="' + Ext.baseCSSPrefix + 'column-header-inner sch-column-header-inner"><span id="{id}-textEl" style="display:none" class="' + Ext.baseCSSPrefix + 'column-header-text"></span><tpl if="topHeaderCells">{topHeaderCells}</tpl><tpl if="middleHeaderCells">{middleHeaderCells}</tpl></div>',
	headerRowTpl: Ext.create("Ext.XTemplate", '<table border="0" cellspacing="0" cellpadding="0" style="{tstyle}" class="sch-header-row sch-header-row-{position}">', "<thead>", "<tr>{cells}</tr>", "</thead>", "</table>", {
		compiled: true
	}),
	headerCellTpl: Ext.create("Ext.XTemplate", '<tpl for=".">', '<td class="sch-column-header x-column-header {headerCls}" style="position : static; text-align: {align}; {style}" tabIndex="0" id="{headerId}" headerPosition="{position}" headerIndex="{index}">', '<div class="x-column-header-inner">{header}</div>', "</td>", "</tpl>", {
		compiled: true
	}),
	columnConfig: {},
	timeCellRenderer: Ext.emptyFn,
	timeCellRendererScope: null,
	stubForResizer: null,
	columnWidth: null,
	initComponent: function () {
		this.columns = [{}];
		this.addEvents("timeheaderdblclick", "timeaxiscolumnreconfigured");
		this.enableBubble("timeheaderdblclick");
		this.stubForResizer = new Ext.Component({
			isOnLeftEdge: function () {
				return false
			},
			isOnRightEdge: function () {
				return false
			},
			el: {
				dom: {
					style: {}
				}
			}
		});
		this.callParent(arguments);
		this.onTimeAxisReconfigure();
		this.mon(this.timeAxis, "reconfigure", this.onTimeAxisReconfigure, this)
	},
	getSchedulingView: function () {
		return this.getOwnerHeaderCt().view
	},
	onTimeAxisReconfigure: function () {
		var f = this.timeAxis,
			e = f.preset.timeColumnWidth,
			g = this.rendered && this.getSchedulingView(),
			h = f.headerConfig,
			c = f.getStart(),
			d = f.getEnd(),
			i = {
				renderer: this.timeColumnRenderer,
				scope: this,
				width: this.rendered ? g.calculateTimeColumnWidth(e) : e
			};
		var k = this.columnConfig = this.createColumns(this.timeAxis, h, i);
		this.suspendLayout = true;
		this.items.each(function (l) {
			this.remove(l)
		}, this);
		if (this.rendered) {
			var b = this.el.child(".x-column-header-inner");
			b.select("table").remove();
			var j = this.initRenderData();
			if (k.top) {
				Ext.core.DomHelper.append(b, j.topHeaderCells)
			}
			if (k.middle) {
				Ext.core.DomHelper.append(b, j.middleHeaderCells)
			}
			if (!k.top && !k.middle) {
				this.el.addCls("sch-header-single-row")
			} else {
				this.el.removeCls("sch-header-single-row")
			}
		}
		var a = 0;
		Ext.each(k.bottom, function (l) {
			a += l.width
		});
		this.width = a;
		this.add(k.bottom);
		this.height = undefined;
		this.doLayout();
		if (this.rendered) {
			if (this.fireEvent("timeaxiscolumnreconfigured", this) !== false) {
				g.refresh()
			}
		}
	},
	afterRender: function () {
		var a = this.columnConfig;
		if (!a.middle && !a.top) {
			this.el.addCls("sch-header-single-row")
		}
		this.callParent(arguments)
	},
	timeColumnRenderer: function (i, e, f, l, d, c, k) {
		var h = this.timeAxis,
			b = h.getAt(d),
			g = b.start,
			j = b.end,
			a = this.timeCellRenderer.call(this.timeCellRendererScope || this, e, f, l, d, c, g, j);
		if (Ext.isIE) {
			e.style += ";z-index:" + (this.items.getCount() - d)
		}
		return a
	},
	initRenderData: function () {
		var a = this.columnConfig;
		var c = a.top ? this.headerRowTpl.apply({
			cells: this.headerCellTpl.apply(a.top),
			position: "top",
			tstyle: "border-top : 0"
		}) : "";
		var b = a.middle ? this.headerRowTpl.apply({
			cells: this.headerCellTpl.apply(a.middle),
			position: "middle",
			tstyle: a.top ? "" : "border-top : 0"
		}) : "";
		return Ext.apply(this.callParent(arguments), {
			topHeaderCells: c,
			middleHeaderCells: b,
			id: this.id
		})
	},
	defaultRenderer: function (c, b, a) {
		return Ext.Date.format(c, a)
	},
	createColumns: function (e, g, c) {
		if (!e || !g) {
			throw "Invalid parameters passed to createColumns"
		}
		var b = [],
			k = g.bottom || g.middle,
			h = e.getTicks(),
			j;
		for (var d = 0, a = h.length; d < a; d++) {
			j = {
				align: k.align || "center",
				headerCls: "",
				startDate: h[d].start,
				endDate: h[d].end
			};
			if (k.renderer) {
				j.header = k.renderer.call(k.scope || this, h[d].start, h[d].end, j, d)
			} else {
				j.header = this.defaultRenderer(h[d].start, h[d].end, k.dateFormat)
			}
			b[b.length] = Ext.create("Sch.column.Time", Ext.apply(j, c))
		}
		var f = this.createHeaderRows(e, g);
		return {
			bottom: b,
			middle: f.middle,
			top: f.top
		}
	},
	createHeaderRows: function (e, c) {
		var d = {};
		if (c.top) {
			var a;
			if (c.top.cellGenerator) {
				a = c.top.cellGenerator.call(this, e.getStart(), e.getEnd())
			} else {
				a = this.createHeaderRow(e, c.top)
			}
			d.top = this.processHeaderRow(a, "top")
		}
		if (c.bottom) {
			var b;
			if (c.middle.cellGenerator) {
				b = c.middle.cellGenerator.call(this, e.getStart(), e.getEnd())
			} else {
				b = this.createHeaderRow(e, c.middle)
			}
			d.middle = this.processHeaderRow(b, "middle")
		}
		return d
	},
	processHeaderRow: function (c, a) {
		var b = this;
		Ext.each(c, function (d, e) {
			d.index = e;
			d.position = a;
			d.headerId = b.stubForResizer.id
		});
		return c
	},
	createHeaderRow: function (e, k) {
		var n = [],
			l, a = e.getStart(),
			c = e.getEnd(),
			m = c - a,
			j = [],
			b = a,
			d = 0,
			f, g = k.align || "center",
			h;
		while (b < c) {
			h = Sch.util.Date.min(e.getNext(b, k.unit, k.increment || 1), c);
			l = {
				align: g,
				start: b,
				end: h,
				headerCls: ""
			};
			if (k.renderer) {
				l.header = k.renderer.call(k.scope || this, b, h, l, d)
			} else {
				l.header = this.defaultRenderer(b, h, k.dateFormat, l, d)
			}
			n.push(l);
			b = h;
			d++
		}
		return n
	},
	afterLayout: function () {
		delete this.columnWidth;
		this.callParent(arguments);
		var c = this.columnConfig;
		var g = this;
		var f = this.el;
		var h = c.top;
		var d = 0;
		var b = 0;
		if (h) {
			f.select(".sch-header-row-top").setWidth(this.getWidth());
			f.select(".sch-header-row-top td").each(function (k, l, i) {
				var j = g.getHeaderGroupCellWidth(h[i].start, h[i].end);
				k.setVisibilityMode(Ext.Element.DISPLAY);
				if (j) {
					d += j;
					k.show();
					k.setWidth(j)
				} else {
					k.hide()
				}
			})
		}
		var e = c.middle;
		if (e) {
			f.select(".sch-header-row-middle").setWidth(this.getWidth());
			f.select(".sch-header-row-middle td").each(function (k, l, i) {
				var j = g.getHeaderGroupCellWidth(e[i].start, e[i].end);
				k.setVisibilityMode(Ext.Element.DISPLAY);
				if (j) {
					b += j;
					k.show();
					k.setWidth(j)
				} else {
					k.hide()
				}
			})
		}
		var a = d || b;
		if (a) {
			f.setWidth(a);
			f.select("table").each(function (i) {
				if (!i.hasCls("sch-nested-hdr-tbl")) {
					i.setWidth(a)
				}
			})
		} else {
			f.setWidth("auto");
			f.select("table").each(function (i) {
				if (!i.hasCls("sch-nested-hdr-tbl")) {
					i.setWidth("auto")
				}
			})
		}
	},
	getHeaderGroupCellWidth: function (h, b) {
		var e = this.timeAxis.unit,
			d = this.timeAxis.increment,
			c, g = Sch.util.Date.getMeasuringUnit(e),
			a = Sch.util.Date.getDurationInUnit(h, b, g),
			f = this.getSchedulingView();
		if (this.timeAxis.isContinuous()) {
			c = a * f.getSingleUnitInPixels(g) / d
		} else {
			c = f.getXYFromDate(b)[0] - f.getXYFromDate(h)[0]
		}
		if (!(Ext.isBorderBox || (Ext.isWebKit && !Ext.isSafari2))) {
			c -= 2
		}
		return c
	},
	onElDblClick: function (d, f) {
		this.callParent(arguments);
		var e = d.getTarget(".sch-column-header");
		if (e) {
			var a = Ext.fly(e).getAttribute("headerPosition"),
				b = Ext.fly(e).getAttribute("headerIndex"),
				c = this.columnConfig[a][b];
			this.fireEvent("timeheaderdblclick", this, c.start, c.end, d)
		}
	},
	getTimeColumnWidth: function () {
		if (this.columnWidth === null) {
			this.columnWidth = this.items.get(0).getWidth()
		}
		return this.columnWidth
	},
	setTimeColumnWidth: function (a) {
		this.suspendLayout = true;
		this.items.each(function (b) {
			if (b.rendered) {
				b.minWidth = undefined;
				b.setWidth(a)
			}
		});
		this.suspendLayout = false;
		this.doLayout()
	}
});
Ext.define("Sch.mixin.Lockable", {
	extend: "Ext.grid.Lockable",
	requires: ["Sch.column.timeAxis.Horizontal"],
	inheritableStatics: {
		featureCascadeMap: {
			"Ext.grid.feature.Grouping": "both"
		},
		pluginCascadeMap: {
			"Ext.grid.plugin.Editing": "locked"
		}
	},
	processPlugins: function (d) {
		d = d || [];
		var j = this,
			b = j.statics().pluginCascadeMap,
			g = d.length,
			f = [],
			h = [],
			a = 0,
			e = 0;
		for (e = g - 1; e >= 0; e--) {
			for (var c in b) {
				if (!d[e].ptype) {
					if (d[e] instanceof Ext.ClassManager.get(c)) {
						if (b[c] === "locked" || b[c] === "both") {
							f.push(d[e])
						}
						if (b[c] === "normal" || b[c] === "both") {
							h.push(new d[e].self())
						}
						Ext.Array.remove(d, d[e])
					}
				} else {
					h.push(Ext.clone(d[e]))
				}
			}
		}
		return {
			locked: f,
			normal: h
		}
	},
	processFeatures: function (d) {
		d = d || [];
		var j = this,
			b = j.statics().featureCascadeMap,
			g = d.length,
			f = [],
			h = [],
			a = 0,
			e = 0;
		for (e = g - 1; e >= 0; e--) {
			for (var c in b) {
				if (d[e].isFeature) {
					if (d[e] instanceof Ext.ClassManager.get(c)) {
						if (b[c] === "locked" || b[c] === "both") {
							f.push(d[e])
						}
						if (b[c] === "normal" || b[c] === "both") {
							h.push(new d[e].self())
						}
						Ext.Array.remove(d, d[e])
					}
				} else {
					h.push(Ext.clone(d[e]))
				}
			}
		}
		return {
			locked: f,
			normal: h
		}
	},
	injectLockable: function () {
		this.createSpacer = this.createSpacer || Ext.emptyFn;
		var k = this.processFeatures(this.features),
			q = this.processPlugins(this.plugins);
		this.hasView = true;
		var u = this;
		var n = u.store instanceof Ext.data.TreeStore;
		var g = u.store.buffered;
		u.normalViewConfig = u.normalViewConfig || {};
		u.lockedViewConfig = u.lockedViewConfig || {};
		var m = u.getSelectionModel(),
			j = u.getEventSelectionModel ? u.getEventSelectionModel() : m,
			s = Ext.applyIf({
				xtype: u.lockedXType,
				columnLines: u.columnLines,
				lockable: false,
				useArrows: true,
				invalidateScroller: Ext.emptyFn,
				resetScrollbars: Ext.emptyFn,
				determineScrollbars: Ext.emptyFn,
				enableAnimations: false,
				scroll: false,
				scrollerOwner: false,
				selModel: m,
				border: false,
				cls: Ext.baseCSSPrefix + "grid-inner-locked",
				plugins: q.locked,
				features: k.locked
			}, u.lockedGridConfig || {}),
			h = Ext.applyIf({
				xtype: u.normalXType,
				columnLines: u.columnLines,
				lockable: false,
				verticalScroller: u.verticalScroller,
				selModel: j,
				border: false,
				enableAnimations: false,
				scrollerOwner: false,
				trackOver: false,
				_top: u,
				orientation: this.orientation,
				weekStartDay: this.weekStartDay,
				viewPreset: u.viewPreset || "hourAndDay",
				timeAxis: u.timeAxis,
				startDate: u.startDate,
				endDate: u.endDate,
				plugins: q.normal,
				features: k.normal
			}, u.schedulerConfig || {}),
			o = 0,
			a, d, r;
		delete u.verticalScroller;
		u.addCls(Ext.baseCSSPrefix + "grid-locked");
		Ext.copyTo(h, u, u.normalCfgCopy);
		Ext.copyTo(s, u, u.lockedCfgCopy);
		for (; o < u.normalCfgCopy.length; o++) {
			delete u[u.normalCfgCopy[o]]
		}
		for (o = 0; o < u.lockedCfgCopy.length; o++) {
			delete u[u.lockedCfgCopy[o]]
		}
		u.lockedHeights = [];
		u.normalHeights = [];
		if (u.orientation === "vertical") {
			s.columns = [Ext.create("Sch.column.timeAxis.Vertical", Ext.apply({
				width: 100,
				timeAxis: u.timeAxis,
				locked: true
			}, this.timeAxisColumnCfg || {}))];
			a = u.processColumns(s.columns);
			s.store = h.store = u.timeAxis.tickStore;
			h.resourceStore = u.resourceStore
		} else {
			h.resourceStore = u.resourceStore;
			a = u.processColumns(u.columns);
			s.store = h.store = u.store;
			s.columns = a.locked;
			h.columns = [{
				xtype: "timeaxiscolumn",
				timeAxis: u.timeAxis,
				viewPreset: u.viewPreset || "hourAndDay",
				timeCellRenderer: u.timeCellRenderer,
				timeCellRendererScope: u.timeCellRendererScope
			}].concat(a.right)
		}
		if (!s.flex && !s.width) {
			s.width = a.lockedWidth
		}
		if (u.eventStore) {
			h.eventStore = u.eventStore
		}
		if (u.dependencyStore) {
			h.dependencyStore = u.dependencyStore
		}
		if (u.layout === "fit") {
			u.layout = {
				type: "hbox",
				align: "stretch"
			};
			h.flex = h.flex || 1
		}
		s.viewConfig = u.lockedViewConfig;
		s.viewConfig.loadingUseMsg = false;
		h.viewConfig = u.normalViewConfig;
		Ext.applyIf(s.viewConfig, u.viewConfig);
		Ext.applyIf(h.viewConfig, u.viewConfig);
		if (n && g) {
			s.viewConfig.storeConfig = {
				buffered: true,
				pageSize: u.store.pageSize || 50,
				purgePageCount: 0,
				refreshFromTree: function () {
					var x = this.eventsSuspended;
					this.suspendEvents();
					this.removeAll();
					this.prefetchData.clear();
					var w = u.store.getRootNode(),
						y = [];
					var i = function (C, B) {
							B(C);
							if (C.isExpanded()) {
								var D = C.childNodes,
									A = D.length;
								for (var z = 0; z < A; z++) {
									i(D[z], B)
								}
							}
						};
					i(w, function (z) {
						if (z != w) {
							y.push(z)
						}
					});
					this.cacheRecords(y);
					this.totalCount = this.prefetchData.getCount();
					if (!x) {
						this.resumeEvents()
					}
				}
			}
		}
		u.lockedGrid = Ext.ComponentManager.create(s);
		var e = u.lockedGrid.getView();
		if (n) {
			h.viewConfig.providedStore = e.store;
			if (g && h.verticalScroller) {
				h.verticalScroller.store = e.store
			}
		}
		u.normalGrid = Ext.ComponentManager.create(h);
		u.view = Ext.create("Sch.view.Locking", {
			locked: u.lockedGrid,
			normal: u.normalGrid,
			panel: u
		});
		u.onLockedGridAfterLayout();
		var v = u.normalGrid.getView();
		var p = v.store;
		if (u.orientation === "vertical") {
			this.refreshResourceColumns(true)
		}
		if (u.syncRowHeight) {
			e.on({
				refresh: u.onLockedGridAfterRefresh,
				itemupdate: u.onLockedGridAfterUpdate,
				scope: u
			});
			v.on({
				refresh: u.onNormalGridAfterRefresh,
				itemupdate: u.onNormalGridAfterUpdate,
				itemadd: u.onNormalGridAfterAdd,
				afterrender: u.createSpacer,
				scope: u
			})
		}
		d = u.lockedGrid.headerCt;
		r = u.normalGrid.headerCt;
		d.lockedCt = true;
		d.lockableInjected = true;
		r.lockableInjected = true;
		d.on({
			columnshow: u.onLockedHeaderShow,
			columnhide: u.onLockedHeaderHide,
			columnmove: u.onLockedHeaderMove,
			sortchange: u.onLockedHeaderSortChange,
			columnresize: u.onLockedHeaderResize,
			scope: u
		});
		u.normalGrid.on({
			scrollershow: u.onScrollerShow,
			scrollerhide: u.onScrollerHide,
			scope: u
		});
		u.normalGrid.headerCt.on("afterlayout", this.onSchedulerGridAfterHeaderLayout, this);
		u.modifyHeaderCt();
		u.items = [u.lockedGrid, u.normalGrid];
		var t;
		var c;
		if (n) {
			var l = u.store;
			var b = function (z, y) {
					var w = l.pageSize || 50;
					var i = p.prefetchData.getCount();
					if (i) {
						var x = y - z + 1;
						if (x < w && i >= x) {
							y = z + w - 1
						}
						if (y > i) {
							z = i - (y - z);
							y = i - 1;
							z = Math.max(0, z)
						}
						p.guaranteeRange(z, y)
					}
				};
			l.on("root-fill-start", function () {
				c = true;
				p.suspendEvents();
				if (g) {
					t = p.node;
					p.setNode()
				}
			});
			l.on("root-fill-end", function () {
				c = false;
				if (g) {
					p.refreshFromTree();
					p.resumeEvents();
					b(0, (l.pageSize || 50) - 1);
					u.refreshView()
				} else {
					p.resumeEvents();
					e.refresh();
					v.refresh()
				}
			});
			if (g) {
				p.on("bufferchange", function () {
					u.refreshView()
				});
				var f = function () {
						if (c) {
							return
						}
						p.refreshFromTree();
						var w = p.guaranteedStart,
							i = p.guaranteedEnd;
						delete p.guaranteedStart;
						delete p.guaranteedEnd;
						b(w, i);
						u.refreshView()
					};
				l.on({
					append: f,
					insert: f,
					remove: f,
					move: f,
					expand: f,
					collapse: f,
					sort: f,
					buffer: 1
				})
			}
			l.on("filter", function (w, i) {
				p.filter.apply(p, i);
				u.refreshView()
			});
			l.on("clearfilter", function (w, i) {
				p.clearFilter();
				u.refreshView()
			});
			l.on("beforecascade", function (w, i) {
				p.suspendEvents()
			});
			l.on("cascade", function (w, i) {
				p.resumeEvents();
				u.refreshView()
			})
		}
		e.on({
			groupexpand: this.onViewGroupExpand,
			groupcollapse: this.onViewGroupCollapse,
			scope: v
		});
		v.on({
			groupexpand: this.onViewGroupExpand,
			groupcollapse: this.onViewGroupCollapse,
			scope: e
		});
		u.on("afterlayout", this.onLockableGridAfterLayout, this, {
			single: true
		})
	},
	onViewGroupExpand: function (b, d) {
		var c = Ext.grid.feature.Grouping.prototype,
			a = Ext.get(this.id + "-gp-" + d);
		a.removeCls(c.collapsedCls);
		a.prev().removeCls(c.hdCollapsedCls)
	},
	onViewGroupCollapse: function (b, d) {
		var c = Ext.grid.feature.Grouping.prototype,
			a = Ext.get(this.id + "-gp-" + d);
		a.addCls(c.collapsedCls);
		a.prev().addCls(c.hdCollapsedCls)
	},
	onLockableGridAfterLayout: function () {
		var a = this.lockedGrid.getView().height;
		if (!a || a == 1) {
			this.doLayout()
		}
	},
	onNormalGridAfterUpdate: function (a, b, c) {
		this.lockedGrid.getView().onUpdate(this.lockedGrid.store, a);
		this.normalHeights[b] = c.clientHeight;
		this.syncRowHeights()
	},
	onNormalGridAfterAdd: function (c, d, b) {
		var a = this.normalHeights;
		var e = this.lockedGrid.getView();
		Ext.each(c, function (g, f) {
			a[e.getNode(g).viewIndex] = b[f].offsetHeight
		});
		this.syncRowHeights()
	},
	processColumns: function (b) {
		var a = this.callParent(arguments);
		var c = [];
		Ext.each(b, function (d) {
			if (d.position == "right") {
				if (!Ext.isNumber(d.width)) {
					Ext.Error.raise('"Right" columns must have a fixed width')
				}
				c.push(d);
				Ext.Array.remove(a.locked, d);
				a.lockedWidth -= d.width
			}
			if (d.locked && d.hidden) {
				a.lockedWidth -= d.width || Ext.grid.header.Container.prototype.defaultWidth
			}
		});
		a.right = c;
		return a
	},
	onNormalGridAfterRefresh: function () {
		if (this.normalGrid.layout.layoutBusy) {
			return
		}
		return this.callParent(arguments)
	},
	syncRowHeights: function () {
		var j = this,
			b = j.lockedHeights,
			k = j.normalHeights,
			a = [],
			h = b.length || k.length,
			f = 0,
			m, c, d, g, e = j.getVerticalScroller();
		if (b.length || k.length) {
			m = j.lockedGrid.getView();
			c = j.normalGrid.getView();
			if (!m.rendered || !c.rendered) {
				return
			}
			d = m.el.query(m.getItemSelector());
			if (!d.length) {
				return
			}
			var l = (Ext.isIE6 || Ext.isIE7) && Ext.isStrict ? 2 : 0;
			for (; f < h; f++) {
				if (!isNaN(k[f])) {
					Ext.fly(d[f]).setHeight(k[f] - l)
				}
			}
			j.normalGrid.determineScrollbars();
			j.normalGrid.invalidateScroller();
			if (e && e.setViewScrollTop) {
				e.setViewScrollTop(j.virtualScrollTop)
			} else {
				j.setScrollTop(m.el.dom.scrollTop)
			}
			j.lockedHeights = [];
			j.normalHeights = []
		}
	},
	getMenuItems: function () {
		return function () {
			return Ext.grid.header.Container.prototype.getMenuItems.call(this)
		}
	},
	onSchedulerGridAfterHeaderLayout: function () {
		var b = this.lockedGrid.headerCt;
		var a = this.normalGrid.headerCt;
		if (Ext.versions.extjs.isGreaterThan("4.0.5")) {
			b.setSize(b.getWidth(), a.getHeight());
			if (b.rendered) {
				b.el.down(".x-box-inner").setHeight("100%")
			}
		} else {
			b.items.each(function (c) {
				if (c.rendered) {
					c.el.down(".x-column-header-inner").setHeight("auto")
				}
			});
			b.layout.align = "stretch";
			b.height = undefined;
			delete this.lockedGrid.componentLayout.lastComponentSize;
			b.setSize(b.getWidth(), a.getHeight());
			b.items.each(function (c) {
				if (c.rendered) {
					c.setPadding()
				}
			})
		}
	},
	refreshView: function () {
		this.lockedGrid.getView().refresh();
		this.normalGrid.getView().refresh()
	}
});
Ext.define("Sch.Overrides", {
	requires: ["Sch.mixin.Lockable", "Ext.tree.View", "Ext.grid.plugin.Editing", "Ext.grid.plugin.CellEditing"]
}, function () {
	Ext.Base.prototype.statics = function () {
		var b = this.statics.caller,
			a = this.self;
		if (!b) {
			return a
		}
		return b.$owner || b.caller.$owner
	};
	Ext.grid.plugin.Editing.override({
		startEditByClick: function (c, a, i, b, g, d, f) {
			var h = c.getHeaderAtIndex(i);
			if (h.getEditor && h.getEditor(b)) {
				this.startEdit(b, h)
			}
		}
	});
	Ext.grid.plugin.CellEditing.override({
		getEditor: function (a, b) {
			if (!b.getEditor) {
				return false
			}
			return this.callOverridden(arguments)
		},
		startEdit: function (a, b) {
			if (!b.getEditor) {
				return false
			}
			this.callOverridden(arguments)
		}
	});
	Ext.tree.Panel.override({
		isContainerPanel: function () {
			return !this.items || !! this.child("treepanel")
		},
		onRootChange: function (a) {
			if (!this.isContainerPanel()) {
				this.callOverridden(arguments)
			}
		},
		constructor: function (a) {
			a = a || {};
			this.enableAnimations = false;
			delete a.animate;
			this.callParent([a]);
			this.onRootChange(this.store.getRootNode())
		}
	});
	Ext.panel.Table.override({
		elScroll: function (d, e, b) {
			var c = this,
				a;
			if (d === "up" || d === "left") {
				e = -e
			}
			if (d === "down" || d === "up") {
				a = c.getVerticalScroller();
				a && a.scrollByDeltaY(e)
			} else {
				a = c.getHorizontalScroller();
				a && a.scrollByDeltaX(e)
			}
		}
	});
	Ext.data.reader.Xml.override({
		extractData: function (a) {
			var b = this.record;
			if (b != a.nodeName) {
				a = Ext.DomQuery.select(">" + b, a)
			} else {
				a = [a]
			}
			return this.callParent([a])
		}
	});
	Ext.data.TreeStore.override({
		onNodeAdded: function (d, e) {
			var c = this.getProxy(),
				a = c.getReader(),
				f = e.raw || e.data,
				g, b;
			Ext.Array.remove(this.removed, e);
			if (!e.isLeaf() && !e.isLoaded()) {
				g = a.getRoot(f);
				if (g) {
					this.fillNode(e, a.extractData(g));
					if (f[a.root]) {
						delete f[a.root]
					}
				}
			}
		}
	});
	Ext.panel.Table.override({
		scrollByDeltaX: function (a) {
			var b = this.getHorizontalScroller();
			if (b) {
				b.scrollByDeltaX(a)
			}
		}
	});
	if (Ext.isIE9 && Ext.isStrict) {
		Sch.mixin.Lockable.override({
			onNormalGridAfterRefresh: function () {
				var e = this,
					a = e.normalGrid.getView(),
					c = a.el,
					f = c.query(a.getItemSelector()),
					d = f.length,
					b = 0;
				e.normalHeights = [];
				for (; b < d; b++) {
					e.normalHeights[b] = f[b].offsetHeight
				}
				e.syncRowHeights()
			},
			onNormalGridAfterUpdate: function (a, b, c) {
				this.lockedGrid.getView().onUpdate(this.lockedGrid.store, a);
				this.normalHeights[b] = c.offsetHeight;
				this.syncRowHeights()
			}
		})
	}
	Ext.grid.feature.Grouping.override({
		expand: function (c) {
			var e = this,
				b = e.view,
				d = b.up("gridpanel"),
				a = Ext.getDom(c),
				f = a.id.match(b.id + "-gp-(.*)")[1];
			e.collapsedState[a.id] = false;
			c.removeCls(e.collapsedCls);
			c.prev().removeCls(e.hdCollapsedCls);
			d.determineScrollbars();
			d.invalidateScroller();
			b.fireEvent("groupexpand", b, f)
		},
		collapse: function (c) {
			var e = this,
				b = e.view,
				d = b.up("gridpanel"),
				a = Ext.getDom(c),
				f = a.id.match(b.id + "-gp-(.*)")[1];
			e.collapsedState[a.id] = true;
			c.addCls(e.collapsedCls);
			c.prev().addCls(e.hdCollapsedCls);
			d.determineScrollbars();
			d.invalidateScroller();
			b.fireEvent("groupcollapse", b, f)
		}
	});
	Ext.tree.View.override({
		providedStore: null,
		storeConfig: null,
		initComponent: function () {
			var a = this;
			if (a.initialConfig.animate === undefined) {
				a.animate = Ext.enableFx
			}
			a.store = a.providedStore || Ext.create("Ext.data.NodeStore", Ext.apply({
				recursive: true,
				rootVisible: a.rootVisible,
				listeners: {
					beforeexpand: a.onBeforeExpand,
					expand: a.onExpand,
					beforecollapse: a.onBeforeCollapse,
					collapse: a.onCollapse,
					scope: a
				}
			}, a.storeConfig || {}));
			if (a.node) {
				a.setRootNode(a.node)
			}
			a.animQueue = {};
			a.callParent(arguments)
		}
	});
	if (Ext.versions.extjs.isGreaterThan("4.0.5")) {
		Ext.LoadMask.override({
			onBeforeLoad: function () {
				var b = this,
					a = b.ownerCt || b.floatParent;
				if (!this.disabled) {
					if (a.componentLayoutCounter) {
						Ext.Component.prototype.show.call(b)
					} else {
						a.afterComponentLayout = Ext.Function.createSequence(a.afterComponentLayout, function () {
							if (!b.hidden) {
								Ext.Component.prototype.show.call(b)
							}
							delete a.afterComponentLayout
						})
					}
				}
			}
		})
	}
});
(function () {
	var a = Ext.data.NodeInterface.getPrototypeBody;
	Ext.data.NodeInterface.getPrototypeBody = function () {
		var b = a.apply(Ext.data.NodeInterface, arguments);
		Ext.apply(b, {
			updateInfo: function (n) {
				var o = this,
					e = o.isRoot(),
					l = o.parentNode,
					g = (!l ? true : l.firstChild == o),
					k = (!l ? true : l.lastChild == o),
					j = 0,
					p = o,
					d = o.childNodes,
					m = d.length,
					h = 0;
				while (p.parentNode) {
					++j;
					p = p.parentNode
				}
				o.beginEdit();
				o.set({
					isFirst: g,
					isLast: k,
					depth: j,
					index: l ? l.indexOf(o) : 0,
					parentId: l ? l.getId() : null
				});
				o.endEdit(n);
				if (n) {
					o.commit()
				}
				for (h = 0; h < m; h++) {
					d[h].updateInfo(n)
				}
				var c = o;
				var f = c.nextSibling;
				while (f && f.get("index") !== c.get("index") + 1) {
					f.beginEdit();
					f.set("index", c.get("index") + 1);
					f.endEdit(n);
					if (n) {
						f.commit()
					}
					c = f;
					f = c.nextSibling
				}
			},
			sort: function (h, d, c) {
				var f = this.childNodes,
					g = f.length,
					e, j;
				if (g > 0) {
					Ext.Array.sort(f, h);
					for (e = 0; e < g; e++) {
						j = f[e];
						j.previousSibling = f[e - 1];
						j.nextSibling = f[e + 1]
					}
					for (e = 0; e < g; e++) {
						j = f[e];
						if (e === 0) {
							this.setFirstChild(j);
							j.updateInfo()
						}
						if (e == g - 1) {
							this.setLastChild(j);
							j.updateInfo()
						}
						if (d && !j.isLeaf()) {
							j.sort(h, true, true)
						}
					}
					if (c !== true) {
						this.fireEvent("sort", this, f)
					}
				}
			}
		});
		return b
	}
})();
Ext.define("Sch.mixin.TimelineView", {
	requires: ["Sch.column.Time", "Sch.data.TimeAxis"]
}, function () {
	Sch.mixin.TimelineView.Config = {
		forceFit: false,
		orientation: "horizontal",
		overScheduledEventClass: "sch-event-hover",
		selectedEventCls: "sch-event-selected",
		cellBorderWidth: 1,
		altColCls: "sch-col-alt",
		inheritableStatics: {
			ScheduleEventMap: {
				click: "Click",
				dblclick: "DblClick",
				contextmenu: "ContextMenu",
				keydown: "KeyDown"
			}
		},
		suppressFitCheck: 0,
		constructor: function (a) {
			var b = a.panel._top;
			Ext.apply(this, {
				eventRenderer: b.eventRenderer,
				eventBorderWidth: b.eventBorderWidth,
				timeAxis: b.timeAxis,
				dndValidatorFn: b.dndValidatorFn || Ext.emptyFn,
				resizeValidatorFn: b.resizeValidatorFn || Ext.emptyFn,
				createValidatorFn: b.createValidatorFn || Ext.emptyFn,
				tooltipTpl: b.tooltipTpl,
				weekStartDay: b.weekStartDay,
				validatorFnScope: b.validatorFnScope || this,
				snapToIncrement: b.snapToIncrement,
				timeCellRenderer: b.timeCellRenderer,
				timeCellRendererScope: b.timeCellRendererScope,
				readOnly: b.readOnly,
				eventResizeHandles: b.eventResizeHandles,
				enableEventDragDrop: b.enableEventDragDrop,
				enableDragCreation: b.enableDragCreation,
				dragDropConfig: b.dragDropConfig,
				resizeConfig: b.resizeConfig,
				createConfig: b.createConfig,
				tipCfg: b.tipCfg,
				orientation: b.orientation,
				getDateConstraints: b.getDateConstraints || Ext.emptyFn
			});
			this.callParent(arguments)
		},
		initComponent: function () {
			this.setOrientation(this.orientation);
			this.addEvents("beforetooltipshow", "scheduleclick", "scheduledblclick", "schedulecontextmenu", "columnwidthchange");
			this.enableBubble("columnwidthchange");
			var a = {},
				b = Sch.util.Date;
			a[b.DAY] = a[b.WEEK] = a[b.MONTH] = a[b.QUARTER] = a[b.YEAR] = null;
			Ext.applyIf(this, {
				eventPrefix: this.id + "-",
				largeUnits: a
			});
			this.callParent(arguments);
			if (this.orientation === "horizontal") {
				this.getTimeAxisColumn().on("timeaxiscolumnreconfigured", this.checkHorizontalFit, this)
			}
		},
		initFeatures: function () {
			this.features = this.features || [];
			this.features.push({
				ftype: "scheduling"
			});
			this.callParent(arguments)
		},
		hasRightColumns: function () {
			return this.headerCt.items.getCount() > 1
		},
		checkHorizontalFit: function () {
			if (this.orientation === "horizontal") {
				var a = this.getActualTimeColumnWidth();
				var c = this.getFittingColumnWidth();
				if (this.forceFit) {
					if (c != a || this.hasRightColumns()) {
						this.fitColumns()
					}
				} else {
					if (this.snapToIncrement) {
						var b = this.calculateTimeColumnWidth(a);
						if (b !== a) {
							this.setColumnWidth(b)
						}
					} else {
						if (a < c) {
							this.fitColumns()
						}
					}
				}
			}
		},
		onDestroy: function () {
			if (this.tip) {
				this.tip.destroy()
			}
			this.callParent(arguments)
		},
		afterComponentLayout: function () {
			this.callParent(arguments);
			if (!this.lockable && !this.suppressFitCheck) {
				this.checkHorizontalFit()
			}
		},
		getTimeAxisColumn: function () {
			return this.headerCt.items.get(0)
		},
		getNumberOfTimeColumns: function () {
			return this.timeAxis.getCount()
		},
		getFirstTimeColumn: function () {
			return this.headerCt.getGridColumns()[0]
		},
		getFormattedDate: function (a) {
			return Ext.Date.format(a, this.getDisplayDateFormat())
		},
		getFormattedEndDate: function (d, a) {
			var b = this.timeAxis,
				c = b.getResolution().unit;
			if (c in this.largeUnits && d.getHours() === 0 && d.getMinutes() === 0 && !(d.getYear() === a.getYear() && d.getMonth() === a.getMonth() && d.getDate() === a.getDate())) {
				d = Sch.util.Date.add(d, Sch.util.Date.DAY, -1)
			}
			return Ext.Date.format(d, this.getDisplayDateFormat())
		},
		getDisplayDateFormat: function () {
			return this.displayDateFormat
		},
		setDisplayDateFormat: function (a) {
			this.displayDateFormat = a
		},
		getSingleUnitInPixels: function (a) {
			return Sch.util.Date.getUnitToBaseUnitRatio(this.timeAxis.getUnit(), a) * this.getSingleTickInPixels()
		},
		getSingleTickInPixels: function () {
			throw "Must be implemented by horizontal/vertical"
		},
		scrollEventIntoView: function (f, b) {
			var c = this.getOuterElementFromEventRecord(f);
			if (c) {
				var a = this.up("[scrollerOwner]"),
					e = a.getHorizontalScroller(),
					g = a.getVerticalScroller();
				var d = c.up(this.getItemSelector());
				e && e.setScrollLeft(c.getLeft(true));
				g && g.setScrollTop(d.dom.offsetTop);
				if (b) {
					if (typeof b === "boolean") {
						c.highlight()
					} else {
						c.highlight(null, b)
					}
				}
			}
		},
		calculateTimeColumnWidth: function (e) {
			if (!this.panel.rendered) {
				return e
			}
			var b = 0,
				d = this.timeAxis.getUnit(),
				j = this.timeAxis.getCount(),
				g = Number.MAX_VALUE;
			if (this.snapToIncrement) {
				var h = this.timeAxis.getResolution(),
					i = h.unit,
					c = h.increment;
				g = Sch.util.Date.getUnitToBaseUnitRatio(d, i) * c
			}
			var f = Sch.util.Date.getMeasuringUnit(d);
			g = Math.min(g, Sch.util.Date.getUnitToBaseUnitRatio(d, f));
			var a = Math.floor(this.getAvailableWidthForSchedule() / j);
			b = this.forceFit || e < a ? a : e;
			if (!this.forceFit || g < 1) {
				b = Math.round(Math[this.forceFit ? "floor" : "round"](g * b) / g)
			}
			return b
		},
		getFittingColumnWidth: function () {
			var a = Math.floor(this.getAvailableWidthForSchedule() / this.getNumberOfTimeColumns());
			return this.calculateTimeColumnWidth(a)
		},
		fitColumns: function (a) {
			this.setColumnWidth(this.getFittingColumnWidth(), a)
		},
		getAvailableWidthForSchedule: function () {
			var b = this.panel.getWidth();
			for (var a = 1; a < this.headerCt.items.getCount(); a++) {
				b -= this.headerCt.items.get(a).getWidth()
			}
			return b - Ext.getScrollBarWidth() - 1
		},
		getElementFromEventRecord: function (a) {
			return Ext.get(this.eventPrefix + a.internalId)
		},
		getEventNodeByRecord: function (a) {
			return document.getElementById(this.eventPrefix + a.internalId)
		},
		getOuterElementFromEventRecord: function (a) {
			return Ext.get(this.eventPrefix + a.internalId)
		},
		isTimeHeaderCell: function (a) {
			return !!Ext.fly(a).up("td.sch-timeheader")
		},
		resolveColumnIndex: function (a) {
			return Math.floor(a / this.getActualTimeColumnWidth())
		},
		getStartEndDatesFromRegion: function (b, a) {
			throw "Must be implemented by horizontal/vertical"
		},
		onRender: function () {
			this.callParent(arguments);
			this.el.addCls("sch-timelineview");
			if (this.readOnly) {
				this.el.addCls(this._cmpCls + "-readonly")
			}
			if (this.overScheduledEventClass) {
				this.mon(this.el, {
					mouseover: this.onMouseOver,
					mouseout: this.onMouseOut,
					delegate: this.eventSelector,
					scope: this
				})
			}
			if (this.tooltipTpl) {
				this.setupTooltip()
			}
			this.setupTimeCellEvents()
		},
		setupTooltip: function () {
			var b = this,
				a = Ext.apply({
					renderTo: Ext.getBody(),
					delegate: b.eventSelector,
					target: b.el,
					anchor: "b",
					listeners: {
						beforeshow: {
							fn: function (d) {
								if (!d.triggerElement || !d.triggerElement.id) {
									return false
								}
								var c = this.resolveEventRecord(d.triggerElement);
								if (!c || this.fireEvent("beforetooltipshow", this, c) === false) {
									return false
								}
								d.update(this.tooltipTpl.apply(this.getDataForTooltipTpl(c)));
								return true
							},
							scope: this
						}
					}
				}, b.tipCfg);
			b.tip = Ext.create("Ext.ToolTip", a)
		},
		getDataForTooltipTpl: function (a) {
			return a.data
		},
		getTimeResolution: function () {
			return this.timeAxis.getResolution()
		},
		setTimeResolution: function (b, a) {
			this.timeAxis.setResolution(b, a);
			if (this.snapToIncrement) {
				this.refresh(true)
			}
		},
		getEventIdFromDomNodeId: function (a) {
			return a.substring(this.eventPrefix.length)
		},
		getDateFromDomEvent: function (b, a) {
			return this.getDateFromXY(b.getXY(), a)
		},
		setupTimeCellEvents: function () {
			this.mon(this.el, {
				click: function (c) {
					var b = c.getTarget("td.sch-timetd", 2);
					if (b) {
						var a = this.getDateFromDomEvent(c, "floor");
						this.fireEvent("scheduleclick", this, a, this.indexOf(this.findItemByChild(b)), c)
					}
				},
				dblclick: function (c) {
					var b = c.getTarget("td.sch-timetd", 2);
					if (b) {
						var a = this.getDateFromDomEvent(c, "floor");
						this.fireEvent("scheduledblclick", this, a, this.indexOf(this.findItemByChild(b)), c)
					}
				},
				contextmenu: function (c) {
					var b = c.getTarget("td.sch-timetd", 2);
					if (b) {
						var a = this.getDateFromDomEvent(c, "floor");
						this.fireEvent("schedulecontextmenu", this, a, this.indexOf(this.findItemByChild(b)), c)
					}
				},
				scope: this
			}, this)
		},
		getSnapPixelAmount: function () {
			if (this.snapToIncrement) {
				var a = this.timeAxis.getResolution();
				return (a.increment || 1) * this.getSingleUnitInPixels(a.unit)
			} else {
				return 1
			}
		},
		getActualTimeColumnWidth: function () {
			return this.headerCt.items.get(0).getTimeColumnWidth()
		},
		setSnapEnabled: function (a) {
			this.snapToIncrement = a;
			if (a) {
				this.refresh(true)
			}
		},
		setReadOnly: function (a) {
			this.readOnly = a;
			this.el[a ? "addCls" : "removeCls"](this._cmpCls + "-readonly")
		},
		setOrientation: function (a) {
			this.orientation = a;
			Ext.apply(this, Sch.view[a.substr(0, 1).toUpperCase() + a.substring(1)].prototype.props)
		},
		getOrientation: function () {
			return this.orientation
		},
		onMouseOver: function (b, a) {
			if (a !== this.lastItem) {
				this.lastItem = a;
				Ext.fly(a).addCls(this.overScheduledEventClass)
			}
		},
		onMouseOut: function (b, a) {
			if (this.lastItem) {
				if (!b.within(this.lastItem, true, true)) {
					Ext.fly(this.lastItem).removeCls(this.overScheduledEventClass);
					delete this.lastItem
				}
			}
		},
		highlightItem: function (b) {
			if (b) {
				var a = this;
				a.clearHighlight();
				a.highlightedItem = b;
				Ext.fly(b).addCls(a.overItemCls)
			}
		},
		translateToScheduleCoordinate: function (a) {
			throw "Abstract method call!"
		},
		translateToPageCoordinate: function (a) {
			throw "Abstract method call!"
		},
		getDateFromXY: function (b, a) {
			throw "Abstract method call!"
		},
		getXYFromDate: function (a, b) {
			throw "Abstract method call!"
		},
		getTimeSpanRegion: function (a, b) {
			throw "Abstract method call!"
		},
		resolveEventRecord: function (a) {
			throw "Abstract method call!"
		},
		processUIEvent: function (f) {
			var c = this,
				a = f.getTarget(this.eventSelector),
				d = c.statics().ScheduleEventMap,
				b = f.type;
			if (a && b in d) {
				this.fireEvent(this.scheduledEventName + b, this, this.resolveEventRecord(a), f)
			} else {
				this.callParent(arguments)
			}
		},
		getStart: function () {
			return this.timeAxis.getStart()
		},
		getEnd: function () {
			return this.timeAxis.getEnd()
		},
		setBarMargin: function (b, a) {
			this.barMargin = b;
			if (!a) {
				this.refresh()
			}
		},
		setRowHeight: function (a, b) {
			this.rowHeight = a || 24;
			if (this.rendered && !b) {
				this.refresh()
			}
		},
		setColumnWidth: function (b, a) {
			throw "Abstract method call!"
		}
	}
});
Ext.define("Sch.view.Horizontal", {
	props: {
		translateToScheduleCoordinate: function (a) {
			return a - this.el.getX() + this.el.getScroll().left
		},
		translateToPageCoordinate: function (a) {
			return a + this.el.getX() - this.el.getScroll().left
		},
		getDateFromXY: function (g, e) {
			var b, a = this.translateToScheduleCoordinate(g[0]),
				d = a / this.getActualTimeColumnWidth(),
				c = this.headerCt.getColumnCount();
			if (d < 0 || d > c) {
				b = null
			} else {
				var f = d - this.resolveColumnIndex(a);
				if (f > 2 && d >= c) {
					return null
				}
				b = this.timeAxis.getDateFromTick(d, e)
			}
			return b
		},
		getXYFromDate: function (b, d) {
			var a, c = this.timeAxis.getTickFromDate(b);
			if (c >= 0) {
				a = this.getActualTimeColumnWidth() * c
			}
			if (d === false) {
				a = this.translateToPageCoordinate(a)
			}
			return [a, 0]
		},
		getEventBox: function (e, b) {
			var a = Math.floor(this.getXYFromDate(e)[0]),
				c = Math.floor(this.getXYFromDate(b)[0]),
				d = Math;
			if (this.managedEventSizing) {
				return {
					top: this.barMargin - this.eventBorderWidth - this.cellBorderWidth,
					left: d.min(a, c),
					width: d.max(1, d.abs(a - c)),
					height: this.rowHeight - (2 * this.barMargin) - this.eventBorderWidth
				}
			}
			return {
				left: d.min(a, c),
				width: d.max(1, d.abs(a - c))
			}
		},
		layoutEvents: function (a) {
			var c = Ext.Array.clone(a);
			c.sort(this.sortEvents);
			var b = this.layoutEventsInBands(0, c);
			return b
		},
		layoutEventsInBands: function (d, a) {
			var c = a[0],
				b = d === 0 ? this.barMargin : (d * this.rowHeight - ((d - 1) * this.barMargin));
			b -= this.cellBorderWidth;
			while (c) {
				c.top = b;
				Ext.Array.remove(a, c);
				c = this.findClosestSuccessor(c, a)
			}
			d++;
			if (a.length > 0) {
				return this.layoutEventsInBands(d, a)
			} else {
				return d
			}
		},
		getScheduleRegion: function (d, f) {
			var h = d ? Ext.fly(this.getNodeByRecord(d)).getRegion() : this.el.down(".x-grid-table").getRegion(),
				e = this.timeAxis.getStart(),
				j = this.timeAxis.getEnd(),
				b = this.getDateConstraints(d, f) || {
					start: e,
					end: j
				},
				c = this.translateToPageCoordinate(this.getXYFromDate(b.start)[0]),
				i = this.translateToPageCoordinate(this.getXYFromDate(b.end)[0]),
				g = h.top + this.barMargin,
				a = (d ? (g + this.rowHeight - this.barMargin) : h.bottom) - this.barMargin;
			return new Ext.util.Region(g, Math.max(c, i), a, Math.min(c, i))
		},
		collectRowData: function (g, p, o) {
			if (this.headerCt.getColumnCount() === 0) {
				return g
			}
			var a = Sch.util.Date,
				m = this.timeAxis,
				n = m.getStart(),
				r = m.getEnd(),
				c = p.getEvents(),
				k = [],
				j, f;
			for (j = 0, f = c.length; j < f; j++) {
				var b = c[j],
					d = b.getStartDate(),
					h = b.getEndDate();
				if (d && h && m.timeSpanInAxis(d, h)) {
					var q = this.generateTplData(b, n, r, p, o);
					k[k.length] = q
				}
			}
			var e = 1;
			if (this.dynamicRowHeight) {
				e = this.layoutEvents(k)
			}
			g.rowHeight = (e * this.rowHeight) - ((e - 1) * this.barMargin);
			g[this.getFirstTimeColumn().id] = "&#160;" + this.eventTpl.apply(k);
			return g
		},
		resolveResource: function (a) {
			var b = this.findItemByChild(a);
			if (b) {
				return this.getRecord(this.findItemByChild(a))
			}
			return null
		},
		getTimeSpanRegion: function (b, f) {
			var d = this.getXYFromDate(b)[0],
				e = this.getXYFromDate(f || b)[0],
				c = this.el.down(".x-grid-table"),
				a = (c || this.el).dom.clientHeight;
			return new Ext.util.Region(0, Math.max(d, e), a, Math.min(d, e))
		},
		getStartEndDatesFromRegion: function (c, b) {
			var a = this.getDateFromXY([c.left, c.top], b),
				d = this.getDateFromXY([c.right, c.bottom], b);
			if (d && a) {
				return {
					start: Sch.util.Date.min(a, d),
					end: Sch.util.Date.max(a, d)
				}
			} else {
				return null
			}
		},
		onEventAdd: function (c, e) {
			var d;
			for (var b = 0, a = e.length; b < a; b++) {
				d = e[b].getResource();
				if (d) {
					this.onUpdate(d.store, d)
				}
			}
		},
		onEventRemove: function (c, a) {
			var b = this.getElementFromEventRecord(a);
			if (b) {
				var d = this.resolveResource(b);
				b.fadeOut({
					callback: function () {
						if (this.resourceStore.indexOf(d) >= 0) {
							this.onUpdate(this.resourceStore, d)
						}
					},
					scope: this
				})
			}
		},
		onEventUpdate: function (b, c, a) {
			var e, d = c.previous;
			if (d && d.ResourceId) {
				e = c.getResource(d.ResourceId);
				if (e) {
					this.onUpdate(this.resourceStore, e)
				}
			}
			e = c.getResource();
			if (e) {
				this.onUpdate(this.resourceStore, e)
			}
		},
		getSingleTickInPixels: function () {
			return this.getActualTimeColumnWidth()
		},
		setColumnWidth: function (b, a) {
			if (this.getTimeAxisColumn()) {
				this.getTimeAxisColumn().setTimeColumnWidth(b);
				this.columnWidth = b;
				if (!a) {
					this.refresh()
				}
			}
			this.fireEvent("columnwidthchange", this, b)
		}
	}
});
Ext.require(["Sch.mixin.TimelineView", "Sch.util.Mixin"], function () {
	Sch.util.Mixin.define("Sch.view.TimelineTreeView", Sch.mixin.TimelineView.Config, {
		extend: "Ext.tree.View"
	});
	Sch.view.TimelineTreeView.override({
		resetScrollersTimeoutId: null,
		cellBorderWidth: 0,
		constructor: function (a) {
			var b = a.panel._top;
			Ext.apply(this, {
				timeAxis: b.timeAxis,
				rowHeight: b.rowHeight,
				dndValidatorFn: b.dndValidatorFn || Ext.emptyFn,
				resizeValidatorFn: b.resizeValidatorFn || Ext.emptyFn,
				createValidatorFn: b.createValidatorFn || Ext.emptyFn,
				eventRenderer: b.eventRenderer,
				eventBorderWidth: b.eventBorderWidth,
				timeCellRenderer: b.timeCellRenderer,
				timeCellRendererScope: b.timeCellRendererScope,
				enableEventDragDrop: b.enableEventDragDrop,
				enableDragCreation: b.enableDragCreation,
				eventResizeHandles: b.eventResizeHandles,
				readOnly: b.readOnly,
				validatorFnScope: b.validatorFnScope || this
			});
			this.callOverridden(arguments)
		},
		afterRender: function () {
			this.el.addCls("sch-timelinetreeview");
			this.callOverridden(arguments)
		},
		resetScrollers: function (a) {
			if (!this.el || !this.el.dom) {
				return
			}
			if (a) {
				return this.callOverridden([])
			}
			if (this.resetScrollersTimeoutId) {
				clearTimeout(this.resetScrollersTimeoutId)
			}
			var b = this;
			this.resetScrollersTimeoutId = setTimeout(function () {
				delete b.resetScrollersTimeoutId;
				b.resetScrollers(true)
			}, 0)
		}
	})
});
Ext.define("Sch.mixin.TimelinePanel", {
	requires: ["Sch.data.TimeAxis", "Sch.feature.Scheduling", "Sch.view.Locking", "Sch.mixin.Lockable", "Sch.preset.Manager", "Sch.Overrides"]
}, function () {
	Sch.mixin.TimelinePanel.Config = {
		lockable: true,
		stateful: false,
		orientation: "horizontal",
		enableColumnMove: false,
		lockedXType: null,
		normalXType: null,
		weekStartDay: 1,
		snapToIncrement: false,
		readOnly: false,
		eventResizeHandles: "both",
		viewPreset: "weekAndDay",
		startDate: null,
		endDate: null,
		eventBorderWidth: 1,
		columnLines: true,
		componentCls: undefined,
		eventSelector: undefined,
		syncCellHeight: Ext.emptyFn,
		tooltipTpl: null,
		tipCfg: {
			cls: "sch-tip",
			showDelay: 1000,
			hideDelay: 0,
			autoHide: true,
			anchor: "b"
		},
		initComponent: function () {
			this.addEvents("timeheaderdblclick", "beforeviewchange", "viewchange");
			if (!this.timeAxis) {
				this.timeAxis = Ext.create("Sch.data.TimeAxis")
			}
			if (!this.columns && !this.colModel) {
				this.columns = []
			}
			if (this.lockable) {
				this.self.mixin("lockable", Sch.mixin.Lockable);
				var b = 0,
					a = this.columns.length,
					c;
				for (; b < a; ++b) {
					c = this.columns[b];
					if (c.locked !== false) {
						c.locked = true
					}
				}
				this.timeAxis.on("reconfigure", this.onTimeAxisReconfigure, this);
				this.switchViewPreset(this.viewPreset, this.startDate, this.endDate, true)
			}
			this.callParent(arguments);
			if (this.lockable) {
				this.applyViewSettings(this.timeAxis.preset);
				if (!this.viewPreset) {
					throw "You must define a valid view preset object. See Sch.preset.Manager class for reference"
				}
			}
			this.relayEvents(this.getView(), ["beforetooltipshow", "scheduleclick", "scheduledblclick", "schedulecontextmenu"])
		},
		setReadOnly: function (a) {
			this.getSchedulingView().setReadOnly(a)
		},
		initStateEvents: function () {
			this.stateEvents.push("viewchange");
			this.callParent()
		},
		getState: function () {
			var a = this,
				b = {};
			Ext.apply(b, {
				viewPreset: a.viewPreset,
				startDate: a.getStart(),
				endDate: a.getEnd()
			});
			return b
		},
		applyState: function (b) {
			var a = this;
			if (b && b.viewPreset) {
				a.switchViewPreset(b.viewPreset, b.startDate, b.endDate)
			}
		},
		switchViewPreset: function (d, a, f, b) {
			if (this.fireEvent("beforeviewchange", this, d, a, f) !== false) {
				if (Ext.isString(d)) {
					this.viewPreset = d;
					d = Sch.preset.Manager.getPreset(d)
				}
				if (!d) {
					throw "View preset not found"
				}
				var e = d.headerConfig;
				var c = {
					unit: e.bottom ? e.bottom.unit : e.middle.unit,
					increment: (e.bottom ? e.bottom.increment : e.middle.increment) || 1,
					resolutionUnit: d.timeResolution.unit,
					resolutionIncrement: d.timeResolution.increment,
					weekStartDay: this.weekStartDay,
					mainUnit: e.middle.unit,
					shiftUnit: d.shiftUnit,
					headerConfig: d.headerConfig,
					shiftIncrement: d.shiftIncrement || 1,
					preset: d,
					defaultSpan: d.defaultSpan || 1
				};
				if (b) {
					c.start = a || new Date();
					c.end = f
				} else {
					c.start = a || this.timeAxis.getStart();
					c.end = f
				}
				if (!b) {
					this.applyViewSettings(d)
				}
				this.timeAxis.reconfigure(c)
			}
		},
		applyViewSettings: function (b) {
			var a = this.getSchedulingView();
			a.setDisplayDateFormat(b.displayDateFormat);
			if (this.orientation === "horizontal") {
				a.setRowHeight(this.rowHeight || b.rowHeight, true)
			}
		},
		getStart: function () {
			return this.timeAxis.getStart()
		},
		getEnd: function () {
			return this.timeAxis.getEnd()
		},
		setTimeColumnWidth: function (b, a) {
			this.getSchedulingView().setColumnWidth(b, a)
		},
		onTimeAxisReconfigure: function () {
			this.fireEvent("viewchange", this)
		},
		shiftNext: function (a) {
			this.timeAxis.shiftNext(a)
		},
		shiftPrevious: function (a) {
			this.timeAxis.shiftPrevious(a)
		},
		goToNow: function () {
			this.setTimeSpan(new Date())
		},
		setTimeSpan: function (b, a) {
			if (this.timeAxis) {
				this.timeAxis.setTimeSpan(b, a)
			}
		},
		setStart: function (a) {
			this.setTimeSpan(a)
		},
		setEnd: function (a) {
			this.setTimeSpan(null, a)
		},
		getTimeAxis: function () {
			return this.timeAxis
		},
		getResourceByEventRecord: function (a) {
			return a.getResource()
		},
		scrollToDate: function (c) {
			var b = this.getSchedulingView().getXYFromDate(c, true)[0];
			if (b >= 0) {
				var a = this.getHorizontalScroller();
				a.setScrollLeft(b)
			}
		},
		getSchedulingView: function () {
			return this.lockable ? this.normalGrid.getView() : this.getView()
		},
		timeCellRenderer: Ext.emptyFn,
		timeCellRendererScope: null,
		setOrientation: function (a) {
			this.el.removeCls("sch-" + this.orientation);
			this.el.addCls("sch-" + a);
			this.orientation = a
		},
		onRender: function () {
			this.callParent(arguments);
			if (this.lockable) {
				this.el.addCls("sch-" + this.orientation)
			}
		},
		afterRender: function () {
			this.callParent(arguments);
			if (!this.lockable) {
				var b = this.headerCt;
				if (b && b.reorderer && b.reorderer.dropZone) {
					var a = b.reorderer.dropZone;
					a.positionIndicator = Ext.Function.createSequence(a.positionIndicator, function () {
						this.valid = false
					})
				}
			}
		}
	}
});
Ext.require(["Sch.mixin.TimelinePanel", "Sch.util.Mixin"], function () {
	Sch.util.Mixin.define("Sch.panel.TimelineTreePanel", Sch.mixin.TimelinePanel.Config, {
		extend: "Ext.tree.Panel",
		useArrows: true,
		rootVisible: false
	});
	Sch.panel.TimelineTreePanel.override({
		initComponent: function () {
			this.callOverridden(arguments);
			if (this.lockable && this.lockedGrid.headerCt.query("treecolumn").length === 0) {
				Ext.Error.raise("You must define an Ext.tree.Column (or use xtype : 'treecolumn').")
			}
		}
	})
});
Ext.define("Sch.plugin.Printable", {
	docType: '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">',
	printableEventTpl: null,
	beforePrint: Ext.emptyFn,
	afterPrint: Ext.emptyFn,
	autoPrintAndClose: true,
	scheduler: null,
	constructor: function (a) {
		Ext.apply(this, a)
	},
	init: function (a) {
		this.scheduler = a;
		a.print = Ext.Function.bind(this.print, this)
	},
	mainTpl: '{docType}<html class="x-border-box {htmlClasses}"><head><meta content="text/html; charset=UTF-8" http-equiv="Content-Type" /><title>{title}</title>{styles}</head><body class="sch-print-body {bodyClasses}"><div class="sch-print-ct {componentClasses}" style="width:{totalWidth}px"><div class="sch-print-headerbg" style="border-left-width:{totalWidth}px;height:{headerHeight}px;"></div><div class="sch-print-header-wrap"><div class="sch-print-lockedheader x-grid-header-ct x-grid-header-ct-default x-docked x-docked-top x-grid-header-ct-docked-top x-grid-header-ct-default-docked-top x-box-layout-ct x-docked-noborder-top x-docked-noborder-right x-docked-noborder-left">{lockedHeader}</div><div style="left:{lockedWidth}px" class="sch-print-normalheader x-grid-header-ct x-grid-header-ct-default x-docked x-docked-top x-grid-header-ct-docked-top x-grid-header-ct-default-docked-top x-box-layout-ct x-docked-noborder-top x-docked-noborder-right x-docked-noborder-left">{normalHeader}</div></div><div style="width:{lockedWidth}px;top:{headerHeight}px;" class="sch-print-locked-rows-ct {innerLockedClasses} x-grid-inner-locked">{lockedRows}</div><div style="left:{[Ext.isIE ? values.lockedWidth : 0]}px;top:{headerHeight}px;width:{normalWidth}px" class="sch-print-normal-rows-ct {innerNormalClasses}">{normalRows}</div></div><script type="text/javascript">{setupScript}<\/script></body></html>',
	getGridContent: function (h) {
		var g = h.normalGrid,
			c = h.lockedGrid,
			i = c.getView(),
			d = g.getView(),
			e, b, f;
		this.beforePrint(h);
		var a = i.store.getRange();
		b = i.tpl.apply(i.collectData(a, 0));
		f = d.tpl.apply(d.collectData(a, 0));
		this.afterPrint(h);
		return {
			normalHeader: g.headerCt.el.dom.innerHTML,
			lockedHeader: c.headerCt.el.dom.innerHTML,
			lockedRows: b,
			normalRows: f,
			lockedWidth: c.getWidth(),
			normalWidth: g.getWidth(),
			headerHeight: g.headerCt.getHeight(),
			innerLockedClasses: c.view.el.dom.className,
			innerNormalClasses: g.view.el.dom.className,
			width: h.getWidth()
		}
	},
	getStylesheets: function () {
		return Ext.getDoc().select('link[rel="stylesheet"]')
	},
	print: function () {
		var g = this.scheduler;
		if (!(this.mainTpl instanceof Ext.Template)) {
			var a = 22;
			this.mainTpl = Ext.create("Ext.XTemplate", this.mainTpl, {
				compiled: true,
				disableFormats: true
			})
		}
		var h = g.getView(),
			i = this.getStylesheets(),
			e = Ext.get(Ext.core.DomHelper.createDom({
				tag: "div"
			})),
			b;
		i.each(function (j) {
			e.appendChild(j.dom.cloneNode(true))
		});
		b = e.dom.innerHTML + "";
		var f = this.getGridContent(g),
			c = this.mainTpl.apply(Ext.apply({
				waitText: this.waitText,
				docType: this.docType,
				htmlClasses: "",
				bodyClasses: Ext.getBody().dom.className,
				componentClasses: g.el.dom.className,
				title: (g.title || ""),
				styles: b,
				totalWidth: g.getWidth(),
				setupScript: "(" + this.setupScript.toString() + ")();"
			}, f));
		var d = window.open("", "printgrid");
		d.document.write(c);
		d.document.close();
		if (this.autoPrintAndClose) {
			d.print();
			d.close()
		}
	},
	setupScript: function () {
		var d = document.getElementsByTagName("table"),
			g = d[d.length - 2],
			e = d[d.length - 1],
			b = g.getElementsByTagName("tr"),
			a = e.getElementsByTagName("tr"),
			f = b.length,
			c = 0;
		for (; c < f; c++) {
			b[c].style.height = a[c].style.height
		}
	}
});
Ext.define("Sch.plugin.TreeCellEditing", {
	extend: "Ext.grid.plugin.CellEditing",
	startEditByClick: function (c, a, h, b, g, d, f) {
		if (f.getTarget(c.expanderSelector)) {
			return
		}
		this.callParent(arguments)
	},
	startEdit: function (a, f) {
		if (!a || !f) {
			return
		}
		var d = this,
			b = d.getEditor(a, f),
			e = a.get(f.dataIndex),
			c = d.getEditingContext(a, f);
		a = c.record;
		f = c.column;
		d.completeEdit();
		if (f && !f.getEditor(a)) {
			return false
		}
		if (b) {
			c.originalValue = c.value = e;
			if (d.beforeEdit(c) === false || d.fireEvent("beforeedit", c) === false || c.cancel) {
				return false
			}
			d.context = c;
			d.setActiveEditor(b);
			d.setActiveRecord(a);
			d.setActiveColumn(f);
			d.editTask.delay(15, b.startEdit, b, [d.getCell(a, f), c.value, c])
		} else {
			d.grid.getView().getEl(f).focus((Ext.isWebKit || Ext.isIE) ? 10 : false)
		}
	},
	getEditingContext: function (e, c) {
		var f = this,
			a = f.grid,
			i = a.store,
			b, d, g = a.getView(),
			h;
		if (Ext.isNumber(e)) {
			b = e;
			e = i.getAt(b)
		} else {
			if (i.indexOf) {
				b = i.indexOf(e)
			} else {
				b = g.indexOf(g.getNode(e))
			}
		}
		if (Ext.isNumber(c)) {
			d = c;
			c = a.headerCt.getHeaderAtIndex(d)
		} else {
			d = c.getIndex()
		}
		h = e.get(c.dataIndex);
		return {
			grid: a,
			record: e,
			field: c.dataIndex,
			value: h,
			row: g.getNode(b),
			column: c,
			rowIdx: b,
			colIdx: d
		}
	},
	startEditByPosition: function (a) {
		var e = this,
			c = e.grid,
			f = c.getSelectionModel(),
			b = e.view,
			d = this.view.getNode(a.row);
		editColumnHeader = c.headerCt.getHeaderAtIndex(a.column);
		editRecord = b.getRecord(d);
		if (f.selectByPosition) {
			f.selectByPosition(a)
		}
		e.startEdit(editRecord, editColumnHeader)
	}
});
Ext.define("Sch.scroller.Paging", {
	extend: "Ext.grid.Scroller",
	alias: "widget.schpagingscroller",
	percentageFromEdge: 0.35,
	scrollToLoadBuffer: 200,
	activePrefetch: true,
	chunkSize: 50,
	snapIncrement: 1,
	syncScroll: true,
	initComponent: function () {
		var a = this,
			b = a.store;
		b.on("guaranteedrange", this.onGuaranteedRange, this);
		this.callParent(arguments)
	},
	onGuaranteedRange: function (c, f, a) {
		var d = this,
			e = d.store,
			b;
		if (c.length && d.visibleStart < c[0].index) {
			return
		}
		e.loadRecords(c);
		e.fireEvent("bufferchange", e, c, f, a, this);
		if (!d.firstLoad) {
			if (d.rendered) {
				d.invalidate()
			} else {
				d.on("afterrender", this.invalidate, this, {
					single: true
				})
			}
			d.firstLoad = true
		} else {
			d.syncTo()
		}
	},
	syncTo: function () {
		if (!this.rendered) {
			return
		}
		var h = this,
			b = h.getPanel(),
			i = this.store,
			g = this.scrollEl.dom,
			d = h.visibleStart - i.guaranteedStart,
			c = d * h.rowHeight,
			j = g.scrollHeight,
			e = g.clientHeight,
			a = g.scrollTop,
			f;
		if (Ext.isIE9 && Ext.isStrict) {
			e = g.offsetHeight + 2
		}
		f = (j - e - a <= 0);
		this.setViewScrollTop(c, f)
	},
	getPageData: function () {
		var b = this.getPanel(),
			c = this.store,
			a = c.getTotalCount();
		return {
			total: a,
			currentPage: c.currentPage,
			pageCount: Math.ceil(a / c.pageSize),
			fromRecord: ((c.currentPage - 1) * c.pageSize) + 1,
			toRecord: Math.min(c.currentPage * c.pageSize, a)
		}
	},
	onElScroll: function (v, l) {
		var x = this,
			j = x.getPanel(),
			g = this.store,
			w = g.pageSize,
			c = g.guaranteedStart,
			s = g.guaranteedEnd,
			r = g.getTotalCount(),
			i = Math.ceil(x.percentageFromEdge * g.pageSize),
			y = l.scrollTop,
			f = Math.floor(y / x.rowHeight),
			k = j.down("tableview"),
			h = k.el,
			m = h.getHeight(),
			b = Math.ceil(m / x.rowHeight),
			a = f + b,
			d = Math.floor(f / g.pageSize),
			p = Math.floor(a / g.pageSize) + 2,
			u = Math.ceil(r / g.pageSize),
			q = Math.floor(f / x.snapIncrement) * x.snapIncrement,
			n = q + w - 1,
			o = x.activePrefetch;
		x.visibleStart = f;
		x.visibleEnd = a;
		x.syncScroll = true;
		if (r >= w) {
			if (n > r - 1) {
				this.cancelLoad();
				if (g.rangeSatisfied(r - w, r - 1)) {
					x.syncScroll = true
				}
				g.guaranteeRange(r - w, r - 1)
			} else {
				if (f < c || a > s) {
					if (g.rangeSatisfied(q, n)) {
						this.cancelLoad();
						delete g.guaranteedStart;
						delete g.guaranteedEnd;
						g.guaranteeRange(q, n)
					} else {
						g.mask();
						x.attemptLoad(q, n)
					}
					x.syncScroll = false
				} else {
					if (o && f < (c + i) && d > 0) {
						x.syncScroll = true;
						g.prefetchPage(d)
					} else {
						if (o && a > (s - i) && p < u) {
							x.syncScroll = true;
							g.prefetchPage(p)
						}
					}
				}
			}
		}
		if (x.syncScroll) {
			x.syncTo()
		}
	},
	getSizeCalculation: function () {
		var b = this.ownerGrid,
			d = b.getView(),
			e = this.store,
			g = this.dock,
			c = this.el.dom,
			f = 1,
			a = 1;
		if (!this.rowHeight) {
			var h = d.el.down(d.getItemSelector());
			if (h) {
				this.rowHeight = h.getHeight(false, true)
			} else {
				return {
					width: 1,
					height: 1
				}
			}
		}
		a = e[(!e.remoteFilter && e.isFiltered()) ? "getCount" : "getTotalCount"]() * this.rowHeight;
		if (isNaN(f)) {
			f = 1
		}
		if (isNaN(a)) {
			a = 1
		}
		return {
			width: f,
			height: a
		}
	},
	attemptLoad: function (c, a) {
		var b = this;
		if (!b.loadTask) {
			b.loadTask = Ext.create("Ext.util.DelayedTask", b.doAttemptLoad, b, [])
		}
		b.loadTask.delay(b.scrollToLoadBuffer, b.doAttemptLoad, b, [c, a])
	},
	cancelLoad: function () {
		if (this.loadTask) {
			this.loadTask.cancel()
		}
	},
	doAttemptLoad: function (c, a) {
		var b = this.store;
		b.guaranteeRange(c, a)
	},
	setViewScrollTop: function (c, d) {
		var b = this.getPanel(),
			l = b.query("tableview"),
			f = 0,
			h = l.length,
			a, e, g, k, j = this.el.dom;
		var m = this.store;
		b.virtualScrollTop = c;
		a = l[1] || l[0];
		e = a.el.dom;
		k = ((m.pageSize * this.rowHeight) - e.clientHeight);
		g = (c % ((m.pageSize * this.rowHeight) + 1));
		if (d) {
			g = k
		}
		if (g > k) {
			return
		}
		for (; f < h; f++) {
			l[f].el.dom.scrollTop = g
		}
	}
});
Ext.define("Sch.plugin.CurrentTimeLine", {
	extend: "Sch.plugin.Lines",
	tooltipText: "Current time",
	updateInterval: 60000,
	autoUpdate: true,
	init: function (c) {
		var b = Ext.create("Ext.data.JsonStore", {
			model: Ext.define("TimeLineEvent", {
				extend: "Ext.data.Model",
				fields: ["Date", "Cls", "Text"]
			}),
			data: [{
				Date: new Date(),
				Cls: "sch-todayLine",
				Text: this.tooltipText
			}]
		});
		var a = b.first();
		if (this.autoUpdate) {
			this.runner = Ext.create("Ext.util.TaskRunner");
			this.runner.start({
				run: function () {
					a.set("Date", new Date())
				},
				interval: this.updateInterval
			})
		}
		c.on("destroy", this.onHostDestroy, this);
		this.store = b;
		this.callParent(arguments)
	},
	onHostDestroy: function () {
		if (this.runner) {
			this.runner.stopAll()
		}
		if (this.store.autoDestroy) {
			this.store.destroy()
		}
	}
});
Ext.define("Gnt.model.CalendarDay", {
	extend: "Ext.data.Model",
	idProperty: "Id",
	fields: [{
		name: "Date",
		type: "date",
		dateFormat: "c",
		convert: function (a) {
			return Ext.Date.clearTime(a)
		}
	}, {
		name: "IsWorkingDay",
		type: "boolean",
		defaultValue: false
	}, {
		name: "Id",
		type: "number",
		convert: function (b, a) {
			return Ext.Date.clearTime(a.get("Date"), true) - 0
		}
	}, {
		name: "Cls",
		defaultValue: "gnt-holiday"
	}, "Name"],
	setDate: function (a) {
		this.set("Date", Ext.Date.clearTime(a, true))
	},
	getDate: function () {
		return this.get("Date")
	}
});
Ext.define("Gnt.model.Assignment", {
	extend: "Ext.data.Model",
	fields: [{
		name: "ResourceId"
	}, {
		name: "TaskId"
	}, {
		name: "Units",
		type: "int",
		defaultValue: 0
	}],
	getResourceName: function () {
		return this.store.resourceStore.getById(this.data.ResourceId).data.Name
	}
});
Ext.define("Gnt.model.Dependency", {
	extend: "Ext.data.Model",
	idProperty: "Id",
	inheritableStatics: {
		Type: {
			StartToStart: 0,
			StartToEnd: 1,
			EndToStart: 2,
			EndToEnd: 3
		}
	},
	fields: [{
		name: "Id"
	}, {
		name: "From"
	}, {
		name: "To"
	}, {
		name: "Type",
		type: "int",
		defaultValue: 2
	}, {
		name: "Lag",
		type: "int",
		defaultValue: 0
	}]
});
Ext.define("Gnt.model.Resource", {
	extend: "Ext.data.Model",
	idProperty: "Id",
	fields: [{
		name: "Id"
	}, {
		name: "Name"
	}]
});
Ext.define("Gnt.model.Task", {
	extend: "Ext.data.Model",
	requires: ["Sch.util.Date"],
	idProperty: "Id",
	fields: [{
		name: "Id"
	}, {
		name: "Name",
		type: "string"
	}, {
		name: "StartDate",
		type: "date",
		dateFormat: "c"
	}, {
		name: "EndDate",
		type: "date",
		dateFormat: "c"
	}, {
		name: "Duration",
		type: "number",
		useNull: true
	}, {
		name: "DurationUnit",
		type: "string",
		defaultValue: "d",
		convert: function (a) {
			return a || "d"
		}
	}, {
		name: "PercentDone",
		type: "int",
		defaultValue: 0
	}, {
		name: "ManuallyScheduled",
		type: "boolean",
		defaultValue: false
	}],
	calendar: null,
	dependencyStore: null,
	taskStore: null,
	normalized: false,
	normalize: function () {
		var e = this.get("Duration"),
			b = this.get("DurationUnit"),
			a = this.getStartDate(),
			d = this.getEndDate(),
			c = this.getCalendar();
		if (e == null && a && d) {
			this.data.Duration = this.calculateDuration(a, d, b)
		}
		if (d == null && a && e) {
			this.data.EndDate = this.calculateEndDate(a, e, b)
		}
		this.normalized = true
	},
	getCalendar: function () {
		var a = this.calendar || this.store && this.store.calendar || this.parentNode && this.parentNode.getCalendar();
		if (!a) {
			Ext.Error.raise("Can't find a calendar in `getCalendar`")
		}
		return a
	},
	getDependencyStore: function () {
		var a = this.dependencyStore || this.getTaskStore().dependencyStore;
		if (!a) {
			Ext.Error.raise("Can't find a dependencyStore in `getDependencyStore`")
		}
		return a
	},
	getTaskStore: function (b) {
		if (this.taskStore) {
			return this.taskStore
		}
		var a = this.store && this.store.taskStore || this.parentNode && this.parentNode.getTaskStore();
		if (!a && !b) {
			Ext.Error.raise("Can't find a taskStore in `getTaskStore`")
		}
		return this.taskStore = a
	},
	getStartDate: function () {
		return this.get("StartDate")
	},
	setStartDate: function (a, d, c) {
		this.beginEdit();
		var b = this.getCalendar();
		if (c && !this.get("ManuallyScheduled")) {
			if (!this.isMilestone() || b.isHoliday(a - 1)) {
				a = b.skipNonWorkingTime(a, true)
			}
		}
		if (d !== false) {
			this.set({
				StartDate: a,
				EndDate: this.calculateEndDate(a, this.get("Duration"), this.get("DurationUnit"))
			})
		} else {
			this.set({
				StartDate: a,
				Duration: this.calculateDuration(a, this.getEndDate(), this.get("DurationUnit"))
			})
		}
		this.endEdit()
	},
	getEndDate: function () {
		return this.get("EndDate")
	},
	setEndDate: function (a, d, c) {
		this.beginEdit();
		var b = this.getCalendar();
		if (c && !this.get("ManuallyScheduled")) {
			a = b.skipNonWorkingTime(a, false)
		}
		if (d !== false) {
			this.set({
				StartDate: this.calculateStartDate(a, this.get("Duration"), this.get("DurationUnit")),
				EndDate: a
			})
		} else {
			this.set({
				EndDate: a,
				Duration: this.calculateDuration(this.getStartDate(), a, this.get("DurationUnit"))
			})
		}
		this.endEdit()
	},
	setStartEndDate: function (a, c, d) {
		this.beginEdit();
		var b = this.getCalendar();
		if (d && !this.get("ManuallyScheduled")) {
			a = b.skipNonWorkingTime(a, true);
			c = b.skipNonWorkingTime(c, false)
		}
		this.set({
			StartDate: a,
			EndDate: c,
			Duration: this.calculateDuration(a, c, this.get("DurationUnit"))
		});
		this.endEdit()
	},
	getDuration: function (a) {
		if (!a) {
			return this.get("Duration")
		}
		var b = this.getCalendar(),
			c = b.convertDurationToMs(this.get("Duration"), this.get("DurationUnit"));
		return b.convertMSDurationToUnit(c, a)
	},
	getCalendarDuration: function (a) {
		return this.getCalendar().convertMSDurationToUnit(this.getEndDate() - this.getStartDate(), a || this.get("DurationUnit"))
	},
	setDuration: function (b, a) {
		a = a || this.get("DurationUnit");
		this.beginEdit();
		this.set({
			EndDate: this.calculateEndDate(this.getStartDate(), b, a),
			Duration: b,
			DurationUnit: a
		});
		this.endEdit()
	},
	calculateStartDate: function (c, b, a) {
		a = a || this.get("DurationUnit");
		if (this.get("ManuallyScheduled")) {
			return Sch.util.Date.add(startDate, a, -b)
		} else {
			return this.getCalendar().calculateStartDate(c, b, a)
		}
	},
	calculateEndDate: function (a, c, b) {
		b = b || this.get("DurationUnit");
		if (this.get("ManuallyScheduled")) {
			return Sch.util.Date.add(a, b, c)
		} else {
			return this.getCalendar().calculateEndDate(a, c, b)
		}
	},
	calculateDuration: function (a, c, b) {
		if (this.get("ManuallyScheduled")) {
			return c - a
		} else {
			return this.getCalendar().calculateDuration(a, c, b || this.get("DurationUnit"))
		}
	},
	indent: function () {
		var a = this.previousSibling;
		if (a) {
			a.appendChild(this);
			a.set("leaf", false);
			a.expand()
		}
	},
	outdent: function () {
		var a = this.parentNode;
		if (a && !a.isRoot()) {
			a.set("leaf", a.childNodes.length === 1);
			if (a.nextSibling) {
				a.parentNode.insertBefore(this, a.nextSibling)
			} else {
				a.parentNode.appendChild(this)
			}
		}
	},
	recalculateParents: function () {
		var b = new Date(9999, 0, 0),
			c = new Date(0),
			e = this.parentNode;
		if (e && !e.isRoot() && e.hasChildNodes()) {
			for (var d = 0, a = e.childNodes.length; d < a; d++) {
				var f = e.childNodes[d];
				b = Sch.util.Date.min(b, f.getStartDate() || b);
				c = Sch.util.Date.max(c, f.getEndDate() || c)
			}
			e.beginEdit();
			if (e.getStartDate() - b !== 0) {
				e.set("StartDate", b)
			}
			if (e.getEndDate() - c !== 0) {
				e.set("EndDate", c)
			}
			e.endEdit();
			e.recalculateParents()
		}
	},
	isMilestone: function () {
		return this.getEndDate() - this.getStartDate() === 0
	},
	getAllDependencies: function (c) {
		var f = this.getId() || this.internalId;
		c = c || this.getDependencyStore();
		var e = [];
		for (var d = 0, a = c.getCount(); d < a; d++) {
			var b = c.getAt(d);
			if (b.get("From") == f || b.get("To") == f) {
				e.push(b)
			}
		}
		return e
	},
	getIncomingDependencies: function (c) {
		var f = this.getId() || this.internalId;
		c = c || this.getDependencyStore();
		var e = [];
		for (var d = 0, a = c.getCount(); d < a; d++) {
			var b = c.getAt(d);
			if (b.get("To") == f) {
				e.push(b)
			}
		}
		return e
	},
	getOutComingDependencies: function (c) {
		var f = this.getId() || this.internalId;
		c = c || this.getDependencyStore();
		var e = [];
		for (var d = 0, a = c.getCount(); d < a; d++) {
			var b = c.getAt(d);
			if (b.get("From") == f) {
				e.push(b)
			}
		}
		return e
	},
	getConstrainContext: function (f) {
		var g = this.getIncomingDependencies();
		if (!g.length) {
			return null
		}
		var h = f || this.getTaskStore(),
			a = Gnt.model.Dependency.Type,
			c = new Date(0),
			b = new Date(0),
			i = Ext.Date,
			e = this.getCalendar(),
			d;
		Ext.each(g, function (m) {
			var l = h.getNodeById(m.get("From"));
			if (l) {
				var o = m.get("Lag") || 0,
					j = 0,
					n = l.getStartDate(),
					k = l.getEndDate();
				switch (m.get("Type")) {
				case a.StartToEnd:
					n = e.skipWorkingDays(n, o);
					if (b < n) {
						b = n;
						d = l
					}
					break;
				case a.StartToStart:
					n = e.skipWorkingDays(n, o);
					if (c < n) {
						c = n;
						d = l
					}
					break;
				case a.EndToStart:
					k = e.skipWorkingDays(k, o);
					if (c < k) {
						c = k;
						d = l
					}
					break;
				case a.EndToEnd:
					k = e.skipWorkingDays(k, o);
					if (b < k) {
						b = k;
						d = l
					}
					break;
				default:
					throw "Invalid case statement";
					break
				}
			}
		});
		return {
			startDate: c > 0 ? c : null,
			endDate: b > 0 ? b : null,
			constrainingTask: d
		}
	},
	getCriticalPaths: function () {
		var b = [this],
			a = this.getConstrainContext();
		while (a) {
			b.push(a.constrainingTask);
			a = a.constrainingTask.getConstrainContext()
		}
		return b
	},
	constrain: function (c) {
		if (this.get("ManuallyScheduled")) {
			return
		}
		c = c || this.getTaskStore();
		var b = this.getConstrainContext(c);
		if (b) {
			var a = b.startDate;
			var d = b.endDate;
			if (a) {
				this.setStartDate(a, true, c.skipWeekendsDuringDragDrop)
			} else {
				if (d) {
					this.setEndDate(d, true, c.skipWeekendsDuringDragDrop)
				}
			}
		}
	},
	cascadeChanges: function (a) {
		a = a || this.getTaskStore();
		if (this.isLeaf()) {
			this.constrain(a);
			this.recalculateParents()
		}
		Ext.each(this.getOutComingDependencies(), function (b) {
			var c = a.getNodeById(b.get("To"));
			if (c && !c.get("ManuallyScheduled")) {
				c.cascadeChanges(a)
			}
		})
	},
	afterEdit: function () {
		this.callParent();
		if (!this.normalized) {
			return
		}
		var a = this.taskStore || this.getTaskStore(true);
		if (a && !a.isFillingRoot) {
			a.afterEdit(this)
		}
	},
	afterCommit: function () {
		this.callParent();
		if (!this.normalized) {
			return
		}
		var a = this.taskStore || this.getTaskStore(true);
		if (a && !a.isFillingRoot) {
			a.afterCommit(this)
		}
	},
	afterReject: function () {
		this.callParent();
		var a = this.getTaskStore(true);
		if (a && !a.isFillingRoot) {
			a.afterReject(this)
		}
	},
	isStartOrEndModified: function () {
		return this.isModified("StartDate") || this.isModified("EndDate")
	}
});
Ext.define("Gnt.data.Calendar", {
	extend: "Ext.data.Store",
	requires: ["Ext.Date", "Gnt.model.CalendarDay", "Sch.model.Range", "Sch.util.Date"],
	model: "Gnt.model.CalendarDay",
	daysPerMonth: 30,
	unitsInMs: null,
	defaultNonWorkingTimeCssCls: "gnt-holiday",
	weekendsAreWorkdays: false,
	weekendFirstDay: 6,
	weekendSecondDay: 0,
	holidaysCache: null,
	constructor: function () {
		this.callParent(arguments);
		this.unitsInMs = {
			MILLI: 1,
			SECOND: 1000,
			MINUTE: 60 * 1000,
			HOUR: 60 * 60 * 1000,
			DAY: 24 * 60 * 60 * 1000,
			WEEK: 7 * 24 * 60 * 60 * 1000,
			MONTH: this.daysPerMonth * 24 * 60 * 60 * 1000,
			QUARTER: 3 * this.daysPerMonth * 24 * 60 * 60 * 1000,
			YEAR: 4 * 3 * this.daysPerMonth * 24 * 60 * 60 * 1000
		};
		this.holidaysCache = {};
		this.on("datachanged", this.clearCache, this)
	},
	clearCache: function () {
		this.holidaysCache = {}
	},
	getCalendarDay: function (a) {
		a = typeof a == "number" ? new Date(a) : a;
		return this.getById(Ext.Date.clearTime(a, true) - 0)
	},
	isHoliday: function (c) {
		var b = c - 0;
		if (this.holidaysCache[b] != null) {
			return this.holidaysCache[b]
		}
		c = typeof c == "number" ? new Date(c) : c;
		var a = this.getCalendarDay(c);
		if (a) {
			return this.holidaysCache[b] = !a.get("IsWorkingDay")
		} else {
			if (this.weekendsAreWorkdays) {
				return false
			}
		}
		return this.holidaysCache[b] = this.isWeekend(c)
	},
	isWeekend: function (b) {
		var a = b.getDay();
		return a === this.weekendFirstDay || a === this.weekendSecondDay
	},
	isWorkingDay: function (a) {
		return !this.isHoliday(a)
	},
	convertMSDurationToUnit: function (a, b) {
		return a / this.unitsInMs[Sch.util.Date.getNameOfUnit(b)]
	},
	convertDurationToMs: function (b, a) {
		return b * this.unitsInMs[Sch.util.Date.getNameOfUnit(a)]
	},
	getHolidaysRanges: function (d, g, a) {
		if (d > g) {
			Ext.Error.raise("startDate can't be bigger than endDate")
		}
		d = Ext.Date.clearTime(d, true);
		g = Ext.Date.clearTime(g, true);
		var c = [],
			h;
		for (var e = d; e < g; e = Sch.util.Date.add(e, Sch.util.Date.DAY, 1)) {
			if (this.isHoliday(e) || (this.weekendsAreWorkdays && a && this.isWeekend(e))) {
				var i = this.getCalendarDay(e);
				var j = i && i.get("Cls") || this.defaultNonWorkingTimeCssCls;
				var f = Sch.util.Date.add(e, Sch.util.Date.DAY, 1);
				if (!h) {
					h = {
						StartDate: e,
						EndDate: f,
						Cls: j
					}
				} else {
					if (h.Cls == j) {
						h.EndDate = f
					} else {
						c.push(h);
						h = {
							StartDate: e,
							EndDate: f,
							Cls: j
						}
					}
				}
			} else {
				if (h) {
					c.push(h);
					h = null
				}
			}
		}
		if (h) {
			c.push(h)
		}
		var b = [];
		Ext.each(c, function (k) {
			b.push(Ext.create("Sch.model.Range", {
				StartDate: k.StartDate,
				EndDate: k.EndDate,
				Cls: k.Cls
			}))
		});
		return b
	},
	calculateDuration: function (a, d, b) {
		if (a > d) {
			Ext.Error.raise("startDate can't be bigger than endDate")
		}
		a = new Date(a);
		var c = 0,
			f = new Date().getTimezoneOffset() * 60 * 1000;
		while (a < d) {
			var e = Sch.util.Date.getNumberOfMsTillTheEndOfDay(a);
			if (this.isHoliday(a)) {
				a = Sch.util.Date.getStartOfNextDay(a);
				continue
			}
			if (d - a <= e) {
				c += d - a
			} else {
				c += e
			}
			a = Sch.util.Date.getStartOfNextDay(a)
		}
		return this.convertMSDurationToUnit(c, b)
	},
	calculateEndDate: function (a, d, b) {
		var c = new Date(a);
		d = this.convertDurationToMs(d, b);
		while (d > 0) {
			var e = Sch.util.Date.getNumberOfMsTillTheEndOfDay(c);
			if (this.isHoliday(c)) {
				c = Sch.util.Date.getStartOfNextDay(c);
				continue
			}
			if (e >= d) {
				c = Sch.util.Date.add(c, Sch.util.Date.MILLI, d);
				d = 0
			} else {
				c = Sch.util.Date.getStartOfNextDay(c);
				d -= e
			}
		}
		return c
	},
	calculateStartDate: function (f, e, c) {
		var a = f,
			d = Sch.util.Date;
		e = this.convertDurationToMs(e, c);
		while (e > 0) {
			var b = d.getNumberOfMsFromTheStartOfDay(a);
			if (this.isHoliday(a - 1)) {
				a = d.getEndOfPreviousDay(a);
				continue
			}
			if (b >= e) {
				a = d.add(a, d.MILLI, -e);
				e = 0
			} else {
				a = d.getEndOfPreviousDay(a);
				e -= b
			}
		}
		return a
	},
	skipNonWorkingTime: function (a, b) {
		while (this.isHoliday(a - (b ? 0 : 1))) {
			if (b) {
				a = Sch.util.Date.getStartOfNextDay(a, true)
			} else {
				a = Sch.util.Date.getEndOfPreviousDay(a)
			}
		}
		return a
	},
	skipWorkingDays: function (a, b) {
		var c = 0,
			d = b > 0,
			e = Ext.Date.clone(a);
		b = Math.abs(b);
		while (c < b) {
			if (!this.isHoliday(e - (d ? 0 : 1))) {
				c++;
				if (d) {
					e = Sch.util.Date.getStartOfNextDay(e, true)
				} else {
					e = Sch.util.Date.getEndOfPreviousDay(e)
				}
			}
			if (d || c < b) {
				e = this.skipNonWorkingTime(e, d)
			}
		}
		return e
	}
});
Ext.define("Gnt.data.TaskStore", {
	extend: "Ext.data.TreeStore",
	requires: ["Gnt.model.Task", "Gnt.data.Calendar"],
	model: "Gnt.model.Task",
	calendar: null,
	dependencyStore: null,
	weekendsAreWorkdays: true,
	buffered: false,
	pageSize: null,
	cascadeChanges: false,
	recalculateParents: true,
	skipWeekendsDuringDragDrop: true,
	cascadeDelay: 10,
	cascading: false,
	isFillingRoot: false,
	constructor: function (c) {
		this.addEvents("root-fill-start", "root-fill-end", "filter", "clearfilter");
		c = c || {};
		if (!c.calendar) {
			var a = {};
			if (c.hasOwnProperty("weekendsAreWorkdays")) {
				a.weekendsAreWorkdays = c.weekendsAreWorkdays
			}
			c.calendar = new Gnt.data.Calendar(a)
		}
		this.callParent([c]);
		this.on({
			remove: this.onTaskDeleted,
			beforesync: this.onTaskStoreBeforeSync,
			scope: this
		});
		var b = this.dependencyStore;
		if (b) {
			delete this.dependencyStore;
			this.setDependencyStore(b)
		}
	},
	onNodeAdded: function (a, b) {
		if (!b.normalized && !b.isRoot()) {
			b.normalize()
		}
		this.callParent(arguments)
	},
	setRootNode: function () {
		var b = this;
		this.tree.setRootNode = Ext.Function.createInterceptor(this.tree.setRootNode, function (c) {
			Ext.apply(c, {
				calendar: b.calendar,
				taskStore: b,
				dependencyStore: b.dependencyStore,
				phantom: false,
				dirty: false
			})
		});
		var a = this.callParent(arguments);
		delete this.tree.setRootNode;
		return a
	},
	fillNode: function (f, b) {
		if (f.isRoot()) {
			this.isFillingRoot = true;
			this.un({
				remove: this.onNodeUpdated,
				append: this.onNodeUpdated,
				insert: this.onNodeUpdated,
				update: this.onTaskUpdated,
				scope: this
			});
			this.fireEvent("root-fill-start", this, f, b)
		}
		var e = this,
			d = b ? b.length : 0,
			c = 0,
			a;
		if (d && e.sortOnLoad && !e.remoteSort && e.sorters && e.sorters.items) {
			a = Ext.create("Ext.util.MixedCollection");
			a.addAll(b);
			a.sort(e.sorters.items);
			b = a.items
		}
		f.set("loaded", true);
		if (this.buffered) {
			for (; c < d; c++) {
				f.appendChild(b[c], true, true);
				this.onNodeAdded(null, b[c]);
				this.tree.registerNode(b[c])
			}
		} else {
			for (; c < d; c++) {
				f.appendChild(b[c], false, true)
			}
		}
		if (f.isRoot()) {
			this.isFillingRoot = false;
			this.on({
				remove: this.onNodeUpdated,
				append: this.onNodeUpdated,
				insert: this.onNodeUpdated,
				update: this.onTaskUpdated,
				scope: this
			});
			this.fireEvent("root-fill-end", this, f, b)
		}
		return b
	},
	getById: function (a) {
		return this.tree.getNodeById(a)
	},
	setDependencyStore: function (b) {
		if (this.dependencyStore) {
			this.dependencyStore.un({
				add: this.onDependencyAddOrUpdate,
				update: this.onDependencyAddOrUpdate,
				scope: this
			})
		}
		this.dependencyStore = b;
		if (b) {
			b.on({
				add: this.onDependencyAddOrUpdate,
				update: this.onDependencyAddOrUpdate,
				beforesync: this.onBeforeDependencySync,
				scope: this
			})
		}
		var a = this.getRootNode();
		if (a) {
			a.dependencyStore = b
		}
	},
	setCalendar: function (b) {
		this.calendar = b;
		var a = this.getRootNode();
		if (a) {
			a.calendar = b
		}
	},
	filter: function () {
		this.fireEvent("filter", this, arguments)
	},
	clearFilter: function () {
		this.fireEvent("clearfilter", this)
	},
	getCriticalPaths: function () {
		var b = this.getRootNode(),
			a = [],
			d = new Date(0);
		b.cascadeBy(function (e) {
			d = Sch.util.Date.max(e.getEndDate(), d)
		});
		b.cascadeBy(function (e) {
			if (d - e.getEndDate() === 0 && !e.isRoot()) {
				a.push(e)
			}
		});
		var c = [];
		Ext.each(a, function (e) {
			c.push(e.getCriticalPaths())
		});
		return c
	},
	isValidDependency: function (d, b) {
		var e = true;
		if (d === b) {
			e = false
		}
		var c = this.getNodeById(d);
		var a = this.getNodeById(b);
		if (e && c.contains(a) || a.contains(c)) {
			e = false
		}
		if (e && this.hasTransitiveDependency(b, d)) {
			e = false
		}
		return e
	},
	hasTransitiveDependency: function (c, a) {
		var b = this;
		return this.dependencyStore.findBy(function (e) {
			var d = e.get("To");
			if (e.get("From") == c) {
				return d == a ? true : b.hasTransitiveDependency(e.get("To"), a)
			}
		}) >= 0
	},
	onNodeUpdated: function (a, b) {
		if (!this.cascading && this.recalculateParents) {
			b.recalculateParents()
		}
	},
	onTaskUpdated: function (c, b, a) {
		if (!this.cascading && (a == Ext.data.Model.EDIT && b.isStartOrEndModified()) || a == Ext.data.Model.REJECT) {
			if (this.cascadeChanges) {
				Ext.Function.defer(this.cascadeChangesForTask, this.cascadeDelay, this, [b])
			}
			if (this.recalculateParents) {
				b.recalculateParents()
			}
		}
	},
	cascadeChangesForTask: function (a) {
		var b = this;
		Ext.each(a.getOutComingDependencies(), function (c) {
			var d = b.getNodeById(c.get("To"));
			if (d) {
				if (!b.cascading) {
					b.fireEvent("beforecascade", b)
				}
				b.cascading = true;
				d.cascadeChanges(b)
			}
		});
		if (b.cascading) {
			b.cascading = false;
			b.fireEvent("cascade", b)
		}
	},
	onTaskDeleted: function (c, b) {
		if (!b.isReplace) {
			var a = this.dependencyStore;
			a.suspendEvents();
			a.remove(b.getAllDependencies(a));
			a.resumeEvents()
		}
	},
	onDependencyAddOrUpdate: function (a, c) {
		if (this.cascadeChanges) {
			var b = this;
			Ext.each(c, function (d) {
				b.getNodeById(d.get("To")).constrain(b)
			})
		}
	},
	getNewRecords: function () {
		return Ext.Array.filter(this.tree.flatten(), this.filterNew, this)
	},
	getUpdatedRecords: function () {
		return Ext.Array.filter(this.tree.flatten(), this.filterUpdated, this)
	},
	filterNew: function (a) {
		return a.phantom === true && a.isValid() && a != this.tree.root
	},
	filterUpdated: function (a) {
		return a.dirty === true && a.phantom !== true && a.isValid() && a != this.tree.root
	},
	onTaskStoreBeforeSync: function (b, c) {
		var a = b.create;
		if (a) {
			for (var e, d = a.length - 1; d >= 0; d--) {
				e = a[d];
				e._phantomId = e.internalId
			}
		}
	},
	onUpdateRecords: function (d, e, l) {
		if (l) {
			var k = this,
				b = d.length,
				g = k.data,
				c, j, h;
			for (var f = 0; f < b; f++) {
				h = d[f];
				c = k.tree.getNodeById(h.getId());
				j = c.parentNode;
				if (j) {
					var m = c.data;
					var a = h.data;
					c.fields.each(function (i) {
						if (i.persist) {
							m[i.name] = a[i.name]
						}
					});
					c.commit()
				}
			}
		}
	},
	onCreateRecords: function (d, e, l) {
		if (l) {
			var b = d.length,
				k = e.records,
				h, g, c, j;
			var n = this.tree;
			for (var f = 0; f < b; f++) {
				g = d[f];
				c = k[f];
				if (c) {
					h = c.parentNode;
					n.unregisterNode(c);
					if (h) {
						var m = c.data;
						var a = g.data;
						c.fields.each(function (i) {
							if (i.persist) {
								m[i.name] = a[i.name]
							}
						})
					}
					c.phantom = false;
					n.registerNode(c);
					c.commit();
					Ext.each(this.dependencyStore.getNewRecords(), function (i) {
						var p = i.get("From");
						var o = i.get("To");
						if (p === c._phantomId) {
							i.set("From", g.get("Id"))
						} else {
							if (o === c._phantomId) {
								i.set("To", g.get("Id"))
							}
						}
					})
				}
			}
		}
	},
	onBeforeDependencySync: function (a, b) {
		if (a.create) {
			for (var d, c = a.create.length - 1; c >= 0; c--) {
				d = a.create[c];
				if (this.getNodeById(d.get("From")).phantom || this.getNodeById(d.get("To")).phantom) {
					Ext.Array.remove(a.create, d)
				}
			}
		}
		return Boolean((a.create && a.create.length > 0) || (a.update && a.update.length > 0) || (a.destroy && a.destroy.length > 0))
	},
	getTotalTimeSpan: function () {
		var a = new Date(9999, 0, 1),
			b = new Date(0),
			c = Sch.util.Date;
		this.getRootNode().cascadeBy(function (d) {
			if (d.getStartDate()) {
				a = c.min(d.getStartDate(), a)
			}
			if (d.getEndDate()) {
				b = c.max(d.getEndDate(), b)
			}
		});
		a = a < new Date(9999, 0, 1) ? a : null;
		b = b > new Date(0) ? b : null;
		return {
			start: a,
			end: b || a || null
		}
	}
});
Ext.define("Gnt.template.Task", {
	extend: "Ext.XTemplate",
	constructor: function (a) {
		this.callParent(['<div class="sch-event-wrap ' + a.baseCls + ' x-unselectable" style="left:{leftOffset}px;">' + (a.leftLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-left"><label class="sch-gantt-label sch-gantt-label-left">{leftLabel}</label></div>' : "") + '<div id="' + a.prefix + '{id}" class="sch-gantt-item sch-gantt-task-bar {internalcls} {cls}" unselectable="on" style="width:{width}px;{style}">' + (a.enableDependencyDragDrop ? '<div unselectable="on" class="sch-gantt-terminal sch-gantt-terminal-start"></div>' : "") + ((a.resizeHandles === "both" || a.resizeHandles === "left") ? '<div class="sch-resizable-handle sch-gantt-task-handle sch-resizable-handle-west"></div>' : "") + '<div class="sch-gantt-progress-bar" style="width:{percentDone}%;{progressBarStyle}" unselectable="on">&#160;</div>' + ((a.resizeHandles === "both" || a.resizeHandles === "right") ? '<div class="sch-resizable-handle sch-gantt-task-handle sch-resizable-handle-east"></div>' : "") + (a.enableDependencyDragDrop ? '<div unselectable="on" class="sch-gantt-terminal sch-gantt-terminal-end"></div>' : "") + (a.enableProgressBarResize ? '<div style="left:{percentDone}%" class="sch-gantt-progressbar-handle"></div>' : "") + "</div>" + (a.rightLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-right" style="left:{width}px"><label class="sch-gantt-label sch-gantt-label-right">{rightLabel}</label></div>' : "") + "</div>",
		{
			compiled: true,
			disableFormats: true
		}])
	}
});
Ext.define("Gnt.template.Milestone", {
	extend: "Ext.XTemplate",
	constructor: function (a) {
		this.callParent(['<div class="sch-event-wrap ' + a.baseCls + ' x-unselectable" style="left:{leftOffset}px">' + (a.leftLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-left"><label class="sch-gantt-label sch-gantt-label-left">{leftLabel}</label></div>' : "") + (a.printable ? ('<img id="' + a.prefix + '{id}" src="' + a.imgSrc + '" class="sch-gantt-item sch-gantt-milestone-diamond {internalcls} {cls}" unselectable="on" style="{style}" />') : ('<div id="' + a.prefix + '{id}" class="sch-gantt-item sch-gantt-milestone-diamond {internalcls} {cls}" unselectable="on" style="{style}">' + (a.enableDependencyDragDrop ? '<div class="sch-gantt-terminal sch-gantt-terminal-start"></div><div class="sch-gantt-terminal sch-gantt-terminal-end"></div>' : "") + "</div>")) + (a.rightLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-right" style="left:{width}px"><label class="sch-gantt-label sch-gantt-label-right">{rightLabel}</label></div>' : "") + "</div>",
		{
			compiled: true,
			disableFormats: true
		}])
	}
});
Ext.define("Gnt.template.ParentTask", {
	extend: "Ext.XTemplate",
	constructor: function (a) {
		this.callParent(['<div class="sch-event-wrap ' + a.baseCls + ' x-unselectable" style="left:{leftOffset}px;width:{width}px">' + (a.leftLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-left"><label class="sch-gantt-label sch-gantt-label-left">{leftLabel}</label></div>' : "") + '<div id="' + a.prefix + '{id}" class="sch-gantt-item sch-gantt-parenttask-bar {internalcls} {cls}" style="{style}"><div class="sch-gantt-progress-bar" style="width:{percentDone}%;{progressBarStyle}">&#160;</div>' + (a.enableDependencyDragDrop ? '<div class="sch-gantt-terminal sch-gantt-terminal-start"></div>' : "") + '<div class="sch-gantt-parenttask-arrow sch-gantt-parenttask-leftarrow"></div><div class="sch-gantt-parenttask-arrow sch-gantt-parenttask-rightarrow"></div>' + (a.enableDependencyDragDrop ? '<div class="sch-gantt-terminal sch-gantt-terminal-end"></div>' : "") + "</div>" + (a.rightLabel ? '<div class="sch-gantt-labelct sch-gantt-labelct-right" style="left:{width}px"><label class="sch-gantt-label sch-gantt-label-right">{rightLabel}</label></div>' : "") + "</div>",
		{
			compiled: true,
			disableFormats: true
		}])
	}
});
Ext.define("Gnt.Tooltip", {
	extend: "Ext.ToolTip",
	requires: ["Ext.Template"],
	startText: "Starts: ",
	endText: "Ends: ",
	durationText: "Duration:",
	mode: "startend",
	cls: "sch-tip",
	height: 40,
	autoHide: false,
	anchor: "b-tl",
	initComponent: function () {
		if (this.mode === "startend" && !this.startEndTemplate) {
			this.startEndTemplate = new Ext.Template('<div class="sch-timetipwrap {cls}"><div>' + this.startText + "{startText}</div><div>" + this.endText + "{endText}</div></div>").compile()
		}
		if (this.mode === "duration" && !this.durationTemplate) {
			this.durationTemplate = new Ext.Template('<div class="sch-timetipwrap {cls}">', "<div>" + this.startText + " {startText}</div>", "<div>" + this.durationText + " {duration} {unit}</div>", "</div>").compile()
		}
		this.callParent(arguments)
	},
	update: function (e, b, d, a) {
		var c;
		if (this.mode === "duration") {
			c = this.getDurationContent(e, b, d, a)
		} else {
			c = this.getStartEndContent(e, b, d, a)
		}
		this.callParent([c])
	},
	getStartEndContent: function (b, f, a, h) {
		var e = this.gantt,
			i = e.getFormattedDate(b),
			d = i,
			g;
		if (f - b > 0) {
			d = e.getFormattedEndDate(f, b)
		}
		var c = {
			cls: a ? "sch-tip-ok" : "sch-tip-notok",
			startText: i,
			endText: d
		};
		if (this.showClock) {
			Ext.apply(c, {
				startHourDegrees: roundedStart.getHours() * 30,
				startMinuteDegrees: roundedStart.getMinutes() * 6
			});
			if (f) {
				Ext.apply(c, {
					endHourDegrees: g.getHours() * 30,
					endMinuteDegrees: g.getMinutes() * 6
				})
			}
		}
		return this.startEndTemplate.apply(c)
	},
	getDurationContent: function (f, b, d, a) {
		var c = a.get("DurationUnit") || Sch.util.Date.DAY;
		var e = a.calculateDuration(f, b, c);
		return this.durationTemplate.apply({
			cls: d ? "sch-tip-ok" : "sch-tip-notok",
			startText: this.gantt.getFormattedDate(f),
			duration: parseFloat(Ext.Number.toFixed(e, 1)),
			unit: Sch.util.Date.getReadableNameOfUnit(c, e > 1)
		})
	},
	show: function (a) {
		if (a) {
			this.setTarget(a)
		}
		this.callParent([])
	}
});
Ext.define("Gnt.feature.TaskDragZone", {
	extend: "Ext.dd.DragZone",
	requires: ["Ext.dd.StatusProxy", "Ext.dd.ScrollManager"],
	containerScroll: false,
	dropAllowed: "sch-gantt-dragproxy",
	dropNotAllowed: "sch-gantt-dragproxy",
	constructor: function (b, a) {
		this.proxy = Ext.create("Ext.dd.StatusProxy", {
			shadow: false,
			dropAllowed: "sch-gantt-dragproxy",
			dropNotAllowed: "sch-gantt-dragproxy"
		});
		this.callParent(arguments);
		this.scroll = false;
		this.isTarget = true;
		this.ignoreSelf = false;
		this.addInvalidHandleClass("sch-resizable-handle");
		this.addInvalidHandleClass("x-resizable-handle");
		this.addInvalidHandleClass("sch-gantt-terminal");
		this.addInvalidHandleClass("sch-gantt-progressbar-handle")
	},
	destroy: function () {
		this.callParent(arguments)
	},
	autoOffset: function (a, e) {
		var d = this.dragData.repairXY,
			c = a - d[0],
			b = e - d[1];
		this.setDelta(c, b)
	},
	setXConstraint: function (c, b, a) {
		this.leftConstraint = c;
		this.rightConstraint = b;
		this.minX = c;
		this.maxX = b;
		if (a) {
			this.setXTicks(this.initPageX, a)
		}
		this.constrainX = true
	},
	setYConstraint: function (a, c, b) {
		this.topConstraint = a;
		this.bottomConstraint = c;
		this.minY = a;
		this.maxY = c;
		if (b) {
			this.setYTicks(this.initPageY, b)
		}
		this.constrainY = true
	},
	constrainTo: function (a, b) {
		this.resetConstraints();
		this.initPageX = a.left;
		this.initPageY = b.top;
		this.setXConstraint(a.left, a.right - (b.right - b.left), this.xTickSize);
		this.setYConstraint(b.top - 1, b.top - 1, this.yTickSize)
	},
	onDragOver: function (g, h) {
		var f = this.dragData,
			d = f.record,
			c = this.gantt,
			b = this.proxy.el.getX() + c.getXOffset(d),
			a = c.getDateFromXY([b, 0], "round");
		if (!f.hidden) {
			Ext.fly(f.sourceNode).hide();
			f.hidden = true
		}
		if (!a || a - f.start === 0) {
			return
		}
		f.start = a;
		this.valid = this.validatorFn.call(this.validatorFnScope || c, d, a, f.duration, g) !== false;
		if (this.tip) {
			this.tip.update(a, d.calculateEndDate(a, d.get("Duration"), d.get("DurationUnit")), this.valid)
		}
	},
	onStartDrag: function () {
		var a = this.dragData.record;
		if (this.tip) {
			this.tip.enable();
			this.tip.show(Ext.get(this.dragData.sourceNode));
			this.tip.update(a.getStartDate(), a.getEndDate(), true)
		}
		this.gantt.fireEvent("taskdragstart", this.gantt, a)
	},
	getDragData: function (i) {
		var h = this.gantt,
			f = i.getTarget(h.eventSelector);
		if (f && !i.getTarget(".sch-gantt-baseline-item")) {
			var c = Ext.get(f),
				d = h.resolveTaskRecord(c);
			if (h.fireEvent("beforetaskdrag", h, d, i) === false) {
				return null
			}
			var j = f.cloneNode(true),
				b = h.getSnapPixelAmount(),
				a = c.getXY();
			j.id = Ext.id();
			if (b <= 1) {
				Ext.fly(j).setStyle("left", 0)
			}
			this.constrainTo(Ext.fly(h.findItemByChild(f)).getRegion(), c.getRegion());
			this.setXConstraint(this.leftConstraint, this.rightConstraint, b);
			return {
				sourceNode: f,
				repairXY: a,
				ddel: j,
				record: d,
				duration: Sch.util.Date.getDurationInMinutes(d.getStartDate(), d.getEndDate())
			}
		}
		return null
	},
	afterRepair: function () {
		Ext.fly(this.dragData.sourceNode).show();
		if (this.tip) {
			this.tip.hide()
		}
		this.dragging = false
	},
	getRepairXY: function () {
		this.gantt.fireEvent("afterdnd", this.gantt);
		return this.dragData.repairXY
	},
	onDragDrop: function (i, c) {
		var j = this.cachedTarget || Ext.dd.DragDropMgr.getDDById(c),
			f = this.dragData,
			h = this.gantt,
			d = f.start,
			k = false;
		if (d) {
			var b = false;
			if (d && this.valid) {
				var a = f.record;
				k = (a.getStartDate() - d) !== 0;
				a.setStartDate(d, true, this.gantt.taskStore.skipWeekendsDuringDragDrop);
				b = true;
				if (!a.dirty) {
					h.onUpdate(h.taskStore, a)
				}
				h.fireEvent("taskdrop", h, a)
			}
		}
		if (this.tip) {
			this.tip.disable()
		}
		h.fireEvent("aftertaskdrop", h, a);
		if (b && k) {
			this.onValidDrop(j, i, c)
		} else {
			this.onInvalidDrop(j, i, c)
		}
	}
});
Ext.define("Gnt.feature.TaskDragDrop", {
	extend: "Ext.util.Observable",
	requires: ["Gnt.Tooltip", "Gnt.feature.TaskDragZone"],
	constructor: function (a) {
		Ext.apply(this, a);
		this.gantt.on({
			afterrender: this.onRender,
			beforedestroy: this.cleanUp,
			scope: this
		});
		this.callParent(arguments)
	},
	useTooltip: true,
	validatorFn: function (a, b, d, c) {
		return true
	},
	validatorFnScope: null,
	cleanUp: function () {
		this.gantt.dragZone.destroy();
		if (this.tip) {
			this.tip.destroy()
		}
	},
	onRender: function () {
		this.setupDragZone()
	},
	setupDragZone: function () {
		var b = this,
			a = this.gantt;
		if (this.useTooltip) {
			this.tip = Ext.create("Gnt.Tooltip", {
				gantt: a
			})
		}
		a.dragZone = Ext.create("Gnt.feature.TaskDragZone", a.el, {
			ddGroup: this.gantt.id + "-task-dd",
			validatorFn: this.validatorFn,
			validatorFnScope: this.validatorFnScope,
			gantt: a,
			tip: this.tip
		})
	}
});
Ext.define("Gnt.feature.DependencyDragDrop", {
	extend: "Ext.util.Observable",
	constructor: function (b) {
		this.addEvents("beforednd", "dndstart", "drop", "afterdnd");
		var a = b.ganttView;
		Ext.apply(this, {
			el: a.el.parent(),
			ddGroup: a.id + "-sch-dependency-dd",
			ganttView: a,
			dependencyStore: a.getDependencyStore()
		});
		this.setupDragZone();
		this.setupDropZone();
		this.callParent(arguments)
	},
	fromText: "From: <strong>{0}</strong> {1}<br/>",
	toText: "To: <strong>{0}</strong> {1}",
	startText: "Start",
	endText: "End",
	useLineProxy: true,
	terminalSelector: ".sch-gantt-terminal",
	isValidDrop: function (a, b) {
		return a !== b
	},
	destroy: function () {
		this.dragZone.destroy();
		this.dropZone.destroy();
		if (this.lineProxyEl) {
			this.lineProxyEl.destroy()
		}
	},
	initLineProxy: function (b, a) {
		var c = this.lineProxyEl = this.el.createChild({
			cls: "sch-gantt-connector-proxy"
		});
		c.alignTo(b, a ? "l" : "r");
		Ext.apply(this, {
			containerTop: this.el.getTop(),
			containerLeft: this.el.getLeft(),
			startXY: c.getXY(),
			startScrollLeft: this.el.dom.scrollLeft,
			startScrollTop: this.el.dom.scrollTop
		})
	},
	updateLineProxy: function (k) {
		var a = this.lineProxyEl,
			h = k[0] - this.startXY[0] + this.el.dom.scrollLeft - this.startScrollLeft,
			g = k[1] - this.startXY[1] + this.el.dom.scrollTop - this.startScrollTop,
			b = Math.max(1, Math.sqrt(Math.pow(h, 2) + Math.pow(g, 2)) - 2),
			f = Math.atan2(g, h) - (Math.PI / 2),
			d;
		if (Ext.isIE) {
			var i = Math.cos(f),
				e = Math.sin(f),
				j = 'progid:DXImageTransform.Microsoft.Matrix(sizingMethod="auto expand", M11 = ' + i + ", M12 = " + (-e) + ", M21 = " + e + ", M22 = " + i + ")";
			d = {
				height: b + "px",
				top: Math.min(0, g) + this.startXY[1] - this.containerTop + (g < 0 ? 2 : 0) + "px",
				left: Math.min(0, h) + this.startXY[0] - this.containerLeft + (h < 0 ? 2 : 0) + "px",
				filter: j,
				"-ms-filter": j
			}
		} else {
			var c = "rotate(" + f + "rad)";
			d = {
				height: b + "px",
				"-o-transform": c,
				"-webkit-transform": c,
				"-moz-transform": c,
				transform: c
			}
		}
		a.show().setStyle(d)
	},
	setupDragZone: function () {
		var b = this,
			a = this.ganttView;
		this.dragZone = Ext.create("Ext.dd.DragZone", this.el, {
			ddGroup: this.ddGroup,
			onStartDrag: function () {
				this.el.addCls("sch-gantt-dep-dd-dragging");
				b.fireEvent("dndstart", b);
				if (b.useLineProxy) {
					var c = this.dragData;
					b.initLineProxy(c.sourceNode, c.isStart)
				}
			},
			getDragData: function (g) {
				var f = g.getTarget(b.terminalSelector);
				if (f) {
					var d = a.resolveTaskRecord(f);
					if (b.fireEvent("beforednd", this, d) === false) {
						return null
					}
					var c = !! f.className.match("sch-gantt-terminal-start"),
						h = Ext.core.DomHelper.createDom({
							cls: "sch-dd-dependency",
							children: [{
								tag: "span",
								cls: "sch-dd-dependency-from",
								html: Ext.String.format(b.fromText, d.get("Name"), c ? b.startText : b.endText)
							}, {
								tag: "span",
								cls: "sch-dd-dependency-to",
								html: Ext.String.format(b.toText, "", "")
							}]
						});
					return {
						fromId: d.getId() || d.internalId,
						isStart: c,
						repairXY: Ext.fly(f).getXY(),
						ddel: h,
						sourceNode: Ext.fly(f).up(a.eventSelector)
					}
				}
				return false
			},
			afterRepair: function () {
				this.el.removeCls("sch-gantt-dep-dd-dragging");
				this.dragging = false;
				b.fireEvent("afterdnd", this)
			},
			onMouseUp: function () {
				this.el.removeCls("sch-gantt-dep-dd-dragging");
				if (b.lineProxyEl) {
					if (Ext.isIE) {
						Ext.destroy(b.lineProxyEl);
						b.lineProxyEl = null
					} else {
						b.lineProxyEl.animate({
							to: {
								height: 0
							},
							duration: 500,
							callback: function () {
								Ext.destroy(b.lineProxyEl);
								b.lineProxyEl = null
							}
						})
					}
				}
			},
			getRepairXY: function () {
				return this.dragData.repairXY
			}
		})
	},
	setupDropZone: function () {
		var b = this,
			a = this.ganttView;
		this.dropZone = Ext.create("Ext.dd.DropZone", this.el, {
			ddGroup: this.ddGroup,
			getTargetFromEvent: function (c) {
				if (b.useLineProxy) {
					b.updateLineProxy(c.getXY())
				}
				return c.getTarget(b.terminalSelector)
			},
			onNodeEnter: function (h, c, g, f) {
				var d = h.className.match("sch-gantt-terminal-start");
				Ext.fly(h).addCls(d ? "sch-gantt-terminal-start-drophover" : "sch-gantt-terminal-end-drophover")
			},
			onNodeOut: function (h, c, g, f) {
				var d = h.className.match("sch-gantt-terminal-start");
				Ext.fly(h).removeCls(d ? "sch-gantt-terminal-start-drophover" : "sch-gantt-terminal-end-drophover")
			},
			onNodeOver: function (k, c, j, i) {
				var d = a.resolveTaskRecord(k),
					f = d.getId() || d.internalId,
					g = k.className.match("sch-gantt-terminal-start"),
					h = Ext.String.format(b.toText, d.get("Name"), g ? b.startText : b.endText);
				c.proxy.el.down(".sch-dd-dependency-to").update(h);
				if (b.isValidDrop.call(b, i.fromId, f)) {
					return this.dropAllowed
				} else {
					return this.dropNotAllowed
				}
			},
			onNodeDrop: function (h, l, i, g) {
				var j, c = true,
					d = Gnt.model.Dependency.Type,
					f = a.resolveTaskRecord(h),
					k = f.getId() || f.internalId;
				if (b.lineProxyEl) {
					Ext.destroy(b.lineProxyEl);
					b.lineProxyEl = null
				}
				this.el.removeCls("sch-gantt-dep-dd-dragging");
				if (g.isStart) {
					if (h.className.match("sch-gantt-terminal-start")) {
						j = d.StartToStart
					} else {
						j = d.StartToEnd
					}
				} else {
					if (h.className.match("sch-gantt-terminal-start")) {
						j = d.EndToStart
					} else {
						j = d.EndToEnd
					}
				}
				c = b.isValidDrop.call(b, g.fromId, k, true);
				if (c) {
					b.fireEvent("drop", this, g.fromId, k, j)
				}
				b.fireEvent("afterdnd", this);
				return c
			}
		})
	}
});
Ext.define("Gnt.feature.DragCreator", {
	requires: ["Ext.Template", "Sch.util.DragTracker", "Gnt.Tooltip"],
	constructor: function (a) {
		Ext.apply(this, a || {});
		this.lastTime = new Date();
		this.template = this.template || Ext.create("Ext.Template", '<div class="sch-gantt-dragcreator-proxy"></div>', {
			compiled: true,
			disableFormats: true
		});
		this.ganttView.on({
			render: this.onGanttRender,
			destroy: this.onGanttDestroy,
			scope: this
		})
	},
	disabled: false,
	showDragTip: true,
	dragTolerance: 2,
	setDisabled: function (a) {
		this.disabled = a;
		if (this.dragTip) {
			this.dragTip.setDisabled(a)
		}
	},
	getProxy: function () {
		if (!this.proxy) {
			this.proxy = this.template.append(this.ganttView.el, {}, true)
		}
		return this.proxy
	},
	onBeforeDragStart: function (d) {
		var b = d.getTarget(".sch-timetd", 2);
		if (b) {
			var c = this.ganttView,
				a = c.resolveTaskRecord(b);
			if (!this.disabled && b && !a.getStartDate() && !a.getEndDate() && c.fireEvent("beforedragcreate", c, a, d) !== false) {
				d.stopEvent();
				this.taskRecord = a;
				this.originalStart = c.getDateFromDomEvent(d);
				this.rowRegion = c.getScheduleRegion(this.taskRecord, this.originalStart);
				this.dateConstraints = c.getDateConstraints(this.resourceRecord, this.originalStart);
				return true
			}
		}
		return false
	},
	onDragStart: function () {
		var c = this,
			a = c.ganttView,
			b = c.getProxy();
		c.start = c.originalStart;
		c.end = c.start;
		if (a.getOrientation() === "horizontal") {
			c.rowBoundaries = {
				top: c.rowRegion.top,
				bottom: c.rowRegion.bottom
			};
			b.setRegion({
				top: c.rowBoundaries.top,
				right: c.tracker.startXY[0],
				bottom: c.rowBoundaries.bottom,
				left: c.tracker.startXY[0]
			})
		} else {
			c.rowBoundaries = {
				left: c.rowRegion.left,
				right: c.rowRegion.right
			};
			b.setRegion({
				top: c.tracker.startXY[1],
				right: c.rowRegion.right,
				bottom: c.tracker.startXY[1],
				left: c.rowRegion.left
			})
		}
		b.show();
		c.ganttView.fireEvent("dragcreatestart", c.ganttView);
		if (c.showDragTip) {
			c.dragTip.update(c.start, c.end, true, this.taskRecord);
			c.dragTip.enable();
			c.dragTip.show(b)
		}
	},
	onDrag: function (g) {
		var d = this,
			c = d.ganttView,
			b = d.tracker.getRegion().constrainTo(d.rowRegion),
			f = c.getStartEndDatesFromRegion(b, "round");
		if (!f) {
			return
		}
		d.start = f.start || d.start;
		d.end = f.end || d.end;
		var a = d.dateConstraints;
		if (a) {
			d.end = Sch.util.Date.constrain(d.end, a.start, a.end);
			d.start = Sch.util.Date.constrain(d.start, a.start, a.end)
		}
		if (d.showDragTip) {
			d.dragTip.update(d.start, d.end, true, this.taskRecord)
		}
		Ext.apply(b, d.rowBoundaries);
		this.getProxy().setRegion(b)
	},
	onDragEnd: function (b) {
		var c = this.ganttView,
			a = true;
		if (this.showDragTip) {
			this.dragTip.disable()
		}
		if (!this.start || !this.end || (this.end < this.start)) {
			a = false
		}
		if (a) {
			this.taskRecord.setStartEndDate(this.start, this.end);
			c.fireEvent("dragcreateend", c, this.taskRecord, b)
		}
		this.proxy.hide();
		c.fireEvent("afterdragcreate", c)
	},
	onGanttRender: function () {
		var c = this.ganttView,
			a = c.el,
			b = Ext.Function.bind;
		this.tracker = new Sch.util.DragTracker({
			el: a,
			tolerance: this.dragTolerance,
			onBeforeStart: b(this.onBeforeDragStart, this),
			onStart: b(this.onDragStart, this),
			onDrag: b(this.onDrag, this),
			onEnd: b(this.onDragEnd, this)
		});
		if (this.showDragTip) {
			this.dragTip = Ext.create("Gnt.Tooltip", {
				mode: "duration",
				cls: "sch-gantt-dragcreate-tip",
				gantt: c
			})
		}
	},
	onGanttDestroy: function () {
		if (this.dragTip) {
			this.dragTip.destroy()
		}
		if (this.tracker) {
			this.tracker.destroy()
		}
		if (this.proxy) {
			Ext.destroy(this.proxy);
			this.proxy = null
		}
	}
});
Ext.define("Gnt.feature.LabelEditor", {
	extend: "Ext.Editor",
	labelPosition: "",
	constructor: function (b, a) {
		this.ganttView = b;
		this.ganttView.on("afterrender", this.onGanttRender, this);
		this.callParent([a])
	},
	edit: function (a) {
		var b = this.ganttView.getElementFromEventRecord(a).up(this.ganttView.eventWrapSelector);
		this.record = a;
		this.startEdit(b.down(this.delegate), this.dataIndex ? a.get(this.dataIndex) : "")
	},
	delegate: "",
	dataIndex: "",
	shadow: false,
	completeOnEnter: true,
	cancelOnEsc: true,
	ignoreNoChange: true,
	onGanttRender: function (a) {
		if (!this.field.width) {
			this.autoSize = "width"
		}
		this.on({
			beforestartedit: function (c, b, d) {
				return a.fireEvent("labeledit_beforestartedit", a, this.record, d, c)
			},
			beforecomplete: function (c, d, b) {
				return a.fireEvent("labeledit_beforecomplete", a, d, b, this.record, c)
			},
			complete: function (c, d, b) {
				this.record.set(this.dataIndex, d);
				a.fireEvent("labeledit_complete", a, d, b, this.record, c)
			},
			scope: this
		});
		a.el.on("dblclick", function (c, b) {
			this.edit(a.resolveTaskRecord(b))
		}, this, {
			delegate: this.delegate
		})
	}
});
Ext.define("Gnt.feature.ProgressBarResize", {
	extend: "Ext.util.Observable",
	requires: ["Ext.QuickTip", "Ext.resizer.Resizer"],
	constructor: function (a) {
		Ext.apply(this, a || {});
		this.gantt.on({
			afterrender: this.onGanttRender,
			destroy: this.cleanUp,
			scope: this
		});
		this.callParent(arguments)
	},
	useTooltip: true,
	increment: 10,
	onGanttRender: function () {
		var a = this.gantt;
		a.mon(a.el, "mousedown", this.onMouseDown, this, {
			delegate: ".sch-gantt-progressbar-handle"
		})
	},
	onMouseDown: function (d, b) {
		var c = this.gantt,
			f = c.resolveTaskRecord(b);
		if (c.fireEvent("beforeprogressbarresize", c, f) !== false) {
			var a = Ext.fly(b).prev(".sch-gantt-progress-bar");
			d.stopEvent();
			this.createResizable(a, f, d);
			c.fireEvent("progressbarresizestart", c, f)
		}
	},
	createResizable: function (d, a, h) {
		var c = h.getTarget(),
			i = d.up(this.gantt.eventSelector),
			g = i.getWidth() - 2,
			b = g * this.increment / 100;
		var f = Ext.create("Ext.resizer.Resizer", {
			target: d,
			taskRecord: a,
			handles: "e",
			minWidth: 0,
			maxWidth: g,
			maxHeight: d.getHeight(),
			widthIncrement: b,
			listeners: {
				resizedrag: this.partialResize,
				resize: this.afterResize,
				scope: this
			}
		});
		f.resizeTracker.onMouseDown(h, f.east.el.dom);
		i.select(".x-resizable-handle, .sch-gantt-terminal, .sch-gantt-progressbar-handle").hide();
		if (this.useTooltip) {
			if (!this.tip) {
				this.tip = Ext.create("Ext.ToolTip", {
					autoHide: false,
					anchor: "b",
					html: "%"
				})
			}
			this.tip.setTarget(d);
			this.tip.show();
			this.tip.body.update(a.get("PercentDone") + "%")
		}
	},
	partialResize: function (c, b) {
		var a = Math.round(b * 100 / (c.maxWidth * this.increment)) * this.increment;
		if (this.tip) {
			this.tip.body.update(a + "%")
		}
	},
	afterResize: function (d, a, b, f) {
		var g = d.taskRecord;
		if (this.tip) {
			this.tip.hide()
		}
		var c = Math.round(a * 100 / (d.maxWidth * this.increment)) * this.increment;
		d.taskRecord.set("PercentDone", c);
		d.destroy();
		this.gantt.fireEvent("afterprogressbarresize", this.gantt, g)
	},
	cleanUp: function () {
		if (this.tip) {
			this.tip.destroy()
		}
	}
});
Ext.define("Gnt.feature.TaskResize", {
	extend: "Ext.util.Observable",
	constructor: function (a) {
		Ext.apply(this, a);
		this.gantt.on({
			render: this.onganttRender,
			destroy: this.cleanUp,
			scope: this
		});
		this.callParent(arguments)
	},
	showDuration: true,
	useTooltip: true,
	validatorFn: Ext.emptyFn,
	validatorFnScope: null,
	onganttRender: function () {
		var a = this.gantt;
		a.mon(a.el, "mousedown", this.onMouseDown, this, {
			delegate: ".sch-resizable-handle"
		})
	},
	onMouseDown: function (c) {
		var b = this.gantt,
			a = c.getTarget(b.eventSelector),
			d = b.resolveTaskRecord(a);
		if (b.fireEvent("beforetaskresize", b, d, c) === false) {
			return
		}
		c.stopEvent();
		this.createResizable(Ext.get(a), d, c);
		b.fireEvent("taskresizestart", b, d)
	},
	createResizable: function (c, k, j) {
		var m = j.getTarget(),
			i = this.gantt,
			a = !! m.className.match("sch-resizable-handle-west"),
			d = i.getSnapPixelAmount(),
			f = c.getWidth(),
			l = c.up(".x-grid-row").getRegion();
		this.resizable = Ext.create("Ext.resizer.Resizer", {
			startLeft: c.getLeft(),
			startRight: c.getRight(),
			target: c,
			maxHeight: c.getHeight(),
			taskRecord: k,
			handles: a ? "w" : "e",
			constrainTo: l,
			minWidth: d,
			increment: d,
			listeners: {
				resizedrag: this[a ? "partialWestResize" : "partialEastResize"],
				resize: this.afterResize,
				scope: this
			}
		});
		this.resizable.resizeTracker.onMouseDown(j, this.resizable[a ? "west" : "east"].el.dom);
		if (this.useTooltip) {
			if (!this.tip) {
				this.tip = Ext.create("Gnt.Tooltip", {
					mode: this.showDuration ? "duration" : "startend",
					gantt: this.gantt
				})
			}
			var b = k.getStartDate(),
				h = k.getEndDate();
			this.tip.show(c);
			this.tip.update(b, h, true, k)
		}
	},
	partialEastResize: function (i, f, b, g) {
		var c = this.gantt,
			a = c.getDateFromXY([i.startLeft + Math.min(f, this.resizable.maxWidth), 0], "round");
		if (!a) {
			return
		}
		var h = i.taskRecord.getStartDate(),
			d = this.validatorFn.call(this.validatorFnScope || this, i.taskRecord, h, a) !== false;
		i.end = a;
		c.fireEvent("partialresize", c, i.taskRecord, h, a, i.el, g);
		if (this.useTooltip) {
			this.tip.update(h, a, d, i.taskRecord)
		}
	},
	partialWestResize: function (i, f, b, g) {
		var c = this.gantt,
			h = c.getDateFromXY([i.startRight - Math.min(f, this.resizable.maxWidth), 0], "round");
		if (!h) {
			return
		}
		var a = i.taskRecord.getEndDate(),
			d = this.validatorFn.call(this.validatorFnScope || this, i.taskRecord, h, a) !== false;
		i.start = h;
		c.fireEvent("partialtaskresize", c, i.taskRecord, h, a, i.el, g);
		if (this.useTooltip) {
			this.tip.update(h, a, d, i.taskRecord)
		}
	},
	afterResize: function (a, l, i, j) {
		if (this.useTooltip) {
			this.tip.hide()
		}
		var k = a.taskRecord,
			g = k.getStartDate(),
			m = k.getEndDate(),
			c = a.start || g,
			f = a.end || m,
			d = this.gantt;
		if (c && f && (f - c >= 0) && (c - g || f - m) && this.validatorFn.call(this.validatorFnScope || this, k, c, f, j) !== false) {
			var b = this.gantt.taskStore.skipWeekendsDuringDragDrop;
			if (c - g !== 0) {
				k.setStartDate(c, false, b)
			} else {
				k.setEndDate(f, false, b)
			}
		}
		a.destroy();
		d.fireEvent("aftertaskresize", d, k)
	},
	cleanUp: function () {
		if (this.tip) {
			this.tip.destroy()
		}
	}
});
Ext.define("Gnt.feature.WorkingTime", {
	extend: "Sch.plugin.Zones",
	requires: ["Ext.data.Store", "Sch.model.Range"],
	calendar: null,
	init: function (a) {
		if (!this.calendar) {
			Ext.Error.raise("Required attribute 'calendar' missed during initialization of 'Gnt.feature.WorkingTime'")
		}
		Ext.apply(this, {
			store: new Ext.data.Store({
				model: "Sch.model.Range",
				data: []
			})
		});
		this.callParent(arguments);
		a.on("viewchange", this.onViewChange, this);
		Ext.Function.defer(this.onViewChange, 1, this)
	},
	onViewChange: function () {
		var b = Sch.util.Date;
		if (b.compareUnits(this.timeAxis.unit, b.WEEK) > 0) {
			this.setDisabled(true)
		} else {
			this.setDisabled(false);
			var a = this.schedulerView;
			this.store.removeAll();
			this.store.add(this.calendar.getHolidaysRanges(a.getStart(), a.getEnd(), true))
		}
	}
});
Ext.define("Gnt.plugin.DependencyEditor", {
	extend: "Ext.form.FormPanel",
	requires: ["Ext.form.DisplayField", "Ext.form.ComboBox", "Ext.form.NumberField", "Gnt.model.Dependency"],
	hideOnBlur: true,
	fromText: "From",
	toText: "To",
	typeText: "Type",
	lagText: "Lag",
	endToStartText: "Finish-To-Start",
	startToStartText: "Start-To-Start",
	endToEndText: "Finish-To-Finish",
	startToEndText: "Start-To-Finish",
	showLag: false,
	border: false,
	height: 150,
	width: 260,
	frame: true,
	labelWidth: 60,
	constrain: false,
	initComponent: function () {
		Ext.apply(this, {
			items: this.buildFields(),
			defaults: {
				width: 240
			},
			floating: true,
			hideMode: "offsets"
		});
		this.callParent(arguments)
	},
	init: function (a) {
		a.on("dependencydblclick", this.onDependencyDblClick, this);
		a.on("render", this.onGanttRender, this);
		this.gantt = a;
		this.taskStore = a.getTaskStore()
	},
	onGanttRender: function () {
		this.render(Ext.getBody());
		this.el.addCls("sch-gantt-dependencyeditor");
		this.collapse(Ext.Component.DIRECTION_TOP, true);
		this.hide();
		if (this.hideOnBlur) {
			this.mon(Ext.getBody(), "click", this.onMouseClick, this)
		}
	},
	show: function (a, b) {
		this.dependencyRecord = a;
		this.getForm().loadRecord(a);
		this.fromLabel.setValue(this.taskStore.getNodeById(this.dependencyRecord.get("From")).get("Name"));
		this.toLabel.setValue(this.taskStore.getNodeById(this.dependencyRecord.get("To")).get("Name"));
		this.callParent([]);
		this.el.setXY(b);
		this.expand(!this.constrain);
		if (this.constrain) {
			this.doConstrain(Ext.util.Region.getRegion(Ext.getBody()))
		}
	},
	buildFields: function () {
		var c = this,
			b = Gnt.model.Dependency.Type,
			a = [this.fromLabel = Ext.create("Ext.form.DisplayField", {
				fieldLabel: this.fromText
			}), this.toLabel = Ext.create("Ext.form.DisplayField", {
				fieldLabel: this.toText
			}), this.typeField = Ext.create("Ext.form.ComboBox", {
				name: "Type",
				fieldLabel: this.typeText,
				triggerAction: "all",
				queryMode: "local",
				valueField: "value",
				displayField: "text",
				editable: false,
				store: Ext.create("Ext.data.JsonStore", {
					fields: ["text", "value"],
					data: [{
						text: this.endToStartText,
						value: b.EndToStart
					}, {
						text: this.startToStartText,
						value: b.StartToStart
					}, {
						text: this.endToEndText,
						value: b.EndToEnd
					}, {
						text: this.startToEndText,
						value: b.StartToEnd
					}]
				})
			})];
		if (this.showLag) {
			a.push(this.lagField = Ext.create("Ext.form.NumberField", {
				name: "Lag",
				fieldLabel: this.lagText
			}))
		}
		return a
	},
	onDependencyDblClick: function (c, a, d, b) {
		if (a != this.dependencyRecord) {
			this.show(a, d.getXY())
		}
	},
	onMouseClick: function (a) {
		if (this.collapsed || a.within(this.getEl()) || a.getTarget(".x-layer") || a.getTarget(".sch-dependency") || a.getTarget(".sch-ignore-click")) {
			return
		}
		this.collapse()
	},
	afterCollapse: function () {
		delete this.dependencyRecord;
		this.hide();
		this.callParent(arguments)
	}
});
Ext.define("Gnt.plugin.TaskContextMenu", {
	extend: "Ext.menu.Menu",
	requires: ["Gnt.model.Dependency"],
	plain: true,
	triggerEvent: "taskcontextmenu",
	texts: {
		newTaskText: "New task",
		newMilestoneText: "New milestone",
		deleteTask: "Delete task(s)",
		editLeftLabel: "Edit left label",
		editRightLabel: "Edit right label",
		add: "Add...",
		deleteDependency: "Delete dependency...",
		addTaskAbove: "Task above",
		addTaskBelow: "Task below",
		addMilestone: "Milestone",
		addSubtask: "Sub-task",
		addSuccessor: "Successor",
		addPredecessor: "Predecessor"
	},
	grid: null,
	rec: null,
	lastHighlightedItem: null,
	createMenuItems: function () {
		var a = this.texts;
		return [{
			handler: this.deleteTask,
			requiresTask: true,
			scope: this,
			text: a.deleteTask
		}, {
			handler: this.editLeftLabel,
			requiresTask: true,
			scope: this,
			text: a.editLeftLabel
		}, {
			handler: this.editRightLabel,
			requiresTask: true,
			scope: this,
			text: a.editRightLabel
		}, {
			text: a.add,
			menu: {
				plain: true,
				items: [{
					handler: this.addTaskAboveAction,
					requiresTask: true,
					scope: this,
					text: a.addTaskAbove
				}, {
					handler: this.addTaskBelowAction,
					scope: this,
					text: a.addTaskBelow
				}, {
					handler: this.addMilestone,
					scope: this,
					text: a.addMilestone
				}, {
					handler: this.addSubtask,
					requiresTask: true,
					scope: this,
					text: a.addSubtask
				}, {
					handler: this.addSuccessor,
					requiresTask: true,
					scope: this,
					text: a.addSuccessor
				}, {
					handler: this.addPredecessor,
					requiresTask: true,
					scope: this,
					text: a.addPredecessor
				}]
			}
		}, {
			text: a.deleteDependency,
			requiresTask: true,
			menu: {
				plain: true,
				listeners: {
					beforeshow: this.populateDependencyMenu,
					mouseover: this.onDependencyMouseOver,
					mouseleave: this.onDependencyMouseOut,
					scope: this
				}
			}
		}]
	},
	buildMenuItems: function () {
		this.items = this.createMenuItems()
	},
	initComponent: function () {
		this.buildMenuItems();
		this.callParent(arguments)
	},
	init: function (b) {
		b.on("destroy", this.cleanUp, this);
		var a = b.getSchedulingView(),
			c = b.lockedGrid.getView();
		if (this.triggerEvent === "itemcontextmenu") {
			c.on("itemcontextmenu", this.onItemContextMenu, this);
			a.on("itemcontextmenu", this.onItemContextMenu, this)
		} else {
			a.on("taskcontextmenu", this.onTaskContextMenu, this)
		}
		a.on("containercontextmenu", this.onContainerContextMenu, this);
		c.on("containercontextmenu", this.onContainerContextMenu, this);
		this.grid = b
	},
	populateDependencyMenu: function (f) {
		var d = this.grid,
			b = d.getTaskStore(),
			e = this.rec.getAllDependencies(),
			a = d.dependencyStore;
		f.removeAll();
		if (e.length === 0) {
			return false
		}
		var c = this.rec.getId() || this.rec.internalId;
		Ext.each(e, function (i) {
			var h = i.get("From"),
				g = b.getById(h == c ? i.get("To") : h);
			if (g) {
				f.add({
					depId: i.internalId,
					text: Ext.util.Format.ellipsis(g.get("Name"), 30),
					scope: this,
					handler: function (j) {
						a.removeAt(a.indexOfId(j.depId))
					}
				})
			}
		}, this)
	},
	onDependencyMouseOver: function (d, a, b) {
		if (a) {
			var c = this.grid.getSchedulingView();
			if (this.lastHighlightedItem) {
				c.unhighlightDependency(this.lastHighlightedItem.depId)
			}
			this.lastHighlightedItem = a;
			c.highlightDependency(a.depId)
		}
	},
	onDependencyMouseOut: function (b, a) {
		if (this.lastHighlightedItem) {
			this.grid.getSchedulingView().unhighlightDependency(this.lastHighlightedItem.depId)
		}
	},
	cleanUp: function () {
		if (this.menu) {
			this.menu.destroy()
		}
	},
	onTaskContextMenu: function (b, a, c) {
		this.activateMenu(a, c)
	},
	onItemContextMenu: function (b, a, d, c, f) {
		this.activateMenu(a, f)
	},
	onContainerContextMenu: function (a, b) {
		this.activateMenu(null, b)
	},
	activateMenu: function (c, b) {
		b.stopEvent();
		this.rec = c;
		var a = this.query("[requiresTask]");
		Ext.each(a, function (d) {
			d.setDisabled(!c)
		});
		this.showAt(b.getXY())
	},
	copyTask: function (c) {
		var b = this.grid.getTaskStore(),
			a = new b.model({
				PercentDone: 0,
				Name: this.texts.newTaskText,
				StartDate: (c && c.getStartDate()) || null,
				EndDate: (c && c.getEndDate()) || null,
				Duration: (c && c.get("Duration")) || null,
				DurationUnit: (c && c.get("DurationUnit")) || "d",
				leaf: true
			});
		return a
	},
	addTaskAbove: function (a) {
		var b = this.rec;
		if (b) {
			b.parentNode.insertBefore(a, b)
		} else {
			this.grid.taskStore.getRootNode().appendChild(a)
		}
	},
	addTaskBelow: function (a) {
		var b = this.rec;
		if (b) {
			b.parentNode.insertBefore(a, b.nextSibling)
		} else {
			this.grid.taskStore.getRootNode().appendChild(a)
		}
	},
	deleteTask: function () {
		var a = this.grid.getSelectionModel().selected;
		if (a.getCount() === 1 && a.first().parentNode.childNodes.length == 1) {
			a.first().parentNode.set("leaf", true)
		}
		a.each(function (b) {
			b.remove()
		})
	},
	editLeftLabel: function () {
		this.grid.getSchedulingView().editLeftLabel(this.rec)
	},
	editRightLabel: function () {
		this.grid.getSchedulingView().editRightLabel(this.rec)
	},
	addTaskAboveAction: function () {
		this.addTaskAbove(this.copyTask(this.rec))
	},
	addTaskBelowAction: function () {
		this.addTaskBelow(this.copyTask(this.rec))
	},
	addSubtask: function () {
		var a = this.rec;
		a.set("leaf", false);
		a.appendChild(this.copyTask(a));
		a.expand()
	},
	addSuccessor: function () {
		var b = this.rec,
			c = this.grid.dependencyStore,
			a = this.copyTask(b);
		this.addTaskBelow(a);
		a.set("StartDate", b.getEndDate());
		a.setDuration(1, Sch.util.Date.DAY);
		var d = c.model;
		c.add(new d({
			From: b.getId() || b.internalId,
			To: a.getId() || a.internalId,
			Type: d.EndToStart
		}))
	},
	addPredecessor: function () {
		var b = this.rec;
		var c = this.grid.dependencyStore;
		var a = this.copyTask(b);
		this.addTaskAbove(a);
		a.set({
			StartDate: a.calculateStartDate(b.getStartDate(), 1, Sch.util.Date.DAY),
			EndDate: b.getStartDate(),
			Duration: 1,
			DurationUnit: Sch.util.Date.DAY
		});
		var d = c.model;
		c.add(new d({
			From: a.getId() || a.internalId,
			To: b.getId() || b.internalId,
			Type: d.EndToStart
		}))
	},
	addMilestone: function () {
		var b = this.rec,
			a = this.copyTask(b);
		this.addTaskBelow(a);
		a.setStartDate(a.getEndDate(), false)
	}
});
Ext.define("Gnt.plugin.Printable", {
	extend: "Sch.plugin.Printable",
	getGridContent: function (b) {
		var e = this.callParent(arguments),
			c = b.getSchedulingView(),
			d = c.dependencyView,
			a = d.painter.getDependencyTplData(c.dependencyStore.getRange());
		e.normalRows += d.lineTpl.apply(a);
		return e
	}
});
Ext.define("Gnt.view.DependencyPainter", {
	extend: "Ext.util.Observable",
	requires: ["Ext.util.Region"],
	constructor: function (a) {
		a = a || {};
		Ext.apply(this, a, {
			xOffset: 8,
			yOffset: 7,
			midRowOffset: 6,
			arrowOffset: 8
		})
	},
	getTaskBox: function (a) {
		var h = Sch.util.Date,
			m = a.get("StartDate"),
			b = a.get("EndDate"),
			e = this.ganttView.getStart(),
			k = this.ganttView.getEnd();
		if (!a.isVisible() || (!h.intersectSpans(m, b, e, k))) {
			return null
		}
		var l = this.ganttView,
			c = l.getXYFromDate(h.max(m, e))[0],
			j = l.getXYFromDate(h.min(b, k))[0],
			g = Ext.get(l.getEventNodeByRecord(a) || l.getNode(a));
		if (!g) {
			return null
		}
		var f = this.view.getXOffset(a),
			d = g.getOffsetsTo(l.el),
			i = d[1] + l.el.getScroll().top;
		if (c > f) {
			c -= f
		}
		j += f - 1;
		return Ext.create("Ext.util.Region", i, j, i + g.getHeight(), c)
	},
	getDependencyTplData: function (c) {
		var n = this,
			j = n.taskStore;
		if (!Ext.isArray(c)) {
			c = [c]
		}
		if (c.length === 0 || j.getCount() <= 0) {
			return
		}
		var d = [],
			b = Gnt.model.Dependency.Type,
			o = n.ganttView,
			p, k, g, m, h, a;
		for (var f = 0, e = c.length; f < e; f++) {
			a = c[f];
			k = j.getNodeById(a.get("From"));
			g = j.getNodeById(a.get("To"));
			if (k && g) {
				m = n.getTaskBox(k);
				h = n.getTaskBox(g);
				if (m && h) {
					switch (a.get("Type")) {
					case b.StartToEnd:
						p = n.getStartToEndCoordinates(m, h);
						break;
					case b.StartToStart:
						p = n.getStartToStartCoordinates(m, h);
						break;
					case b.EndToStart:
						p = n.getEndToStartCoordinates(m, h);
						break;
					case b.EndToEnd:
						p = n.getEndToEndCoordinates(m, h);
						break;
					default:
						throw "Invalid case statement";
						break
					}
					if (p) {
						d.push({
							lineCoordinates: p,
							id: a.internalId
						})
					}
				}
			}
		}
		return d
	},
	intersectsViewport: function (f, d, b, a) {
		var e = this.taskStore.indexOf(f),
			c = this.taskStore.indexOf(d);
		return !((e < b && c < b) || (e > a && c > a))
	},
	getStartToStartCoordinates: function (e, d, c, i) {
		var b = e.left,
			g = e.top - 1 + ((e.bottom - e.top) / 2),
			a = d.left,
			f = d.top - 1 + ((d.bottom - d.top) / 2),
			h = e.top < d.top ? (f - this.yOffset - this.midRowOffset) : (f + this.yOffset + this.midRowOffset),
			j = this.xOffset + this.arrowOffset;
		if (b > (a + this.xOffset)) {
			j += (b - a)
		}
		return [{
			x1: b,
			y1: g,
			x2: b - j,
			y2: g
		}, {
			x1: b - j,
			y1: g,
			x2: b - j,
			y2: f
		}, {
			x1: b - j,
			y1: f,
			x2: a - this.arrowOffset,
			y2: f
		}]
	},
	getStartToEndCoordinates: function (f, e) {
		var c = f.left,
			i = f.top - 1 + ((f.bottom - f.top) / 2),
			a = e.right,
			g = e.top - 1 + ((e.bottom - e.top) / 2),
			j = f.top < e.top ? (g - this.yOffset - this.midRowOffset) : (g + this.yOffset + this.midRowOffset),
			h, b;
		if (a > (c + this.xOffset - this.arrowOffset) || Math.abs(a - c) < (2 * (this.xOffset + this.arrowOffset))) {
			b = c - this.xOffset - this.arrowOffset;
			var d = a + this.xOffset + this.arrowOffset;
			h = [{
				x1: c,
				y1: i,
				x2: b,
				y2: i
			}, {
				x1: b,
				y1: i,
				x2: b,
				y2: j
			}, {
				x1: b,
				y1: j,
				x2: d,
				y2: j
			}, {
				x1: d,
				y1: j,
				x2: d,
				y2: g
			}, {
				x1: d,
				y1: g,
				x2: a + this.arrowOffset,
				y2: g
			}]
		} else {
			b = c - this.xOffset - this.arrowOffset;
			h = [{
				x1: c,
				y1: i,
				x2: b,
				y2: i
			}, {
				x1: b,
				y1: i,
				x2: b,
				y2: g
			}, {
				x1: b,
				y1: g,
				x2: a + this.arrowOffset,
				y2: g
			}]
		}
		return h
	},
	getEndToStartCoordinates: function (f, e) {
		var c = f.right,
			i = f.top - 1 + ((f.bottom - f.top) / 2),
			a = e.left,
			g = e.top - 1 + ((e.bottom - e.top) / 2),
			j = f.top < e.top ? (g - this.yOffset - this.midRowOffset) : (g + this.yOffset + this.midRowOffset),
			h, b;
		if (a >= (c - 6) && g > i) {
			b = Math.max(c - 6, a) + this.xOffset;
			g = e.top;
			h = [{
				x1: c,
				y1: i,
				x2: b,
				y2: i
			}, {
				x1: b,
				y1: i,
				x2: b,
				y2: g - this.arrowOffset
			}]
		} else {
			b = c + this.xOffset + this.arrowOffset;
			var d = a - this.xOffset - this.arrowOffset;
			h = [{
				x1: c,
				y1: i,
				x2: b,
				y2: i
			}, {
				x1: b,
				y1: i,
				x2: b,
				y2: j
			}, {
				x1: b,
				y1: j,
				x2: d,
				y2: j
			}, {
				x1: d,
				y1: j,
				x2: d,
				y2: g
			}, {
				x1: d,
				y1: g,
				x2: a - this.arrowOffset,
				y2: g
			}]
		}
		return h
	},
	getEndToEndCoordinates: function (a, c) {
		var d = a.right,
			f = a.top - 1 + ((a.bottom - a.top) / 2),
			b = c.right + this.arrowOffset,
			e = c.top - 1 + ((c.bottom - c.top) / 2),
			g = b + this.xOffset + this.arrowOffset;
		if (d > (b + this.xOffset)) {
			g += d - b
		}
		return [{
			x1: d,
			y1: f,
			x2: g,
			y2: f
		}, {
			x1: g,
			y1: f,
			x2: g,
			y2: e
		}, {
			x1: g,
			y1: e,
			x2: b,
			y2: e
		}]
	}
});
Ext.define("Gnt.view.Dependency", {
	extend: "Ext.util.Observable",
	requires: ["Gnt.feature.DependencyDragDrop", "Gnt.view.DependencyPainter"],
	ganttView: null,
	painter: null,
	taskStore: null,
	store: null,
	dnd: null,
	lineTpl: null,
	enableDependencyDragDrop: true,
	constructor: function (a) {
		a = a || {};
		Ext.apply(this, a);
		var b = this.ganttView;
		this.taskStore = b.getTaskStore();
		b.on({
			refresh: this.renderAllDependencies,
			scope: this,
			buffer: 5,
			delay: (this.taskStore.buffered && Ext.isIE) ? 1 : 0
		});
		this.taskStore.on({
			"root-fill-start": this.unBindTaskStore,
			"root-fill-end": this.bindTaskStore,
			scope: this
		});
		this.bindTaskStore();
		this.store.on({
			datachanged: this.renderAllDependencies,
			load: this.renderAllDependencies,
			scope: this,
			buffer: 1
		});
		this.store.on({
			add: this.onDependencyAdd,
			update: this.onDependencyUpdate,
			remove: this.onDependencyDelete,
			scope: this
		});
		if (!this.lineTpl) {
			this.lineTpl = Ext.create("Ext.XTemplate", '<tpl for="."><tpl for="lineCoordinates"><div class="sch-dependency sch-dep-{parent.id} sch-dependency-line" style="left:{[Math.min(values.x1, values.x2)]}px;top:{[Math.min(values.y1, values.y2)]}px;width:{[Math.abs(values.x1-values.x2)' + (Ext.isBorderBox ? "+2" : "") + "]}px;height:{[Math.abs(values.y1-values.y2)" + (Ext.isBorderBox ? "+2" : "") + ']}px"></div></tpl><div style="left:{[values.lineCoordinates[values.lineCoordinates.length - 1].x2]}px;top:{[values.lineCoordinates[values.lineCoordinates.length - 1].y2]}px" class="sch-dependency-arrow-ct sch-dependency sch-dep-{id} "><img src="' + Ext.BLANK_IMAGE_URL + '" class="sch-dependency-arrow sch-dependency-arrow-{[this.getArrowDirection(values.lineCoordinates)]}" /></div></tpl>', {
				compiled: true,
				disableFormats: true,
				getArrowDirection: function (d) {
					var c = d[d.length - 1];
					if (c.x1 === c.x2) {
						return "down"
					} else {
						if (c.x1 > c.x2) {
							return "left"
						} else {
							return "right"
						}
					}
				}
			})
		}
		this.painter = Ext.create("Gnt.view.DependencyPainter", Ext.apply({
			rowHeight: b.rowHeight,
			taskStore: this.taskStore,
			view: b
		}, a));
		this.addEvents("beforednd", "dndstart", "drop", "afterdnd", "beforecascade", "cascade", "dependencydblclick");
		if (this.enableDependencyDragDrop) {
			this.dnd = Ext.create("Gnt.feature.DependencyDragDrop", {
				ganttView: this.ganttView
			});
			this.dnd.on("drop", this.onDependencyDrop, this);
			this.relayEvents(this.dnd, ["beforednd", "dndstart", "afterdnd", "drop"])
		}
		this.ganttView.mon(this.containerEl, "dblclick", function (d, c) {
			var f = this.getRecordForDependencyEl(c);
			this.fireEvent("dependencydblclick", this, f, d, c)
		}, this, {
			delegate: ".sch-dependency"
		});
		this.callParent(arguments)
	},
	bindTaskStore: function () {
		this.taskStore.on({
			expand: this.renderAllDependencies,
			collapse: this.renderAllDependencies,
			cascade: this.renderAllDependencies,
			remove: this.renderAllDependencies,
			insert: this.renderAllDependencies,
			append: this.renderAllDependencies,
			scope: this,
			buffer: 1
		});
		this.taskStore.on({
			update: this.onTaskUpdated,
			scope: this,
			delay: 1
		})
	},
	unBindTaskStore: function () {
		this.taskStore.un({
			expand: this.renderAllDependencies,
			collapse: this.renderAllDependencies,
			cascade: this.renderAllDependencies,
			remove: this.renderAllDependencies,
			insert: this.renderAllDependencies,
			append: this.renderAllDependencies,
			scope: this,
			buffer: 1
		});
		this.taskStore.un({
			update: this.onTaskUpdated,
			scope: this,
			delay: 1
		})
	},
	highlightDependency: function (a) {
		if (!(a instanceof Ext.data.Model)) {
			a = this.getDependencyRecordByInternalId(a)
		}
		this.getElementsForDependency(a).addCls("sch-dependency-selected")
	},
	unhighlightDependency: function (a) {
		if (!(a instanceof Ext.data.Model)) {
			a = this.getDependencyRecordByInternalId(a)
		}
		this.getElementsForDependency(a).removeCls("sch-dependency-selected")
	},
	getElementsForDependency: function (a) {
		var b = a instanceof Ext.data.Model ? a.internalId : a;
		return this.containerEl.select(".sch-dep-" + b)
	},
	depRe: new RegExp("sch-dep-([^\\s]+)"),
	getDependencyRecordByInternalId: function (d) {
		var c, b, a;
		for (b = 0, a = this.store.getCount(); b < a; b++) {
			c = this.store.getAt(b);
			if (c.internalId == d) {
				return c
			}
		}
		return null
	},
	getRecordForDependencyEl: function (c) {
		var a = c.className.match(this.depRe),
			d = null;
		if (a && a[1]) {
			var b = a[1];
			d = this.getDependencyRecordByInternalId(b)
		}
		return d
	},
	renderAllDependencies: function () {
		this.containerEl.select(".sch-dependency").remove();
		this.renderDependencies(this.store.data.items)
	},
	renderDependencies: function (b) {
		if (b && b.length > 0) {
			var a = this.painter.getDependencyTplData(b);
			this.lineTpl[Ext.isIE ? "insertFirst" : "append"](this.containerEl, a)
		}
	},
	renderTaskDependencies: function (h) {
		var d, a = this.store.getCount(),
			g, f = [];
		if (!Ext.isArray(h)) {
			h = [h]
		}
		for (var c = 0, e = h.length; c < e; c++) {
			g = h[c].getId() || h[c].internalId;
			for (var b = 0; b < a; b++) {
				d = this.store.getAt(b);
				if (g == d.get("To") || g == d.get("From")) {
					f.push(d)
				}
			}
		}
		this.renderDependencies(f)
	},
	onDependencyUpdate: function (b, a) {
		this.removeDependencyElements(a, false);
		this.renderDependencies(a)
	},
	onDependencyAdd: function (a, b) {
		var c = b[0];
		this.renderDependencies(c)
	},
	removeDependencyElements: function (a, b) {
		if (b !== false) {
			this.getElementsForDependency(a).fadeOut({
				remove: true
			})
		} else {
			this.getElementsForDependency(a).remove()
		}
	},
	onDependencyDelete: function (b, a) {
		this.removeDependencyElements(a)
	},
	dimEventDependencies: function (a) {
		this.containerEl.select(this.depRe + a).setOpacity(0.2)
	},
	onTaskUpdated: function (c, b, a) {
		if (a != Ext.data.Model.COMMIT) {
			this.updateDependencies(b)
		}
	},
	updateDependencies: function (b) {
		if (!Ext.isArray(b)) {
			b = [b]
		}
		var a = this;
		Ext.each(b, function (c) {
			Ext.each(c.getAllDependencies(), function (d) {
				a.removeDependencyElements(d, false)
			})
		});
		this.renderTaskDependencies(b)
	},
	onDependencyDrop: function (d, b, a, c) {
		if (this.taskStore.isValidDependency(b, a)) {
			this.store.add(new this.store.model({
				From: b,
				To: a,
				Type: c
			}))
		}
	},
	destroy: function () {
		if (this.dnd) {
			this.dnd.destroy()
		}
	}
});
Ext.define("Gnt.view.Gantt", {
	extend: "Sch.view.TimelineTreeView",
	alias: ["widget.ganttview"],
	requires: ["Gnt.view.Dependency", "Gnt.model.Task", "Gnt.template.Task", "Gnt.template.ParentTask", "Gnt.template.Milestone", "Gnt.feature.TaskDragDrop", "Gnt.feature.ProgressBarResize", "Gnt.feature.TaskResize", "Sch.view.Horizontal"],
	uses: ["Gnt.feature.LabelEditor", "Gnt.feature.DragCreator"],
	_cmpCls: "sch-ganttview",
	rowHeight: 22,
	barMargin: 4,
	scheduledEventName: "task",
	toggleParentTasksOnClick: true,
	trackOver: false,
	toggleOnDblClick: false,
	milestoneOffset: 8,
	parentTaskOffset: 6,
	eventSelector: ".sch-gantt-item",
	eventWrapSelector: ".sch-event-wrap",
	progressBarResizer: null,
	taskResizer: null,
	taskDragDrop: null,
	dragCreator: null,
	dependencyView: null,
	constructor: function (a) {
		var b = a.panel._top;
		Ext.apply(this, {
			taskStore: b.taskStore,
			dependencyStore: b.dependencyStore,
			enableDependencyDragDrop: b.enableDependencyDragDrop,
			enableTaskDragDrop: b.enableTaskDragDrop,
			enableProgressBarResize: b.enableProgressBarResize,
			enableDragCreation: b.enableDragCreation,
			allowParentTaskMove: b.allowParentTaskMove,
			toggleParentTasksOnClick: b.toggleParentTasksOnClick,
			resizeHandles: b.resizeHandles,
			showBaseline: b.showBaseline,
			leftLabelField: b.leftLabelField,
			rightLabelField: b.rightLabelField,
			eventTemplate: b.eventTemplate,
			parentEventTemplate: b.parentEventTemplate,
			milestoneTemplate: b.milestoneTemplate
		});
		this.addEvents("taskclick", "taskdblclick", "taskcontextmenu", "beforetaskresize", "taskresizestart", "partialtaskresize", "aftertaskresize", "beforeprogressbarresize", "progressbarresizestart", "afterprogressbarresize", "beforetaskdrag", "taskdragstart", "taskdrop", "aftertaskdrop", "labeledit_beforestartedit", "labeledit_beforecomplete", "labeledit_complete", "beforedependencydrag", "dependencydragstart", "dependencydrop", "afterdependencydragdrop");
		this.callParent(arguments)
	},
	initComponent: function () {
		this.configureLabels();
		this.setupGanttEvents();
		this.configureFeatures();
		this.callParent(arguments);
		this.setupTemplates()
	},
	getTaskStore: function () {
		return this.taskStore
	},
	getDependencyStore: function () {
		return this.dependencyStore
	},
	configureFeatures: function () {
		if (this.enableProgressBarResize !== false) {
			this.progressBarResizer = Ext.create("Gnt.feature.ProgressBarResize", {
				gantt: this
			});
			this.on({
				beforeprogressbarresize: this.onBeforeTaskProgressBarResize,
				progressbarresizestart: this.onTaskProgressBarResizeStart,
				afterprogressbarresize: this.onTaskProgressBarResizeEnd,
				scope: this
			})
		}
		if (this.resizeHandles !== "none") {
			this.taskResizer = Ext.create("Gnt.feature.TaskResize", Ext.apply({
				gantt: this,
				validatorFn: this.resizeValidatorFn || Ext.emptyFn,
				validatorFnScope: this.validatorFnScope || this
			}, this.resizeConfig || {}));
			this.on({
				beforedragcreate: this.onBeforeDragCreate,
				beforeresize: this.onBeforeTaskResize,
				taskresizestart: this.onTaskResizeStart,
				aftertaskresize: this.onTaskResizeEnd,
				scope: this
			})
		}
		if (this.enableTaskDragDrop) {
			this.taskDragDrop = Ext.create("Gnt.feature.TaskDragDrop", Ext.apply({
				gantt: this,
				validatorFn: this.dndValidatorFn || Ext.emptyFn,
				validatorFnScope: this.validatorFnScope || this
			}, this.dragDropConfig));
			this.on({
				beforetaskdrag: this.onBeforeTaskDrag,
				taskdragstart: this.onDragDropStart,
				aftertaskdrop: this.onDragDropEnd,
				scope: this
			})
		}
		if (this.enableDragCreation) {
			this.dragCreator = Ext.create("Gnt.feature.DragCreator", Ext.apply({
				ganttView: this
			}))
		}
	},
	prepareData: function (c, a, b) {
		var e = this.callParent(arguments);
		if (this.headerCt.items.getCount() === 0) {
			return e
		}
		e[this.headerCt.getGridColumns()[0].id] = this.renderTask(b);
		e.rowHeight = this.rowHeight;
		return e
	},
	renderTask: function (i) {
		var j = i.get("StartDate"),
			l = this.timeAxis,
			p = Sch.util.Date,
			b = {},
			m;
		if (j) {
			var x = "",
				s = i.getEndDate() || Sch.util.Date.add(j, Sch.util.Date.DAY, 1),
				g = l.getStart(),
				f = l.getEnd(),
				d = Sch.util.Date.intersectSpans(j, s, g, f);
			if (d) {
				var z = i.isMilestone(),
					u = i.isLeaf(),
					r = s > f,
					n = p.betweenLesser(j, g, f),
					w = Math.floor(this.getXYFromDate(n ? j : g)[0]),
					e = z ? 0 : Math.floor(this.getXYFromDate(r ? f : s)[0]) - w;
				if (!z && !u) {
					e += 12
				}
				b = {
					id: i.internalId,
					leftOffset: w,
					internalcls: (i.dirty ? " sch-dirty" : ""),
					width: Math.max(1, e),
					percentDone: i.get("PercentDone") || 0
				};
				m = this.eventRenderer.call(this, i, b, i.store) || {};
				var q = this.leftLabelField,
					h = this.rightLabelField,
					y;
				if (q) {
					b.leftLabel = q.renderer.call(q.scope || this, i.data[q.dataIndex], i)
				}
				if (h) {
					b.rightLabel = h.renderer.call(h.scope || this, i.data[h.dataIndex], i)
				}
				Ext.apply(b, m);
				if (z) {
					y = this.milestoneTemplate
				} else {
					b.width = Math.max(1, e);
					if (r) {
						b.internalcls += " sch-event-endsoutside "
					}
					if (!n) {
						b.internalcls += " sch-event-startsoutside "
					}
					y = this[u ? "eventTemplate" : "parentEventTemplate"]
				}
				x += y.apply(b)
			}
		}
		if (this.showBaseline) {
			var o = i.get("BaselineStartDate"),
				a = i.get("BaselineEndDate");
			if (!m) {
				m = this.eventRenderer.call(this, i, b, i.store) || {}
			}
			if (o && a) {
				var c = (a - o === 0),
					t = c ? this.baselineMilestoneTemplate : (i.isLeaf() ? this.baselineTaskTemplate : this.baselineParentTaskTemplate),
					k = Math.floor(this.getXYFromDate(o)[0]),
					v = Math.floor(this.getXYFromDate(a)[0]) - k;
				x += t.apply({
					basecls: m.basecls || "",
					id: i.internalId + "-base",
					percentDone: i.get("BaselinePercentDone") || 0,
					leftOffset: k,
					width: Math.max(1, Ext.isBorderBox ? v : v - this.eventBorderWidth)
				})
			}
		}
		return x
	},
	setupTemplates: function () {
		var a = {
			leftLabel: !! this.leftLabelField,
			rightLabel: !! this.rightLabelField,
			prefix: this.eventPrefix,
			enableDependencyDragDrop: this.enableDependencyDragDrop !== false,
			resizeHandles: this.resizeHandles,
			enableProgressBarResize: this.enableProgressBarResize
		};
		if (!this.eventTemplate) {
			a.baseCls = "sch-gantt-task {ctcls}";
			this.eventTemplate = Ext.create("Gnt.template.Task", a)
		}
		if (!this.parentEventTemplate) {
			a.baseCls = "sch-gantt-parent-task {ctcls}";
			this.parentEventTemplate = Ext.create("Gnt.template.ParentTask", a)
		}
		if (!this.milestoneTemplate) {
			a.baseCls = "sch-gantt-milestone {ctcls}";
			this.milestoneTemplate = Ext.create("Gnt.template.Milestone", a)
		}
		if (this.showBaseline) {
			a = {
				prefix: this.eventPrefix
			};
			if (!this.baselineTaskTemplate) {
				a.baseCls = "sch-gantt-task-baseline sch-gantt-baseline-item {basecls}";
				this.baselineTaskTemplate = Ext.create("Gnt.template.Task", a)
			}
			if (!this.baselineParentTaskTemplate) {
				a.baseCls = "sch-gantt-parenttask-baseline sch-gantt-baseline-item {basecls}";
				this.baselineParentTaskTemplate = Ext.create("Gnt.template.ParentTask", a)
			}
			if (!this.baselineMilestoneTemplate) {
				a.baseCls = "sch-gantt-milestone-baseline sch-gantt-baseline-item {basecls}";
				this.baselineMilestoneTemplate = Ext.create("Gnt.template.Milestone", a)
			}
		}
	},
	getDependencyView: function () {
		return this.dependencyView
	},
	getTaskStore: function () {
		return this.taskStore
	},
	initDependencies: function () {
		if (this.dependencyStore) {
			var b = this,
				a = Ext.create("Gnt.view.Dependency", {
					containerEl: b.el,
					ganttView: b,
					enableDependencyDragDrop: b.enableDependencyDragDrop,
					store: b.dependencyStore
				});
			a.on({
				beforednd: b.onBeforeDependencyDrag,
				dndstart: b.onDependencyDragStart,
				drop: b.onDependencyDrop,
				afterdnd: b.onAfterDependencyDragDrop,
				beforecascade: b.onBeforeCascade,
				cascade: b.onCascade,
				scope: b
			});
			b.dependencyView = a;
			b.relayEvents(a, ["dependencydblclick"])
		}
	},
	setupGanttEvents: function () {
		var a = this.getSelectionModel();
		if (this.toggleParentTasksOnClick) {
			this.on({
				taskclick: function (c, b) {
					if (!b.isLeaf()) {
						this.toggle(b)
					}
				},
				scope: this
			})
		}
	},
	configureLabels: function () {
		var c = {
			renderer: function (d) {
				return d
			},
			dataIndex: undefined
		};
		var b = this.leftLabelField;
		if (b) {
			if (Ext.isString(b)) {
				b = this.leftLabelField = {
					dataIndex: b
				}
			}
			Ext.applyIf(b, c);
			if (b.editor) {
				b.editor = Ext.create("Gnt.feature.LabelEditor", this, {
					alignment: "r-r",
					delegate: ".sch-gantt-label-left",
					labelPosition: "left",
					field: b.editor,
					dataIndex: b.dataIndex
				})
			}
		}
		var a = this.rightLabelField;
		if (a) {
			if (Ext.isString(a)) {
				a = this.rightLabelField = {
					dataIndex: a
				}
			}
			Ext.applyIf(a, c);
			if (a.editor) {
				a.editor = Ext.create("Gnt.feature.LabelEditor", this, {
					alignment: "l-l",
					delegate: ".sch-gantt-label-right",
					labelPosition: "right",
					field: a.editor,
					dataIndex: a.dataIndex
				})
			}
		}
		this.on("labeledit_beforestartedit", this.onBeforeLabelEdit, this)
	},
	onBeforeTaskDrag: function (b, a) {
		return !this.readOnly && (this.allowParentTaskMove || a.isLeaf())
	},
	onDragDropStart: function () {
		if (this.tip) {
			this.tip.disable()
		}
	},
	onDragDropEnd: function () {
		if (this.tip) {
			this.tip.enable()
		}
	},
	onTaskProgressBarResizeStart: function () {
		if (this.tip) {
			this.tip.hide();
			this.tip.disable()
		}
	},
	onTaskProgressBarResizeEnd: function () {
		if (this.tip) {
			this.tip.enable()
		}
	},
	onTaskResizeStart: function () {
		if (this.tip) {
			this.tip.hide();
			this.tip.disable()
		}
	},
	onTaskResizeEnd: function () {
		if (this.tip) {
			this.tip.enable()
		}
	},
	onBeforeDragCreate: function () {
		return !this.readOnly
	},
	onBeforeTaskResize: function () {
		return !this.readOnly
	},
	onBeforeTaskProgressBarResize: function () {
		return !this.readOnly
	},
	onBeforeLabelEdit: function () {
		return !this.readOnly
	},
	onBeforeEdit: function () {
		return !this.readOnly
	},
	afterRender: function () {
		this.el.addCls("sch-ganttview");
		this.initDependencies();
		this.callParent(arguments)
	},
	resolveTaskRecord: function (a) {
		var b = this.findItemByChild(a);
		if (b) {
			return this.getRecord(this.findItemByChild(a))
		}
		return null
	},
	resolveEventRecord: function (a) {
		return this.resolveTaskRecord(a)
	},
	highlightTask: function (b, a) {
		if (!(b instanceof Ext.data.Model)) {
			b = this.taskStore.getById(b)
		}
		if (b) {
			Ext.fly(this.getNode(b)).addCls("sch-gantt-task-highlighted");
			var c = b.getId() || b.internalId;
			if (a !== false) {
				this.dependencyStore.each(function (d) {
					if (d.get("From") == c) {
						this.highlightDependency(d.id);
						this.highlightTask(d.get("To"), a)
					}
				}, this)
			}
		}
	},
	unhighlightTask: function (a, c) {
		if (!(a instanceof Ext.data.Model)) {
			a = this.taskStore.getById(a)
		}
		if (a) {
			Ext.fly(this.getNode(a)).removeCls("sch-gantt-task-highlighted");
			var b = a.getId() || a.internalId;
			if (c !== false) {
				this.dependencyStore.each(function (d) {
					if (d.get("From") == b) {
						this.unhighlightDependency(d.id);
						this.unhighlightTask(d.get("To"), c)
					}
				}, this)
			}
		}
	},
	clearSelectedTasksAndDependencies: function () {
		this.getSelectionModel().deselectAll();
		this.el.select(".sch-dependency-selected").removeCls("sch-dependency-selected");
		this.el.select("tr.sch-gantt-task-highlighted").removeCls("sch-gantt-task-highlighted")
	},
	getCriticalPaths: function () {
		return this.taskStore.getCriticalPaths()
	},
	highlightCriticalPaths: function () {
		this.clearSelectedTasksAndDependencies();
		var g = this.getCriticalPaths(),
			c = this.getDependencyView(),
			f = this.dependencyStore,
			e, d, b, a;
		Ext.each(g, function (h) {
			for (d = 0, b = h.length; d < b; d++) {
				e = h[d];
				this.highlightTask(e, false);
				if (d < (b - 1)) {
					a = f.getAt(f.findBy(function (i) {
						return i.get("To") === (e.getId() || e.internalId) && i.get("From") === (h[d + 1].getId() || h[d + 1].internalId)
					}));
					c.highlightDependency(a)
				}
			}
		}, this);
		this.el.addCls("sch-gantt-critical-chain");
		this.getSelectionModel().setLocked(true)
	},
	unhighlightCriticalPaths: function () {
		this.el.removeCls("sch-gantt-critical-chain");
		this.getSelectionModel().setLocked(false);
		this.clearSelectedTasksAndDependencies()
	},
	getXOffset: function (a) {
		var b = 0;
		if (a.isMilestone()) {
			b = this.milestoneOffset
		} else {
			if (!a.isLeaf()) {
				b = this.parentTaskOffset
			}
		}
		return b
	},
	onDestroy: function () {
		if (this.dependencyView) {
			this.dependencyView.destroy()
		}
		this.callParent(arguments)
	},
	highlightDependency: function (a) {
		this.dependencyView.highlightDependency(a)
	},
	unhighlightDependency: function (a) {
		this.dependencyView.unhighlightDependency(a)
	},
	onBeforeDependencyDrag: function (b, a) {
		return this.fireEvent("beforedependencydrag", this, a)
	},
	onDependencyDragStart: function (a) {
		this.fireEvent("dependencydragstart", this);
		if (this.tip) {
			this.tip.disable()
		}
	},
	onDependencyDrop: function (b, c, a, d) {
		this.fireEvent("dependencydrop", this, this.taskStore.getNodeById(c), this.taskStore.getById(a), d)
	},
	onAfterDependencyDragDrop: function () {
		this.fireEvent("afterdependencydragdrop", this);
		if (this.tip) {
			this.tip.enable()
		}
	},
	onBeforeCascade: function (a, b) {
		this.taskStore.un("update", this.onUpdate, this)
	},
	onCascade: function (a, b) {
		this.taskStore.on("update", this.onUpdate, this)
	},
	getLeftEditor: function () {
		return this.leftLabelField.editor
	},
	getRightEditor: function () {
		return this.rightLabelField.editor
	},
	editLeftLabel: function (a) {
		var b = this.getLeftEditor();
		if (b) {
			b.edit(a)
		}
	},
	editRightLabel: function (a) {
		var b = this.getRightEditor();
		if (b) {
			b.edit(a)
		}
	},
	getOuterElementFromEventRecord: function (a) {
		return this.callParent([a]).up(this.eventWrapSelector)
	},
	getDependenciesForTask: function (a) {
		console.warn("`ganttPanel.getDependenciesForTask()` is deprecated, use `task.getAllDependencies()` instead");
		return a.getAllDependencies()
	},
	setNewTemplate: function () {
		var b = this,
			a = b.headerCt.getColumnsForTpl(true);
		b.tpl = b.getTableChunker().getTableTpl({
			columns: [a[0]],
			features: b.features
		})
	}
});
Ext.define("Gnt.panel.Gantt", {
	extend: "Sch.panel.TimelineTreePanel",
	alias: ["widget.ganttpanel"],
	alternateClassName: ["Sch.gantt.GanttPanel"],
	requires: ["Gnt.view.Gantt", "Gnt.model.Dependency", "Gnt.feature.WorkingTime", "Gnt.data.Calendar", "Gnt.data.TaskStore"],
	uses: ["Sch.plugin.CurrentTimeLine"],
	lockedXType: "treepanel",
	normalXType: "ganttpanel",
	viewType: "ganttview",
	leftLabelField: null,
	rightLabelField: null,
	highlightWeekends: true,
	weekendsAreWorkdays: false,
	skipWeekendsDuringDragDrop: true,
	enableTaskDragDrop: true,
	enableDependencyDragDrop: true,
	enableProgressBarResize: false,
	toggleParentTasksOnClick: true,
	recalculateParents: true,
	cascadeChanges: false,
	showTodayLine: false,
	showBaseline: false,
	workingTimePlugin: null,
	todayLinePlugin: null,
	allowParentTaskMove: false,
	enableDragCreation: true,
	eventRenderer: Ext.emptyFn,
	eventTemplate: null,
	parentEventTemplate: null,
	milestoneTemplate: null,
	autoHeight: null,
	calendar: null,
	taskStore: null,
	resourceStore: null,
	assignmentStore: null,
	columnLines: false,
	dndValidatorFn: Ext.emptyFn,
	resizeHandles: "both",
	resizeValidatorFn: Ext.emptyFn,
	resizeConfig: null,
	initComponent: function () {
		this.autoHeight = false;
		var a = this.taskStore || this.store;
		if (!a) {
			Ext.Error.raise("You must specify an taskStore config")
		}
		if (!(a instanceof Gnt.data.TaskStore)) {
			Ext.Error.raise("A `taskStore` should be an instance of `Gnt.data.TaskStore` (or of its subclass)")
		}
		Ext.apply(this, {
			store: a,
			taskStore: a
		});
		var c = this.calendar = a.calendar;
		if (this.hasOwnProperty("weekendsAreWorkdays")) {
			c.weekendsAreWorkdays = this.weekendsAreWorkdays
		}
		if (a.dependencyStore) {
			this.dependencyStore = a.dependencyStore
		} else {
			if (this.dependencyStore) {
				a.setDependencyStore(this.dependencyStore)
			} else {
				this.dependencyStore = Ext.create("Ext.data.Store", {
					model: "Gnt.model.Dependency"
				});
				a.setDependencyStore(this.dependencyStore)
			}
		}
		if (this.hasOwnProperty("cascadeChanges")) {
			this.setCascadeChanges(this.cascadeChanges)
		}
		if (this.hasOwnProperty("recalculateParents")) {
			this.setRecalculateParents(this.recalculateParents)
		}
		if (this.hasOwnProperty("skipWeekendsDuringDragDrop")) {
			this.setSkipWeekendsDuringDragDrop(this.skipWeekendsDuringDragDrop)
		}
		if (this.lockable) {
			this.configureFunctionality()
		}
		this.callParent(arguments);
		if (this.lockable) {
			var b = this.getSchedulingView();
			b.store.calendar = c;
			if (this.assignmentStore) {
				this.assignmentStore.on({
					datachanged: function () {
						this.getView().refresh()
					},
					scope: this
				})
			}
			if (this.resourceStore) {
				this.resourceStore.on({
					datachanged: function () {
						this.getView().refresh()
					},
					scope: this
				})
			}
			this.relayEvents(b, ["taskclick", "taskdblclick", "taskcontextmenu", "beforetaskresize", "taskresizestart", "partialtaskresize", "aftertaskresize", "beforeprogressbarresize", "progressbarresizestart", "afterprogressbarresize", "beforetaskdrag", "taskdragstart", "taskdrop", "aftertaskdrop", "labeledit_beforestartedit", "labeledit_beforecomplete", "labeledit_complete", "beforedependencydrag", "dependencydragstart", "dependencydrop", "afterdependencydragdrop", "dependencydblclick"])
		}
	},
	getDependencyView: function () {
		return this.getSchedulingView().getDependencyView()
	},
	disableWeekendHighlighting: function (a) {
		this.workingTimePlugin.setDisabled(a)
	},
	resolveTaskRecord: function (a) {
		return this.getSchedulingView().getRecord(a)
	},
	fitTimeColumns: function () {
		this.getSchedulingView().fitColumns()
	},
	getTaskStore: function () {
		return this.taskStore
	},
	getDependencyStore: function () {
		return this.dependencyStore
	},
	onDragDropStart: function () {
		if (this.tip) {
			this.tip.hide();
			this.tip.disable()
		}
	},
	onDragDropEnd: function () {
		if (this.tip) {
			this.tip.enable()
		}
	},
	configureFunctionality: function () {
		var a = this.plugins = [].concat(this.plugins || []);
		if (this.highlightWeekends) {
			this.workingTimePlugin = Ext.create("Gnt.feature.WorkingTime", {
				calendar: this.calendar
			});
			a.push(this.workingTimePlugin)
		}
		if (this.showTodayLine) {
			this.todayLinePlugin = new Sch.plugin.CurrentTimeLine();
			a.push(this.todayLinePlugin)
		}
	},
	afterRender: function () {
		if (this.lockable) {
			var a = "sch-ganttpanel ";
			a += ["sch-horizontal", (this.highlightWeekends ? "sch-ganttpanel-highlightweekends" : ""), (this.showBaseline ? "sch-ganttpanel-showbaseline" : "")].join(" ");
			this.addCls(a)
		}
		this.callParent(arguments)
	},
	zoomToFit: function () {
		var a = this.taskStore.getTotalTimeSpan();
		if (a.start && a.end && a.start < a.end) {
			this.setTimeSpan(a.start, a.end);
			this.fitTimeColumns()
		}
	},
	getCascadeChanges: function () {
		return this.taskStore.cascadeChanges
	},
	setCascadeChanges: function (a) {
		this.taskStore.cascadeChanges = a
	},
	getRecalculateParents: function () {
		return this.taskStore.recalculateParents
	},
	setRecalculateParents: function (a) {
		this.taskStore.recalculateParents = a
	},
	setSkipWeekendsDuringDragDrop: function (a) {
		this.taskStore.skipWeekendsDuringDragDrop = this.skipWeekendsDuringDragDrop = a
	},
	getSkipWeekendsDuringDragDrop: function () {
		return this.taskStore.skipWeekendsDuringDragDrop
	}
});
Ext.define("Gnt.column.Duration.Field", {
	extend: "Ext.form.field.Number",
	alias: "widget.durationfield",
	disableKeyFilter: true,
	minValue: 0,
	durationRegex: /(-?\d+(?:[.,]\d+)?)\s*(\w+)?/i,
	unitsRegex: {
		MILLI: /^ms$|^mil/i,
		SECOND: /^s$|^sec/i,
		MINUTE: /^m$|^min/i,
		HOUR: /^h$|^hr$|^hour/i,
		DAY: /^d$|^day/i,
		WEEK: /^w$|^wk|^week/i,
		MONTH: /^mo|^mnt/i,
		QUARTER: /^q$|^quar|^qrt/i,
		YEAR: /^y$|^yr|^year/i
	},
	durationUnit: "h",
	rawToValue: function (b) {
		var a = this.parseDuration(b);
		if (!a) {
			return null
		}
		this.durationUnit = a.unit;
		return a.value != null ? a.value : null
	},
	valueToRaw: function (a) {
		if (Ext.isNumber(a)) {
			return parseFloat(Ext.Number.toFixed(a, this.decimalPrecision)) + " " + Sch.util.Date.getReadableNameOfUnit(this.durationUnit)
		}
		return ""
	},
	parseDuration: function (c) {
		if (c == null || !this.durationRegex.test(c)) {
			return null
		}
		var a = this.durationRegex.exec(c);
		var e = this.parseValue(a[1]);
		var b = a[2];
		var d;
		if (b) {
			Ext.iterate(this.unitsRegex, function (f, g) {
				if (g.test(b)) {
					d = Sch.util.Date.getUnitByName(f);
					return false
				}
			})
		}
		return {
			value: e,
			unit: d || this.durationUnit
		}
	},
	getDurationValue: function () {
		return this.parseDuration(this.getRawValue())
	},
	getErrors: function (b) {
		var a = this.parseDuration(b);
		if (!a) {
			return ["Invalid number format"]
		}
		return this.callParent([a.value])
	}
});
Ext.define("Gnt.column.Duration.Editor", {
	extend: "Ext.grid.CellEditor",
	alias: "widget.durationcolumneditor",
	context: null,
	decimalPrecision: 2,
	constructor: function (a) {
		a = a || {};
		a.field = a.field || Ext.create("Gnt.column.Duration.Field", {
			decimalPrecision: a.decimalPrecision || 2
		});
		this.callParent([a])
	},
	startEdit: function (c, b, a) {
		this.context = a;
		this.field.durationUnit = a.record.get("DurationUnit");
		return this.callParent(arguments)
	},
	completeEdit: function (a) {
		var d = this,
			g = d.field,
			e;
		if (!d.editing) {
			return
		}
		if (g.assertValue) {
			g.assertValue()
		}
		e = d.getValue();
		if (!g.isValid()) {
			if (d.revertInvalid !== false) {
				d.cancelEdit(a)
			}
			return
		}
		if (String(e) === String(d.startValue) && d.ignoreNoChange) {
			d.hideEdit(a);
			return
		}
		if (d.fireEvent("beforecomplete", d, e, d.startValue) !== false) {
			e = d.getValue();
			if (d.updateEl && d.boundEl) {
				d.boundEl.update(e)
			}
			d.hideEdit(a);
			var c = this.context;
			var b = c.record;
			var f = this.field.getDurationValue();
			b.setDuration(f.value, f.unit)
		}
	}
});
Ext.define("Gnt.column.AssignmentUnits", {
	extend: "Ext.grid.column.Number",
	alias: "widget.assignmentunitscolumn",
	header: "Units",
	dataIndex: "Units",
	format: "0 %",
	align: "left"
});
Ext.define("Gnt.column.Duration", {
	extend: "Ext.grid.column.Column",
	alias: "widget.durationcolumn",
	requires: ["Gnt.column.Duration.Field", "Gnt.column.Duration.Editor"],
	header: "Duration",
	dataIndex: "Duration",
	width: 80,
	align: "left",
	decimalPrecision: 2,
	constructor: function (a) {
		a = a || {};
		a.editor = a.editor || Ext.create("Gnt.column.Duration.Editor", {
			decimalPrecision: a.decimalPrecision || 2
		});
		this.scope = this;
		this.callParent([a])
	},
	renderer: function (b, c, a) {
		if (!Ext.isNumber(b)) {
			return ""
		}
		b = parseFloat(Ext.Number.toFixed(b, this.decimalPrecision));
		return b + " " + Sch.util.Date.getReadableNameOfUnit(a.get("DurationUnit"), b > 1)
	}
});
Ext.define("Gnt.column.EndDate", {
	extend: "Ext.grid.column.Date",
	alias: "widget.enddatecolumn",
	header: "Finish",
	format: "Y-m-d",
	width: 100,
	align: "left",
	dataIndex: "EndDate",
	task: null,
	constructor: function (a) {
		a = a || {};
		var b = a.field || a.editor;
		delete a.field;
		delete a.editor;
		this.field = new Ext.grid.CellEditor({
			ignoreNoChange: true,
			field: b || {
				xtype: "datefield",
				format: a.format || this.format
			},
			listeners: {
				beforecomplete: this.onBeforeEditComplete,
				scope: this
			}
		});
		this.callParent([a]);
		this.scope = this;
		this.renderer = this.rendererFunc
	},
	rendererFunc: function (b, c, a) {
		if (!b) {
			return
		}
		if (a.getEndDate() > a.getStartDate()) {
			b = Sch.util.Date.add(b, Sch.util.Date.MILLI, -1)
		}
		return Ext.util.Format.date(b, this.format)
	},
	afterRender: function () {
		this.callParent(arguments);
		this.tree = this.ownerCt.up("treepanel");
		this.tree.on({
			edit: this.onTreeEdit,
			beforeedit: this.onBeforeTreeEdit,
			scope: this
		})
	},
	onBeforeTreeEdit: function (b) {
		if (b.column == this) {
			var a = this.task = b.record;
			if (a.getEndDate() > a.getStartDate()) {
				var c = Sch.util.Date.add(b.value, Sch.util.Date.MILLI, -1);
				this.field.startValue = b.value = Ext.Date.clearTime(c)
			}
		}
	},
	onBeforeEditComplete: function (b, c, a) {
		if (this.task && c < this.task.getStartDate()) {
			return false
		}
	},
	onTreeEdit: function (b, a) {
		if (a.column === this) {
			var c = Sch.util.Date.add(a.value, Sch.util.Date.DAY, 1);
			if (c - a.originalValue !== 0) {
				a.record.setEndDate(c, false)
			}
		}
	}
});
Ext.define("Gnt.column.PercentDone", {
	extend: "Ext.grid.column.Number",
	alias: "widget.percentdonecolumn",
	header: "% Done",
	dataIndex: "PercentDone",
	width: 50,
	format: "0",
	align: "center",
	field: {
		xtype: "numberfield",
		minValue: 0,
		maxValue: 100
	}
});
Ext.define("Gnt.column.ResourceAssignment", {
	extend: "Ext.grid.column.Column",
	alias: "widget.resourceassigmentcolumn",
	header: "Assigned Resources",
	dataIndex: "Id",
	tdCls: "sch-assignment-cell",
	showUnits: true,
	initComponent: function () {
		this.formatString = "{0}" + (this.showUnits ? " [{1}%]" : "");
		this.callParent(arguments)
	},
	render: function () {
		this.scope = this;
		this.callParent(arguments);
		this.assignmentStore = this.getOwnerHeaderCt().up("ganttpanel").assignmentStore
	},
	renderer: function (j, n, e, g, m, k, h) {
		var f = [],
			c = this.assignmentStore,
			a;
		if (c.resourceStore.getCount() > 0) {
			for (var d = 0, b = c.getCount(); d < b; d++) {
				a = c.getAt(d);
				if (a.data.TaskId === j) {
					f.push(Ext.String.format(this.formatString, a.getResourceName(), a.data.Units))
				}
			}
			return f.join(", ")
		}
	}
});
Ext.define("Gnt.column.ResourceName", {
	extend: "Ext.grid.column.Column",
	alias: "widget.resourcenamecolumn",
	header: "Resource Name",
	dataIndex: "ResourceName",
	flex: 1,
	align: "left"
});
Ext.define("Gnt.column.StartDate", {
	extend: "Ext.grid.column.Date",
	alias: "widget.startdatecolumn",
	header: "Start",
	format: "Y-m-d",
	dataIndex: "StartDate",
	width: 100,
	align: "left",
	constructor: function (a) {
		a = a || {};
		var b = a.field || a.editor;
		a.field = b || {
			xtype: "datefield",
			format: a.format || this.format
		};
		this.callParent([a])
	},
	afterRender: function () {
		this.callParent(arguments);
		this.tree = this.ownerCt.up("treepanel");
		this.tree.on("edit", this.onTreeEdit, this)
	},
	onTreeEdit: function (b, a) {
		if (a.column instanceof this.self && (a.value - a.originalValue !== 0)) {
			a.record.setStartDate(a.value, true)
		}
	}
});
Ext.define("Gnt.column.WBS", {
	extend: "Ext.grid.column.Column",
	alias: "widget.wbscolumn",
	header: "#",
	width: 40,
	align: "left",
	dataIndex: "index",
	renderer: function (f, g, b, h, d, e) {
		var a = e.getRootNode(),
			c = [];
		while (b !== a) {
			c.push(b.data.index + 1);
			b = b.parentNode
		}
		return c.reverse().join(".")
	}
});
Ext.define("Gnt.widget.AssignmentGrid", {
	extend: "Ext.grid.Panel",
	readOnly: false,
	cls: "gnt-assignmentgrid",
	defaultAssignedUnits: 100,
	alias: "widget.assignmentgrid",
	requires: ["Gnt.model.Resource", "Gnt.model.Assignment", "Gnt.column.ResourceName", "Gnt.column.AssignmentUnits", "Ext.grid.plugin.CellEditing"],
	sorter: {
		sorterFn: function (b, a) {
			var d = b.get("Units"),
				c = a.get("Units");
			if ((!d && !c) || (d && c)) {
				return b.get("ResourceName") < a.get("ResourceName") ? -1 : 1
			}
			return d ? -1 : 1
		}
	},
	constructor: function (a) {
		this.store = Ext.create("Ext.data.JsonStore", {
			model: Ext.define("Gnt.model.AssignmentEditing", {
				extend: "Gnt.model.Assignment",
				fields: ["ResourceName"]
			})
		});
		this.columns = this.buildColumns();
		if (!this.readOnly) {
			this.plugins = this.buildPlugins()
		}
		Ext.apply(this, {
			selModel: {
				selType: "checkboxmodel",
				mode: "MULTI",
				checkOnly: true
			}
		});
		this.callParent(arguments)
	},
	initComponent: function () {
		this.loadResources();
		this.resourceStore.on({
			datachanged: this.loadResources,
			scope: this
		});
		this.getSelectionModel().on("select", this.onSelect, this, {
			delay: 50
		});
		this.callParent(arguments)
	},
	onSelect: function (b, a) {
		if (!a.get("Units")) {
			a.set("Units", this.defaultAssignedUnits)
		}
	},
	loadResources: function () {
		var d = [],
			b = this.resourceStore,
			e;
		for (var c = 0, a = b.getCount(); c < a; c++) {
			e = b.getAt(c).data.Id;
			d.push({
				ResourceId: e,
				ResourceName: b.getById(e).get("Name")
			})
		}
		this.store.loadData(d)
	},
	buildPlugins: function () {
		var a = Ext.create("Ext.grid.plugin.CellEditing", {
			clicksToEdit: 1
		});
		a.on("edit", this.onEditingDone, this);
		return [a]
	},
	onEditingDone: function (a, b) {
		if (b.value) {
			this.getSelectionModel().select(b.record, true)
		} else {
			this.getSelectionModel().deselect(b.record);
			b.record.reject()
		}
	},
	buildColumns: function () {
		return [{
			xtype: "resourcenamecolumn",
			resourceStore: this.resourceStore
		}, {
			xtype: "assignmentunitscolumn",
			assignmentStore: this.assignmentStore,
			editor: {
				xtype: "numberfield",
				minValue: 0,
				step: 10
			}
		}]
	},
	loadTaskAssignments: function (e) {
		var c = this.store,
			g = this.getSelectionModel(),
			b;
		g.deselectAll(true);
		for (var d = 0, a = c.getCount(); d < a; d++) {
			c.getAt(d).data.Units = ""
		}
		c.suspendEvents();
		var f = this.assignmentStore.queryBy(function (h) {
			return h.data.TaskId === e
		});
		f.each(function (h) {
			b = c.findRecord("ResourceId", h.data.ResourceId, 0, false, true, true);
			if (b) {
				b.set("Units", h.data.Units);
				g.select(b, true, true)
			}
		});
		c.resumeEvents();
		c.sort(this.sorter);
		this.getView().refresh()
	}
});
Ext.define("Gnt.widget.AssignmentField", {
	extend: "Ext.form.field.Picker",
	alias: "widget.assignmenteditor",
	requires: ["Gnt.widget.AssignmentGrid"],
	matchFieldWidth: false,
	editable: false,
	cancelText: "Cancel",
	closeText: "Save and Close",
	assignmentStore: null,
	resourceStore: null,
	createPicker: function () {
		var a = Ext.create("Gnt.widget.AssignmentGrid", {
			ownerCt: this.ownerCt,
			renderTo: document.body,
			frame: true,
			floating: true,
			hidden: true,
			height: 200,
			width: 300,
			resourceStore: this.resourceStore,
			assignmentStore: this.assignmentStore,
			fbar: this.buildButtons()
		});
		return a
	},
	buildButtons: function () {
		return ["->", {
			text: this.closeText,
			handler: this.onGridClose,
			scope: this
		}, {
			text: this.cancelText,
			handler: this.collapse,
			scope: this
		}]
	},
	onExpand: function () {
		var a = this.resourceStore,
			b = this.picker;
		b.loadTaskAssignments(this.taskId)
	},
	onGridClose: function () {
		var b = this.picker.getSelectionModel(),
			a = b.selected;
		this.fireEvent("select", this, a);
		this.collapse()
	}
});
Ext.define("Gnt.widget.AssignmentCellEditor", {
	extend: "Ext.grid.CellEditor",
	requires: ["Gnt.model.Assignment", "Gnt.widget.AssignmentField"],
	assignmentStore: null,
	resourceStore: null,
	taskId: null,
	fieldConfig: null,
	constructor: function (a) {
		a = a || {};
		var b = a.fieldConfig || {};
		this.field = Ext.create("Gnt.widget.AssignmentField", Ext.apply(b, {
			assignmentStore: a.assignmentStore,
			resourceStore: a.resourceStore
		}));
		this.field.on("select", this.onSelect, this);
		this.callParent(arguments)
	},
	startEdit: function (c, d, b) {
		this.parentEl = null;
		var a = c.child("div").dom.innerHTML;
		this.taskId = this.field.taskId = b.value;
		this.callParent([c, a === "&nbsp;" ? "" : a])
	},
	onSelect: function (g, e) {
		var b = this.assignmentStore,
			d = this.taskId,
			f = [],
			c, a;
		e.each(function (h) {
			c = h.get("Units");
			if (c > 0) {
				f.push(Ext.create(b.model, {
					TaskId: d,
					ResourceId: h.get("ResourceId"),
					Units: c
				}))
			}
		});
		b.remove(b.queryBy(function (h) {
			return h.data.TaskId === d
		}).items);
		b.add(f);
		this.completeEdit()
	}
});
Ext.define("Gnt.widget.Calendar", {
	extend: "Ext.picker.Date",
	alias: "widget.ganttcalendar",
	requires: ["Gnt.data.Calendar", "Sch.util.Date"],
	calendar: null,
	startDate: null,
	endDate: null,
	disabledDatesText: "Holiday",
	initComponent: function () {
		if (!this.calendar) {
			Ext.Error.raise('Required attribute "calendar" is missed during initialization of `Gnt.widget.Calendar`')
		}
		if (!this.startDate) {
			Ext.Error.raise('Required attribute "calendar" is missed during initialization of `Gnt.widget.Calendar`')
		}
		if (!this.endDate) {
			this.endDate = Sch.util.Date.add(this.startDate, Sch.util.Date.MONTH, 1)
		}
		this.minDate = this.value = this.startDate;
		var a = this.disabledDates = [];
		Ext.each(this.calendar.getHolidaysRanges(this.startDate, this.endDate), function (b) {
			Ext.each(b.getDates(), function (c) {
				a.push(Ext.Date.format(c, "m/d/Y"))
			})
		});
		this.callParent(arguments)
	}
});
Ext.onReady(function () {
	if (Gnt.feature.DependencyDragDrop) {
		Gnt.feature.DependencyDragDrop.prototype.fromText = "From: <strong>{0}</strong> {1}<br/>";
		Gnt.feature.DependencyDragDrop.prototype.toText = "To: <strong>{0}</strong> {1}";
		Gnt.feature.DependencyDragDrop.prototype.startText = "Start";
		Gnt.feature.DependencyDragDrop.prototype.endText = "End"
	}
	if (Gnt.Tooltip) {
		Gnt.Tooltip.prototype.startText = "Starts: ";
		Gnt.Tooltip.prototype.endText = "Ends: ";
		Gnt.Tooltip.prototype.durationText = "Duration:";
		Gnt.Tooltip.prototype.dayText = "d"
	}
	if (Gnt.plugin.TaskContextMenu) {
		Gnt.plugin.TaskContextMenu.prototype.texts = {
			newTaskText: "New task",
			newMilestoneText: "New milestone",
			deleteTask: "Delete task(s)",
			editLeftLabel: "Edit left label",
			editRightLabel: "Edit right label",
			add: "Add...",
			deleteDependency: "Delete dependency...",
			addTaskAbove: "Task above",
			addTaskBelow: "Task below",
			addMilestone: "Milestone",
			addSubtask: "Sub-task",
			addSuccessor: "Successor",
			addPredecessor: "Predecessor"
		}
	}
	if (Gnt.plugin.DependencyEditor) {
		Gnt.plugin.DependencyEditor.override({
			fromText: "From",
			toText: "To",
			typeText: "Type",
			lagText: "Lag",
			endToStartText: "Finish-To-Start",
			startToStartText: "Start-To-Start",
			endToEndText: "Finish-To-Finish",
			startToEndText: "Start-To-Finish"
		})
	}
});
Ext.onReady(function () {
	if (window.location.href.match("bryntum.com|ext-scheduler.com")) {
		return
	} else {
		if (Sch && Sch.view && Sch.view.TimelineGridView) {
			var b = false;
			Sch.view.TimelineGridView.override({
				refresh: function () {
					this.callOverridden(arguments);
					if (b) {
						return
					}
					b = true;
					/*Ext.Function.defer(function () {
						this.el.select(this.eventSelector).setOpacity(0.15)
					}, 10 * 60 * 1000, this);
					var c = this.el.parent().createChild({
						tag: "a",
						href: "http://www.bryntum.com/store",
						title: "Click here to purchase a license",
						style: "display:block;height:54px;width:230px;background: #fff url(http://kotakpasir.local/gantt2/doraemon1.jpg) no-repeat;z-index:10000;border:1px solid #ddd;-webkit-box-shadow: 2px 2px 2px rgba(100, 100, 100, 0.5);-moz-box-shadow: 2px 2px 2px rgba(100, 100, 100, 0.5);-moz-border-radius:5px;-webkit-border-radius:5px;position:absolute;bottom:10px;right:15px;"
					});
					Ext.Function.defer(c.fadeOut, 10000, c);
					try {
						if (!Ext.util.Cookies.get("bmeval")) {
							Ext.util.Cookies.set("bmeval", new Date().getTime(), Ext.Date.add(new Date(), Ext.Date.YEAR, 2))
						} else {
							var g = Ext.util.Cookies.get("bmeval"),
								d = new Date(parseInt(g, 10));
							if (Ext.Date.add(d, Ext.Date.DAY, 45) < new Date()) {
								this.el.select(this.eventSelector).hide();
								this.el.mask("Trial Period Expired!").setStyle("z-index", 10000);
								this.refresh = Ext.emptyFn
							}
						}
					} catch (f) {}*/
				}
			})
		}
		if (Sch && Sch.view && Sch.view.TimelineTreeView) {
			var a = false;
			Sch.view.TimelineTreeView.override({
				refresh: function () {
					this.callOverridden(arguments);
					if (a) {
						return
					}
					a = true;
					/*Ext.Function.defer(function () {
						this.el.select(this.eventSelector).setOpacity(0.15)
					}, 10 * 60 * 1000, this);
					var c = this.el.parent().createChild({
						tag: "a",
						href: "http://www.bryntum.com/store",
						title: "Cocot lo!",
						style: "display:block;height:54px;width:230px;background: #fff url(http://kotakpasir.local/gantt2/doraemon1.jpg) no-repeat;z-index:10000;border:1px solid #ddd;-webkit-box-shadow: 2px 2px 2px rgba(100, 100, 100, 0.5);-moz-box-shadow: 2px 2px 2px rgba(100, 100, 100, 0.5);-moz-border-radius:5px;-webkit-border-radius:5px;position:absolute;bottom:10px;right:15px;"
					});
					Ext.Function.defer(c.fadeOut, 10000, c);
					try {
						if (!Ext.util.Cookies.get("bmeval")) {
							Ext.util.Cookies.set("bmeval", new Date().getTime(), Ext.Date.add(new Date(), Ext.Date.YEAR, 2))
						} else {
							var g = Ext.util.Cookies.get("bmeval"),
								d = new Date(parseInt(g, 10));
							if (Ext.Date.add(d, Ext.Date.DAY, 45) < new Date()) {
								this.el.select(this.eventSelector).hide();
								this.el.mask("Trial Period Expired!").setStyle("z-index", 10000);
								this.refresh = Ext.emptyFn
							}
						}
					} catch (f) {}*/
				}
			})
		}
	}
});
