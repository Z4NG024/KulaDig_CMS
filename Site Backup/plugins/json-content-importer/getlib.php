<?PHP
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 #update_option('jci_free_api_access_items', '', JCIFREE_UO_AUTOLOAD); drop all

class jci_free_request {

	private $methtech = Array();
	private $method = Array();
	private $meth = Array();
	private $dataformat = Array();
	private $dataformatshortcode = Array();
	private $apiitems = '';
	private $apiitemsArr = Array();
	
	
	public function __construct(){ 
		$this->methtech["curl"] = "CURL";
		$this->methtech["php"]= "PHP";
		$this->methtech["wp"] = "WordPress";
		
		$this->meth["get"] = "GET";
		$this->meth["post"]= "POST";
		$this->meth["put"] = "PUT";
		
		$this->method["curlget"] = "curlget";
		$this->method["curlpost"] = "curlpost";
		$this->method["curlput"] = "curlput";
		$this->method["phpget"] = "rawget";
		$this->method["phppost"] = "rawpost";
		$this->method["wpget"] = "get";
		$this->method["wppost"] = "post";
		
		$this->dataformat["json"] = "JSON";
		$this->dataformatshortcode["json"] = "";
		$this->dataformat["xml"] = "XML";
		$this->dataformatshortcode["xml"] = 'inputtype="xml"';
		$this->dataformat["csv"] = "CSV/TXT";
		$this->dataformatshortcode["csv"] = 'inputtype="csv"';
		
		$this->apiitems = get_option( 'jci_free_api_access_items' );
		$this->apiitemsArr = json_decode($this->apiitems, TRUE);
		
	}
	
