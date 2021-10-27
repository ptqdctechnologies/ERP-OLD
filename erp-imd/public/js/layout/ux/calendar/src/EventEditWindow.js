/*!
 * Ext JS Library 3.3.0
 * Copyright(c) 2006-2010 Ext JS, Inc.
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.calendar.EventEditWindow
 * @extends Ext.Window
 * <p>A custom window containing a basic edit form used for quick editing of events.</p>
 * <p>This window also provides custom events specific to the calendar so that other calendar components can be easily
 * notified when an event has been edited via this component.</p>
 * @constructor
 * @param {Object} config The config object
 */
Ext.calendar.EventEditWindow = function(config) {
    var formPanelCfg = {
        xtype: 'form',
        labelWidth: 80,
        frame: false,
        bodyStyle: 'background:transparent;padding:5px 10px 10px;',
        bodyBorder: false,
        border: false,
        items: [
//        {
//            id: 'title',
//            name: Ext.calendar.EventMappings.Title.name,
//            fieldLabel: 'Title',
//            xtype: 'textfield',
//            anchor: '100%'
//        },
        {
            xtype: 'daterangefield',
            id: 'date-range',
            anchor: '100%',
            fieldLabel: 'When'
        },
        {
            xtype: 'projectselector',
            fieldLabel: 'Project Code',
            anchor: '100%',
            id: 'project-select',
            Selectid: 'prj_kode_text',
            Nameid: 'prj_nama_text'
        },
        {
            xtype: 'siteselector',
            fieldLabel: 'Site Code',
            anchor: '100%',
            independent: false,
            id: 'site-select',
            SiteSelectid: 'sit_kode_text',
            SiteNameid: 'sit_nama_text',
            ProjectSelectid: 'prj_kode_text'
        },
        {
            xtype: 'userselector',
            name: Ext.calendar.EventMappings.BehalfOf.name,
            fieldLabel: 'On Behalf Of',
            anchor: '100%',
            uid: '',
            id: 'user-select',
            UserSelectid: 'user_kode_text'
        },
        {
            id: 'notes',
            name: Ext.calendar.EventMappings.Notes.name,
            fieldLabel: 'Daily Act',
            xtype: 'textarea',
            enableKeyEvents: true,
            anchor: '100%',
            listeners: {
                keyup: function(field,e){
                    var charKode = e.getCharCode();
                    if (charKode == 222 || charKode == 16 ) //|| charKode == 13)
                    {
                        //Ext.Msg.alert('Error','Do not input these Character : \' (Single Quote), " (Double Quote) and ENTER Key!');
                        field.setValue(field.getValue().replace(/\'|\"|\n|\r|\t/g,''));
                    }
                }
            }
        }
        ]
    };

//    if (config.calendarStore) {
//        this.calendarStore = config.calendarStore;
//        delete config.calendarStore;
//        formPanelCfg.items.push({
//            xtype: 'calendarpicker',
//            id: 'calendar',
//            name: 'calendar',
//            anchor: '100%',
//            store: this.calendarStore
//        });
//    }

    Ext.calendar.EventEditWindow.superclass.constructor.call(this, Ext.apply({
        titleTextAdd: 'Add Event',
        titleTextEdit: 'Edit Event',
        width: 600,
        autocreate: true,
        border: true,
        closeAction: 'hide',
        modal: true,
        resizable: false,
        buttonAlign: 'left',
        savingMessage: 'Saving changes...',
        deletingMessage: 'Deleting event...',

        fbar: [
//        {
//            xtype: 'tbtext',
//            text: '<a href="#" id="tblink">Edit Details...</a>'
//        },
        '->',
        {
            text: 'Save',
            disabled: false,
            handler: this.onSave,
            scope: this
        },
        {
            id: 'delete-btn',
            text: 'Delete',
            disabled: false,
            handler: this.onDelete,
            scope: this,
            hideMode: 'offsets'
        },
        {
            text: 'Cancel',
            disabled: false,
            handler: this.onCancel,
            scope: this
        }],
        items: formPanelCfg
    },
    config));
};

Ext.extend(Ext.calendar.EventEditWindow, Ext.Window, {
    // private
    newId: 0,
    closeAction: 'close',
    // private
    initComponent: function() {
        Ext.calendar.EventEditWindow.superclass.initComponent.call(this);

        this.formPanel = this.items.items[0];
        this.newId = this.sumRecord;
        this.addEvents({
            /**
             * @event eventadd
             * Fires after a new event is added
             * @param {Ext.calendar.EventEditWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was added
             */
            eventadd: true,
            /**
             * @event eventupdate
             * Fires after an existing event is updated
             * @param {Ext.calendar.EventEditWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was updated
             */
            eventupdate: true,
            /**
             * @event eventdelete
             * Fires after an event is deleted
             * @param {Ext.calendar.EventEditWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was deleted
             */
            eventdelete: true,
            /**
             * @event eventcancel
             * Fires after an event add/edit operation is canceled by the user and no store update took place
             * @param {Ext.calendar.EventEditWindow} this
             * @param {Ext.calendar.EventRecord} rec The new {@link Ext.calendar.EventRecord record} that was canceled
             */
            eventcancel: true,
            /**
             * @event editdetails
             * Fires when the user selects the option in this window to continue editing in the detailed edit form
             * (by default, an instance of {@link Ext.calendar.EventEditForm}. Handling code should hide this window
             * and transfer the current event record to the appropriate instance of the detailed form by showing it
             * and calling {@link Ext.calendar.EventEditForm#loadRecord loadRecord}.
             * @param {Ext.calendar.EventEditWindow} this
             * @param {Ext.calendar.EventRecord} rec The {@link Ext.calendar.EventRecord record} that is currently being edited
             */
            editdetails: true
        });
    },

    // private
    afterRender: function() {
        Ext.calendar.EventEditWindow.superclass.afterRender.call(this);

        this.el.addClass('ext-cal-event-win');

//        Ext.get('tblink').on('click',
//        function(e) {
//            e.stopEvent();
//            this.updateRecord();
//            this.fireEvent('editdetails', this, this.activeRecord);
//        },
//        this);
    },

    /**
     * Shows the window, rendering it first if necessary, or activates it and brings it to front if hidden.
	 * @param {Ext.data.Record/Object} o Either a {@link Ext.data.Record} if showing the form
	 * for an existing event in edit mode, or a plain object containing a StartDate property (and 
	 * optionally an EndDate property) for showing the form in add mode. 
     * @param {String/Element} animateTarget (optional) The target element or id from which the window should
     * animate while opening (defaults to null with no animation)
     * @return {Ext.Window} this
     */
    show: function(o, animateTarget) {
        // Work around the CSS day cell height hack needed for initial render in IE8/strict:
        var anim = (Ext.isIE8 && Ext.isStrict) ? null: animateTarget;

        Ext.calendar.EventEditWindow.superclass.show.call(this, anim,
        function() {
//            Ext.getCmp('title').focus(false, 100);
        });
        Ext.getCmp('delete-btn')[o.data && o.data[Ext.calendar.EventMappings.EventId.name] ? 'show': 'hide']();

        var rec,
        f = this.formPanel.form;

        if (o.data) {
            rec = o;
            this.isAdd = !!rec.data[Ext.calendar.EventMappings.IsNew.name];
            if (this.isAdd) {
                // Enable adding the default record that was passed in
                // if it's new even if the user makes no changes
                rec.markDirty();
                this.setTitle(this.titleTextAdd);
            }
            else {
                this.setTitle(this.titleTextEdit);
            }

            rec.data['IsPrev'] = rec.data['IsPrev'];
            f.loadRecord(rec);
        }
        else {
            this.isAdd = true;
            this.setTitle(this.titleTextAdd);

            var M = Ext.calendar.EventMappings,
            eventId = M.EventId.name,
            start = o[M.StartDate.name],
            end = o[M.EndDate.name] || start.add('h', 1);
            
            rec = new Ext.calendar.EventRecord();
            rec.data[M.EventId.name] = this.newId++;
            start.setHours(8,0,0);
            end.setHours(8,0,0);
            rec.data[M.StartDate.name] = start;
            rec.data[M.EndDate.name] = end;
            
            rec.data[M.IsAllDay.name] = !!o[M.IsAllDay.name] || start.getDate() != end.clone().add(Date.MILLI, 1).getDate();
            rec.data[M.IsNew.name] = true;
            rec.data[M.IsPrev.name] = false;
            f.reset();
            f.loadRecord(rec);
        }

        if (this.calendarStore) {
            Ext.getCmp('calendar').setValue(rec.data[Ext.calendar.EventMappings.CalendarId.name]);
        }
        Ext.getCmp('date-range').setValue(rec.data);
        Ext.getCmp('prj_kode_text').setValue(rec.data[Ext.calendar.EventMappings.ProjectCode.name]);
        Ext.getCmp('sit_kode_text').setValue(rec.data[Ext.calendar.EventMappings.SiteCode.name]);
        Ext.getCmp('user_kode_text').setValue(rec.data[Ext.calendar.EventMappings.BehalfOfName.name]);
        this.activeRecord = rec;

        return this;
    },

    // private
    roundTime: function(dt, incr) {
        incr = incr || 15;
        var m = parseInt(dt.getMinutes(), 10);
        return dt.add('mi', incr - (m % incr));
    },

    // private
    onCancel: function() {
        this.cleanup(true);
        this.fireEvent('eventcancel', this);
    },

    // private
    cleanup: function(hide) {
        if (this.activeRecord && this.activeRecord.dirty) {
            this.activeRecord.reject();
        }
        delete this.activeRecord;

        if (hide === true) {
            // Work around the CSS day cell height hack needed for initial render in IE8/strict:
            //var anim = afterDelete || (Ext.isIE8 && Ext.isStrict) ? null : this.animateTarget;
            this.hide();
        }
    },

    // private
    updateRecord: function() {
        var f = this.formPanel.form,
        dates = Ext.getCmp('date-range').getValue(),
        prjKode = Ext.getCmp('prj_kode_text').getValue(),
        sitKode = Ext.getCmp('sit_kode_text').getValue(),
        uid = Ext.getCmp('user-select').getUid(),
        name = Ext.getCmp('user_kode_text').getValue(),
        M = Ext.calendar.EventMappings;
        var title = prjKode;
        if (sitKode != undefined && sitKode != "")
            title = title + '-' + sitKode;

        f.updateRecord(this.activeRecord);
        if (dates[2])
        {
            dates[0].setHours(8,0,0);
            dates[1].setHours(17,0,0);
        }

        this.activeRecord.set(M.StartDate.name, dates[0]);
        this.activeRecord.set(M.EndDate.name, dates[1]);
        this.activeRecord.set(M.IsAllDay.name, dates[2]);
        this.activeRecord.set(M.Title.name, title);
        this.activeRecord.set(M.ProjectCode.name, prjKode);
        this.activeRecord.set(M.SiteCode.name, sitKode);
        this.activeRecord.set(M.BehalfOf.name, uid);
        this.activeRecord.set(M.BehalfOfName.name, name);
//        this.activeRecord.set(M.CalendarId.name, this.formPanel.form.findField('calendar').getValue());
        this.activeRecord.set(M.CalendarId.name, 1);
    },

    // private
    onSave: function() {
        if (!this.formPanel.form.isValid()) {
            return;
        }

        var prjKode = Ext.getCmp('prj_kode_text').getValue();
        
        if (prjKode == undefined || prjKode == "")
        {
            Ext.Msg.alert('Error', 'Please select project');
            return;
        }
        var sitKode = Ext.getCmp('sit_kode_text').getValue();

        if (sitKode == undefined || sitKode == "")
        {
            Ext.Msg.alert('Error', 'Please select site');
            return;
        }
        var notes = Ext.getCmp('notes').getValue();

        if (notes == undefined || notes == "")
        {
            Ext.Msg.alert('Error', 'Please Fill Daily Act');
            return;
        }

        this.updateRecord();

        if (!this.activeRecord.dirty) {
            this.onCancel();
            return;
        }

        this.fireEvent(this.isAdd ? 'eventadd': 'eventupdate', this, this.activeRecord);
    },

    // private
    onDelete: function() {
        this.fireEvent('eventdelete', this, this.activeRecord);
    }
});