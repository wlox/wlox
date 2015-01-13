<?php
class Encryption
{
    const CYPHER = 'blowfish';
    const MODE   = 'cfb';

    public static function encrypt($plaintext){
    	global $CFG;
    	
        $td = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td,$CFG->mcrypt_key, $iv);
        $crypttext = mcrypt_generic($td, $plaintext);
        mcrypt_generic_deinit($td);
        return $iv.$crypttext;
    }

    public static function decrypt($crypttext){
    	global $CFG;
    	
        $plaintext = '';
        $td        = mcrypt_module_open(self::CYPHER, '', self::MODE, '');
        $ivsize    = mcrypt_enc_get_iv_size($td);
        $iv        = substr($crypttext, 0, $ivsize);
        $crypttext = substr($crypttext, $ivsize);
        if ($iv)
        {
            mcrypt_generic_init($td,$CFG->mcrypt_key, $iv);
            $plaintext = mdecrypt_generic($td, $crypttext);
        }
        return $plaintext;
    }
    
    public static function hash($pass) {
    	$blowfish_salt = bin2hex(openssl_random_pseudo_bytes(22));
    	return crypt($pass, "$2a$12$".$blowfish_salt);
    }
    
    public static function verify_hash($pass,$hash) {
    	return (crypt($pass, $hash) == $hash);
    }
}

?>