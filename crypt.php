<?php
/*
 PURPOSE: library for extended string classes
 HISTORY:
  2013-09-01 split off Crypt class from strings.php
*/

/* ORIGINAL VERSION
class Cipher {
    private $securekey, $iv;
    function __construct($textkey) {
        $this->securekey = hash('sha256',$textkey,TRUE);
        $this->iv = mcrypt_create_iv(32);
    }
    function encrypt($input) {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->securekey, $input, MCRYPT_MODE_ECB, $this->iv));
    }
    function decrypt($input) {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->securekey, base64_decode($input), MCRYPT_MODE_ECB, $this->iv));
    }
}
*/
abstract class Cipher {
    abstract public function encrypt($input);
}
/*
abstract class Cipher_onekey {
    abstract public function decrypt($input);
}

If this class is actually needed, it should probably use "whirlpool":

class Cipher_mcrypt extends Cipher_onekey {
    private $securekey, $iv;
    function __construct($iKey) {
        $this->securekey = hash('sha256',$iKey,TRUE);
    }
    public function Seed($iValue=NULL) {
	if (!is_null($iValue)) {
	    $this->iv = $iValue;
	}
	return $this->iv;
    }
    public function MakeSeed() {
        $this->iv = mcrypt_create_iv(32);
	return $this->iv;
    }
    public function encrypt($input) {
	if (!isset($this->iv)) {
	    $this->MakeSeed();
	}
	return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->securekey, $input, MCRYPT_MODE_ECB, $this->iv));
    }
    public function decrypt($input) {
	if (empty($this->iv)) {
	    return 'ENCRYPTION SEED NOT SET!';
	} else {
	    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->securekey, base64_decode($input), MCRYPT_MODE_ECB, $this->iv));
	}
    }
}
*/
/*%%%%
  PURPOSE: public-key encryption
  LATER: this should be abstract, and openssl should be implemented in a child-class
*/
class Cipher_pubkey extends Cipher {
    private $sPubKey;

    public function PubKey($iPubKey) {
	$this->sPubKey = $iPubKey;
    }
    public function PubKey_isSet() {
	return isset($this->sPubKey);
    }
    public function encrypt($input) {
	openssl_public_encrypt($input, $sEncrypted, $this->sPubKey);
	return $sEncrypted;
    }
    public function decrypt($input,$iPvtKey) {
	$ok = openssl_private_decrypt($input, $sDecrypted, $iPvtKey);
	if (!$ok) {
	    $this->ReportErrors('decryption');
	}
	return $sDecrypted;
    }
    protected function ReportErrors($sAction) {
	$sMsg = NULL;
	$qMsg = 0;
	while ($sErr = openssl_error_string()) {
	    $sMsg .= "\nCURRENT ERROR: $sErr";
	    $qMsg++;
	}
	$sDescr = clsString::Pluralize($qMsg,'gave an error',"gave $qMsg errors:");
	throw new exception("$sAction $sDescr$sMsg");
    }
}