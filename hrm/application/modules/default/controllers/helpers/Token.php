<?php
class Zend_Controller_Action_Helper_Token extends
                Zend_Controller_Action_Helper_Abstract
{
	
    private $db;
    private $filter;
    private $defilter;
    protected $myToken;
    private $user;
    
    function  __construct() {
        $this->db = Zend_Registry::get('db');
        $session = new Zend_Session_Namespace('login');
        $uid = $session->idUser;
        $this->filter = new Zend_Filter_Encrypt(array(
		    'adapter'=>'mcrypt',
	        'algorithm'=>MCRYPT_RIJNDAEL_128,
	        'mode'=>MCRYPT_MODE_CBC,
	        'key'=>'F38RXE0LUXZ6JB084JPMU22AJYR076RD', //random key 32-byte
	        'vector'=>'1F5CSA60UMKH15RJ' //random vector 16-byte
		));
		$this->defilter = new Zend_Filter_Decrypt(array(
		    'adapter'=>'mcrypt',
	        'algorithm'=>MCRYPT_RIJNDAEL_128,
	        'mode'=>MCRYPT_MODE_CBC,
	        'key'=>'F38RXE0LUXZ6JB084JPMU22AJYR076RD', 
	        'vector'=>'1F5CSA60UMKH15RJ' 
		));
        $this->myToken = base64_encode($this->filter->filter($userName . "-" . $uid));
		$this->user = new Admin_Model_User();
    }   
    
    function getMyToken()
    {
    	return $this->myToken;
    }
    
    function getTokenByUser($uid='')
    {
    	$credential = $this->user->fetchRow($this->db->quoteInto("uid = ?",$uid));
    	if ($credential)
    	{
    		$token = base64_encode($this->filter->filter($credential['uid'] . "-" . $credential['id']));
    		return $token;
    	}
    	return false;
    }
    
    function getDocumentSignature()
    {
    	$dateSign = date('Y-m-d H:i:s');
    	$sign = md5($this->myToken . "|" . $dateSign);
    	return array("signature" => $sign,"date" => $dateSign);
    }

    function getDocumentSignatureByUserID($uid = '')
    {
    	$dateSign = date('Y-m-d H:i:s');
        $token = $this->getTokenByUser($uid);
        if ($token)
        {
            $sign = md5( $token . "|" . $dateSign);
            return array("signature" => $sign,"date" => $dateSign); 
        }
        else
        {
            return false;
        }

    }
    
    function isValidByUser($uid='',$dateSign='',$sign)
    {
    	$otherToken = $this->getTokenByUser($uid);
    	if ($otherToken)
    	{
	    	$check = md5($otherToken . "|" . $dateSign);
	    	if ($check == $sign)
	    	{
	    		return true;	
	    	}
    	}
    	return false;
    }

//////////////////////////////////////////////////////////
	/* Next prime greater than 62 ^ n / 1.618033988749894848 */
	private static $golden_primes = array(
		1,41,2377,147299,9132313,566201239,35104476161,2176477521929
	);

	/* Ascii :                    0  9,         A  Z,         a  z     */
	/* $chars = array_merge(range(48,57), range(65,90), range(97,122)) */
	private static $chars = array(
		0=>48,1=>49,2=>50,3=>51,4=>52,5=>53,6=>54,7=>55,8=>56,9=>57,10=>65,
		11=>66,12=>67,13=>68,14=>69,15=>70,16=>71,17=>72,18=>73,19=>74,20=>75,
		21=>76,22=>77,23=>78,24=>79,25=>80,26=>81,27=>82,28=>83,29=>84,30=>85,
		31=>86,32=>87,33=>88,34=>89,35=>90,36=>97,37=>98,38=>99,39=>100,40=>101,
		41=>102,42=>103,43=>104,44=>105,45=>106,46=>107,47=>108,48=>109,49=>110,
		50=>111,51=>112,52=>113,53=>114,54=>115,55=>116,56=>117,57=>118,58=>119,
		59=>120,60=>121,61=>122
	);

	public static function base62($int) {
		$key = "";
		while($int > 0) {
			$mod = $int-(floor($int/62)*62);
			$key .= chr(self::$chars[$mod]);
			$int = floor($int/62);
		}
		return strrev($key);
	}

	public static function udihash($num, $len = 5) {
		$ceil = pow(62, $len);
		$prime = self::$golden_primes[$len];
		$dec = ($num * $prime)-floor($num * $prime/$ceil)*$ceil;
		$hash = self::base62($dec);
		return str_pad($hash, $len, "0", STR_PAD_LEFT);
	}

    function generateDocumentSignature()
    {
        $num = rand(1,1000);
        $num2 = rand(1,getrandmax());
        $hash1 = $this->udihash($num,4);
        $hash2 = $this->encode($num2);
        return $hash1 . $hash2;
    }
////////////////////////////////////////////////////////////

    //const $codeset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    //readable character set excluded (0,O,1,l)
    const codeset = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";

    static function encode($n){
        $base = strlen(self::codeset);
        $converted = '';

        while ($n > 0) {
            $converted = substr(self::codeset, bcmod($n,$base), 1) . $converted;
            $n = self::bcFloor(bcdiv($n, $base));
        }

        return $converted ;
    }

    static function decode($code){
        $base = strlen(self::codeset);
        $c = '0';
        for ($i = strlen($code); $i; $i--) {
            $c = bcadd($c,bcmul(strpos(self::codeset, substr($code, (-1 * ( $i - strlen($code) )),1))
                    ,bcpow($base,$i-1)));
        }

        return bcmul($c, 1, 0);
    }

    static private function bcFloor($x)
    {
        return bcmul($x, '1', 0);
    }

    static private function bcCeil($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, ceil(bcsub($x, $floor)));
    }

    static private function bcRound($x)
    {
        $floor = bcFloor($x);
        return bcadd($floor, round(bcsub($x, $floor)));
    }

}
?>