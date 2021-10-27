function loadMenuLink(curId,menuId)
{
    link = '';
    //alert(curId + ' ' + menuId);
	    if (curId != menuId)
	    {
	    	
	    	var menuArray = menuId.split("-");
	    
	    if (menuId == 'abs-form-po')
	        {
	            link = '/procurement/procurement/poh';
	        }
	    else if (menuId == 'charts-po')
	        {
	            link = '/procurement/procurement/chart';
	            //link = '/index';
	        }
	    else if (menuId == 'abs-form-budget')
	        {
	            link = '/default/report/showbudget';
	            //link = '/index';
	        }
	    else if (menuId == 'abs-form-boq3')
		    {
		    	link = '/default/report/showboq3';	
		    }
		else if (menuId == 'abs-form-boq3-revisi')
		    {
		    	link = '/default/report/showboq3revisi';	
		    }  
		else if (menuId == 'abs-form-compare-boq')
		    {
		    	link = '/default/report/showcompareboq';	
		    }
		else if (menuId == 'abs-form-pr')
	    {
	    	link = '/default/report/showpr';	
	    } 
		else if (menuId == 'abs-form-outprpo')
	    {
	    	link = '/default/report/showoutprpo';	
	    }	    
		else if (menuId == 'abs-form-arfasf')
        {
            link = '/default/report/showarfasf';
        }
		else if (menuId == 'abs-form-porpi')
        {
            link = '/default/report/showporpi';
        }
	    
		else if (menuId == 'abs-form-rmdi')
	{
	    link = '/default/report/showrmdi';
	}

                else if (menuId == 'abs-form-mdimdo')
	{
	    link = '/default/report/showmdimdo';
	}

                    else if (menuId == 'abs-form-mdodo')
	{
	    link = '/default/report/showmdodo';
	}
	    
	    else
	    {
	    	if (menuArray[0] == 'project')
	    	{
	    		link = '/myproject/show/prj_kode/' + menuArray[1];
	    	}
	    }
	    
	    
	    if (link !='')
	        {
	        //window.location =  link;
	        cPanel = Ext.getCmp('content-panel');
	        cPanel.load({
	                url: link,
	               scripts: true
	        });
        }
    }
}

