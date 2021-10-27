Ext.ns('Ext.ux.Chip');

/**
 * Component which manages the Notifications
 *
 * @author djungowski
 *
 */
Ext.ux.Chip.NotificationMgr = function()
{

};

/**
 * All current notifications
 *
 */
Ext.ux.Chip.NotificationMgr._notifications = [];

/**
 * Hashmap for which notifications is saved at which array offset
 *
 */
Ext.ux.Chip.NotificationMgr._notificationsMap = {};

/**
 * Add new notification to the Manager
 * Returns the offset at which the notification has been added
 * starting with 0
 *
 * @param Ext.ux.Chip.Notification notification
 * @return Number
 */
Ext.ux.Chip.NotificationMgr.add = function(notification)
{
	var offsetId = (Ext.ux.Chip.NotificationMgr._notifications.length);

	Ext.ux.Chip.NotificationMgr._notificationsMap[notification.getId()] = offsetId;
	Ext.ux.Chip.NotificationMgr._notifications.push(notification);

	return (Ext.ux.Chip.NotificationMgr._notifications.length - 1);
};

/**
 * Removes a notification from the manager
 *
 * @param Ext.ux.Chip.Notification notification
 *
 */
Ext.ux.Chip.NotificationMgr.remove = function(notification)
{
	var offsetId = Ext.ux.Chip.NotificationMgr._notificationsMap[notification.getId()];
	delete Ext.ux.Chip.NotificationMgr._notificationsMap[notification.getId()];
	Ext.ux.Chip.NotificationMgr._notifications.splice(offsetId, 1);
	Ext.ux.Chip.NotificationMgr.updateOffsets(offsetId);
};

/**
 * Updates the Notification offset when a notification has been removed
 * Takes as parameter the offset ID of the notification, which has just been closed
 *
 * @param Number offsetId
 *
 */
Ext.ux.Chip.NotificationMgr.updateOffsets = function(offsetId)
{
	for (var i in Ext.ux.Chip.NotificationMgr._notificationsMap) {
		// Only update notifications that have been added after the notification which has just been closed
		if (Ext.ux.Chip.NotificationMgr._notificationsMap[i] > offsetId) {
			var newoffsetId = Ext.ux.Chip.NotificationMgr._notificationsMap[i] - 1;
			Ext.ux.Chip.NotificationMgr._notificationsMap[i] = newoffsetId;
			// Now fire event at notification that it's offset has been changed
			var notification = Ext.ux.Chip.NotificationMgr._notifications[newoffsetId];
			notification.fireEvent('offsetchange', notification, newoffsetId);
		}
	}
};

/**
 * Notfication Manager instance
 *
 */
Ext.ux.Chip.NotificationMgr._instance = null;

/**
 * Get Notification Manager instance
 *
 * @return Ext.ux.Chip.NotificationMgr
 */
Ext.ux.Chip.NotificationMgr.getInstance = function()
{
    if (Ext.ux.Chip.NotificationMgr._instance == null) {
        Ext.ux.Chip.NotificationMgr._instance = new Ext.ux.Chip.NotificationMgr();
    }
    return Ext.ux.Chip.NotificationMgr._instance;
};