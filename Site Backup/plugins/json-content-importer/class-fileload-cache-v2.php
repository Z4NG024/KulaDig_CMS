<?php
# version 2

class JSONdecodeFreeV2 {
	/* internal */
	private $jsondata = "";
	private $feedData = "";
	private $jsonDecodeCompleteToArray = FALSE;
	private $isAllOk = TRUE;
	private $debugModeIsOn = FALSE;
	private $debugLevel = 2;
	private $convertJsonNumbers2Strings = FALSE;
	private $debugmessage = "";
	private $contenttype = "";
	private $cacheFile = "";
	private $inputtype="json";
	private $inputtypedelimiter = ",";
	private $inputtypecsvline = "\n";
	private	$inputtypeenclosure = '"';
	private $inputtypeskipempty = FALSE;
	private $inputtypeescape = "\\";


	public function __construct($feedData, $jsonDecodeCompleteToArray, $debugLevel, $debugModeIsOn, $convertJsonNumbers2Strings, $cacheFile="", $contenttype="", 
			$inputtype="json", $inputtypedelimiter=",", $inputtypecsvline="\n", 
			$inputtypeenclosure='"', $inputtypeskipempty=FALSE, $inputtypeescape = "\\"
		){
		require_once plugin_dir_path( __FILE__ ) . '/lib/logdebug.php';
		$this->feedData = $feedData;
		$this->contenttype = $contenttype;
		$this->jsondata = "";
		$this->jsonDecodeCompleteToArray = $jsonDecodeCompleteToArray;
		$this->debugLevel = $debugLevel;
		$this->debugModeIsOn = $debugModeIsOn;
		$this->cacheFile = $cacheFile;
		$this->inputtype = $inputtype;
		$this->inputtypedelimiter = $inputtypedelimiter;
		$this->inputtypecsvline = $inputtypecsvline;
		$this->inputtypeenclosure = $inputtypeenclosure;
		$this->inputtypeskipempty = $inputtypeskipempty;
		$this->inputtypeescape = $inputtypeescape;
		
		if ("xml"==$this->inputtype) {
			$this->convertXML2JSON();
		}
		if ("csv"==$this->inputtype) {
			$this->convertCSV2JSON();
		}
		
		
		if ($convertJsonNumbers2Strings) {
			$this->convertJsonNumbers2Strings = TRUE;
		}
		$this->isAllOk = $this->decodeFeedData();
	}



		private function loadFileWP($file){
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}		
			$url = wp_nonce_url( 'index.php', 'my-nonce-jci-file' );
			$credentials = request_filesystem_credentials( $url );
			if ( ! WP_Filesystem( $credentials ) ) {
				$this->errormsg .= __('Initialize of WP_Filesystem failed', 'json-content-importer');
				return $this->errormsg;
			}
			global $wp_filesystem;	

