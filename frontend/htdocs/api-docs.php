<?php
include '../lib/common.php';

API::add('Content','getRecord',array('api-docs'));
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$page_title = $content['title'];

$code['api_request_limit'] = 60;

$code['usage_example_request'] = '# Example request using CURL on the command line
curl "[api_url]/transactions" \
-d currency="EUR" \
-d limit=5	
';

$code['usage_example_response'] = '// Example valid response
{"transactions": {
	"0":{"id":"131","date":"2014-11-13 10:42:46","btc":"1.00000000","maker_type":"buy","price":"10.00","amount":"10.00","currency":"USD"},
	"1":{"id":"129","date":"2014-11-11 11:14:12","btc":"0.50000000","maker_type":"buy","price":"11.27","amount":"5.63","currency":"EUR"},
	"2":{"id":"128","date":"2014-11-11 11:13:49","btc":"0.50000000","maker_type":"buy","price":"10.91","amount":"5.46","currency":"USD"},
	"3":{"id":"127","date":"2014-11-10 18:29:15","btc":"0.50000000","maker_type":"buy","price":"11.20","amount":"5.60","currency":"USD"},
	"4":{"id":"126","date":"2014-11-10 18:25:21","btc":"0.50000000","maker_type":"buy","price":"11.20","amount":"5.60","currency":"USD"},
	"request_currency":"USD"
	}
}
';

$code['usage_example_error'] = '// Example error response
{"errors":[{"message":"Invalid currency.","code":"INVALID_CURRENCY"}]}
';

$code['api_sign_javascript'] = '// Javascript Example
	
// Uses http://crypto-js.googlecode.com/svn/tags/3.0.2/build/rollups/hmac-sha256.js
// ...and http://crypto-js.googlecode.com/svn/tags/3.0.2/build/components/enc-base64-min.js

// we add our public key and nonce to whatever parameters we are sending
var params = {};
params.currency = "eur";
params.price = 200;
params.api_key = api_key;
params.nonce = Math.round(new Date().getTime() / 1000);
	
// create the signature
var hash = CryptoJS.HmacSHA256(JSON.stringify(data), api_secret);
var hashInBase64 = CryptoJS.enc.Hex.stringify(hash);
	
// add signature to request parameters
params.signature = hashInBase64;
';

$code['api_sign_php'] = '// PHP Example
	
// we add our public key and nonce to whatever parameters we are sending
$commands[\'side\'] = \'sell\';
$commands[\'type\'] = \'stop\';
$commands[\'api_key\'] = $api_key;
$commands[\'nonce\'] = time();
	
// create the signature
$signature = hash_hmac(\'sha256\', json_encode($commands), $api_secret);
	
// add signature to request parameters
$commands[\'signature\'] = $signature;
';

$code['api_sign_python'] = '# Python Example
	
import hashlib
import hmac
	
// we add our public key and nonce to whatever parameters we are sending
params = {\'currency\': \'eur\', \'price\': 200, \'api_key\': api_key, \'nonce\': time.time()}

// create the signature
message = bytes(json.dumps(params)).encode(\'utf-8\')
secret = bytes(api_secret).encode(\'utf-8\')
signature = hmac.new(secret, message, digestmod=hashlib.sha256).hexdigest()
	
// add signature to request parameters	
params[\'signature\'] = signature	
';

$code['api_sign_c#'] = '// C# Example
	
using System.Security.Cryptography;

// we add our public key and nonce to whatever parameters we are sending
var params1 = new List<KeyValuePair<string, int>>();
params1.Add(new KeyValuePair<string, string>("api_key", api_key));
params1.Add(new KeyValuePair<string, int>("nonce", (Int32)(DateTime.UtcNow.Subtract(new DateTime(1970, 1, 1))).TotalSeconds));
	
// create the signature
JavaScriptSerializer serializer = new JavaScriptSerializer();
var message = serializer.Serialize(params1);
	
secret = secret ?? "";
var encoding = new System.Text.ASCIIEncoding();
byte[] keyByte = encoding.GetBytes(secret);
byte[] messageBytes = encoding.GetBytes(message);
using (var hmacsha256 = new HMACSHA256(keyByte))
{
	byte[] hashmessage = hmacsha256.ComputeHash(messageBytes);
	var signature = BitConverter.ToString(hashmessage);
	signature = signature.Replace("-", "");
	
	// add signature to request parameters
	params1.Add(new KeyValuePair<string, string>("signature", signature));
}
';

$code['api_sign_java'] = '/* Java Example */
	
/* Dependent on Apache Commons Codec to encode in base64. */
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import org.apache.commons.codec.binary.Hex;

/* we add our public key and nonce to whatever parameters we are sending */
Map<String, String> params = new HashMap<String, String>();
params.put("api_key", "demo");
params.put("nonce", ((int) (System.currentTimeMillis() / 1000L)));
	
/* create the signature */
String secret = "secret";
String message = new JSONObject(params).toString();

Mac sha256_HMAC = Mac.getInstance("HmacSHA256");
SecretKeySpec secret_key = new SecretKeySpec(secret.getBytes(), "HmacSHA256");
sha256_HMAC.init(secret_key);

String hash = Hex.encodeHexString(sha256_HMAC.doFinal(message.getBytes()));

/* add signature to request parameters */
params.put("signature", hash);
';

$code['api_url'] = 'https://1btcxe.com/api';


if ($code) {
	foreach ($code as $key => $sample) {
		$content = str_replace('['.$key.']',$sample,$content);
	}
}
//$content = str_replace('language-url','language-http',$content);
$content = str_replace('GET','<span class="token property">GET</span>',$content);
$content = str_replace('POST','<span class="token property">POST</span>',$content);
$content = str_replace('PAYLOAD','<span class="token property">PAYLOAD</span>',$content);
$content = str_replace('language-js','language-javascript',$content);
$content = str_replace('(float)','<u>(float)</u>',$content);
$content = str_replace('(int)','<u>(int)</u>',$content);
$content = str_replace('(string)','<u>(string)</u>',$content);
$content = str_replace('(boolean)','<u>(boolean)</u>',$content);
$content = str_replace('(array)','<u>(array)</u>',$content);
$content = preg_replace("#<div\s(.+?)>\s+<p>(.+?)<\/p>\s+<\/div>#is", "<pre $1><code $1>$2</code></pre>", $content);

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="api-docs.php"><?= Lang::string('api-docs') ?></a></div>
	</div>
</div>
<div class="container">
	<? include 'includes/sidebar_topics.php'; ?>
	<div class="content_right">
    <div class="text2"><?= $content['content'] ?></div>
    </div>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>