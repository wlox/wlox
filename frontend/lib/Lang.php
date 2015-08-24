<?php
class Lang {
	public static function string($key=false) {
		global $CFG;
		
		if (empty($key))
			return false;
			
		if (!empty($CFG->lang_table[$key][$CFG->language]))
			return $CFG->lang_table[$key][$CFG->language];
		else
			return false;
	}
	
	public static function url($url=false,$get_alts=false,$get_js=false) {
		global $CFG;
		
		$urls['index.php']['en'] = 'index.php';
		$urls['index.php']['es'] = 'es/index.php';
		$urls['index.php']['ru'] = 'ru/index.php';
		$urls['index.php']['zh'] = 'zh/index.php';
		$urls['order-book.php']['en'] = 'order-book.php';
		$urls['order-book.php']['es'] = 'es/libreta-de-ordenes.php';
		$urls['order-book.php']['ru'] = 'ru/'.urlencode('книга-заявок').'.php';
		$urls['order-book.php']['zh'] = 'zh/'.urlencode('订货簿').'.php';
		$urls['contact.php']['en'] = 'contact.php';
		$urls['contact.php']['es'] = 'es/contacto.php';
		$urls['contact.php']['ru'] = 'ru/'.urlencode('связаться').'.php';
		$urls['contact.php']['zh'] = 'zh/'.urlencode('联系').'.php';
		$urls['register.php']['en'] = 'register.php';
		$urls['register.php']['es'] = 'es/registro.php';
		$urls['register.php']['ru'] = 'ru/'.urlencode('регистрация').'.php';
		$urls['register.php']['zh'] = 'zh/'.urlencode('注册').'.php';
		$urls['our-security.php']['en'] = 'our-security.php';
		$urls['our-security.php']['es'] = 'es/nuestra-seguridad.php';
		$urls['our-security.php']['ru'] = 'ru/'.urlencode('наша-безопасность').'.php';
		$urls['our-security.php']['zh'] = 'zh/'.urlencode('我们的安全').'.php';
		$urls['how-to-register.php']['en'] = 'how-to-register.php';
		$urls['how-to-register.php']['es'] = 'es/como-registrarse.php';
		$urls['how-to-register.php']['ru'] = 'ru/'.urlencode('как-зарегистрироваться').'.php';
		$urls['how-to-register.php']['zh'] = 'zh/'.urlencode('注册').'.php';
		$urls['fee-schedule.php']['en'] = 'fee-schedule.php';
		$urls['fee-schedule.php']['es'] = 'es/tarifas-y-comisiones.php';
		$urls['fee-schedule.php']['ru'] = 'ru/'.urlencode('комиссионные-сборы').'.php';
		$urls['fee-schedule.php']['zh'] = 'zh/'.urlencode('手续费').'.php';
		$urls['about.php']['en'] = 'about.php';
		$urls['about.php']['es'] = 'es/nosotros.php';
		$urls['about.php']['ru'] = 'ru/'.urlencode('об').'.php';
		$urls['about.php']['zh'] = 'zh/'.urlencode('对于').'.php';
		$urls['what-are-bitcoins.php']['en'] = 'what-are-bitcoins.php';
		$urls['what-are-bitcoins.php']['es'] = 'es/que-son-los-bitcoins.php';
		$urls['what-are-bitcoins.php']['ru'] = 'ru/'.urlencode('что-такое-Биткойн').'.php';
		$urls['what-are-bitcoins.php']['zh'] = 'zh/'.urlencode('什么是比特币').'.php';
		$urls['how-bitcoin-works.php']['en'] = 'how-bitcoin-works.php';
		$urls['how-bitcoin-works.php']['es'] = 'es/como-funciona-bitcoin.php';
		$urls['how-bitcoin-works.php']['ru'] = 'ru/'.urlencode('как-работает-Биткойн').'.php';
		$urls['how-bitcoin-works.php']['zh'] = 'zh/'.urlencode('比特币的工作原理').'.php';
		$urls['reset_2fa.php']['en'] = 'reset_2fa.php';
		$urls['reset_2fa.php']['es'] = 'es/reiniciar_2fa.php';
		$urls['reset_2fa.php']['ru'] = 'ru/'.urlencode('сброс-2fa').'.php';
		$urls['reset_2fa.php']['zh'] = 'zh/'.urlencode('复位双因素身份验证').'.php';
		$urls['news.php']['en'] = 'news.php';
		$urls['news.php']['es'] = 'es/noticias.php';
		$urls['news.php']['ru'] = 'ru/'.urlencode('новости').'.php';
		$urls['news.php']['zh'] = 'zh/'.urlencode('消息').'.php';
		$urls['terms.php']['en'] = 'terms.php';
		$urls['terms.php']['es'] = 'es/terminos.php';
		$urls['terms.php']['ru'] = 'ru/'.urlencode('условия').'.php';
		$urls['terms.php']['zh'] = 'zh/'.urlencode('条件').'.php';
		
		if (!$get_alts && !$get_js)
			return $urls[$url][$CFG->language];
		elseif ($get_alts) {
			$HTML = '';
			
			if (array_key_exists($url,$urls)) {
				foreach ($urls[$url] as $lang1 => $url1) {
					if ($lang1 == $CFG->language)
						continue;
					
					$HTML .= '<link rel="alternate" href="'.$CFG->baseurl.$url1.'" hreflang="'.$lang1.'" />';
				}
			}
			return $HTML;
		}
		elseif ($get_js) {
			$HTML = '';
			foreach ($urls as $url1 => $arr) {
				foreach ($arr as $lang2 => $url2) {
					$HTML .= '<input type="hidden" id="url_'.str_replace('.','_',$url1).'_'.$lang2.'" value="'.$url2.'" />';
				}
			}
			return $HTML;
		}
	}
	
	public static function jsCurrencies() {
		global $CFG;
		
		foreach ($CFG->currencies as $currency) {
			echo '<input type="hidden" class="curr_abbr_'.$currency['currency'].'" id="curr_abbr_'.$currency['id'].'" name="'.$currency['id'].'" value="'.$currency['currency'].'" />';
			echo '<input type="hidden" class="curr_sym_'.$currency['currency'].'" id="curr_sym_'.$currency['id'].'" name="'.$currency['id'].'" value="'.$currency['fa_symbol'].'" />';
		}
	}
}
?>