			$filecontent = $wp_filesystem->get_contents($file);	
			return $filecontent;
		}

		private function getJSONFromCache(){
					if (file_exists($this->cacheFile)) {
						$this->feedData = $this->loadFileWP($this->cacheFile); #file_get_contents($this->cacheFile);
						$this->jci_load_collectDebugMessage("getJSONFromCache successful");
						return TRUE;
					} else {
						$this->jci_load_collectDebugMessage("getJSONFromCache failed");
						return FALSE;
					}
		}

	private function jci_utf8_encode($string) { # v8.2: utf8_encode deprecated in PHP 8.2, replaced by mb_convert_encoding
		return mb_convert_encoding($string, 'UTF-8', mb_list_encodings());	# mb_list_encodings: array of all supported encodings
	}

	/* decodeFeedData: convert raw-json-data into array */
	/* return value: TRUE if all ok, FALSE if an error occured */
	private function decodeFeedData() {
		if(empty($this->feedData)) {
			$this->feedData = '{"nojsonvalue": "emptyapianswer"}';
			#$this->jci_load_collectDebugMessage("Empty JSON-Feed");
			#return FALSE;
		}
			if ($this->convertJsonNumbers2Strings) {    
				$this->jci_load_collectDebugMessage("Convert JSON-Numbers to JSON-Strings to avoid unsatisfactory PHP-number-handling");
				$tmpfeedData = preg_replace('/"([ ]*):([ ]*)([0-9.,]*)([ ]*)([,}])/', '"\1:\2"\3"\4\5', $this->feedData);
				$tmpfeedDataArr = json_decode($tmpfeedData, $this->jsonDecodeCompleteToArray);
				if (!is_null($tmpfeedDataArr)) {
					# preg_replace might invalidate the JSON, therefore convert only if JSON still vlaid
					$this->feedData = $tmpfeedData;
				}			
			}
			$this->jsondata =  json_decode($this->feedData, $this->jsonDecodeCompleteToArray);
			if (is_null($this->jsondata)) {
				# $enc = mb_detect_encoding($this->feedData);
				# jci_utf8_encode JSON-datastring, then try json_decode again
				$this->jsondata = json_decode($this->jci_utf8_encode($this->feedData), $this->jsonDecodeCompleteToArray);
				if (is_null($this->jsondata)) {
					# sometimes linefeeds in the string are corrupting the JSON
					$this->feedData = preg_replace("/([\n\r]*)/", "", $this->feedData);
					$this->jsondata =  json_decode($this->feedData, $this->jsonDecodeCompleteToArray);
					if (is_null($this->jsondata)) {
						$mb_detect_encoding = mb_detect_encoding($this->feedData);
						$str = iconv($mb_detect_encoding, 'UTF-8' , $this->feedData);
						$this->jsondata =  json_decode($str, $this->jsonDecodeCompleteToArray);
						if (is_null($this->jsondata)) {
							$this->jci_load_collectDebugMessage("FAILED: Try to convert mb_detect_encoding $mb_detect_encoding to UTF-8 for being able to decode String to JSON");
						} else {
							$this->jci_load_collectDebugMessage("OK: Try to convert mb_detect_encoding $mb_detect_encoding to UTF-8 for being able to decode String to JSON");
						}
					}
					# sometimes content-types gives us the key to convert to utf8
					if (is_null($this->jsondata)) {				
						if (!empty($this->contenttype)) {
							if (preg_match("/charset=/", $this->contenttype)) {
								$csArr = explode("charset=", $this->contenttype);
								$ctsent = trim($csArr[1]);
							} else {
								$ctsent = $this->contenttype;
							}
							$this->jci_load_collectDebugMessage("Try to convert Content-Type $ctsent to UTF-8 for being able to decode String to JSON");
							
							$str = @iconv($in_charset = $ctsent, $out_charset = 'UTF-8' , $this->feedData);
							$this->jsondata =  json_decode($str, $this->jsonDecodeCompleteToArray);
							if (is_null($this->jsondata)) {
								$this->jci_load_collectDebugMessage("FAILED: Try to convert Content-Type $ctsent to UTF-8 for being able to decode String to JSON");
							} else {
								$this->jci_load_collectDebugMessage("OK: Try to convert Content-Type $ctsent to UTF-8 for being able to decode String to JSON");
							}
						}
					}
					
					# last try: use cached json
					if (is_null($this->jsondata)) {				
						$jci_pro_api_errorhandling = get_option("jci_pro_api_errorhandling");
						if (
							(2==$jci_pro_api_errorhandling || 3==$jci_pro_api_errorhandling)
						) {
							$this->jci_load_collectDebugMessage("try to use JSON from the cache");
							if ($this->getJSONFromCache()) {
								$this->jsondata = json_decode($this->feedData, $this->jsonDecodeCompleteToArray);
								if (is_null($this->jsondata)) {
									$this->jci_load_collectDebugMessage("Failed: use JSON from the cache");
								} else {
									$this->jci_load_collectDebugMessage("Ok: use JSON from the cache");
								}
							}
						}
					}
					
					if (
						(!is_Array($this->jsondata))
						&& (!is_Object($this->jsondata))
					) {
						$this->jci_load_collectDebugMessage("Invalid JSON-Feed after trying to heal JSON: Build artificial JSON-Feed with key 'nojsonvalue'. Value is the raw data from the API");
						#$tmp = $this->jsondata;
						unset($this->jsondata);
						$this->jsondata = array( "nojsonvalue" => $this->feedData);
					}
					return TRUE;
				}
			}
			if (
				(!is_Array($this->jsondata))
				&& (!is_Object($this->jsondata))
			) {  
				$this->jci_load_collectDebugMessage("Invalid JSON-Feed: Build artificial JSON-Feed with key 'nojsonvalue'. Value is the raw data from the API", 2, "", "<br>");
				$tmp = $this->jsondata;
				unset($this->jsondata);
				$this->jsondata = array( "nojsonvalue" => $tmp);
			}
			if (is_array($this->jsondata)) {
				if (isset($this->jsondata["jsonrpc"]) && ($this->jsondata["jsonrpc"]=="2.0") && isset($this->jsondata["result"]) && is_string($this->jsondata["result"])) {  
					$this->jci_load_collectDebugMessage("JSON-Feed is a jsonrpc 2.0 feed: try to convert result-string to array for twig-usage");
					$tmpArr =  json_decode($this->jci_utf8_encode($this->jsondata["result"]), $this->jsonDecodeCompleteToArray);
					if (is_null($tmpArr)) {
						$this->jci_load_collectDebugMessage("JSON-Feed is a jsonrpc 2.0 feed: conversion failed");
					} else {
						unset($this->jsondata["result"]);
						$this->jsondata["result"] = $tmpArr;
						$this->jci_load_collectDebugMessage("JSON-Feed is a jsonrpc 2.0 feed: conversion successful, result-JSON is available for twig");
					}
				}
			} else {
				$this->jci_load_collectDebugMessage("no jsonrpc-detection: only with twig-parser, set parser=twig in shortcode for that");
			}
			
			#echo  "<hR>RES: ".($this->feedData)."<hr>";
			
			return TRUE;
	}

  /* convert XML 2 JSON */
	public function convertXML2JSON() {
        $inputXMLtmp = str_replace("<soap:Body>","",$this->feedData);
        $inputXMLtmp = str_replace("</soap:Body>","",$inputXMLtmp);
        $inputXMLtmp = str_replace(":","_DOPPEL_",$inputXMLtmp); # otherwise XML-nodes like aa:bb are ignored
        $xml = @simplexml_load_string($inputXMLtmp, "SimpleXMLElement", LIBXML_NOCDATA);
        $tmpFeedData = wp_json_encode($xml);
        $tmpFeedData = str_replace("_DOPPEL_",":",$tmpFeedData);
        if ($tmpFeedData!="") {
          $this->feedData = $tmpFeedData;
        }
	}

  /* convert CSV 2 JSON */
	public function convertCSV2JSON() {
		$delimiter = ","; #field delimiter (one character only)
		if (!empty($this->inputtypedelimiter)) {
			$delimiter = $this->inputtypeparamArr_replacePlaceholders($this->inputtypedelimiter);
			$delimiter = preg_replace("/#TAB#/", "	", $delimiter);
		}
		
		$enclosure = '"'; # enclosure character (one character only)
		if (!empty($this->inputtypeenclosure)) {
			$enclosure = $this->inputtypeparamArr_replacePlaceholders($this->inputtypeenclosure);
		}

		$escape = "\\"; # Defaults as a backslash (\)
		if (!empty($this->inputtypeescape)) {
			$escape = $this->inputtypeparamArr_replacePlaceholders($this->inputtypeescape);
		}

		$csvline = "\n"; # 
		if (!empty($this->inputtypecsvline)) {
			$csvline = $this->inputtypeparamArr_replacePlaceholders($this->inputtypecsvline);
		}

		$skipempty = FALSE;
		if (!empty($this->inputtypeskipempty) && ("yes" == $this->inputtypeskipempty)) {
			$skipempty = TRUE;
		}
		
		$data = array();
		$csvLines = explode($csvline, $this->feedData);
		
		$headerline = "";
		#$headerline = str_getcsv($csvLines[0], $delimiter, $enclosure, $escape);
		try {
			$headerline = str_getcsv($csvLines[0], $delimiter, $enclosure, $escape);

			foreach (array_slice($csvLines, 1) as $line) {   # csv mit headerline!
				if ($skipempty && empty(trim($line))) {
					continue;
				}
				$tmp = str_getcsv($line, $delimiter, $enclosure, $escape);
				$data[] = array_combine($headerline, $tmp);
			}
			$data1["lines"] = $data;
			$json = wp_json_encode($data1);
			if ($json) {
				$this->feedData = $json;
				return TRUE;
			}
		} catch (\ValueError $e) {
			#error_log('str_getcsv ValueError: ' . $e->getMessage());
		}		
	}

	private	function inputtypeparamArr_replacePlaceholders($value) {
		$value = preg_replace("/#QM#/i", "\"", $value);
		$value = preg_replace("/#CR#/i", "\r", $value);
		$value = preg_replace("/#LF#/i", "\n", $value);
		$value = preg_replace("/#BS#/i", "\\", $value);
		return $value;
	}


  /* get */
	public function getJsondata() {
		return $this->jsondata;
	}
  /* get */
	public function getIsAllOk() {
    return $this->isAllOk;
  }

    private function jci_load_collectDebugMessage($debugMessage, $debugLevel=2, $prefix="", $suffix="") {
		jcifreelogDebug::jci_addDebugMessage($debugMessage, $this->debugModeIsOn, $this->debugLevel, $debugLevel, $suffix, TRUE, FALSE, $prefix, 400);
    }
}
/* class JSONdecode END */