	private function jci_handle_postinput_sanitize_text_field($fieldkey, $default="") {
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) {
			return "";
		}		
		$clean = trim(sanitize_textarea_field( (isset($_POST[$fieldkey]) ? wp_unslash($_POST[$fieldkey]) : $default) )); 
		return $clean;
		#return sanitize_textarea_field(wp_unslash(($_POST[$fieldkey] ?? $default)));
	}	
	
	private function jci_handle_postinput_wp_kses_array($fieldkey, $default="") {
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) {
			return "";
		}	
		$str = $this->jci_handle_postinput_wp_kses($fieldkey, $default);
		$arr = json_decode($str , TRUE);
		$arr['jciurl'] = trim(esc_url_raw( (isset($_POST['storeapirequest_jciurl']) ? wp_unslash($_POST['storeapirequest_jciurl']) : $default) )); 
		#$arr['headoauth2val'] = trim(sanitize_textarea_field( (isset($_POST['storeapirequest_headoauth2val']) ? wp_unslash($_POST['storeapirequest_headoauth2val']) : $default) )); 
		return $arr;
	}

	private function jci_handle_postinput_wp_kses($fieldkey, $default="") {
		#echo $fieldkey."<hr>".$_POST[$fieldkey]."<hr>";
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) {
			return "";
		}	
		$clean = "";
		if ('jciurl'==$fieldkey) {
			$clean = trim(esc_url_raw( (isset($_POST[$fieldkey]) ? wp_unslash($_POST[$fieldkey]) : $default) )); 
		} else {
			$clean = trim(wp_kses( (isset($_POST[$fieldkey]) ? wp_unslash($_POST[$fieldkey]) : $default), 'post' )); 
		}
		return $clean;
	}	

	private function jci_handle_getinput($fieldkey, $default="") {
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) { # nonce failed
			return "";
		}	
		return sanitize_text_field(wp_unslash(($_GET[$fieldkey] ?? $default)));
	}

	public function step1getjson() {
		echo "<h1>Step 1: Get API-Data (JSON, XML, CSV, TXT) - Insert URL, Authentication etc. for the API and check response</h1>";
		#echo "<hr>".wp_json_encode($_POST)."<hr>";

		##########
		# manage API-Access-Sets: show, delete, activate, inactivate
		$post_manage = $this->jci_handle_postinput_sanitize_text_field('manage');
		$get_del = $this->jci_handle_getinput('del');
		$get_act = $this->jci_handle_getinput('act');
		$get_ina = $this->jci_handle_getinput('ina');
		if (
			!empty($post_manage)
			|| !empty($get_del)
			|| !empty($get_act)
			|| !empty($get_ina)
		) { 
			$this->showapis();
			return TRUE;
		}

		##########
		# update API-Access-Sets
		$post_updatejas = $this->jci_handle_postinput_sanitize_text_field('updatejas');
		if (!empty($post_updatejas)) { 
			#echo json_encode($_POST);

			$accset = $this->jci_handle_postinput_sanitize_text_field('accset');
			$inp_nameofjas = $this->jci_handle_postinput_sanitize_text_field('nameofjas'); #$_POST['nameofjas'];
			if (empty($inp_nameofjas)) {
				$inp_nameofjas = $this->calc_unique_id(time());
			}
			
			#$post_storeapirequestval = $this->jci_handle_postinput_sanitize_text_field('storeapirequestval');
			$post_storeapirequestval = $this->jci_handle_postinput_wp_kses('storeapirequestval'); ## problem, da JSON-string behandelt wird, der ggf. eine URL enthält....
			
			
			#$inp_storeapirequestval = json_decode($post_storeapirequestval, TRUE);
			$inp_storeapirequestval = $this->jci_handle_postinput_wp_kses_array('storeapirequestval');

			$apiitemsArrNew = Array();
			#echo "<hr>stored: ".wp_json_encode($this->apiitemsArr)."<br>";
			foreach($this->apiitemsArr as $t) {
				#echo "<hr>t: ".wp_json_encode($t)."<br>";
				#echo "md5id: ".$t['md5id']."<br>";
				if (isset($t['md5id']) && (""!=$t['md5id'])) {
					#echo "md5id: ".$t['md5id']."<br>";
					$apiitemsArrNew[$t['md5id']]["md5id"] = $t['md5id'];
					if ($t['md5id']==$accset) {
						#echo "UP: $accset<br>";
						$apiitemsArrNew[$t['md5id']]["set"] = $inp_storeapirequestval;
						$apiitemsArrNew[$t['md5id']]["nameofjas"] = trim($inp_nameofjas);
						$apiitemsArrNew[$t['md5id']]["time"] = time();
						$apiitemsArrNew[$t['md5id']]["status"] = $this->apiitemsArr[$accset]["status"];
					} else {
						$apiitemsArrNew[$t['md5id']]["set"] = $this->apiitemsArr[$t['md5id']]["set"] ;
						$apiitemsArrNew[$t['md5id']]["nameofjas"] = trim($this->apiitemsArr[$t['md5id']]["nameofjas"] );
						$apiitemsArrNew[$t['md5id']]["time"] = $this->apiitemsArr[$t['md5id']]["time"] ;
						$apiitemsArrNew[$t['md5id']]["status"] = $this->apiitemsArr[$t['md5id']]["status"];
					}
				}
			}
			$save_storeapirequestval_str = wp_json_encode($apiitemsArrNew);
			$this->apiitemsArr = $apiitemsArrNew;
			update_option('jci_free_api_access_items', $save_storeapirequestval_str, JCIFREE_UO_AUTOLOAD);
			$this->showapis();
			return TRUE;
		}

		###### 
		# store API-Access-Set
		$post_storeapirequest = $this->jci_handle_postinput_sanitize_text_field('storeapirequest');
		if ("save"==$post_storeapirequest) { 

			# get existing Sets

			################## save new or update old
			#echo "<h2>Save API-Access-Set</h2>";
			#	$post_storeapirequestval = $this->jci_handle_postinput_sanitize_text_field('storeapirequestval');
			$post_storeapirequestval = $this->jci_handle_postinput_wp_kses('storeapirequestval');
			#$inp_set = urldecode($post_storeapirequestval);
			$inp_storeapirequestval = $this->jci_handle_postinput_wp_kses_array('storeapirequestval');
			
			#$inp_storeapirequestval['jciurl'] = trim(esc_url_raw( (isset($_POST['storeapirequest_jciurl']) ? wp_unslash($_POST['storeapirequest_jciurl']) : $default) )); 
			
			$post_storeapirequestjson = $this->jci_handle_postinput_sanitize_text_field('storeapirequestjson');
			$inp_storeapirequestjson = json_decode(urldecode($post_storeapirequestjson), TRUE);

			$post_nameofjas = $this->jci_handle_postinput_sanitize_text_field('nameofjas');
			$inp_nameofjas = urldecode($post_nameofjas);
			if (empty($inp_nameofjas)) {
				#$inp_nameofjas = substr(md5(time()), 0, 15);
				$inp_nameofjas = $this->calc_unique_id(time());
			}

			#$inp_id = md5($inp_set);
			$inp_id = $this->calc_unique_id($post_storeapirequestval);
			$this->apiitemsArr[$inp_id]["set"] = $inp_storeapirequestval;
			#$this->apiitemsArr[$inp_id]["json"] = $inp_storeapirequestjson; ## needed? danger if json too big
			$this->apiitemsArr[$inp_id]["time"] = time();
			$this->apiitemsArr[$inp_id]["status"] = "active";
			$this->apiitemsArr[$inp_id]["md5id"] = $inp_id;
			$this->apiitemsArr[$inp_id]["nameofjas"] = $inp_nameofjas;
			#echo $inp_nameofjas;
		
			$save_storeapirequestval_str = wp_json_encode($this->apiitemsArr);
			#echo urldecode($_POST['storeapirequestval'])."<hr>";
			#echo urldecode($_POST['storeapirequestjson'])."<hr>";
			#var_Dump( $save_storeapirequestval);
			update_option('jci_free_api_access_items', $save_storeapirequestval_str, JCIFREE_UO_AUTOLOAD);
			
			################## save end
			$this->showapis();
			return TRUE;
		}
		
		#################
		# set form with POST-input
		#var_Dump($_POST);
		$isnewjas = TRUE;
		#if (isset($_POST['noheader'])) { 
			$noheader = $this->jci_handle_postinput_sanitize_text_field('noheader', 3);
			#$noheader = $_POST['noheader']; 
		#} else {
		#	$noheader = 3;
		#}
		$formdata["nameofselectedjas"] = $this->calc_unique_id(time());
		$post_nameofselectedjas = $this->jci_handle_postinput_sanitize_text_field('nameofselectedjas');
		if (!empty($post_nameofselectedjas)) {  		
			$formdata["nameofselectedjas"] = $post_nameofselectedjas; #$_POST['nameofselectedjas']; 	
			$isnewjas = FALSE;
		}
		$post_accset = $this->jci_handle_postinput_sanitize_text_field('accset');
		if (!empty($post_accset)) {  		
			$isnewjas = FALSE;
		}
		

		$formdata["noheader"] = $noheader;
		$formdata["cbheadaccess"] = $this->jci_handle_postinput_wp_kses('cbheadaccess');
		$formdata["headaccesskey"] = $this->jci_handle_postinput_sanitize_text_field('headaccesskey', "Access");
		$formdata["headaccessval"] = $this->jci_handle_postinput_sanitize_text_field('headaccessval', "json/application");
		$formdata["cbheaduseragent"] = $this->jci_handle_postinput_wp_kses('cbheaduseragent');
		$formdata["headuseragentkey"] = $this->jci_handle_postinput_sanitize_text_field('headuseragentkey', "User-Agent");
		$formdata["headuseragentval"] = $this->jci_handle_postinput_sanitize_text_field('headuseragentval', "Mozilla");

		$formdata["headoauth2key"] = $this->jci_handle_postinput_sanitize_text_field('headoauth2key', "Authentication");
		$formdata["headoauth2val"] = $this->jci_handle_postinput_sanitize_text_field('headoauth2val', "Bearer [jsoncontentimporter apiaccesset=getoauth2token]{token}[/jsoncontentimporter]");
		$formdata["cbheadoauth2"] = $this->jci_handle_postinput_wp_kses('cbheadoauth2');
		
		$nooffilledheader = 0;
		for ($i = 1; $i <= $noheader; $i++) {
			$post_headerl = $this->jci_handle_postinput_sanitize_text_field('headerl'.$i); #$_POST["headerl".$i] ?? '';
			$post_headerr = $this->jci_handle_postinput_sanitize_text_field('headerr'.$i); # $_POST["headerr".$i] ?? '';
			if (!empty($post_headerr) || !empty($post_headerr)) {
				$nooffilledheader++;
				$formdata["headerl".$nooffilledheader] = $this->jci_handle_postinput_sanitize_text_field('headerl'.$i);# $_POST['headerl'.$i] ?? '';
				$formdata["headerr".$nooffilledheader] = $this->jci_handle_postinput_sanitize_text_field('headerr'.$i);#$_POST['headerr'.$i] ?? '';
			}
		}
		if ($nooffilledheader==0) { 
			$nooffilledheader = 4; 
		}		
		@$formdata["headernooffilledheader"] = $nooffilledheader;
		$methodTmp = $this->jci_handle_postinput_sanitize_text_field('method', "get");
		$methodtechTmp = $this->jci_handle_postinput_sanitize_text_field('methodtech', "curl");
		$indataformat = $this->jci_handle_postinput_sanitize_text_field('indataformat', "json");
		$csvdelimiter = $this->jci_handle_postinput_sanitize_text_field('csvdelimiter', ",");
		$csvline = $this->jci_handle_postinput_sanitize_text_field('csvline', "#LF#");
		$csvenclosure = $this->jci_handle_postinput_sanitize_text_field('csvenclosure', "#QM#");
		$csvskipempty = $this->jci_handle_postinput_sanitize_text_field('csvskipempty', "");
		$csvescape = $this->jci_handle_postinput_sanitize_text_field('csvescape', "#BS#");

		# put only with curl, not with wp and php
		$errormsg = "";
		if ( ("put"==$methodTmp) && ("wp"==$methodtechTmp || "php"==$methodtechTmp)) {
			$errormsg = "PUT can be done only with CURL: Set CURL instead of ".$this->methtech[$methodtechTmp];
			$methodtechTmp = "curl";
			$methodTmp = "put";
		}
		$formdata["method"] = $methodTmp;
		$formdata["methodtech"] = $methodtechTmp;
		$formdata["indataformat"] = $indataformat;
		$formdata["csvdelimiter"] = stripslashes($csvdelimiter);
		$formdata["csvline"] = stripslashes($csvline);
		$formdata["csvenclosure"] = stripslashes($csvenclosure);
		$formdata["csvskipempty"] = FALSE;
		if ($csvskipempty=="y") {
			$formdata["csvskipempty"] = TRUE;
		}
		$formdata["csvescape"] = stripslashes($csvescape);

		$postPayload = ""; 
		$post_payload = $this->jci_handle_postinput_sanitize_text_field('payload');
		if (!empty($post_payload)) { 
			#$postPayload = stripslashes(htmlentities($_POST['payload'])); 
			$postPayload = stripslashes($post_payload); 
		}
		$formdata["payload"] = $postPayload;
	
		$selectedmethod = $this->getSelectedmethod($methodtechTmp, $methodTmp);
		
		
		$formdata["selectedmethod"] = $selectedmethod;

		$httpsverify = 1;  # check!
		$post_httpsverify = $this->jci_handle_postinput_sanitize_text_field('httpsverify');
		if (!empty($post_httpsverify) && 2 == $post_httpsverify) { 
			#checkbox NOT active, no check
			$httpsverify = 2;
		}
		$formdata["httpsverify"] = $httpsverify;

		$ignorehttpcode = $this->jci_handle_postinput_sanitize_text_field('ignorehttpcode');
		#$post_jciurl = $this->jci_handle_postinput_sanitize_text_field('jciurl');
		$post_jciurl = $this->jci_handle_postinput_wp_kses('jciurl');
		#esc_url($formdata['jciurl']); 
		
		if (empty($post_jciurl)) { 
			$jciurl = plugin_dir_url(__FILE__).'json/gutenbergblockexample1.json';
		} else {
			$jciurl = stripslashes($post_jciurl); 
		}
		$formdata["jciurl"] = $jciurl ?? '';
		

		$urlgettimeout = $this->jci_handle_postinput_sanitize_text_field('timeout', 5);
		#if (isset($_POST['timeout'])) { 
		#	$urlgettimeout = $_POST['timeout']; 
		#} else {
		#	$urlgettimeout = 5;
		#}
		$formdata["timeout"] = $urlgettimeout;
		#$postPayload = "";
		#var_Dump( $formdata);
		#echo "P: ".$_POST['accset']."<hr>";
		#echo "<hr>POST: ".wp_json_encode($_POST)."<hr>";
		$post_accset = $this->jci_handle_postinput_sanitize_text_field('accset');
		
		
		if (!empty($post_accset)) { 
			$formdata["accset"] = $post_accset; 
			
			
			$t = $this->apiitemsArr[$formdata["accset"]] ?? NULL;
			$loadaccset = TRUE;
			$post_testrequest = $this->jci_handle_postinput_sanitize_text_field('testrequest');
			if (!empty($post_testrequest)) {
				$loadaccset = FALSE; # do not load the set from the stored data
			}
			$formdata = $this->setDataFromAccSet($t, $formdata, $loadaccset);
		}

		echo '<table class="widefat striped">';
		$jci_free_api_access_items = $this->apiitemsArr; #####json_decode(get_option('jci_free_api_access_items'), TRUE);
		$this->showExistingAPIAccesSets($jci_free_api_access_items, $formdata);

		#######
		# show set
		if (!empty($errormsg)){
			echo '<tr><td bgcolor="red">';
			echo "<font color=white>".esc_html($errormsg)."</font>";
			echo "</td></tr>";
		}

		echo '<tr><td>';
		if ($isnewjas) {
			echo "<h2>";
			esc_html_e('Create a new API-Access-Set: You might use the Test-URL or another URL', 'json-content-importer');
			echo "</h2>";
		} else {
			echo "<h2>";
			esc_html_e('Change the API-Access-Set', 'json-content-importer');
			echo " \"".esc_html($formdata["nameofselectedjas"])."\"</h2>";
		}

		$httpcode  = -1;
	
		$httpr[200] = "ok";
		$httpr[301] = "301 Moved Permanently";
		$httpr[302] = "302 Found (Previously Moved temporarily)";
		$httpr[400] = "400 Bad Request";
		$httpr[401] = "401 Unauthorized (RFC 7235)";
		$httpr[403] = "403 Forbidden";
		$httpr[404] = "404 Not Found";
		$httpr[405] = "405 Method Not Allowed";
		$httpr[500] = "500 Internal Server Error";
		$httpr[501] = "501 Not Implemented - e.g. wrong http-method";
		$httpr["cache"] = "JSON loaded from local cache";

		#if (isset($_POST['payload'])) { 
		#	$postPayload = stripslashes(htmlentities($_POST['payload'])); 
		#}
		#$formdata["payload"] = $postPayload;
		
		$resu = "";

		######## REQUEST
		$post_testrequest = $this->jci_handle_postinput_sanitize_text_field('testrequest');
		if (!empty($post_testrequest)) {
			$nameofselectedjas = $this->jci_handle_postinput_sanitize_text_field('nameofselectedjas');
			#$nameofselectedjas = $_POST["nameofselectedjas"];
			$nameofjas =  $nameofselectedjas;
			if (empty($nameofjas)) {
				$nameofjas = $this->calc_unique_id(time());
			}


		#######
		# do request
		
		# BEGIN param
		$debugModeIsOn = FALSE;
		$debugLevel = 10;
		# END: param

		# BEGIN cache: The  retrieved JSON from the API-Access-Set is NOT cached
		$cacheEnable = FALSE;#TRUE;
		$cacheFile = "";
		$cacheExpireTime = 0;
		# END Cache 
		
		#echo "<hr>".wp_json_encode($formdata)."<hr>";

		$fileLoadWithCacheObj = $this->doRequest($formdata, $selectedmethod, $debugModeIsOn, $debugLevel, $cacheFile);
		$receivedData = $fileLoadWithCacheObj->getHttpResponse();
		
				
		$httpcode = $fileLoadWithCacheObj->getErrormsgHttpCode();
		#echo "testrequest selectedmethod: $selectedmethod<br>receivedData: $receivedData<br>httpcode: $httpcode<br>";
		# END LOAD
		
		$httplev = "";
		if (!empty($httpcode)) {
			$httplev = $httpr[$httpcode] ?? '';
			if (empty($httplev)) {
				$httplev = "Error-Code: ".$httpcode;
			}
		}
		
		$errorcol = "black";
		$okcol = "#afa";
		$shortcodeparam = "";
		if (200!=$httpcode && "cache"!=$httpcode) {
			$formdata["httpcode"] = 1;
			$formdata["ignorehttpcode"] = $ignorehttpcode;
			$shortcodeparam = " httpstatuscodemustbe200=\"no\"";
			$errorcol = "red";
			$okcol = "#DDD";
		}
		if ("cache"==$httpcode) {
			$errorcol = "black";
		}
		if (!empty($httplev)) {
			$resu .= "<strong><font color=$errorcol>".__('API-answer', 'json-content-importer').":</font></strong> ".$httplev;
			if (!preg_match("/$httpcode/", $resu)) {
				$resu .= " (http-Code: $httpcode)";
			}
		}

		$this->jci_test_form($formdata);
		echo '</td></tr>';
		echo '<tr><td style="padding: 10px" bgcolor='.esc_attr($okcol).'>';
	
		$feedData = $fileLoadWithCacheObj->getFeeddataWithoutpayloadinputstr();
		
		#######
		# did we get JSON?
		$convertJsonNumbers2Strings = TRUE; # default!
        $jsonDecodeObj = new JSONdecodeFreeV2($feedData, TRUE, $debugLevel, $debugModeIsOn, $convertJsonNumbers2Strings, $cacheFile, $fileLoadWithCacheObj->getContentType(), 
			$formdata["indataformat"], $formdata["csvdelimiter"], $formdata["csvline"],
			$formdata["csvenclosure"], $formdata["csvskipempty"], $formdata["csvescape"]
			);

        $vals = $jsonDecodeObj->getJsondata();
		#echo "<textarea>".wp_json_encode($vals)."</textarea>";
		
		$resu .= "<hr><strong>".__("Valid JSON received?", 'json-content-importer')."</strong> ";
		$phpfunc = "";
		if ($jsonDecodeObj->getIsAllOk()) {
			if (is_null(($vals["nojsonvalue"] ?? NULL))) {
				$resu .= __("decoding ok, we got JSON-data!", 'json-content-importer');
				
				$phpfunc = 'jcifree_getjson("'.$formdata["nameofselectedjas"].'")';
			} else {
				$resu .= __("decoding failed, API-answer was packed into nojsonvalue-JSON", 'json-content-importer');
			}
		} else {
			$resu .= __("decoding due to invalid JSON failed. Check structure and encoding of JSON-data", 'json-content-importer');
		}


	$allowed_html = array(
		'hr' => array(),
		'strong' => array(),
		'font' => array(
        'color' => true,
		),
	);
	echo wp_kses($resu, $allowed_html);
	#echo htmlentities($resu).
	echo ' - <a href="#" id="showapianswer" status="off">';
	esc_html_e('Show API-Answer', 'json-content-importer');
	echo '</a>';
	if (!empty($phpfunc)) {
		echo "<br><font color=blue>";
		esc_html_e('PHP function to get API data', 'json-content-importer');
		echo ": ".esc_html($phpfunc)."</font><br>";
	}
?>
		<script>
		jQuery(function() {
			jQuery('a[id=showapianswer]').click(function() {
				var status = jQuery('a[id=showapianswer]').attr('status');
				if (status=='off') {
					jQuery('div[id=divapianswer]').show();
					jQuery('a[id=showapianswer]').text('Hide API-Answer');
					jQuery('a[id=showapianswer]').attr('status', 'on');
				} else {
					jQuery('div[id=divapianswer]').hide();
					jQuery('a[id=showapianswer]').text('Show API-Answer');
					jQuery('a[id=showapianswer]').attr('status', 'off');
				}
			});
		});
		</script>
<?PHP
		echo '<div id="divapianswer" style="display: none;"><textarea rows="7" cols="80">'.wp_json_encode($vals).'</textarea><br><a href="https://jsoneditoronline.org" target="_blank">You might copypaste the JSON to jsoneditoronline.org to analyze the JSON in detail</a></div>';

		############
		# build shortcode
		$inputtypeparam = "";
		if ("csv"==$formdata["indataformat"]) {
			$inputtypeparam = " inputtypeparam='";
			$itpArr = array();
			if (","!=$formdata["csvdelimiter"]) {
				$itpArr["delimiter"] = $formdata["csvdelimiter"];
			}
			if ("#LF#"!=$formdata["csvline"]) {
				$itpArr["csvline"] = $formdata["csvline"];
			}
			if ("#QM#"!=$formdata["csvenclosure"]) {
				$itpArr["enclosure"] = $formdata["csvenclosure"];
			}
			if ("#BS#"!=$formdata["csvescape"]) {
				$itpArr["escape"] = $formdata["csvescape"];
			}
			if ($formdata["csvskipempty"]) {
				$itpArr["skipempty"] = "yes";
			}
			$inputtypeparam .= wp_json_encode($itpArr)."' ";
		}
		$urlgettimeoutstr = '';
		if ($formdata["timeout"]!=5) {
			$urlgettimeoutstr = ' urlgettimeout="'.$formdata["timeout"].'" ';
		}

		if ($methodtechTmp!="curl" || empty($curloptions)) {
			$curloptions4Shortcode = '';
		} else {
			$curloptions4Shortcode = ' curloptions="'.$curloptions.'"';
		}
		
		#############
		# show JSON-tree
		if (is_array($vals)) {
			$vals4js = "<pre>".str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string) wp_json_encode($vals)), "\0..\37'\\")))."</pre>"; # https://stackoverflow.com/questions/168214/pass-a-php-string-to-a-javascript-variable-and-escape-newlines
			#<script>		var win = window.open("", "Title", "toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=780,height=200,top="+(screen.height-400)+",left="+(screen.width-840));			win.document.body.innerHTML = " $vals4js;";			</script>
			echo "<hr><strong>JSON</strong><br>";
			$this->displayJSONpure($vals);
			echo "<hr>";
			
			#############
			# store, rename or copy set
			echo "<strong>";
			esc_html_e('Name and store API-Access-Set', 'json-content-importer');
			echo ":</strong>";
			$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
			$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
			echo '<form method="post" action="'.esc_attr($secure_url).'">';
			#echo '<input type=hidden name=storeapirequestjson value="'.urlencode($feedData).'">';
			$feedData = wp_json_encode($vals);
			#echo $feedData; exit;
			#echo '<input type=hidden name=storeapirequestjson value="'.urlencode($feedData).'">';
			echo '<input type=hidden name=storeapirequestjson value="'.esc_attr($feedData).'">';
			$fdstr = wp_json_encode($formdata);
