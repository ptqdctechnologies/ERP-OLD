Ext.namespace('Ext.ux.plugins'); 
/******************************************************************
 * Ext.ux.plugins.TreePanelStatefull plugin for Ext.tree.TreePanel
 *
 * @author  Miladin Joksic
 * @date    May 16, 2008
 *
 * @class Ext.ux.plugins.TreePanelStatefull
 * @extends Ext.util.Observable
 
 * @constructor
 * var treePanel = new Ext.ux.TreePanel({        
        ...add TreePanel options
        plugins:new Ext.ux.plugins.TreePanelStatefull()
   });
 *****************************************************************/
Ext.ux.plugins.TreePanelStatefull = function(config) {
    Ext.apply(this, config);
};
 
Ext.extend(Ext.ux.plugins.TreePanelStatefull, Ext.util.Observable, {   
    init:function(treePanel) {
        Ext.apply(treePanel, {            
            //CookieProvider - hold state of TreePanel
            cp: null,
            
            //TreePanel state - simple array
            state: null,
            
            //stateful option set to true
            stateful: true,
            
            //Last selected node
            lastSelectedNode: null,
            
            //Function which saves TreePanel state
            saveState : function(newState) {
	            this.state = newState;
	            this.cp.set('TreePanelStatefull_' + this.id, this.state);
            },            
            
            //Function which restores TreePanel state
            restoreState : function(defaultPath) {
                var lastSelectedNode = this.cp.get('LastSelectedNode_' + this.id);
                var stateToRestore = this.state;
                                	
	            if (this.state.length == 0) {
		            var newState = new Array(defaultPath);
		            this.saveState(newState);		
		            this.expandPath(defaultPath);
		            return;
	            }
        	    
	            for (var i = 0; i < stateToRestore.length; ++i) 
	            {
		            // activate all path strings from the state
		            try {
			            var path = stateToRestore[i];
			            this.expandPath(path);
		            } 
		            catch(e) {
			            // ignore invalid path, seems to be remove in the datamodel
			            // TODO fix state at this point
		            }
	            }	
            },
            
            /***** Events which cause TreePanel to remember its state
             * click, expandnode, collapsenode, load, textchange,
             * remove, render
            ********************************************************/
            stateEvents: [{
                click: {
                    fn: function(node) {
                       
                    }
                },
                expandnode: {
                    fn: function(node) {
  	                    var currentPath = node.getPath();
  	                    var newState = new Array();
                	    
	                    for (var i = 0; i < this.state.length; ++i) 
	                    {
		                    var path = this.state[i];
                		    
		                    if (currentPath.indexOf(path) == -1) {
			                    // this path does not already exist
			                    newState.push(path);			
		                    }
	                    }
                	    
	                    // now ad the new path
	                    newState.push(currentPath);
	                    this.saveState(newState);
                    }
                },
                collapsenode: {
                    fn: function(node) {
                        if(node.id = this.root.id) {
                            return;
                        }
                        
                        var closedPath = node.getPath();
                        var newState = new Array();
                        
                        for (var i = 0; i < this.state.length; ++i) 
                        {
	                        var path = this.state[i];
	                        if (path.indexOf(closedPath) == -1) {
		                        // this path is not a subpath of the closed path
		                        newState.push(path);			
	                        }
                        }
                	    
                        if (newState.length == 0) {
	                        var parentNode = node.parentNode;
	                        newState.push((parentNode == null ? this.pathSeparator : parentNode.getPath()));
                        }
                	    
                        this.saveState(newState);
                    }
                },
                load: {
                    fn: function(node) {
                        var lastSelectedNodePath = this.cp.get('LastSelectedNodePath_' + this.id);
                        var lastSelectedNodeId = this.cp.get('LastSelectedNodeId_' + this.id);
                        
                        var rootNode = this.getRootNode();
                        if(node.id == rootNode.id) {
                            this.restoreState(this.root.getPath());
                            if(node.id == lastSelectedNodeId) {
                                this.selectPath(lastSelectedNodePath);
                                node.fireEvent('click', node);
                            }
                            return;
                        }
        	    
	                    if(node.id == lastSelectedNodeId) {
                            node.fireEvent('click', node);
                        }
                        else {                
                            var childNode = node.findChild('id', lastSelectedNodeId);
                        
                            if(childNode && childNode.isLeaf()) {
                                childNode.ensureVisible();
                                this.selectPath(lastSelectedNodePath);
                                childNode.fireEvent('click', childNode);
                            }
                            else if(childNode && !childNode.isLeaf()) {
                                this.selectPath(lastSelectedNodePath);
                                childNode.fireEvent('click', childNode);
                            }
                        }
                    }
                },
                textchange: {
                    fn: function(node, text, oldText) {
                        var lastSelectedNodePath = this.cp.get('LastSelectedNodePath_' + this.id);
                        var newSelectedNodePath = lastSelectedNodePath.replace(oldText, text);
                        
                        this.cp.set('LastSelectedNodePath_' + this.id, newSelectedNodePath);            
                        
                        this.expandPath(node.getPath());
                        this.selectPath(node.getPath());
                    }
                },
                remove: {
                    fn: function(tree, parent, node) {
                        var lastSelectedNodePath = this.cp.get('LastSelectedNodePath_' + this.id);
                        var lastSelectedNodeId = this.cp.get('LastSelectedNodeId_' + this.id);
                        if(node.id == lastSelectedNodeId) {
                            this.cp.set('LastSelectedNodePath_' + this.id, parent.getPath());
                            this.cp.set('LastSelectedNodeId_' + this.id, parent.id);
                        }
                    }
                }
            }]
        });
        
        if(!treePanel.stateful) {
            treePanel.stateful = true;
        }
        
        if(!treePanel.cp) {
            treePanel.cp = new Ext.state.CookieProvider({expires: null});
        }
        
        if(!treePanel.lastSelectedNode) {
            var cookieLastSelectedNode = treePanel.cp.get('LastSelectedNode_' + treePanel.id);
            
            if(!cookieLastSelectedNode) {
                treePanel.lastSelectedNode = treePanel.root;
            }
            else {
                treePanel.lastSelectedNode = cookieLastSelectedNode;
            }
        }
        
        if(!treePanel.state) {
            var cookieState = treePanel.cp.get('TreePanelStatefull_' + treePanel.id);
            
            if(!cookieState) {
                treePanel.state = new Array();
            }
            else {
                treePanel.state = cookieState;
            }
        }
    }
});