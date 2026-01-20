<?php
# 20241110-free
# used in getlib.php
class jci_free_request_prepare {
	private $formdata = NULL;
	private $methodTmp = "";
	private $methodtech = "";
	private $curloptions = "";
	
	public function __construct($formdata) {  #, $methodTmp){
		$this->formdata = $formdata;
		$this->methodTmp = $formdata["method"]; # $methodTmp;
		$this->methodtech = $formdata["methodtech"]; #$methodtech;
    }
	
	public function getCurlOptions4Request($curloptions) {
		$curloptionsForRequest = $this->do_shortcode($curloptions); # execute shortcode if there is one
		return $curloptionsForRequest;
	}
	
	public function getPageArr() {
		$propArr = Array();
		$propArr["get_permalink"] = get_permalink();
		$propArr["home_url"] = home_url();
		$userid = get_current_user_id();
		$propArr["get_current_user_id"] = $userid;
		$usrdataArr = get_userdata($userid);
		$propArr["userdata"] = $usrdataArr;

		$postparam = get_post();
		if (!is_null($postparam)) {
			#var_Dump($postparam);
			$postparam->post_content = preg_replace("/jsoncontentimporterpro/", "", trim($postparam->post_content)); # shortcode in $postparam->post_content are executed when used in twig, result: infinite loop or plugin-termination -> remove "jsoncontentimporterpro" to invalidate shortcode
			$propArr["get_post"] = $postparam;
			$pageid = $postparam->ID;
			$cpflist = get_post_meta($pageid);
			if (is_array($cpflist)) {
				$propArr["cpf"] = $cpflist;
			}
		}
		return $propArr;
	}

	public function do_shortcode($shortcodestring) {
		$shortcodestring = $this->unmask($shortcodestring);
		$shortcodestring = do_shortcode($shortcodestring);
		return $shortcodestring;
	}	

	private function mask($string) {
		$string = preg_replace("/\[/i", "#BRO#", $string);
		$string = preg_replace("/\]/i", "#BRC#", $string);
		$string = preg_replace("/\"/i", "#QM#", $string);
		return $string;
	}

	private function unmask($string) {
		$string = preg_replace("/#BRO#/i", "[", $string);
		$string = preg_replace("/#BRC#/i", "]", $string);
		$string = preg_replace("/#QM#/i", "\"", $string);
		return $string;
	}

	public function getCurlOptionsString() {
		$curloptions = "";
		$curloptions_there = FALSE;
		$curloptions_header_values = "";
		# header
		# CURLOPT_HTTPHEADER=accept:application/json##Authorization:Bearer whatever##a:{{urlparam.VAR1}}##c:d;CURLOPT_POSTFIELDS=e:f##{“g”:”h”}##i:j
		
		if ($this->formdata["headernooffilledheader"]>0) {
			$noofheaderitems = @$this->formdata["headernooffilledheader"];
			for ($i = 1; $i <= $noofheaderitems; $i++) {
				if (isset($this->formdata["headerl".$i]) && isset($this->formdata["headerr".$i])) {
					$key = preg_replace("/ /", "", $this->formdata["headerl".$i]);
					$val = $this->formdata["headerr".$i];
					if (!empty($key.$val)) {
						$curloptions_header_values .= $this->clear_httpheaderkey($key).':'.$val."##";
						$curloptions_there = TRUE;
					}
				}
			}
		}

		if ($this->formdata["cbheadaccess"]=="y") {
			$curloptions_header_values .= $this->clear_httpheaderkey($this->formdata["headaccesskey"]).":".$this->formdata["headaccessval"]."##";
			$curloptions_there = TRUE;
		}
		if ($this->formdata["cbheaduseragent"]=="y") {
			$curloptions_header_values .= $this->clear_httpheaderkey($this->formdata["headuseragentkey"]).":".$this->formdata["headuseragentval"]."##";
			$curloptions_there = TRUE;
		}
		if ($this->formdata["cbheadoauth2"]=="y") {
			$curloptions_header_values .= $this->clear_httpheaderkey($this->formdata["headoauth2key"]).":".$this->formdata["headoauth2val"]."##";
			$curloptions_there = TRUE;
		}
		if ($curloptions_there) {
			$curloptions_header_values = preg_replace("/##$/", "", $curloptions_header_values);
			$curloptions .= "CURLOPT_HTTPHEADER=".$curloptions_header_values.";";
		}
		
		if ($this->formdata["httpsverify"] == 2) {
			$httpverifycurl = "CURLOPT_SSL_VERIFYHOST=false;CURLOPT_SSL_VERIFYPEER=false;";
			$curloptions_there = TRUE;
			$curloptions .= $httpverifycurl;
		}

		#payload
		# CURLOPT_POSTFIELDS=
		if ($this->methodTmp=="post" || $this->methodTmp=="put") {
			$httppayload = "CURLOPT_POSTFIELDS=".$this->formdata["payload"].";"; #$postPayload;
			$curloptions .= $httppayload;
			$curloptions_there = TRUE;
		}
		if ($curloptions_there) {
			$curloptions = substr($curloptions, 0, -1);
		}

		$curloptions = $this->mask($curloptions);
		return stripslashes($curloptions);
	}

	private function clear_httpheaderkey($key) {
		return preg_replace("/[^a-z0-9\-]/i", "", $key);  #remove spaces, specialchars etc. 
	}	
}
?>