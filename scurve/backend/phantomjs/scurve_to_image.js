var system = require('system'),
    fs = require('fs'),
    page = require('webpage').create(),
    loadInProgress = false,
    openingPageInProgress = false,
    taskRunning = false;

var fileConf = fs.read('conf/scurve_to_image.conf'),
    jsonConfig = eval(fileConf);

if (jsonConfig.length == 0)
{
    console.log('No Config  found... Please check conf/scurve_to_image.conf content!');
    phantom.exit();
}

jsonConfig = jsonConfig[0];

var fetchConf = fs.read(jsonConfig.fetch_list),
    fetchList = eval(fetchConf);

if (fetchList.length == 0)
{
    console.log('No Config  found... Please check ' + jsonConfig.fetch_list + ' content!');
    phantom.exit();
}


var theUrl = jsonConfig.scurve_url,
    save_path = jsonConfig.save_image_path,
    pageWidth = jsonConfig.page_width,
    pageHeight = jsonConfig.page_height,
    imageWidth = jsonConfig.image_width,
    imageHeight = jsonConfig.image_height,
    ERP_TOKEN = jsonConfig.erp_token;

theUrl = theUrl + '/token/' + ERP_TOKEN;
page.viewportSize = { width: pageWidth, height: pageHeight };

page.onConsoleMessage = function(msg) {
    if (msg == 'ready')
    return console.log(msg);
};

page.onLoadStarted = function() {
    loadInProgress = true;
    console.log("load started");
};

page.onLoadFinished = function() {
    loadInProgress = false;
    console.log("load finished");
};

var pageOpen = function(url, fileName)
{
    var svg = '';
    page.open(url, function (status) {
        if (status !== 'success') {
            console.log('Unable to access the network!');
            phantom.exit();
        }
        else
        {
//            page.injectJs('lib/jquery-1.11.0.min.js');
            var interval = setInterval(function() {
                console.log('Capturing screenshot..');
                page.render(save_path + fileName);
                clearInterval(interval); //< Stop this interval
                openingPageInProgress = true;
                taskRunning = false;
            }, 1000); //< repeat check every 250ms
        }
    });

};

var i = 0,
    successFile = [];
var interval2 = setInterval(function() {
    if (i < fetchList.length)
    {
        if (!openingPageInProgress)
        {
            if (!taskRunning)
            {
                var obj = fetchList[i],
                    prjKode = obj.prj_kode,
                    sitKode = '',
                    fileName = obj.filename;
                if (obj.sit_kode != undefined)
                {
                    sitKode = obj.sit_kode;
                }

                var tmp = theUrl,
                    url = tmp+'/prj_kode/' + prjKode + '/sit_kode/' + sitKode + '/notitle/true/use_layout_421/true/height/' + imageHeight + '/width/' + imageWidth;

                taskRunning = true;
                console.log("Opening " + url);
                pageOpen(url,fileName);

                //Push into our success file array
                successFile.push({
                    file_name: fileName,
                    title: prjKode + ((sitKode) ? " - " + sitKode : '')
                });
            }
        }
        else if (openingPageInProgress)
        {
            openingPageInProgress = false;
            taskRunning = false;
            i++;
        }
    }
    else
    {
        clearInterval(interval2);
        //Writing file...
        try {
            var theJson = JSON.stringify(successFile);
            fs.write(save_path + "/images.json", theJson , 'w');
        } catch(e) {
            console.log(e);
        }
        phantom.exit();
    } //< Stop this interval
}, 250);