#			echo '<input type=hidden name=storeapirequestval value="'.urlencode($fdstr).'">';
			echo '<input type=hidden name=storeapirequestval value="'.esc_attr($fdstr).'">';

			####
			foreach($formdata as $formdatak => $formdatav) {
				#echo $formdatak.": ".$formdatav." - ".strlen($formdatav)."<br>";
				echo '<input type=hidden name=storeapirequest_'.esc_attr($formdatak).' value="'.esc_attr($formdatav).'">';
			}


			#var_Dump($fdstr);  # settings of the API-Access-Set
#			$nameofselectedjas = $_POST["nameofselectedjas"];
#			$nameofjas =  $nameofselectedjas;
#			if (empty($nameofjas)) {
#				$nameofjas = $this->calc_unique_id(time());
#			}
			echo '<input type=text name=nameofjas size=30 value="'.esc_attr($nameofjas).'">&nbsp;&nbsp;&nbsp;';
			if (empty($formdata["accset"])) {
				echo '<input type=hidden name=storeapirequest value="save">';
				
				$submitButtonValue = "If this is the data you need: Click here to store this API-Access-Set!";
				submit_button($submitButtonValue, 'primary', '', FALSE); 
			} else {
				echo '<input type=hidden name=storeapirequest value="save">';
				echo '<input type=hidden name=accset value="'.esc_attr($formdata["accset"]).'">';
				$submitButtonValue = __("Update this API-Access-Set ", 'json-content-importer');#.htmlentities($nameofjas);
				submit_button($submitButtonValue, 'primary', 'updatejas', FALSE);
				echo "&nbsp;&nbsp;&nbsp;&nbsp;";
				$submitButtonValue = __("Create new API-Access-Set", 'json-content-importer');
				submit_button($submitButtonValue, 'large', 'createnewjas', FALSE); 
			}

			echo "</form>";
		}
		
		############
		# show Shortcode
		#$sc = '[jsoncontentimporter url="'.$formdata["jciurl"].'" debugmode="10" '.$dataformatshortcode[$formdata["indataformat"]].$inputtypeparam.$urlgettimeoutstr.' method="'.$selectedmethod.'"'.$curloptions4Shortcode.']Create a template for displaying the Data with the JCI-Gutenberg block[/jsoncontentimporterpro]';
		echo '</td></tr>';
		echo '</table>';
		return "";
	} else {
		$this->jci_test_form($formdata);
		echo '</td></tr>';
		echo '</table>';
	}
	

	echo esc_html($resu);
}


	public function doRequest($formdata, $selectedmethod,$debugModeIsOn=FALSE, $debugLevel=0, $cacheFile="") {

		#######
		# do request
		
		# BEGIN buildrequest
		require_once plugin_dir_path( __FILE__ ) . 'lib/lib_request.php';

		$jci_request_handler = new jci_free_request_prepare($formdata);
		$curloptions = $jci_request_handler->getCurlOptionsString();
		$curloptions4Request = $jci_request_handler->getCurlOptions4Request($curloptions);
		# END buildrequest


		# BEGIN cache: The  retrieved JSON from the API-Access-Set is NOT cached
		$cacheEnable = FALSE;#TRUE;
		$cacheFile = "";
		$cacheExpireTime = 0;
		# END Cache 

		# BEGIN LOAD
		if (
			(!class_exists('JSONdecodeFreeV2'))
			|| (!class_exists('FileLoadWithCacheFreeV2'))
		) {
			require_once plugin_dir_path( __FILE__ ) . 'class-fileload-cache-v2.php';
		}
		$header = "";
		$urlencodepostpayload = "";
		$encodingofsource = "";
		$httpstatuscodemustbe200 = "no";
		$auth = "";
		$showapiresponse = FALSE;
		$followlocation = TRUE; # follow 301 etc.
		
        $fileLoadWithCacheObj = new FileLoadWithCacheFreeV2(
            $formdata["jciurl"], $formdata["timeout"], $cacheEnable, $cacheFile, $cacheExpireTime, $selectedmethod, NULL, '', '',
            $formdata["payload"], $header, $auth, $formdata["payload"],
            $debugLevel, $debugModeIsOn, $urlencodepostpayload, $curloptions4Request,
            $httpstatuscodemustbe200, $encodingofsource, $showapiresponse, $followlocation 
            );
        $fileLoadWithCacheObj->retrieveJsonData();
		return $fileLoadWithCacheObj;
	}


	public function showExistingAPIAccesSets($jci_free_api_access_items, $formdata, $buttontext="", $showManageAAS=TRUE, $showBasenodeField=FALSE, $basenodearr = null) {
		$thereisnojac = TRUE;
		$thereisnoactivejac = TRUE;
		if (is_array($jci_free_api_access_items) && count($jci_free_api_access_items)>0) {
			$thereisnojac = FALSE;
			foreach($jci_free_api_access_items as $t) {
				if ($t["status"]=="active") {
					$thereisnoactivejac = FALSE;
				}
			}
		}
		if (isset($jci_free_api_access_items)) {
			#################
			# show existing Sets
			echo '<tr><td>';
			echo '<h2>'.esc_html(__('Existing API-Load-Sets', 'json-content-importer')).'</h2>';
			if ($thereisnojac) {
				esc_html_e("There is no stored API-Access-Set yet. Create one below!", 'json-content-importer');
			} else if ($thereisnoactivejac) {
				echo "<h2>";
				esc_html_e('There are stored, but no active API-Access-Sets. Activate one or create one below!', 'json-content-importer');
				echo "</h2>";
				$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
				$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
				echo "<form action=".esc_attr($secure_url)." method=post>";
				$submitButtonValue = __("Activate a stored API-Access-Set", 'json-content-importer');
				submit_button($submitButtonValue, 'large', 'manage', FALSE); 
				echo "</form>";
			} else {
				$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
				$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
				echo "<form action=".esc_attr($secure_url)." method=post>";
				#echo "Load stored API-Access-Sets: ";
				$i = 1;
				$loadaccset = TRUE;
				
				$post_testrequest = $this->jci_handle_postinput_sanitize_text_field('testrequest');
				if (!empty($post_testreques)) {
					#echo "acc: $accset";
					$loadaccset = FALSE; # do not load the set from the stored data
				#} else {
					#$accset = $_POST['accset']; 
					#$accset = 1;
				}
				$formdata["accset"] = $formdata["accset"] ?? '';
				
				#echo "F: ".wp_json_encode($formdata);
				
				$t = $jci_free_api_access_items[$formdata["accset"]] ?? NULL;
				#echo "T: ".wp_json_encode($t);
				####$formdata = $this->setDataFromAccSet($t, $formdata, $loadaccset);
				echo "<select name=accset>";
				foreach($jci_free_api_access_items as $t) {
					$sel = "";
					if ($t['md5id']==$formdata["accset"]) { 
						$sel = " selected ";
					}
					if ("active"==$t['status']) {
						# show only active sets
						echo "<option value=".esc_attr($t['md5id'])." ".esc_attr($sel).">";
						echo esc_html($t['nameofjas']);
						echo "</option>";
					}
				}
				echo "</select>";
				echo '<input type=hidden name="type" value="loadaccset">';
				$submitButtonValue = __("Load API-Access-Set", 'json-content-importer');
				if (!empty($buttontext)) {
					$submitButtonValue = $buttontext;
				}
				submit_button($submitButtonValue, 'primary', 'load', FALSE); 
				if ($showManageAAS) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					$submitButtonValue = __("Manage stored API-Access-Sets", 'json-content-importer');
					submit_button($submitButtonValue, 'large', 'manage', FALSE); 
				}
				if ($showBasenodeField) {
					$basenodein = $this->jci_handle_postinput_sanitize_text_field('basenode');
					#$basenodein =$_POST["basenode"] ?? '';
					echo '<input type=hidden name="usebasenode" value="yes">';
					
					if (is_null($basenodearr)) {
						esc_html_e('Basenode', 'json-content-importer');
						echo ': <input type=text name="basenode" value="'.esc_attr($basenodein).'">';
					} else {
						echo '        ';
						esc_html_e('Basenode', 'json-content-importer');
						echo ': <select name="basenode">';
						echo '<option value="">';
						esc_html_e('No Basenode', 'json-content-importer');
						echo '</option>';
						foreach($basenodearr as $bk) {
							$selec = "";
							if ($bk===$basenodein) {
								$selec = " selected ";
							}
							
							if (preg_match("/\.\d+\./", $bk)) {
								continue;
							}
							
							echo '<option value="'.esc_attr($bk).'" '.esc_attr($selec).'>'.esc_html($bk);
							echo '</option>';
						}
						echo '</select>';
						#echo wp_json_encode($basenodearr);
					}
					$submitButtonValue = __("Generate Shortcode", 'json-content-importer');
					submit_button($submitButtonValue, 'large', 'gensc', FALSE); 
				}
				echo "</form>";
			}
			echo "</td></tr>";
		}
		return $formdata;
	}


	public function getSelectedmethod($methodtechTmp, $methodTmp) {
		$selectedmethod = $this->method[$methodtechTmp.$methodTmp] ?? '';
		if (empty($selectedmethod)) {
			$selectedmethod = "curlget  ".$methodtechTmp.$methodTmp;
		}
		return $selectedmethod;
	}


	public function setDataFromAccSet($t, $formdata, $loadaccset) {
		#echo "<hr>".json_Encode($formdata)."<hr>";
		if (is_null($t)) {
			return $formdata;
		}
		if (empty($formdata["accset"])) {
			return $formdata;
		}
		#$t = $jci_free_api_access_items[$formdata["accset"]] ?? NULL;
		#echo $t['set']['csvescape'];
		if ($loadaccset) {
			# load existing data
			$jciurl = $t['set']['jciurl'] ?? '';
			$formdata["jciurl"] = $jciurl;
			$formdata["method"] = $t['set']['method'] ?? '';
			$formdata["methodtech"] = $t['set']['methodtech'] ?? '';
			$postPayload = $t['set']['payload'] ?? '';
			$formdata["payload"] = $postPayload;
			$formdata["headernooffilledheader"] = $t['set']['headernooffilledheader'] ?? '';
			for ($i = 1; $i <= $formdata["headernooffilledheader"]; $i++) {
				$formdata["headerl".$i] = $this->clear_httpheaderkey(($t['set']['headerl'.$i] ?? ''));
				$formdata["headerr".$i] = $t['set']['headerr'.$i] ?? '';
			}
			$formdata["cbheadoauth2"] = $t['set']['cbheadoauth2'] ?? '';
			$formdata["headoauth2key"] = $this->clear_httpheaderkey(($t['set']['headoauth2key'] ?? ''));
			$formdata["headoauth2val"] = $t['set']['headoauth2val'] ?? '';
			$formdata["cbheadaccess"] = $t['set']['cbheadaccess'] ?? '';
			$formdata["headaccesskey"] = $this->clear_httpheaderkey(($t['set']['headaccesskey'] ?? ''));
			$formdata["headaccessval"] = $t['set']['headaccessval'] ?? '';
			$formdata["cbheaduseragent"] = $t['set']['cbheaduseragent'] ?? '';
			$formdata["headuseragentkey"] = $this->clear_httpheaderkey(($t['set']['headuseragentkey'] ?? ''));
			$formdata["headuseragentval"] = $t['set']['headuseragentval'] ?? '';
			$formdata["timeout"] = $t['set']['timeout'] ?? '';
			$formdata["indataformat"] = $t['set']['indataformat'] ?? '';
			$formdata["csvdelimiter"] =  $t['set']['csvdelimiter'] ?? '';
			$formdata["csvline"] =  $t['set']['csvline'] ?? '';
			$formdata["csvenclosure"] =  $t['set']['csvenclosure'] ?? '';
			$formdata["csvskipempty"] = FALSE;
			$csvskipempty = "n";
			if (!empty($t['set']['csvskipempty'] ?? '')) {
				$formdata["csvskipempty"] = TRUE;
				$csvskipempty = "y";
			}
			$formdata["csvescape"] = stripslashes(($t['set']['csvescape'] ?? ''));
			$formdata["httpsverify"] = $t['set']['httpsverify'] ?? '';
			$nameofselectedjas = $t['nameofjas'] ?? '';
			$formdata["nameofselectedjas"] = $nameofselectedjas ?? '';
		}
		return $formdata;
	}


	private function calc_unique_id($idin, $prefix="") {
		return $prefix.substr("jci".md5($idin), 0, 10);
	}

	private function displayJSONpure($jsonArr, $addcheckboxes=FALSE, $openall=TRUE) {
		$this->displayJSON($jsonArr, FALSE, $addcheckboxes, $openall);
	}

	private function displayJSON($jsonArr, $showSelectionForm = TRUE, $addcheckboxes=FALSE, $openall=TRUE) {
		require_once plugin_dir_path( __FILE__ ) . 'lib/lib_jsonphp.php';
		echo '<a href="#" id="showapiansweruse" status="off">';
		esc_html_e('Show JSON', 'json-content-importer');
		echo '</a>';
		?>
		<script>
		jQuery(function() {
			jQuery('a[id=showapiansweruse]').click(function() {
				var status = jQuery('a[id=showapiansweruse]').attr('status');
				if (status=='off') {
					jQuery('div[id=divapiansweruse]').show();
					jQuery('a[id=showapiansweruse]').text('<?php __("Hide JSON", 'json-content-importer'); ?>');
					jQuery('a[id=showapiansweruse]').attr('status', 'on');
				} else {
					jQuery('div[id=divapiansweruse]').hide();
					jQuery('a[id=showapiansweruse]').text('<?php __("Show JSON", 'json-content-importer'); ?>');
					jQuery('a[id=showapiansweruse]').attr('status', 'off');
				}
			});
		});
		</script>
		<?PHP
		echo '<div id="divapiansweruse" style="display: none;"><textarea rows="7" cols="80">'.wp_json_encode($jsonArr);
		echo '</textarea><br><a href="https://jsoneditoronline.org" target="_blank">';
		esc_html_e('You might copypaste the JSON to jsoneditoronline.org to analyze the JSON in detail', 'json-content-importer');
		echo '</a><hr></div>';
###############	
	
	$wwj = new workWithJSON($jsonArr);
	$noofshowedlistitems = 5;
	$jtz =  $wwj->showJSON($jsonArr, "", $noofshowedlistitems, 1, $openall);
	
	echo '<input type="text" id="plugins4_q" value="" placeholder="search JSON">';
	wp_enqueue_style('jci-jsontree-css', plugin_dir_url(__FILE__) . '/js/jstree/dist/themes/default/style.min.css', NULL, 1);
	#echo '<link rel="stylesheet" href="'.esc_attr(plugin_dir_url(dirname(__FILE__))).'/js/jstree/dist/themes/default/style.min.css" />';
	
	#echo '<script src="'.esc_attr(plugin_dir_url(dirname(__FILE__))).'/js/jstree/jQuery/jquery.min.js"></script>';
	#wp_enqueue_script('jci-jsontree-jquery', plugin_dir_url(__FILE__) . '/js/jstree/jQuery/jquery.min.js', NULL, '1.0.0', TRUE);
	if ($addcheckboxes) {
		echo '&nbsp;<a href="#" id="chkSelectAll" >';
		esc_html_e('Check All', 'json-content-importer');
		echo '</a>';
		echo '&nbsp;&nbsp;&nbsp;<a href="#" id="chkUnSelectAll" >';
		esc_html_e('Uncheck All', 'json-content-importer');
		echo '</a><br />';
	}
	$allowed_html = array(
		'ul' => true,
		'li' => true,
		'a' => array(
			'href' => true,
		),
		'code' => true,
	);
	#echo "<hr>NO:".htmlentities(wp_kses($jtz, $allowed_html))."<hr>";  #json-tree
	#echo "<hr>OK: ".htmlentities($jtz)."<hr>";  #json-tree
	echo '<div id="jci_json_jstree">';
	echo wp_kses($jtz, $allowed_html);  #json-tree
	echo '</div>';

	#echo '<script src="'.esc_attr(plugin_dir_url(__FILE__)).'/js/jstree/dist/jstree.min.js"></script>';
	#wp_enqueue_script('jci-jsontree-js', plugin_dir_url(__FILE__) . '/js/jstree/dist/jstree.min.js', NULL, 1, FALSE); # loads in footer, does not sync
	function enqueue_jsontreescript() {  #load it right here, not in footer
		wp_enqueue_script('jci-jsontree-js', plugin_dir_url(__FILE__) . '/js/jstree/dist/jstree.min.js', NULL, '1.0.0', TRUE );
	    wp_print_scripts('jci-jsontree-js');
	}
	add_action('load_jsontreescript', 'enqueue_jsontreescript');	
	do_action('load_jsontreescript');
?>
	<script type="text/javascript">	
		var $jq = jQuery.noConflict();
		$jq(document).ready(function () {
			$jq('#chkSelectAll').click(function() {          
				$("#jci_json_jstree").jstree().check_all(true);				   
			}); 
			$jq('#chkUnSelectAll').click(function() {          
				$("#jci_json_jstree").jstree().uncheck_all(true);
			}); 
			
			$jq('#jci_json_jstree').jstree( {
				"core" : {
					"check_callback" : false
				},
				<?PHP if ($addcheckboxes) { echo ' "checkbox" : {  "keep_selected_style" : false    },	'; } ?>
				"plugins" : [ 
					<?PHP if ($addcheckboxes) { echo '"checkbox", '; } ?>
					, "themes", "search" ]
				}
			);
			<?PHP
				$post_seljs = $this->jci_handle_postinput_sanitize_text_field('seljs');
				if (!empty($post_seljs)) {
					$selnodes = $post_seljs;
					$selnodesArr = explode(",", $selnodes);
					foreach ($selnodesArr as $selnode) {
						echo "$('#jci_json_jstree').jstree(true).check_node('".esc_attr($selnode)."');\n";
					}
				} else {
					//echo "$('#jci_json_jstree').jstree(true).check_all();\n";
				}
			?>
			// search
			var to = false;
			$jq('#plugins4_q').keyup(function () {
				if(to) { 
					clearTimeout(to); 
				}
				to = setTimeout(function () {
					var v = $('#plugins4_q').val();
					$jq('#jci_json_jstree').jstree(true).search(v);
				}, 250);
			});
		});
	</script>
<?PHP					
}

	private function jci_test_form($formdata) {
		$chk_ignorehttpcode = "";
		wp_enqueue_style('jciform-style', plugin_dir_url(__FILE__) . '/css/getdata.css', null, 1);
	
		$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
		$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
		echo '<form method="post" action="'.esc_attr($secure_url).'">';
		
		echo '<input type=text name=jciurl value="'.esc_attr($formdata["jciurl"]).'" placeholder="';
		esc_html_e('Insert API-URL', 'json-content-importer');
		echo '" size="150">';
		$this->insert_tooltip(__("If the API expects BasicAuth: Use syntax https://username:passwort@www... (urlencode Username or Passwort in case of special chars)", 'json-content-importer'));
		
		if (empty(@$formdata["method"])) {
			$formdata["method"] = "get";
		}
		#if (empty(@$formdata["methodtech"])) {
		#	$formdata["methodtech"] = "curl";
		#}
		echo "<h3>";
		esc_html_e("Optional settings, if needed", 'json-content-importer');
		echo ":</h3>";
		echo '<table border=0>';
		echo '<tr><td valign=top>';
		echo '<table border=0>';
		echo '<tr><td>';
		esc_html_e("HTTP-Method", 'json-content-importer');
		echo ": ";
		echo '</td><td>';
		echo '<select name="method">';
		foreach($this->meth as $k => $v) {
			if (@$formdata["method"]==$k) { $chk_method  = " selected "; } else { $chk_method  = ""; }
			echo '<option value="'.esc_attr($k).'"'.esc_attr($chk_method).'>'.esc_html($v).'</option>';
		}
		echo '</select>';
		$this->insert_tooltip(
			__("Check the API which HTTP-method is expected", 'json-content-importer')
			);
		echo '</td></tr>';
		
		echo '<input type="hidden" name="methodtech" value="curl">';

		/*
		echo '<tr><td>';
		echo "Request-Technique:";
		echo '</td><td>';
		echo '<select delname="methodtech">';
		foreach($this->methtech as $k => $v) {
			if (@$formdata["methodtech"]==$k) { $chk_method  = " selected "; } else { $chk_method  = " "; }
			echo '<option value="'.esc_attr($k).'"'.esc_attr($chk_method).'>'.esc_html($v).'</option>';
		}
		echo '</select>';
		$this->insert_tooltip('Choose the technical approach for contacting the API: Some APIs are flexible in this regard, while others expect a very specific request. By experimenting, you can find a working method. The "WordPress" method sends a request formatted specifically by WordPress. "PHP" means a very simple request is sent. "CURL" is the most flexible approach, allowing you to define request details with additional fields. The key is to match the APIs expectations.');
		echo '</td></tr>';
		*/
		
		
		echo '<tr><td>';
		if (empty(@$formdata["timeout"])) {
			$formdata["timeout"] = 5;
		}
		esc_html_e('Timeout (sec waiting for answer', 'json-content-importer');
		echo ':';
		echo '</td><td>';
		echo '<input type=number name=timeout value="'.esc_attr($formdata["timeout"]).'" size=3>';
		$this->insert_tooltip(__('Set the time in seconds by which the API response must be received. Some APIs are slow, so this setting allows you to accommodate such APIs.', 'json-content-importer'));
		echo '</td></tr>';

		echo '<tr><td>';
		if (2==$formdata["httpsverify"]) {
			$chk_httpsverifyno = " checked ";
			$chk_httpsverifyyes = "";
		} else {
			$chk_httpsverifyno = "";
			$chk_httpsverifyyes = " checked ";
		}
		esc_html_e('Check valid HTTPS-/TLS-certificate?', 'json-content-importer');
		echo '</td><td>';
		echo '<input type=radio name=httpsverify '.esc_attr($chk_httpsverifyyes).' value="1">';
		esc_html_e('YES', 'json-content-importer');
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type=radio name=httpsverify '.esc_attr($chk_httpsverifyno).' value="2">NO&nbsp;&nbsp;&nbsp;';
		$this->insert_tooltip(__('Sometimes the HTTPS TLS certificate of the API server is not compatible with YOUR WordPress server. In such cases, disable the certificate check.', 'json-content-importer'));
		echo '</td></tr>';


		echo '<tr><td valign=top>';
		#echo '<div class="tooltip">';
		esc_html_e('Format of Data', 'json-content-importer');
		echo '<div id=csvsettings class="csvsettings">';
		esc_html_e('Placeholders', 'json-content-importer');
		echo " ";
		$this->insert_tooltip(__('please use the following placeholders for special characters', 'json-content-importer'));
		echo "<br>";
		esc_html_e('Tabulator', 'json-content-importer');
		echo "<br>";
		esc_html_e('Linefeed (\\n): #LF#', 'json-content-importer');
		echo "<br>";
		esc_html_e('Car. Return (\\r): #CR#', 'json-content-importer');
		echo "<br>";
		esc_html_e('Quot. Mark ("): #QM#', 'json-content-importer');
		echo "<br>";
		esc_html_e('Backspace (\\): #BS#', 'json-content-importer');
		echo "<br>";
		echo '</td><td>';
		echo '<select name="indataformat">';
		foreach($this->dataformat as $k => $v) {
			if (@$formdata["indataformat"]==$k) { $chk_dataformat = " selected "; } else { $chk_dataformat  = " "; }
			echo '<option value="'.esc_attr($k).'"'.esc_attr($chk_dataformat).'>'.esc_html($v).'</option>';
		}
		echo '</select>';
		$this->insert_tooltip(__('What data format will the API provide? JSON, XML, CSV or Text. If CSV/Text is provided, you must also specify where each data field begins and ends.', 'json-content-importer')	);
		echo "<div id=csvsettings class=csvsettings>";
		esc_html_e('CSV-Item-Delimiter (default: ,)', 'json-content-importer');
		echo ': <input type=text name=csvdelimiter  id=jas size=3 value='.esc_attr(@$formdata["csvdelimiter"]).'>';
		$this->insert_tooltip(__('A CSV item delimiter is a character used to separate individual data items (fields) in a CSV (Comma-Separated Values) file. Common delimiters include commas, semicolons, or tabs, which allow each value in a row to be distinguished from others.', 'json-content-importer')
			);
		echo '<br>';
		esc_html_e('CSV-Line-Delimiter (default: #LF#)', 'json-content-importer');
		echo ': <label><input type=text name=csvline  id=jas size=3 value='.esc_attr(@$formdata["csvline"]).'>';
		$this->insert_tooltip(__("A CSV line delimiter is a character or sequence of characters used to indicate the end of a row in a CSV (Comma-Separated Values) file. It typically marks where one row of data ends and the next begins. Common line delimiters include newline characters like `\\n` (Unix) or `\\r\\n` (Windows).", 'json-content-importer'));

		echo '<br>';
		esc_html_e('CSV-Enclosure (default: #QM#)', 'json-content-importer');
		echo ': <input type=text name=csvenclosure  id=jas size=3 value='.esc_attr(@$formdata["csvenclosure"]).'>';
		$this->insert_tooltip(__('A CSV enclosure is a character used to wrap data fields in a CSV file, especially when the data contains special characters like commas, line breaks, or quotes. Commonly, double quotes (`" "`) are used as enclosures, ensuring that the enclosed data is treated as a single field even if it contains delimiters. For example, `"New York, USA"` keeps "New York, USA" as one item rather than two.', 'json-content-importer'));
		echo '<br>';
		esc_html_e('CSV-Escape (default: #BS#)', 'json-content-importer');
		echo ': <input type=text name=csvescape  id=jas size=3 value='.esc_attr(@$formdata["csvescape"]).'>';
		$this->insert_tooltip(__('A CSV escape character is used to indicate that the following character should be interpreted literally rather than as a special character. This is especially useful within enclosed fields to prevent confusion with the enclosure or delimiter characters. For example, if double quotes (`"`) are used as both the enclosure and escape character, a double quote inside a field would be represented as `""`. So, `"He said, ""Hello"""` would be interpreted as `He said, "Hello"`.', 'json-content-importer'));

		echo '<br>';
		esc_html_e('Skip empty Lines?', 'json-content-importer');
		echo " ";
		if (@$formdata["csvskipempty"]) {
			$checkcsvskiemptyline_y = " checked ";
			$checkcsvskiemptyline_n = "";
		} else {
			$checkcsvskiemptyline_n = " checked ";
			$checkcsvskiemptyline_y = "";
		}
		echo '<input type=radio id=jas name=csvskipempty '.esc_attr($checkcsvskiemptyline_y).' value=y>';
		esc_html_e('YES', 'json-content-importer');
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type=radio id=jas  name=csvskipempty '.esc_attr($checkcsvskiemptyline_n).' value=n>';
		esc_html_e('NO', 'json-content-importer');
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		$this->insert_tooltip(__('How should empty lines in the CSV/Text file be handled?', 'json-content-importer'));

		echo '<input type=hidden name=nameofselectedjas value='.esc_attr(@$formdata["nameofselectedjas"]).'>';
		echo '<input type=hidden name=accset value='.esc_attr(@$formdata["accset"]).'>';
		
		echo "</div>";
		echo '</td></tr>';
		
		
		echo '</table>';
		echo '</td><td valign=top width=120>&nbsp;&nbsp;&nbsp;</td><td valign=top>';
	?>
		<script>
		function showHidePayloadTextarea() {
			var selmethod = jQuery('select[name=method]').val();
			if ("post"==selmethod || "put"==selmethod) {
				jQuery("div[id=pay]").show();
			} else {
				jQuery("div[id=pay]").hide();
			}
		}
		/*
		function showHideHeaderSettings() {
			var selmethodtech = jQuery('select[name=methodtech]').val();
			if ("curl"==selmethodtech) {
				jQuery("div[id=httpheader]").show();
			} else {
				jQuery("div[id=httpheader]").hide();
			}
		}
		*/
		function showHideCSVSettings() {
			var selidf = jQuery('select[name=indataformat]').val();
			if ("csv"==selidf) {
				jQuery("div[id=csvsettings]").show();
			} else {
				jQuery("div[id=csvsettings]").hide();
			}
		}
		jQuery(function() {
			try {
				//jQuery('select[name=methodtech]').change(function() {
					//showHideHeaderSettings();
				//});
				jQuery('select[name=method]').change(function() {
					showHidePayloadTextarea();
				});
				//showHideHeaderSettings();
				showHidePayloadTextarea();
			} catch(e) {
				alert('error: '+e);
			}
			try {
				jQuery('select[name=indataformat]').change(function() {
					showHideCSVSettings();
				});
				showHideCSVSettings();
			} catch(e) {
				alert('error: '+e);
			}
		});
		</script>
<?php
		echo '<div id=pay class=payload>';
		esc_html_e('POST-Payload', 'json-content-importer');
		echo ' ';
		$this->insert_tooltip(__("A POST payload is the data included in the body of a POST request in HTTP communication. It can be in formats like JSON, XML, or form data, depending on the server's requirements. Check the API documentation to see what format the API expects.", 'json-content-importer'));
		echo '<br><textarea name=payload rows=3 cols=80>'.esc_attr(@$formdata["payload"]).'</textarea>';
		echo '<br></div>';

		/*
		echo '<div id=httpheader><a href="'.esc_url('https://en.wikipedia.org/wiki/List_of_HTTP_header_fields').'" target="_blank">';
		esc_html_e('HTTP-Header', 'json-content-importer');
		echo '</a> ';
		$this->insert_tooltip(__("HTTP header fields are key-value pairs in an HTTP request or response that convey additional information about the request or response. They can include details like content type, authentication tokens, and client or server information, helping both the client and server understand how to handle the data exchange.", 'json-content-importer'));

		$formdata["headernooffilledheader"] = $formdata["headernooffilledheader"] ?? 4;
		if (empty($formdata["headernooffilledheader"])) {
			$formdata["headernooffilledheader"] = 4;
		}
		$formdata["headernooffilledheader"]++;
		
		echo '<br><input type=hidden name=noheader value="'.esc_attr($formdata["headernooffilledheader"]).'">';
		for ($i = 1; $i <= $formdata["headernooffilledheader"]; $i++) {
			$hltmp = "";
			if (isset($formdata["headerl".$i])) {
				$hltmp = $this->clear_httpheaderkey($formdata["headerl".$i]);
			}
#			echo '<input type=text id=jas name=headerl'.$i.' value="'.stripslashes(htmlspecialchars($hltmp)).'" size="10"> : ';
			echo '<input type=text id=jas name=headerl'.esc_attr($i).' value="'.esc_attr($hltmp).'" size="10"> : ';
			$formdata["headerr".$i] = $formdata["headerr".$i] ?? "";
#			echo '<input type=text id=jas name=headerr'.$i.' value="'.stripslashes(htmlspecialchars($formdata["headerr".$i])).'" size="10"><br>';
			echo '<input type=text id=jas name=headerr'.esc_attr($i).' value="'.esc_attr($formdata["headerr".$i]).'" size="10"><br>';
		}
		*/

		echo '</div></td><td valign=top>';
		esc_html_e('Common HTTP header fields often expected by APIs', 'json-content-importer');
		echo ' ';
		$this->insert_tooltip(__("'Access' and 'User-Agent' are often expected by APIs. Modify according to the API documentation and check 'Use it' if these fields should be sent to the API", 'json-content-importer'));
		echo '<br>';
			#echo '<input type=text  id=jas name=headaccesskey value="'.stripslashes(htmlspecialchars($formdata["headaccesskey"])).'" size="10"> : ';
			#echo '<input type=text  id=jas name=headaccessval value="'.stripslashes(htmlspecialchars($formdata["headaccessval"])).'"  size=30> ';
			echo '<input type=text  id=jas name=headaccesskey value="'.esc_attr($formdata["headaccesskey"]).'" size="10"> : ';
			echo '<input type=text  id=jas name=headaccessval value="'.esc_attr($formdata["headaccessval"]).'"  size=30> ';
			$cbheadaccess_checked = ""; if ($formdata["cbheadaccess"]=="y") {  	$cbheadaccess_checked = " checked "; 			}
			esc_html_e('Use it', 'json-content-importer');
			echo ': <input type=checkbox name=cbheadaccess value="y" '.esc_attr($cbheadaccess_checked).'><br>';
			
			#echo '<input type=text  id=jas name=headuseragentkey value="'.stripslashes(htmlspecialchars($formdata["headuseragentkey"])).'" size="10"> : ';
			#echo '<input type=text  id=jas name=headuseragentval value="'.stripslashes(htmlspecialchars($formdata["headuseragentval"])).'" size=30>   ';
			echo '<input type=text  id=jas name=headuseragentkey value="'.esc_attr($formdata["headuseragentkey"]).'" size=10> : ';
			echo '<input type=text  id=jas name=headuseragentval value="'.esc_attr($formdata["headuseragentval"]).'" size=30>   ';
			$cbheaduseragent_checked = ""; if ($formdata["cbheaduseragent"]=="y") {  				$cbheaduseragent_checked = " checked "; 			}
			esc_html_e('Use it', 'json-content-importer');
			echo ': <input type=checkbox name=cbheaduseragent value="y"'.esc_attr($cbheaduseragent_checked).'>';
			
		#$formdata["headoauth2key"] = $this->clear_httpheaderkey($formdata["headoauth2key"]);
		echo '<hr><strong>OAuth2-Authentication</strong> ';
		$this->insert_tooltip(__("OAuth2 is an authentication protocol that allows applications to access resources on behalf of a user without needing their password. Instead, it uses tokens issued by an authorization server, enabling secure, controlled access to APIs and services.", 'json-content-importer'));
		echo '<br>';
		esc_html_e('If the API expects OAuth2, the documentation should describe the requirements.', 'json-content-importer');
		echo '<br>';
		esc_html_e('Typically, "Authorization" is the key for the HTTP header used in OAuth2.', 'json-content-importer');
		echo '<br>';
		esc_html_e('The value usually is formatted as "Bearer TOKEN", where TOKEN represents the value obtained from a prior request to the API.', 'json-content-importer');
		echo '<br>';
		esc_html_e('Therefore in this case, it’s necessary first to determine how to retrieve the OAuth2 token from the API.', 'json-content-importer');
		echo '<br>';
		esc_html_e('This involves creating a separate API-access-set specifically to obtain this token, which can then be used in another API-access-set for the actual data retrieval.', 'json-content-importer').'</span><br>';
		
		echo '<br>';
		esc_html_e('Key: ', 'json-content-importer');
		echo '<br>';
		#echo '<input type=text id=jas name=headoauth2key value="'.stripslashes(htmlspecialchars($formdata["headoauth2key"])).'" size="15"> ';
		echo '<input type=text id=jas name=headoauth2key value="'.(esc_attr($formdata["headoauth2key"])).'" size="15"> ';
		#echo '<input type=text  id=jas  name=headoauth2val value="'.stripslashes(htmlspecialchars($formdata["headoauth2val"])).'" size=50> ';
		echo '<br>';
		esc_html_e('Value', 'json-content-importer');
		echo ': ';
		echo '<br><textarea name=headoauth2val rows=3 cols=80>'.esc_attr($formdata["headoauth2val"]).'</textarea>';
			
		$cbheadoauth2_checked = ""; if ($formdata["cbheadoauth2"]=="y") { $cbheadoauth2_checked = " checked "; 			}
		echo '<br>';
		esc_html_e('Use it', 'json-content-importer');
		echo ' <input type=checkbox name=cbheadoauth2 value="y"'.esc_attr($cbheadoauth2_checked).'><span class="tooltiptext">';

		echo '</td></tr>';
		echo '</table>';

		#var_dump($formdata);


		#echo '<input type=hidden name=nameofselectedjas value="'.stripslashes(htmlspecialchars(@$formdata["nameofselectedjas"])).'">';
		echo '<input type=hidden name=nameofselectedjas value="'.(esc_attr(@$formdata["nameofselectedjas"])).'">';
		$formdata["accset"] = $formdata["accset"] ?? '';
		#echo '<input type=hidden name=accset value="'.stripslashes(htmlspecialchars($formdata["accset"])).'">';
		echo '<input type=hidden name=accset value="'.(esc_attr($formdata["accset"])).'">';

	$submitButtonValue = __("Test Request", 'json-content-importer');#.$formdata["accset"];
	submit_button($submitButtonValue, 'primary', 'testrequest', FALSE); 
	echo "</form>";
?>

<?PHP	
}

	private function insert_tooltip($in) {
		echo '<div class=tooltip><span class=tooltip-text>';
		echo esc_html($in);
		echo '</span><span class="tooltip-icon">?</span></div>';
		return TRUE;
	}


	private function clear_httpheaderkey($key) {
		return preg_replace("/[^a-z0-9\-]/i", "", $key);  #remove spaces, specialchars etc. 
	}
	
	
