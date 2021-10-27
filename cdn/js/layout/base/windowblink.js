Ext.ns('Ext.plugin');
Ext.plugin.ModalNotice = Ext.extend(Ext.util.Observable, {
	init: function(win){
		this.window = win;		
		this.window.on('render', function(){
		    if(this.window.modal === true){		
	                this.mask = this.window.container.select('div.ext-el-mask');
	                this.mask.applyStyles('background-color: transparent');
	                this.mask.on('click', this.onMaskClick, this);
	            }
		}, this);
	},	
	
	/**
	 * First we hide the window, then after some delay we show it.
	 * This gives the shake effect to the window. With the window reshown
	 * we now try to highlight it like it is blinking by hiding
	 * the shadow after a deley and then showing it again.
	 */
	onMaskClick: function(){
		if(!this.shadowEl){
			this.shadowEl = this.window.getEl().shadow.el;
		}		
		this.window.hide();
                (function() {
                       this.window.show();
			
			(function(){
				Ext.TaskMgr.start({
					run: function(){
						this.hide();
						(function(){
							this.show();
							//this.frame('#808080');
						}).defer(150, this);
					},
					repeat: 1,
					interval: 1000,
					scope: this.shadowEl
				});
			}).defer(150, this);
			
              }).defer(150, this);
		
	}
});