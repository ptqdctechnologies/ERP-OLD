<?php

   namespace
      {
      $varURL = require_once(getcwd().'/Config/WebServiceURL.conf');

      $varParameterName='JSONParameter';

      $varParameterData = array(
         'Authentication' => array(
            'LoginUserName' => 'SysAdmin',
            'LoginPassword' => '748159263'
            ),
         'Data' => array(
            'Report' => array(
               'Mode' => 'HTML',
               'Type' => 'Accounting_GeneralLedgerDetail'
               ),
	    'Parameter' => array(
               'COA' => (isset($_GET['COA'])?$_GET['COA']:''),
               'StartDateTime' => (isset($_GET['StartDateTime'])?$_GET['StartDateTime']:'').' 00:00:00',
               'FinishDateTime' => (isset($_GET['FinishDateTime'])?$_GET['FinishDateTime']:'').' 23:59:59'
               )
            )
         );

      $varPOSTString= $varParameterName.'='.json_encode($varParameterData);

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            $ch = curl_init($varURL);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $varPOSTString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            curl_setopt($ch, CURLOPT_URL, $varURL);  // Set the url path we want to call

            //execute post
            $varResult = curl_exec($ch);
            //see the results
            $varReturnJSON=json_decode($varResult, true);
            curl_close($ch);

      $varReceiveData = $varReturnJSON;

//      header('Content-Type: text/html; charset=utf-8');
/*      header('Content-Disposition: inline; filename=YOURFILE.html');
      header('Content-Transfer-Encoding: binary');
      header('Accept-Ranges: bytes');
      header('Cache-Control: private, max-age=0, must-revalidate');
      header('Pragma: public');
      ini_set('zlib.output_compression','0');
 */
	    //	    echo "xxxxx";

      if($varReceiveData['AuthenticationSign']==1)
         {    
         echo gzuncompress(base64_decode($varReceiveData['CompressedReportContent']));
         }
      }

?>
