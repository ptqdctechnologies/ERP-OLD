/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

//
// This is the main layout definition.
//
Ext.onReady(function(){
	
	Ext.QuickTips.init();
	
	// This is an inner body element within the Details panel created to provide a "slide in" effect
	// on the panel body without affecting the body's box itself.  This element is created on
	// initial use and cached in this var for subsequent access.
	var detailEl;
	
	// This is the main content center region that will contain each example layout panel.
	// It will be implemented as a CardLayout since it will contain multiple panels with
	// only one being visible at any given time.
            

	// Go ahead and create the TreePanel now so that we can use it below
    var treePanel = new Ext.tree.TreePanel({
    	id: 'tree-panel',
    	title: 'Sample Layouts',
        region:'north',
        split: true,
        height: 300,
        minSize: 150,
        autoScroll: true,
        //plugins: new Ext.ux.plugins.TreePanelStatefull(),
        // tree-specific configs:
        rootVisible: false,
        lines: false,
        singleExpand: true,
        useArrows: true,
        
        loader: new Ext.tree.TreeLoader({
            dataUrl:'/js/layout/base/menu_tree.json'
        }),
        
        root: new Ext.tree.AsyncTreeNode({
                expanded: true
})
    });
    
	// Assign the changeLayout function to be called on tree node click.
    treePanel.on('click', function(n){

    	var sn = this.selModel.selNode || {}; // selNode is null on initial selection
    	//bypass for treeSaveState
        if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node

                //Use for treestatesave
                //Save to cookie
//                this.cp.set('LastSelectedNodePath_' + this.id, n.getPath());
//                this.cp.set('LastSelectedNodeId_' + this.id, n.id);
                loadMenuLink(sn.id,n.id);
    		Ext.getCmp('content-panel').layout.setActiveItem(n.id + '-panel');
    		if(!detailEl){
    			var bd = Ext.getCmp('details-panel').body;
    			bd.update('').setStyle('background','#fff');
    			detailEl = bd.createChild(); //create default empty div
    		}
    		detailEl.hide().update(Ext.getDom(n.id+'-details').innerHTML).slideIn('l', {stopFx:true,duration:.2});

    	}
    });
    
	// This is the Details panel that contains the description for each example layout.
	var detailsPanel = {
		id: 'details-panel',
        title: 'Details',
        region: 'center',
        bodyStyle: 'padding-bottom:15px;background:#eee;',
		autoScroll: true,
		html: '<p class="details-info">When you select a layout from the tree, additional details will display here.</p>'
    };

var menu = new Ext.Toolbar({
            id : 'toolbar-menu',
            items : [{
	xtype: 'tbbutton',
	text: 'User',
        iconCls: 'user',
        menu: {
        
        items: [
                        {id:"menu-mod-AD", text:'Logout', iconCls: 'logout',handler: function() {  window.location = '/logout'; } , scope:this }
                        <?php echo "{id:\"menu-mod-AD\", text:'Logout', iconCls: 'logout',handler: function() {  window.location = '/logout'; } , scope:this }"; ?>

                    ]}
    }]
//                    new Ext.Toolbar.MenuButton({
//                 text:'Module'
//                 ,menu: {
//                    items: [
//                        {id:"menu-mod-AD", text:'Administration' ,handler: function() { } , scope:this }
//
//
//                    ]
//                  }
//               })
//              ,
//                  new Ext.Toolbar.MenuButton({
//                 text:'Session'
//                 ,menu: {
//                    items: [
//                        {id:"menu-login", text:'Log-in' ,handler: function() {  } , scope:this}
//
//                    ]
//                  }
//               })
//
//             ,
//                 new Ext.Toolbar.MenuButton({
//                 text:'Language'
//                 ,menu: {
//                    items: [
//                        { text:'English',handler: function() {  } , scope:this }
//
//                    ]
//                  }
//               })
//             ,
//                  new Ext.Toolbar.MenuButton({
//                 text:'Help'
//                 ,menu: {
//                    items: [
//                        { text:'About',handler: function() {    } , scope:this}
//                    ]
//                  }
//               })
//              ]
        });

    var toolmenu = new Ext.Panel({
  region:'north',
  applyTo: 'header',
  height:30,
  tbar: menu
});

var datas = new Ext.data.SimpleStore({
    fields:['type','name']
            ,data:[['procurement','Procurement'],['project','Project'],['finance','Finance'],['hr','Human Resource']]
        });
        
var combo=new Ext.form.ComboBox({
    name:'selectMenu'
    ,region:'north'
    ,store: datas
    ,valueField:'type'
    ,displayField:'name'
    ,typeAhead: true
    ,mode: 'local'
    ,triggerAction: 'all'
    ,emptyText:'Select..'
    ,value: 'procurement'
    ,selectOnFocus:true
    ,anchor:'95%'
    ,hiddenName:'type'
    
});        
        
	// Finally, build the main layout once all the pieces are ready.  This is also a good
	// example of putting together a full-screen BorderLayout within a Viewport.
    new Ext.Viewport({
		layout: 'border',
		title: 'Ext Layout Browser',
		items: [
                   toolmenu
//                    {
//			xtype: 'box',
//			region: 'north',
//			applyTo: 'header',
//			height: 30}
			,{
			layout: 'border',
	    	id: 'layout-browser',
	        region:'west',
	        border: false,
	        split:true,
			margins: '2 0 5 5',
	        width: 275,
	        minSize: 100,
	        maxSize: 500,
			items: [combo,treePanel, detailsPanel]
		},
			contentPanel
		],
        renderTo: Ext.getBody()
    });
});
