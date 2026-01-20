<?php
/*
CLASS JsonContentImporter
Description: Class for WP-plugin "JSON Content Importer"
Version: 2.0.0
Author: Bernhard Kux
Author URI: https://www.kux.de/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

class JsonContentImporter {

    /* shortcode-params */		
    private $numberofdisplayeditems = -1; # -1: show all
    private $feedUrl = ""; # url of JSON-Feed
    private $urlgettimeout = 5; # 5 sec default timeout for http-url
    private $basenode = ""; # where in the JSON-Feed is the data? 
    private $debugmode = Array();#0; # 10: show ebug-messages
    private $oneofthesewordsmustbein = ""; # optional: one of these ","-separated words have to be in the created html-code
    private $oneofthesewordsmustbeindepth = 1; # optional: one of these ","-separated words have to be in the created html-code
    private $oneofthesewordsmustnotbein = ""; # optional: one of these ","-separated words must NOT in the created html-code
    private $oneofthesewordsmustnotbeindepth = 1; # optional: one of these ","-separated words must NOT to in the created html-code
	private $execshortcode = FALSE;

    /* plugin settings */
    private $isCacheEnable = FALSE;
 
    /* internal */
	private $cacheFile = Array();
	private $cacheEnable = FALSE;
	private $jsondata = "";
	private $feedData  = "";
 	private $cacheFolder = "";
    private $datastructure = "";
    private $triggerUnique = NULL;
    private $cacheExpireTime = 0;
    private $oauth_bearer_access_key = "";
    private $http_header_default_useragent_flag = 0;
    private $debugmessage = Array();
	private $fallback2cache = 0;
	private $removewrappingsquarebrackets = FALSE;
	private $nojsonvalue = FALSE;
	private $trytorepairjson = 0;
	private $apiaccesset = '';
	private $returnapidata = '';
    private $nestedlevel = 0; # initial value
	

	public function __construct(){  
			 add_shortcode('jsoncontentimporter' , array(&$this , 'shortcodeExecute')); # hook shortcode
	}
	
	#public function jci_getdata($api_access_set_name) {
	#	$sc = '[jsoncontentimporter apiaccesset="check" returnapidata=y]';
	#	$scE = do_shortcode($sc);
	#	return "$scE anfield: $api_access_set_name";
	#}	
    
	private function showdebugmessage($message, $showDEBUG=TRUE){
      if ($this->debugmode[$this->nestedlevel]!=10) {
        return "";
      }
     if ($showDEBUG) {
        $this->debugmessage[$this->nestedlevel] .= __('DEBUG' , 'json-content-importer').' ('.$this->nestedlevel.'-'.$this->debugmode[$this->nestedlevel].'): ';
     }
      $this->debugmessage[$this->nestedlevel] .= "$message<br>";
    }
    

	public function handle_postinput($fieldkey, $default="") {
		$noncein = $this->handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, $this->plugincode.'-set-nonce' );	
		if (!$chknon) {
			return "";
		}		
		return sanitize_text_field(wp_unslash(($_POST[$fieldkey] ?? $default)));
	}
	public function handle_getinput($fieldkey, $default="") {
		$noncein = $this->handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, $this->plugincode.'-set-nonce');	
		if (!$chknon) { # nonce failed
			return "";
		}	
		return sanitize_text_field(wp_unslash(($_GET[$fieldkey] ?? $default)));
	}

	public function handle_requestinput($fieldkey, $default="") {
		$chknon = FALSE;
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			$chknon = wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), $this->plugincode.'-set-nonce' );	
		}
		if (!$chknon) {
			return "";
		}				
		return sanitize_text_field(wp_unslash(($_REQUEST[$fieldkey] ?? $default)));
	}

	public function getAASIdFromName($aasname){
		$return = Array();
		if (!empty($aasname)) {
			$apiitems = get_option( 'jci_free_api_access_items' );
			$apiitemsArr = json_decode($apiitems, TRUE);
			$aasfound = FALSE;
			foreach($apiitemsArr as $aask => $aasv) {
				$nameofjas = $aasv["nameofjas"] ?? NULL;
				if (!is_null($nameofjas)) {
					$selcaas = $selcaasSet["nameofjas"] ?? '';
					if (!empty($nameofjas) && ($aasname==$nameofjas)) {
						$return["apiaccessetarr"] = $aasv;
						$return["apiaccesset"] = $aask;
						$aasfound = TRUE;
					}
				}
			}

			if ($aasfound) {
				$return["status"] = TRUE;
			} else {
				$errormsg = __('API-Access-Set', 'json-content-importer');
				$errormsg .= ' <b>'.esc_html($this->apiaccesset).'</b> ';
				$errormsg .= __('defined in Shortcode not found', 'json-content-importer');
				$rdamtmp = $this->debugmessage[$this->nestedlevel].$errormsg;
				$return["status"] = FALSE;
				$return["msg"] = apply_filters("json_content_importer_result_root", $rdamtmp);
			}
			return $return;
		}
	}
	
    /* shortcodeExecute: read shortcode-params and check cache */
	public function shortcodeExecute($atts , $content = ""){
       
	   
	$val_jci_contributor_off = get_option('jci_contributor_off', 1);
	if (1==$val_jci_contributor_off) {
		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		if ( in_array( 'contributor', $roles, true ) ) {
			$renderedContent = "Access denied: You must be an Administrator, Editor, or Author to use the JCI plugin. Your current role 'Contributor' does not have the required permissions.";
			return $renderedContent;
		}
	}
	   
	   
	   
	   
	   
	   
	   $attsIn = shortcode_atts(array(
        'url' => '',
        'urlgettimeout' => '',
        'numberofdisplayeditems' => '',
        'oneofthesewordsmustbein' => '',
        'oneofthesewordsmustbeindepth' => '',
        'oneofthesewordsmustnotbein' => '',
        'oneofthesewordsmustnotbeindepth' => '',
        'basenode' => '',
        'fallback2cache' => 0,
        'debugmode' => '',
        'removewrappingsquarebrackets' => '',
        'nojsonvalue' => '',
        'trytorepairjson' => 0,
		'execshortcode' => '',
		'apiaccesset' => '',
		'returnapidata' => '',
		), $atts);
		
		$this->nestedlevel++;
		$this->debugmessage[$this->nestedlevel] = "";
		$this->returnapidata = $attsIn["returnapidata"] ?? "";
		$this->apiaccesset = $attsIn["apiaccesset"] ?? "";
		$apiaccessetarr["status"] = '';
		
		if (!empty($this->apiaccesset)){
			$ret = $this->getAASIdFromName($this->apiaccesset);
			if ($ret["status"]) {
				$apiaccessetarr = $ret["apiaccessetarr"];
				$this->apiaccesset = $ret["apiaccesset"];
			} else {
				return $ret["msg"];
			}
		}

		/*
		if (!empty($this->apiaccesset)) {
			$apiitems = get_option( 'jci_free_api_access_items' );
			#echo $apiitems;
			$apiitemsArr = json_decode($apiitems, TRUE);
			$aasfound = FALSE;
			foreach($apiitemsArr as $aask => $aasv) {
				$selcaasSet = $aasv["set"] ?? NULL;
				if (!is_null($selcaasSet)) {
					$selcaas = $selcaasSet["nameofselectedjas"] ?? '';
					if (!empty($selcaas) && ($this->apiaccesset==$selcaas)) {
						$apiaccessetarr = $aasv;
						$this->apiaccesset = $aask;
						$aasfound = TRUE;
					}
				}
			}
			if (!$aasfound) {
				$errormsg = __('API-Access-Set', 'json-content-importer');
				$errormsg .= ' <b>'.esc_html($this->apiaccesset).'</b> ';
				$errormsg .= __('defined in Shortcode not found', 'json-content-importer');
				$rdamtmp = $this->debugmessage.$errormsg;
				return apply_filters("json_content_importer_result_root", $rdamtmp);
			}
		}
		*/
		
		$url = $attsIn["url"] ?? "";
		if (empty($url) && ($apiaccessetarr["status"]=="active")) {
			$url = $apiaccessetarr["set"]["jciurl"] ?? "";
		}
		
	    $urlgettimeout = $attsIn["urlgettimeout"] ?? 5;
		if (5==$urlgettimeout && ($apiaccessetarr["status"]=="active")) {
			$urlgettimeout = $apiaccessetarr["set"]["timeout"] ?? 5;
		}

		$basenode = $attsIn["basenode"];
        $numberofdisplayeditems = $attsIn["numberofdisplayeditems"] ?? -1;
        $oneofthesewordsmustbein = $attsIn["oneofthesewordsmustbein"] ?? "";
        $oneofthesewordsmustbeindepth = $attsIn["oneofthesewordsmustbeindepth"] ?? "";
        $oneofthesewordsmustnotbein = $attsIn["oneofthesewordsmustnotbein"] ?? "";
        $oneofthesewordsmustnotbeindepth = $attsIn["oneofthesewordsmustnotbeindepth"] ?? "";
        $basenode = $attsIn["basenode"] ?? "";
        $fallback2cache = $attsIn["fallback2cache"] ?? 0;
        $debugmode = $attsIn["debugmode"] ?? "";
		
        $removewrappingsquarebrackets = $attsIn["removewrappingsquarebrackets"] ?? "";
        $nojsonvalue = $attsIn["nojsonvalue"] ?? "";
        $trytorepairjson = $attsIn["trytorepairjson"] ?? 0;
        $execshortcode = $attsIn["execshortcode"] ?? '';
		
		$this->debugmode[$this->nestedlevel] = 0;
		if ($debugmode==10) {
			$this->debugmode[$this->nestedlevel] = $debugmode;
		}	
      
      $this->feedUrl = $this->removeInvalidQuotes($url);
      $this->oneofthesewordsmustbein = $this->removeInvalidQuotes( ($oneofthesewordsmustbein ?? "") );
      $this->oneofthesewordsmustbeindepth = $this->removeInvalidQuotes($oneofthesewordsmustbeindepth);
      $this->oneofthesewordsmustnotbein = $this->removeInvalidQuotes( ($oneofthesewordsmustnotbein ?? "") ) ?? "";
      $this->oneofthesewordsmustnotbeindepth = $this->removeInvalidQuotes($oneofthesewordsmustnotbeindepth);
	  
		if ("y" == $nojsonvalue) {
			$this->nojsonvalue = TRUE;
		}
		if (is_int($trytorepairjson) && ($trytorepairjson > 0)) {
			$this->trytorepairjson = $trytorepairjson;
		}
		if ("y" == $removewrappingsquarebrackets) {
			$this->removewrappingsquarebrackets = TRUE;
		}
		#if (get_option('jci_api_errorhandling')>=0) {
			$this->fallback2cache = get_option('jci_api_errorhandling', 0);
		#}
	  if (
		"1"==$fallback2cache ||
		"2"==$fallback2cache ||
		"3"==$fallback2cache
		) {
		$this->fallback2cache = $fallback2cache;
	  }
	  
      /* caching or not? */
	  /*
      if (
          (!class_exists('FileLoadWithCache'))
          || (!class_exists('JSONdecode'))
		 ) {
        require_once plugin_dir_path( __FILE__ ) . '/class-fileload-cache.php';
      }
 	*/
	
	
    require_once plugin_dir_path( __FILE__ ) . '/class-fileload-cache.php';

    $this->cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/';
    # cachefolder ok: set cachefile
	$this->cacheFile[$this->nestedlevel] = $this->cacheFolder . sanitize_file_name(md5($this->feedUrl)) . ".cgi";  # cache json-feed

      /* set other parameter */      
      if ($numberofdisplayeditems>=0) {
        $this->numberofdisplayeditems = $this->removeInvalidQuotes($numberofdisplayeditems);
      }
      if (is_numeric($urlgettimeout) && ($urlgettimeout>=0)) {
        $this->urlgettimeout = $this->removeInvalidQuotes($urlgettimeout);
      }

      /* cache */
      $this->cacheEnable = FALSE;
      if (get_option('jci_enable_cache')==1) {
        $this->cacheEnable = TRUE;
        $checkCacheFolderObj = new CheckCacheFolder(WP_CONTENT_DIR.'/cache/', $this->cacheFolder);
        $this->showdebugmessage(__('Cache is active', 'json-content-importer'));
      } else {
        $this->showdebugmessage(__('Cache is NOT active', 'json-content-importer'));
      }

      $cacheTime = get_option('jci_cache_time');  # max age of cachefile: if younger use cache, if not retrieve from web
	  $format = get_option('jci_cache_time_format');
      #$cacheExpireTime = strtotime(date('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));
      $cacheExpireTime = strtotime(gmdate('Y-m-d H:i:s'  , strtotime(" -".$cacheTime." " . $format )));
      $this->cacheExpireTime = $cacheExpireTime;
	  
      if ($this->cacheEnable) {
        $this->showdebugmessage("CacheExpireTime: ".$cacheTime." $format");
      }

      $this->oauth_bearer_access_key = get_option('jci_oauth_bearer_access_key');
	  if (!empty($this->oauth_bearer_access_key)) {
		$this->showdebugmessage("oAuth Bearer Authentication Setting: ".stripslashes(htmlentities($this->oauth_bearer_access_key)));
	  }
		$this->http_header_default_useragent_flag = get_option('jci_http_header_default_useragent');

      if (""==$this->feedUrl) {
        $errormsg = __('No URL defined: Check the shortcode - one typical error: is there a blank after url= ?', 'json-content-importer');
        $rdamtmp = $this->debugmessage[$this->nestedlevel].$errormsg;
  			return apply_filters("json_content_importer_result_root", $rdamtmp);
      } else {
        $this->showdebugmessage("try to retieve this url: ".$this->feedUrl);
      }
	  
		if (empty($this->apiaccesset)) {
			$fileLoadWithCacheObj = new FileLoadWithCache($this->feedUrl, $this->urlgettimeout, $this->cacheEnable, $this->cacheFile[$this->nestedlevel],
				$this->cacheExpireTime, $this->oauth_bearer_access_key, $this->http_header_default_useragent_flag, $this->debugmode[$this->nestedlevel], $this->fallback2cache, $this->removewrappingsquarebrackets);
      
			$fileLoadWithCacheObj->retrieveJsonData();
			$this->feedData = $fileLoadWithCacheObj->getFeeddata();
			if ($trytorepairjson & 16) {
				$this->feedData = preg_replace("/[^\x0A\x20-\x7E]/",'',$this->feedData); 
			}
			$this->showdebugmessage($fileLoadWithCacheObj->getdebugmessage(), FALSE);
		} else {
			# load with API-Access-Set
			#echo "apiaccessetarr: ".json_encode($apiaccessetarr);
			if ($apiaccessetarr["status"]!="active") {
				return "API Access Set is configured but inactive. No further output.";
			}
			if (
				(!class_exists('jci_free_request'))
			) {
				require_once plugin_dir_path( __FILE__ ) . 'getlib.php';
			}
			$jci_free_request = new jci_free_request();
			$formdata["accset"] = $this->apiaccesset;
			$loadaccset = TRUE;
			$formdata = $jci_free_request->setDataFromAccSet($apiaccessetarr, $formdata, $loadaccset);
			
			# BEGIN buildrequest
			if (
				(!class_exists('jci_free_request_prepare'))
			) {
				require_once plugin_dir_path( __FILE__ ) . 'lib/lib_request.php';
			}
			$jci_request_handler = new jci_free_request_prepare($formdata);
			$curloptions = $jci_request_handler->getCurlOptionsString();
			# END buildrequest
			
			# BEGIN cache: The  retrieved JSON from the API-Access-Set is NOT cached
			$cacheEnable = $this->cacheEnable;  
			$cacheFile = $this->cacheFile[$this->nestedlevel];
			$cacheExpireTime = $this->cacheExpireTime;
			# END Cache 

			# BEGIN LOAD
			if (
				(!class_exists('FileLoadWithCacheFreeV2'))
				|| (!class_exists('JSONdecodeFreeV2'))
			) {
				require_once plugin_dir_path( __FILE__ ) . 'class-fileload-cache-v2.php';
			}
			$debugModeIsOn = FALSE;
			$debugLevel = 10;
			$header = "";
			$urlencodepostpayload = "";
			$encodingofsource = "";
			$httpstatuscodemustbe200 = "no";
			$auth = "";
			$showapiresponse = FALSE;
			$followlocation = TRUE; # follow 301 etc.
			
			$methodtechTmp = $formdata["methodtech"];
			$methodTmp = $formdata["method"];
			
			$selectedmethod = $jci_free_request->getSelectedmethod($methodtechTmp, $methodTmp);
			$curloptions4Request = $jci_request_handler->getCurlOptions4Request($curloptions);
			$fileLoadWithCacheObj = new FileLoadWithCacheFreeV2(
				$formdata["jciurl"], $formdata["timeout"], $cacheEnable, $this->cacheFile[$this->nestedlevel], $cacheExpireTime, 
				$selectedmethod, NULL, '', '',
				$formdata["payload"], $header, $auth, $formdata["payload"],
				$debugLevel, $debugModeIsOn, $urlencodepostpayload, $curloptions4Request,
				$httpstatuscodemustbe200, $encodingofsource, $showapiresponse, $followlocation 
				);
				
			$fileLoadWithCacheObj->retrieveJsonData();
			$this->feedData = $fileLoadWithCacheObj->getFeeddataWithoutpayloadinputstr();  
			
			$httpcode = $fileLoadWithCacheObj->getErrormsgHttpCode();
			# END LOAD
		}
		if (""==$this->feedData) {
			$errormsg = __('EMPTY api-answer: No JSON received - is the API down? Check the URL you use in the shortcode!', 'json-content-importer');
			$rdamtmp = $this->debugmessage[$this->nestedlevel].$errormsg;
  			return apply_filters("json_content_importer_result_root", $rdamtmp);
		} else {
			$inspurl = "https://jsoneditoronline.org";
			$this->buildDebugTextarea(__('api-answer', 'json-content-importer').":<br>".__('Inspect JSON: Copypaste (click in box, Strg-A marks all, then insert into clipboard) the JSON from the following box to', 'json-content-importer')." <a href=\"".$inspurl."\" target=_blank>https://jsoneditoronline.org</a>):", $this->feedData);
		}
			# build json-array
		if ($this->nojsonvalue) {
			$nojsonArr = Array(); 
			$nojsonArr["nojsonvalue"] = $this->feedData;
			$this->feedData = wp_json_encode($nojsonArr);
		}
		
		if (
			(!class_exists('JSONdecodeFreeV2'))
			|| (!class_exists('FileLoadWithCacheFreeV2'))
		) {
			require_once plugin_dir_path( __FILE__ ) . 'class-fileload-cache-v2.php';
		}
		
		$formdata["indataformat"] = $formdata["indataformat"] ?? "";
		$formdata["csvdelimiter"] = $formdata["csvdelimiter"] ?? "";
		$formdata["csvline"] = $formdata["csvline"] ?? "";
		$formdata["csvenclosure"] = $formdata["csvenclosure"] ?? "";
		$formdata["csvskipempty"] = $formdata["csvskipempty"] ?? "";
		$formdata["csvescape"] = $formdata["csvescape"] ?? "";

      #$jsonDecodeObj = new JSONdecodeFreeV2($this->feedData, FALSE, 0, FALSE, TRUE);  #first FALSE: nmeeded for free JCI parser!
      $jsonDecodeObj = new JSONdecodeFreeV2($this->feedData, FALSE, 0, FALSE, TRUE, "", "", 
		$formdata["indataformat"],
		$formdata["csvdelimiter"], 
		$formdata["csvline"], 
		$formdata["csvenclosure"],
		$formdata["csvskipempty"],
		$formdata["csvescape"]
	  );  #first FALSE: needed for free JCI parser!
 


 $this->jsondata = $jsonDecodeObj->getJsondata();
	  
      $this->basenode = $this->removeInvalidQuotes($basenode);
	  if (empty($basenode)) {
		$this->showdebugmessage("basenode: no basenode defined");
	  } else {
		$this->showdebugmessage("basenode: ".$basenode);
	  }
      
      $this->datastructure = preg_replace("/\n/", "", $content);
	  
	  
	$this->execshortcode = FALSE;
	if ($execshortcode=="y") {
		$this->execshortcode = TRUE;
    }
	if ($this->execshortcode) {
		$this->datastructure = preg_replace("/#BRO#/", "[", $this->datastructure);	
		$this->datastructure = preg_replace("/#BRC#/", "]", $this->datastructure);	
		$this->datastructure = preg_replace("/&#8221;/", "\"", $this->datastructure);	
		$this->datastructure = preg_replace("/&#8217;/", "\"", $this->datastructure);	
		$this->datastructure = preg_replace("/&#8243;/", "\"", $this->datastructure);	
		$this->datastructure = do_shortcode($this->datastructure);
	}
	  
      $outdata = htmlentities($this->datastructure);
      $this->buildDebugTextarea("template:", $outdata);
      
      require_once plugin_dir_path( __FILE__ ) . '/class-json-parser.php';
      $JsonContentParser = new JsonContentParser123($this->jsondata, $this->datastructure, $this->basenode, $this->numberofdisplayeditems,
            $this->oneofthesewordsmustbein, $this->oneofthesewordsmustbeindepth,
            $this->oneofthesewordsmustnotbein, $this->oneofthesewordsmustnotbeindepth);
      $rdam = $JsonContentParser->retrieveDataAndBuildAllHtmlItems();
      $outdata = htmlentities($rdam);
      $parseMsg = $JsonContentParser->getErrorDebugMsg();
      $this->showdebugmessage($parseMsg);
      $this->buildDebugTextarea(__('result:', 'json-content-importer'), $outdata);
      $rdamtmp = $this->debugmessage[$this->nestedlevel].$rdam;
			$this->nestedlevel--;
			return apply_filters("json_content_importer_result_root", $rdamtmp);
		}

    private function buildDebugTextarea($message, $txt, $addline=FALSE) {
        $norowsmax = 20;
        $norows = $norowsmax; 
        $strlentmp = round(strlen($txt)/90);
        if ($strlentmp<20) {
          $norows = $strlentmp;
        }
        $nooflines = substr_count($txt, "\n");
        if ($nooflines > $norows) {
          $norows = $nooflines;
        }
        if ($norows > $norowsmax) {
          $norows = $norowsmax;
        }
        $norows = $norows + 2;
        $out = $message."<br><textarea rows=".$norows." cols=90>".$txt."</textarea>";
        if ($addline) {
          $out .= "<hr>";
        }
        $this->showdebugmessage($out);
    }

    private function removeInvalidQuotes($txtin) {
      $invalid1 = urldecode("%E2%80%9D");
      $invalid2 = urldecode("%E2%80%B3");
      $txtin = preg_replace("/^[".$invalid1."|".$invalid2."]*/i", "", $txtin);
      $txtin = preg_replace("/[".$invalid1."|".$invalid2."]*$/i", "", $txtin);
      return $txtin;
    }

}
?>