/* class FileLoadWithCache BEGIN */
class FileLoadWithCacheFreeV2 {
  /* internal */
  private $feedData = "";
  private $urlgettimeout = 5; # 5 seconds default timeout for get of JSON-URL
  private $cacheEnable = "";
  private $cacheFile = "";
  private $feedUrl = "";
  private $cacheExpireTime = 0;
  private $cacheWritesuccess = FALSE;
  private $method = "get";
  private $requestArgs = NULL;
  private $allok = TRUE;
  private $errormsgout = "";
  private $errorhttpcode = "";
  private $errorhttpcodestring = "";
  private $httpresponse = NULL;
  private $feedSource = "http";
  private $feedFilename = "";
  private $postPayload = "";
  private $header = "";
  private $auth = "";
  private $postbody = "";
  private $debugModeIsOn = FALSE;
  private $debugLevel = 2;
  private $urlencodepostpayload = '';
  private $curloptions = "";
  private $httpstatuscodemustbe200 = TRUE;
  private $debugmessage = "";
  private $contenttype = "";
  private $encodingofsource = "";
  private $showapiresponse = FALSE;
  private $apiresponseinfo = Array();
  private $followlocation = FALSE;
  private $args = NULL;

  public function getContentType() {
	  return $this->contenttype;
  }


  public function __construct($feedUrl, $urlgettimeout, $cacheEnable, $cacheFile, $cacheExpireTime, $method, $requestArgs, $feedSource, $feedFilename, $postPayload, $header, $auth, $postbody,
        $debugLevel, $debugModeIsOn, $urlencodepostpayload, $curloptions, $httpstatuscodemustbe200, $encodingofsource="", $showapiresponse=FALSE, $followlocation=FALSE
    ){
	require_once plugin_dir_path( __FILE__ ) . '/lib/logdebug.php';
    $this->cacheEnable = $cacheEnable;
    if ($urlgettimeout!="" && intval($urlgettimeout)>0) {
    #if (is_numeric($urlgettimeout) && $urlgettimeout>=0) {
      $this->urlgettimeout = $urlgettimeout;
    }
    $this->showapiresponse = $showapiresponse;
    $this->encodingofsource = $encodingofsource;
	$this->httpstatuscodemustbe200 = $httpstatuscodemustbe200;
    $this->cacheFile = $cacheFile;
    $this->feedUrl = $feedUrl;
    $this->feedSource = $feedSource;
    $this->feedFilename = $feedFilename;
    $this->cacheExpireTime = $cacheExpireTime;
      if ($method=="post" ||
        $method=="rawpost" ||
        $method=="get" ||
        $method=="curlget" ||
        $method=="curlpost" ||
        $method=="curldelete" ||
        $method=="curlput" ||
        $method=="rawget"
      ) {
      $this->method = $method;
    }
    $this->args = $requestArgs;
    $this->postPayload = $postPayload;
    $this->header = $header;
    $this->auth = $auth;
    if ("no"==$urlencodepostpayload) {
      $this->urlencodepostpayload = $urlencodepostpayload;
    }
    $this->curloptions = $curloptions;
    $this->debugLevel = $debugLevel;
    $this->debugModeIsOn = $debugModeIsOn;
	$this->followlocation = $followlocation;
    if (isset($postbody)) {
		$postbody = $this->replace_BRO_BRC($postbody);
		$this->postbody = $postbody;
		$postbodyout = $this->postbody;
		if (strlen($postbodyout)>10) {
			$postbodyout = substr($this->postbody, 0, 100)."... (length: ".strlen($this->postbody).")";
		}
		if ($this->method=="post") {
			$this->jci_load_collectDebugMessage("used postbody: ".$postbodyout);
		} else {
			$this->jci_load_collectDebugMessage("postbody IGNORED, this is used only if WP-POST is selected as method: ".$postbodyout);
		}
    }
}

  /* replace #BRO# and #BRC# */
private function replace_BRO_BRC($intxt) {
    $intxt = preg_replace("/#BRO#/", "[", $intxt);
    $intxt = preg_replace("/#BRC#/", "]", $intxt);
    return $intxt;
}


		private function loadFileWP($file){
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}		
			$url = wp_nonce_url( 'index.php', 'my-nonce-jci-file' );
			$credentials = request_filesystem_credentials( $url );
			if ( ! WP_Filesystem( $credentials ) ) {
				$this->errormsg .= __('Initialize of WP_Filesystem failed', 'json-content-importer');
				return $this->errormsg;
			}
			global $wp_filesystem;	