#############################################################

	/**
	 * show, update, delete table of API-Acces-Sets
	 */
	private function showapis() {
		echo "<h2>";
		esc_html_e("Stored API-Access-Sets", 'json-content-importer');
		echo " - ";
		
		$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
		$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
		echo "<a href=".esc_url($secure_url).">";
		esc_html_e("Add or modify a API-Access-Set", 'json-content-importer');
		echo "</a></h2><hr>";
	
		$post_seljs = $this->jci_handle_getinput('seljs');


		$act = $this->jci_handle_getinput('act');   #  $_GET["act"] ?? '';
		$mid = $this->jci_handle_getinput('mid');   # $_GET["mid"] ?? '';
		if (!empty($act) && !empty($mid)) {
			if ("del"==$act) {
				unset($this->apiitemsArr[$mid]);
			}
			if ("act"==$act) {
				$this->apiitemsArr[$mid]["status"] = "active";
			}
			if ("ina"==$act) {
				$this->apiitemsArr[$mid]["status"] = "inactive";
			}
			$save_storeapirequestval_str = wp_json_encode($this->apiitemsArr);
			update_option('jci_free_api_access_items', $save_storeapirequestval_str, JCIFREE_UO_AUTOLOAD);
		}
		
		echo '<table class="widefat striped">';
		if (count($this->apiitemsArr)>0) {
			echo "<tr><td>";
			echo "<b>";
			esc_html_e("Name", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			echo "<b>";
			esc_html_e("Load", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>Status</b><br>Show on lists with API-Access-Sets";
			echo "<b>";
			esc_html_e("Status", 'json-content-importer');
			echo "</b><br>";
			esc_html_e("Show on lists with API-Access-Sets", 'json-content-importer');
			echo "</td><td>";
			#echo "<b>Date</b>";
			echo "<b>";
			esc_html_e("Date", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>URL</b>";
			echo "<b>";
			esc_html_e("URL", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>Timeout</b>";
			echo "<b>";
			esc_html_e("Timeout", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>method</b>";
			echo "<b>";
			esc_html_e("method", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>technique</b>";
			echo "<b>";
			esc_html_e("technique", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>Header</b>";
			echo "<b>";
			esc_html_e("Header", 'json-content-importer');
			echo "</b>";
			echo "</td><td>";
			#echo "<b>Delete?</b>";
			echo "<b>";
			esc_html_e("Delete", 'json-content-importer');
			echo "</b>";
			echo "</td></tr>";
			$namechecker = Array();
			foreach($this->apiitemsArr as $i) {
				echo "<tr><td>";
				echo esc_html($i["nameofjas"]);
				echo "</td><td>";
				$urlin = "admin.php?page=unique_jci_menu_slug&tab=step1";
				$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
				echo "<form action=".esc_attr($secure_url)." method=post>";
				echo '<input type=hidden name="accset" value="'.esc_attr($i["md5id"]).'">';
				echo '<input type=hidden name="type" value="loadaccset">';
				$submitButtonValue = "Load this API-Access-Set";
				submit_button($submitButtonValue, 'primary', 'loadjas', FALSE); 
				$namechecker[trim($i["nameofjas"])] = $namechecker[trim($i["nameofjas"])] ?? 0;
				@$namechecker[trim($i["nameofjas"])]++;
				if ($namechecker[$i["nameofjas"]]>1) {
					echo "<br><font color=red>Attention: Name already used - rename it please</font>";
				}
				echo "</form>";
				echo "</td><td>";
				
				$status = $i["status"] ?? '';
				if (empty($status)) {
					$status = "active";
				}

				$urlin = "?page=unique_jci_menu_slug&tab=step1&act=";
				if ("inactive"==$status) {
					echo "<font color=red>NO</font> - ";
					$urlin .= "act&mid=".$i["md5id"];
					$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
					echo '<a href='.esc_attr($secure_url).'>';
					esc_html_e('switch to YES', 'json-content-importer');
					echo '</a>';
				} else {
					echo "<font color=green>YES</font> - ";
					$urlin .= "ina&mid=".$i["md5id"];
					$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
					echo '<a href='.esc_attr($secure_url).'>';
					esc_html_e('switch to NO', 'json-content-importer');
					echo '</a>';
				}
				echo "</td><td>";
				#echo esc_html(gmdate("F d.Y, H:i:s", $i["time"]));
				
				echo esc_html(date_i18n('j. F Y, H:i', $i["time"]));
				
				echo "</td><td>";
				echo esc_html($i["set"]["jciurl"]);
				echo "</td><td>";
				echo esc_html($i["set"]["timeout"]);
				echo "</td><td>";
				echo esc_html($i["set"]["method"]);
				echo "</td><td>";
				echo esc_html($i["set"]["methodtech"]);
				echo "</td><td>";
				$hn = $i["set"]["noheader"];
				for ($j = 1; $j <= $hn ; $j++) {
					if (!empty(($i["set"]["headerl".$j] ?? '')) || !empty(($i["set"]["headerr".$j] ?? ''))) {
						echo esc_html($i["set"]["headerl".$j])." : ".esc_html($i["set"]["headerr".$j])."<br>";
					}
				}
				echo "</td><td>";

				# check if a API-Access-Set is connected to a Use-Set!
				$is_this_access_set_connected_to_an_use_set = FALSE;
			
				$jci_free_api_use_items = json_decode(get_option('jci_free_api_use_items'), TRUE) ?? array();
				if ( count($jci_free_api_use_items)>0) {
					foreach($jci_free_api_use_items as $k => $v) {
							if ($i["md5id"]==$v["selectejas"]) {
								$is_this_access_set_connected_to_an_use_set = TRUE;
							}
					}
				}
				if ("deleted"==$status) {
					echo "<font color=red>";
					esc_html_e('deleted', 'json-content-importer');
					echo "</font>";
				} else {
					if ($is_this_access_set_connected_to_an_use_set) {
						esc_html_e('This API-Access-Set can\'t be deleted:', 'json-content-importer');
						echo '<br>';
						esc_html_e('It is used at a API-Access-Set', 'json-content-importer');
						echo '<p>';
						#jcifree_load_json_use_set($i["md5id"], $k, $v, "Load the connected API-Use-Set");
						 
					} else {
						$urlin = '?page=unique_jci_menu_slug&tab=step1&act=del&mid='.$i["md5id"];
						$secure_url = wp_nonce_url( $urlin, 'jci-set-nonce' );
						echo '<a href='.esc_attr($secure_url).' onclick = "if (! confirm(\'';
						esc_html_e('Really deleting this API-Access-Set?', 'json-content-importer');
						echo '\')) { return false; }" title="';
						esc_html_e('delete API-Access-Set permanently', 'json-content-importer');
						echo '">';
						esc_html_e('delete this API-Access-Set', 'json-content-importer');
						echo '</a>';
					}
				}
				echo "</td></tr>";
			}
		} else {
			echo "<tr><td>";
			echo "No API-Access-Sets defined";
			echo "</td></tr>";
		}
		echo "</table>";
	}	

}
?>