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
    private $sPrvKey;
    private $sLastPlain;	// data last encrypted

    // these two static functions probably belong somewhere else, but at the moment I don't know where

    static public function Textify($binary) {
	return base64_encode($binary);
    }
    static public function Detextify($sText) {
	return base64_decode($sText);
    }

    public function PublicKey($sPubKey=NULL) {
	if (!is_null($sPubKey)) {
	    $this->sPubKey = $sPubKey;
	}
	return $this->sPubKey;
    }
    public function PrivateKey($sPrvKey=NULL) {
	if (!is_null($sPrvKey)) {
	    $this->sPrvKey = $sPrvKey;
	}
	return $this->sPrvKey;
    }
    public function LastPlain() {
	return $this->sLastPlain;
    }
    /*
    public function PubKey_isSet() {
	return isset($this->sPubKey);
    }*/
    public function encrypt($input) {
	$this->sLastPlain = $input;
	$ok = openssl_public_encrypt($input, $sEncrypted, $this->PublicKey(), OPENSSL_SSLV23_PADDING);
	if (!$ok) {
	    $this->ReportErrors('encryption');
	}
	return $sEncrypted;
    }
    public function decrypt($input) {
	$ok = openssl_private_decrypt($input, $sDecrypted, $this->PrivateKey());
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
	if ($qMsg > 0) {
	    $sDescr = clsString::Pluralize($qMsg,'gave an error',"gave $qMsg errors:");
	} else {
	    $sDescr = 'failed for some reason.';
	}
	throw new exception("$sAction $sDescr$sMsg");
    }
}