			$filecontent = $wp_filesystem->get_contents($file);	
			return $filecontent;
		}
	/* get */
	public function getFeeddata() {
		return $this->feedData;
  }

  public function getFeeddataWithoutpayloadinputstr() {
    $payloadinputstrPattern = "##payloadinputstr##";
    $ret = $this->feedData;
    if (preg_match("/$payloadinputstrPattern/", $this->feedData)) {
      $tmpFeeddataArr = explode($payloadinputstrPattern, $this->feedData);
      $ret = $tmpFeeddataArr[0];
    }
    return $ret;
  }


  /* get errorlevel */
	public function getAllok() {
    return $this->allok;
  }
  /* get errorlevel */
	public function getErrormsg() {
    return $this->errormsgout;
  }
	public function getErrormsgHttpCode() {
    return $this->errorhttpcode;
  }
	public function getErrormsgHttpCodeString() {
    return $this->errorhttpcodestring;
  }
	public function getHttpResponse() {
    return $this->httpresponse;
  }

	public function getApiresponseinfo() {
		if ($this->showapiresponse) {
			return $this->apiresponseinfo;
		}
		return NULL;
	}

  /* retrieveJsonData: get json-data and build json-array */
	public function retrieveJsonData(){
		if ($this->feedSource == "file") {
			$this->retrieveFeedFromFilesystem();
		} else {
			$isRetrieveFromWebok = TRUE;
			# check cache: is there a not expired file?
			if ($this->cacheEnable) {
				# use cache
				if ($this->isCacheFileExpired()) {
					# get json-data from cache
					$this->retrieveFeedFromCache();
				} else {
					$isRetrieveFromWebok = $this->retrieveFeedFromWeb();
				}
			} else {
				# no use of cache OR cachefile expired: retrieve json-url
				$isRetrieveFromWebok = $this->retrieveFeedFromWeb();
			}
			if (!$isRetrieveFromWebok) {
				# requesting JSON from the API via web failed
				# use cached JSON, if needed and available:
				# 1: use cache if http-response is not 200 --> this is handled here
				# 2: use cache if response is not JSON
				# 3: use cache if http-response is not 200 OR not JSON --> this is handled here
				$jci_pro_api_errorhandling = get_option("jci_pro_api_errorhandling");
				if (
					(1==$jci_pro_api_errorhandling || 3==$jci_pro_api_errorhandling)
					&& (200!=$this->errorhttpcode)
					) {
						# not 200, use cached JSON
						$this->getJSONFromCache();
				}
			}
		}
		
		#echo "<hr>isRetrieveFromWebok: $isRetrieveFromWebok<hr>";
	
		if(""==$this->feedData) {
			$this->feedData = '{"nojsonvalue": "emptyapianswer"}';
		}
	}
	

	private function getJSONFromCache(){
		if(!class_exists('jci_Cache')) {
			require_once plugin_dir_path( __FILE__ ) . '/lib/cache.php';
		}
		$cacheFolderObj = new jci_Cache();
		$this->cacheFile = $cacheFolderObj->getCacheFileName($this->feedUrl, $this->postPayload, $this->postbody, $this->curloptions);
		if (file_exists($this->cacheFile)) {
			$this->feedData = $this->loadFileWP($this->cacheFile); #file_get_contents($this->cacheFile);
			$this->allok = TRUE;
		}
	}
	

      /* isCacheFileExpired: check if cache enabled, if so: */
		public function isCacheFileExpired(){
			# get age of cachefile, if there is one...
      if (file_exists($this->cacheFile)) {
        $ageOfCachefile = filemtime($this->cacheFile);  # time of last change of cached file
      } else {
        # there is no cache file yet
        return FALSE;
      }

      # if $ageOfCachefile is < $cacheExpireTime use the cachefile:  isCacheFileExpired = FALSE
      if ($ageOfCachefile < $this->cacheExpireTime) {
        return FALSE;
      } else {
        return TRUE;
      }
		}

    /* storeFeedInCache: store retrieved data in cache */
		private function storeFeedInCache(){
			if (!$this->cacheEnable) {
				# no use of cache if cache is not enabled or not working
				return NULL;
			}
	  
			if (""==$this->feedData) {
				$this->feedData = 'emptyapianswer';
			}
	  
			############ cache write BEGIN
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}		
			$url = wp_nonce_url( 'index.php', 'my-nonce-jci-file' );
			$credentials = request_filesystem_credentials( $url );
			if ( ! WP_Filesystem( $credentials ) ) {
				$this->errormsg .= __('Initialize of WP_Filesystem failed', 'json-content-importer');
				return $this->errormsg;
			}
			global $wp_filesystem;	
		
			if ( ! $wp_filesystem->exists( $this->cacheFile ) ) {
				$this->cacheWritesuccess = $wp_filesystem->put_contents( $this->cacheFile, $this->feedData, FS_CHMOD_FILE );
			}
			if ($this->cacheWritesuccess) {
				return $this->cacheWritesuccess;
			} else {
				$errormsg = stripslashes(get_option('jci_pro_errormessage'));
				if ($errormsg=="") {
					$this->errormsgout .= "cache-error:<br>".$this->cacheFile."<br>can't be stored - plugin aborted";
				} else {
					$this->errormsgout .= $errormsg." (300)";
				}
				$this->allok = FALSE;
				return "";
			}
		}

		public function retrieveFeedFromFilesystem(){
      $val_jci_pro_json_fileload_basepath = get_option('jci_pro_json_fileload_basepath'); # basepath
      if ($val_jci_pro_json_fileload_basepath=="") {
        $val_jci_pro_json_fileload_basepath = WP_CONTENT_DIR;
      }
      if (!preg_match("/\/$/", $val_jci_pro_json_fileload_basepath)) {
        $val_jci_pro_json_fileload_basepath .= "/";
      }
      $filename = $this->feedFilename;
      if (preg_match("/^./", $filename)) {  # protect access to dirs beyond default-dir
        $filename = preg_replace("/(\.\.\/)+/", "", $filename);
      }
      $completefilename = $val_jci_pro_json_fileload_basepath.$filename;
      # get json-feed from filesystem
      $getFile = FALSE;
			if(file_exists($completefilename)) {
        $getFile = $this->loadFileWP($completefilename);  #@file_get_contents($completefilename);
      }
      if (!$getFile) {
        # file loading failed
        $errormsg = stripslashes(get_option('jci_pro_errormessage'));
        if ($errormsg=="") {
          $this->errormsgout .= "reading of $completefilename failed";
        } else {
          $this->errormsgout .= $errormsg." (error: readfile)";
        }
        $this->allok = FALSE;
        return FALSE;
      } else {
        $this->feedData = $getFile;
        return TRUE;
      }
      return FALSE;
    }

	private function is_relative_url($url) {
		if (preg_match("/^\//", $url)) {
			return TRUE;
		}
		return FALSE;
	}
	
	private function add_domain_to_url($url) {
		return home_url().$url;
	}

	public function retrieveFeedFromWeb(){
      # wordpress unicodes http://openstates.org/api/v1/bills/?state=dc&q=taxi&apikey=4680b1234b1b4c04a77cdff59c91cfe7;
      # to  http://openstates.org/api/v1/bills/?state=dc&#038;q=taxi&#038;apikey=4680b1234b1b4c04a77cdff59c91cfe7
      # and the param-values are corrupted
      # un_unicode ampersand:
      $this->feedUrl = preg_replace("/&#038;/", "&", $this->feedUrl);
	  if ($this->is_relative_url($this->feedUrl)) {
			$this->feedUrl = $this->add_domain_to_url($this->feedUrl);
	  }
	  
      $argsHeader = array(
            'timeout'     => $this->urlgettimeout,
			'followlocation' => $this->followlocation
            );
      $val_jci_pro_http_header_useragent = get_option('jci_pro_http_header_useragent');
      if ($val_jci_pro_http_header_useragent!="") {
        $argsHeader["user-agent"] = $val_jci_pro_http_header_useragent;
      }

      $argsHeaderSub = array();
            #'httpversion' => '1.0',
            #'blocking'    => true,
            #'headers'     => array(),
            #'cookies'     => array(),
            #'body'        => null,
            #'compress'    => false,
            #'decompress'  => true,
            #'sslverify'   => true,
            #'stream'      => false,
            #'filename'    => null

      $val_jci_pro_allow_oauth_code = get_option('jci_pro_allow_oauth_code');
      if ($val_jci_pro_allow_oauth_code!="") {
          if (preg_match("/^Basic /", $val_jci_pro_allow_oauth_code)) {
            $outhheader = $val_jci_pro_allow_oauth_code;
          } else {
            $outhheader = 'Bearer '.$val_jci_pro_allow_oauth_code;
          }
          $argsHeaderSub["Authorization"] = $outhheader;
          $this->jci_load_collectDebugMessage("Add OAuth-Key from plugin-options to header: Authorization=".$outhheader);
          $argsHeader["sslverify"] = FALSE;
      }
      if ($this->auth!="") {
        $authShortcodeParamArr = explode("##", $this->auth);
        for ($i=0; $i<count($authShortcodeParamArr);$i++) {
          if (trim($authShortcodeParamArr[$i])=="") {
            continue;
          }
          $authTmpArr = explode(":", $authShortcodeParamArr[$i], 2);
          if (trim($authTmpArr[0])!="" && $authTmpArr[1]!="") {
            if (preg_match("/^true$/i", trim($authTmpArr[1]))) {
               $authTmpArr[1] = TRUE;
            }
            if (preg_match("/^false$/i", trim($authTmpArr[1]))) {
               $authTmpArr[1] = FALSE;
            }
            $argsHeaderSub[$authTmpArr[0]] = $authTmpArr[1];
          }
        }
      }

      $val_jci_pro_http_header_accept = get_option('jci_pro_http_header_accept');
      if ($val_jci_pro_http_header_accept!="") {
        $argsHeaderSub["Accept"] = $val_jci_pro_http_header_accept;
        $this->jci_load_collectDebugMessage("Add Accept from plugin-options to header: Accept=".$val_jci_pro_http_header_accept);
      }

      if ($this->header!="") {
        $headerShortcodeParamArr = explode("##", $this->header);
        for ($i=0; $i<count($headerShortcodeParamArr);$i++) {
          if (trim($headerShortcodeParamArr[$i])=="") {
            continue;
          }
          $hscTmpArr = explode(":", $headerShortcodeParamArr[$i], 2);
          if (trim($hscTmpArr[0])!="" && $hscTmpArr[1]!="") {
		  $argsHeaderSub[$hscTmpArr[0]] = $hscTmpArr[1];
            $this->jci_load_collectDebugMessage("Add header: key=".$hscTmpArr[0].", value=".$hscTmpArr[1]);
          }
        }
      }

      $argsHeader["headers"] = $argsHeaderSub;
	    $payloadInputJSONstr = "";

      if ($this->postPayload!="") {
        $this->jci_load_collectDebugMessage("POST-payload (before POSTGET-replacement): ".$this->postPayload);
        $this->postPayload = $this->replace_BRO_BRC($this->postPayload);

        ### insert data: match on a POSTGET_ field to be replaced by input values from $_GET or $_POST
        $number_of_to_be_filled_payloadfields = preg_match_all("/\"([a-z0-9]+)\"([ ]*):([ ]*)\"POSTGET_([a-z0-9]+)\"/i", $this->postPayload, $match_filler);
        if ($number_of_to_be_filled_payloadfields>0) {
  	      for ($i=0; $i<$number_of_to_be_filled_payloadfields; $i++) {
            $foundString = $match_filler[0][$i];
            $fi = trim($match_filler[1][$i]);
            #$input = @sanitize_text_field($_GET[$fi]);
			$input = $this->jci_handle_getinput($fi);
			
			
            if (empty($input)) {
				#$input = @sanitize_text_field($_POST[$fi]);
				$input = $this->jci_handle_postinput($fi);
            }
            $this->postPayload = preg_replace("/\"POSTGET_".$fi."\"/", "\"".$input."\"", $this->postPayload);
            $payloadInputArr[$fi] = $input;
          }
          $this->jci_load_collectDebugMessage("POST-payload (after POSTGET-replacement): ".$this->postPayload);
        } else {
          # static payload: no POSTGET_
          $this->jci_load_collectDebugMessage("POST-payload: no POSTGET-replacement");
        }
		 
		if (isset($payloadInputArr)) {
		    $payloadInputJSONstr = wp_json_encode($payloadInputArr);
		   } else {
		    $payloadInputJSONstr = $this->postPayload;
        }

        # shortcodeparam payload (which must be a valid JSON-feed) has more for POST-requests
 	      $payloadArr =  json_decode($this->postPayload, TRUE);
        if (is_array($payloadArr)) {
		$argsHeader["payload"] = $payloadArr;
          $this->jci_load_collectDebugMessage("POST-payload sent to API: ".wp_json_encode($argsHeader["payload"]));
        } else {
			$argsHeader["payload"][] = $this->postPayload;
			$this->jci_load_collectDebugMessage("Invalid POST-payload - must be valid JSON: ".$this->postPayload);
        }
      }

	if ("dummyrequest" == $this->feedUrl || "localrequest" == $this->feedUrl) {
        $postparam = get_post();

			$out = array();
			$out["time"] = time();
			$request_body = file_get_contents('php://input');
			$out["payload"] = $request_body;
			$out["header"] = getallheaders();
			$out["server"] = $_SERVER;
			$pa["jcipagerequest"] = $out;
			#$this->feedData = json_encode($pa);			

        if ($postparam) {
			$pa["jcipageparam"]["post"] = $postparam;
        } else {
			$this->feedData = wp_json_encode($pa);			
			return TRUE;
		}
        $postId = $postparam->ID;
		if ($postId>0) {
			$custom_fields_arr = get_post_custom();
		} else {
			$custom_fields_arr = array();
		}
        $pa["jcipageparam"]["custom_fields"] = $custom_fields_arr;
		$this->feedData = wp_json_encode($pa);
	} else {
		if ($this->method=="post") {
			$argsHeader["method"] = "POST";
			$this->jci_load_collectDebugMessage($argsHeader, 2, "WPPOST-Header sent to API: (", ")<br>");
			$argsHeader["body"] = $this->get_option_prepare_for_usage('jci_pro_http_body');
			if (""!=$this->postbody) {
				$argsHeader["body"] = $this->postbody;
			}
			$response = wp_remote_post($this->feedUrl, $argsHeader);
		#} else if ($this->method=="rawget") {
			#$response = $this->raw_remote_get($this->feedUrl, $argsHeader);
		} else if ($this->method=="curlget") {
			$response = $this->curl_get($this->feedUrl, $argsHeader);
		} else if ($this->method=="curlpost") {
			$response = $this->curl_post($this->feedUrl, $argsHeader);
		} else if ($this->method=="curlput") {
			#$response = $this->curl_put($this->feedUrl, $argsHeader);
			$response = $this->curl_post($this->feedUrl, $argsHeader, TRUE);
		} else if ($this->method=="curldelete") {
			$response = $this->curl_post($this->feedUrl, $argsHeader, FALSE, TRUE);
			
		#} else if ($this->method=="rawpost") {
		#	$response = $this->raw_remote_post($this->feedUrl, $argsHeader);
		} else { # default: WP-Get
			$this->method = "get";
			$this->jci_load_collectDebugMessage($argsHeader, 2, "GET-Header sent to API: (", ")<br>");
			$response = wp_remote_get($this->feedUrl, $argsHeader);
			$this->errorhttpcode = wp_remote_retrieve_response_code($response);
			$this->errorhttpcodestring = wp_remote_retrieve_response_message($response);
		}
      $this->httpresponse = $response;
      # get http-status code
      if (
        $this->method=="post" ||
        $this->method=="get"
      ) {
        if (is_array($response)) {
          $http_errorcode = $response['response']['code'];
        } else {
          $http_errorcode = 1000;
        }
        if ( is_wp_error( $response ) ) {
          $errormsg = stripslashes(get_option('jci_pro_errormessage'));
          if ($errormsg=="") {
            $error_message = $response->get_error_message();
            $this->errormsgout .= "Fetching URL failed: $error_message";
            $this->errorhttpcode = $error_message;
          } else {
            $this->errormsgout .= $errormsg." ($http_errorcode)";
            $this->errorhttpcode = $http_errorcode; #$error_message;
          }
           $this->allok = FALSE;
          return FALSE;
        }

        # response from JSON-Server, but maybe an invalid one
        # if there is an client error (errorcode 4xx) oder server error (5xx) do not cache and
        # do not use this "JSON" or whatever it is
        if (is_numeric($http_errorcode) && $http_errorcode>=400 && $http_errorcode<=600) {
          #if(isset($response['body']) && !empty($response['body'])) {
          # client or server error
          # do not cache, this would write invalid data into cache
          if ($this->cacheEnable) {
            # if cache is used try to use cached data, even if this is outdated
            $this->errormsgout .= "Fetching URL failed, loading cached data: http-errorcode $http_errorcode";
            $this->errorhttpcode = $http_errorcode;
            $this->retrieveFeedFromCache(FALSE);
            return TRUE;
          } else {
            # if cache is not used, display error message
            $errormsg = stripslashes(get_option('jci_pro_errormessage'));
            if ($errormsg=="") {
              $error_message = "http-errorcode ".$http_errorcode;
              $this->errormsgout .= "Fetching URL failed: http-errorcode $http_errorcode";
            } else {
              $this->errormsgout .= $errormsg." (error: $http_errorcode)";
            }
            $this->errorhttpcode = $http_errorcode;
            $this->allok = FALSE;
          }
          return FALSE;
        }
        # request was ok
		#echo "<hr>".$response['body']."<hr>";
		
		
     	 $this->feedData = $response['body'];
     } else if (
        $this->method=="curlget" ||
        $this->method=="curlpost" ||
        $this->method=="curlput" ||
        $this->method=="curldelete"
		) {
		if (is_bool($response) && !$response) {
			return FALSE;
		} else {
			$this->feedData = $response;
			if (!empty($this->encodingofsource)) {
				$this->feedData = iconv( $this->encodingofsource, 'UTF-8' , $this->feedData); 
			}
			/* maybe an option in special cases
			$contenttype = $this->getContentType();
			if (!empty($contenttype)) {
				$csArr = @explode("charset=", $contenttype);
				$ctsent = trim(@$csArr[1]);
				if ($ctsent=="utf-16le") {
					$this->feedData = iconv( "utf-16le", 'UTF-8' , $this->feedData); 
				}
			}
			*/
			if (!empty($payloadInputJSONstr)) {
				$this->feedData = $response."##payloadinputstr##".$payloadInputJSONstr;
			}
		}
     } else if (
        $this->method=="rawpost" ||
        $this->method=="rawget"
     ) {
      if (!$response) {
        return FALSE;
      } else {
    	   $this->feedData = $response;
      }
     }
     $this->storeFeedInCache();
	}
     return TRUE;
		}

	private function jci_handle_postinput($fieldkey, $default="") {
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) {
			return "";
		}		
		return sanitize_text_field(wp_unslash(($_POST[$fieldkey] ?? $default)));
	}	
	
	private function jci_handle_getinput($fieldkey, $default="") {
		$noncein = jci_handle_requestinput('_wpnonce');
		$chknon = wp_verify_nonce($noncein, 'jci-set-nonce' );	
		if (!$chknon) { # nonce failed
			return "";
		}	
		return sanitize_text_field(wp_unslash(($_GET[$fieldkey] ?? $default)));
	}


    /* retrieveFeedFromCache: get cached filedata  */
	public function retrieveFeedFromCache($retrieveFeedFromWebIfTheresNoCachefile = TRUE){
#		if((!is_dir($this->cacheFile))  && file_exists($this->cacheFile)) {
		if(  file_exists($this->cacheFile)) {
			$this->feedData = $this->loadFileWP($this->cacheFile);  #@file_get_contents($this->cacheFile);
			$this->errorhttpcode = "cache";
		} else {
			if ($retrieveFeedFromWebIfTheresNoCachefile) {
				# get from cache failed, try from web
				$this->retrieveFeedFromWeb();
			} else {
				$this->feedData = "";
			}
		}
	}


    private function get_option_prepare_for_usage($optionname) {
      return stripslashes(get_option($optionname));
    }
	
    private function jci_load_collectDebugMessage($debugMessage, $debugLevel=2, $prefix="", $suffix="", $convert2html=TRUE) {
		jcifreelogDebug::jci_addDebugMessage($debugMessage, $this->debugModeIsOn, $this->debugLevel, $debugLevel, $suffix, $convert2html, FALSE, $prefix, 400);
		}


	private function jci_array_diff_assoc_recursive($array1, $array2) {
		$difference = [];

		foreach ($array1 as $key => $value) {
			// Wenn der Schlüssel nicht existiert oder der Wert unterschiedlich ist
			if (!array_key_exists($key, $array2)) {
				$difference[$key] = $value;
			} elseif (is_array($value) && is_array($array2[$key])) {
				// Rekursiver Vergleich für verschachtelte Arrays
				$nestedDiff = $this->jci_array_diff_assoc_recursive($value, $array2[$key]);
				if (!empty($nestedDiff)) {
					$difference[$key] = $nestedDiff;
				}
			} elseif ($value !== $array2[$key]) {
				$difference[$key] = $value;
			}
			unset($difference["SERVER_PROTOCOL"]);
			unset($difference["REMOTE_PORT"]);
			unset($difference["REQUEST_TIME_FLOAT"]);
			unset($difference["H2_STREAM_TAG"]);
			unset($difference["H2_STREAM_ID"]);
			
			unset($difference["H2_PUSHED_ON"]);
			unset($difference["H2_PUSHED"]);
			unset($difference["H2_PUSH"]);
			unset($difference["HTTP2"]);
			unset($difference["UNIQUE_ID"]);
			unset($difference["H2PUSH"]);
			unset($difference["REQUEST_TIME"]);
    }


    return $difference;
}

    private function curl_post($url, $argsHeader, $putflag=FALSE, $deleteflag=FALSE) {
		############
		# POST via wp_remote_post
		#echo "<hr>curlgetreturnval: $curlgetreturnval<hr>";
		#$curlArr = json_decode($curlgetreturnval, TRUE);
		$curloptionlist = $this->curloptions;
	 
		$args = array();
		$args['body'] = $this->postPayload;
		$timeout = $argsHeader["timeout"] ?? 5;
		$args['timeout'] = $timeout;
		$args['headers']['user-agent'] = ''; # otherwiese WP sends something
		$args['headers']['Accept-Encoding'] = ''; # otherwiese WP sends something
		$args['headers']['Connection'] = ''; # otherwiese WP sends something

		$method = "POST";
		if ($putflag) {
			$method = "PUT";
		} else if ($deleteflag) {
			$method = "DELETE";
		}
		$args['method'] = $method;
		
		$curloptionlistArr = explode(";CURLOPT_", trim(stripslashes($curloptionlist)),2); # fails with CURLOPT_HTTPHEADER=Accept:text/xml##Content-Type:text/xml; charset=utf-8
		for ($j=0;$j<count($curloptionlistArr);$j++) {
			$curloptionItemArr = explode("=", $curloptionlistArr[$j],2); 
			if ($curloptionItemArr[0]=="CURLOPT_HTTPHEADER") {
				$headerArr = explode("##", $curloptionItemArr[1]); 
				for ($h=0;$h<count($headerArr);$h++) {
					$headerItem = explode(":", $headerArr[$h],2); 
					$args['headers'][$headerItem[0]] = $headerItem[1];
				}
			} else {
				#echo "$j NOT used yet: ". json_encode($curloptionItemArr)."<hr>";
			}
		}
	 
		# echo "<hr>args: ".json_encode($args)."<hr>";
	 
		$response = wp_remote_post($url, $args);
		
		$this->errorhttpcode = wp_remote_retrieve_response_code($response);

		if (is_wp_error($response)) {
			$this->errorhttpcodestring = $response->get_error_message();		
		}
		
		$this->contenttype = wp_remote_retrieve_header($response, 'content-type');
		return $response["body"];
    }
    
    private function unmaskqm4json($value) {
		$value = str_replace('##QMJSON##', '\"', $value);
		
		
		
		#return urldecode($value); 
		
		$value = str_replace('#SAMP#', '\'', $value);
		$value = str_replace('#GT#', '>', $value);
		$value = str_replace('#LT#', '<', $value);
		$value = str_replace('##BRO##', '[', $value);
		$value = str_replace('##BRC##', ']', $value);
		return $value;
	}
    
 
    /*
    private function store_file_ln_wp_lib($filename_source, $filename_lib, $uploadsamefilename=TRUE) {
		if (!$uploadsamefilename) {
			$foundImg = FALSE;
			$args = array(
				'post_type' => 'attachment',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => null, // any parent
			); 
			$attachments = get_posts($args);
			if ($attachments) {
				foreach ($attachments as $post) {
					if ($post->post_title==$filename_lib) {
						$foundImg = TRUE;
						break;
					}
				}
			}
			if ($foundImg) {
				$retval["status"] = "imgthere";
				$retval["msg"] = "no upload, as image with same name is already there";
				return $retval;
			}
		}
		
		$uploadfile = wp_upload_bits($filename_lib, null, file_get_contents($filename_source)); # return: file, urltype, error (bool)
		if ($uploadfile['error']) {
			$retval["status"] = "upload failed";
			$retval["msg"] = "ok";
			return $retval;
		} else {
			$wp_filetype = wp_check_filetype($filename_lib, null );
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				#	'post_parent' => $parent_post_id,
				'post_title' => $filename_lib,
				'post_content' => '',
				'post_status' => 'inherit'
			);
			
			$attachment_id = wp_insert_attachment( $attachment, $uploadfile['file']);
			if (is_wp_error($attachment_id)) {
				$retval["status"] = "is_wp_error";
				#$retval .= "0";
			} else {
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $uploadfile['file'] );
				wp_update_attachment_metadata( $attachment_id,  $attachment_data );
				$retval["status"] = "ok";
				$retval["attachment_id"] = $attachment_id;
				$retval["attachment_url"] = wp_get_attachment_url($attachment_id);
			}
		}
		return $retval;
   }
   */


	/*
    private function curl_setopt_wrapper($methodname, $curl, $curloptname, $curloptid, $curloptval) {
		$this->jci_load_collectDebugMessage($methodname.": curl_setopt $curloptname ($curloptid) with value ".wp_json_encode($curloptval), 10);
		if (!is_integer($curloptid)) {
			return FALSE;
		}
		$errorlevelcurlsetop = FALSE;
		$atLeastPHP8 = (version_compare(PHP_VERSION, '8.0.0') >= 0);
		$curloptval = str_replace("\0", "", $curloptval); # null byte causes fatal crash of curl_setopt
		if ($atLeastPHP8) {
			try {
				$errorlevelcurlsetop = curl_setopt($curl, $curloptid, $curloptval);
			} catch (Throwable $e) {	}
			return $errorlevelcurlsetop;
		} else {
			$errorlevelcurlsetop = curl_setopt($curl, $curloptid, $curloptval);
		}
		return $errorlevelcurlsetop;
	}
	*/

    private function curl_get($url, $argsHeader) {
      $methodname = "curlGET";
      if (empty($this->postPayload)) {
        $url4curl = $url;
      } else {
        $this->postPayload = $this->replace_BRO_BRC($this->postPayload);
        $this->jci_load_collectDebugMessage("curlGET: payload parameter: ".$this->postPayload);
        $params = json_decode($this->postPayload, TRUE);
        $queryString = NULL;
        if (empty($params)) {
          $this->jci_load_collectDebugMessage("curlGET: payload parameter: json-decoding failed, check syntax!");
        } else {
          $queryString = http_build_query($params);
          $this->jci_load_collectDebugMessage("curlGET: payload parameter - querystring build: ".$queryString);
        }
        if (preg_match("/\?/", $url)) {
          $url4curl = $url.'&'.$queryString;
        } else {
          $url4curl = $url.'?'.$queryString;
        }
        $this->jci_load_collectDebugMessage("curlGET: payload parameter - url 4 API: ".$url4curl);
      }
		
	  ########## WP BEGIN
		$url4curl = str_replace("\0", "", $url4curl);
	  		$curloptionlist = $this->curloptions;
			
		#	echo "<hr>curloptionlist: $curloptionlist<hr>";
			
	 
		$args = array();
		#$args['body'] = $this->postPayload;
		$timeout = $argsHeader["timeout"] ?? 5;

		$args['timeout'] = 5;
		if (is_numeric($this->urlgettimeout) && $this->urlgettimeout>0) {
			$args['timeout'] = $timeout;
		}
		
		
		
		$args['headers']['user-agent'] = ''; # otherwiese WP sends something
		$args['headers']['Accept-Encoding'] = ''; # otherwiese WP sends something
		$args['headers']['Connection'] = ''; # otherwiese WP sends something

		$method = "GET";
		#if ($putflag) {
			#$method = "PUT";
		#} else if ($deleteflag) {
			#$method = "DELETE";
		#}
		$args['method'] = $method;
		
		$curloptionlistArr = explode(";CURLOPT_", trim(stripslashes($curloptionlist)),2); # fails with CURLOPT_HTTPHEADER=Accept:text/xml##Content-Type:text/xml; charset=utf-8
		for ($j=0;$j<count($curloptionlistArr);$j++) {
			$curloptionItemArr = explode("=", $curloptionlistArr[$j],2); 
			if ($curloptionItemArr[0]=="CURLOPT_HTTPHEADER") {
				$headerArr = explode("##", $curloptionItemArr[1]); 
				for ($h=0;$h<count($headerArr);$h++) {
					$headerItem = explode(":", $headerArr[$h],2); 
					$args['headers'][$headerItem[0]] = $headerItem[1];
				}
			} else {
				#echo "$j NOT used yet: ". json_encode($curloptionItemArr)."<hr>";
			}
		}
	 
		# echo "<hr>args: ".json_encode($args)."<hr>";
		
		$val_jci_sslverify_off = get_option('jci_sslverify_off') ?? 3;
		if (1==$val_jci_sslverify_off ) {
			$args['sslverify'] = false;
		}
		if (2==$val_jci_sslverify_off ) {
			$args['sslverify'] = true;
		}
		
	 
		$response = wp_remote_get($url4curl, $args);
		
		$this->errorhttpcode = wp_remote_retrieve_response_code($response);

		if (is_wp_error($response)) {
			$this->errorhttpcodestring = $response->get_error_message();		
		}
		
		$this->contenttype = wp_remote_retrieve_header($response, 'content-type');
		if (is_object($response)) {
			#var_Dump($response);
			return json_encode($response);
		}
		#echo json_encode($response["body"]);
		return $response["body"];
    }
}
/* class FileLoadWithCache END */
?>