Ext.ns('Ext.ux.Chip');

Ext.ux.Chip.Notification = Ext.extend(Ext.BoxComponent, {
	constructor: function() {
		Ext.ux.Chip.Notification.superclass.constructor.apply(this, arguments);
		this.addEvents('offsetchange');
	},
	autoShow: true,
	renderTo: Ext.getBody(),
	padding: null,
	title: null,
	titleId: null,
	text: null,
	textId: null,
	icon: null,
	iconId: null,
	autoClose: 0,
	cls: 'ux-chip-notification',
	width: 300,
	height: 100,
	offset: 0,
	createElements: function() {
		this.titleId = Ext.id();
		this.textId = Ext.id();
		this.getEl().dom.innerHTML = '<div class="x-panel-header" id="' + this.titleId + '"></div>';
		this.getEl().dom.innerHTML += '<div style="padding: 5px;" id="' + this.textId + '"></div>';
	},
	setText: function(value) {
		document.getElementById(this.textId).innerHTML = value;
	},
	setTitle: function(value) {
		document.getElementById(this.titleId).innerHTML = value;
		if (this.icon !== null) {
			document.getElementById(this.titleId).innerHTML += '<img src="' + this.icon + '" align="right" />';
		}
	},
	handler: function() {
		this.destroy();
	},
	updateOffset: function(offset) {
		if (typeof offset != 'undefined') {
			this.offset = offset;
		}

		this.renderOffset();
	},
	renderOffset: function() {
		if (this.offset == 1) {
			var offset = this.offset * this.height + 20;
		} else {
			var offset = this.offset * this.height + this.offset * 10 + 10;
		}
		this.getEl().dom.style.bottom = offset + 'px';
	},
	listeners: {
		beforerender: function(notification) {
			notification.offset = Ext.ux.Chip.NotificationMgr.add(notification);
		},
		beforedestroy: function(notification) {
			Ext.ux.Chip.NotificationMgr.remove(notification);
		},
		offsetchange: function(notification, offset) {
			this.updateOffset(offset);
		},
		afterrender: function() {
			this.createElements();

			if (this.title !== null) {
				this.setTitle(this.title);
			}
			if (this.text !== null) {
				this.setText(this.text);
			}
			if (this.padding !== null) {
				this.getEl().dom.style.padding = this.padding;
			}
			if (this.border !== null) {
				this.getEl().dom.style.border = this.border;
			}

			if (this.autoClose > 0) {
				var task = new Ext.util.DelayedTask(this.destroy, this);
				task.delay(this.autoClose);
			}

			if (this.offset > 0) {
				this.renderOffset();
			}

			// Close notification on click
			this.getEl().on('click', this.handler.createDelegate(this));
		}
	}
});