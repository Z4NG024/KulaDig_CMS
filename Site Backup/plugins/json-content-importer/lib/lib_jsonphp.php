<?php

class workWithJSON {
	private $jsonArr = NULL;
	private $jsonKeysArr4UseBasenode = NULL;
	private $outstr = "";
	private $jsonf = array();
#	private $NUMBERPC = "#number#";
	private $ROOT = "_context";
	private $SEPARATOR = ".";
	private $ARRAYLIST = "#ARRAYLIST#";

	private $jsonKeysArr = Array();
	private $showAllNodes = FALSE;
	private $basenodeSelectedJSON = NULL;
	
	public function __construct($jsonArr){
		$this->jsonArr = $jsonArr;
		$this->outstr = "<table border=1><tr bgcolor=yellow><td>Wert</td><td>Value</td><td>DataType</td></tr>\n";
		#echo "INP: ";
		#var_Dump($this->jsonArr);
		$this->showNodes($this->ROOT, $this->jsonArr);
    }
	
	public function getJsonKeysArr(){
		return $this->jsonKeysArr;
    }


##### BEGIN JSON-Nodes for USE-Set Basenode
	public function getJsonKeysArr4UseBasenode(){
		return $this->jsonKeysArr4UseBasenode;
    }
	
	

	public function showJSONkeys4UseBasenode($showAllNodes=TRUE) {
		$this->showJSONkeysItems4UseBasenode("", $this->jsonArr, $showAllNodes);
	}

	private function showJSONkeysItems4UseBasenode($keynode, $jsonArrItem, $showAllNodes=TRUE) {
		if (!is_array($jsonArrItem)) {
			return "";
		}
		foreach($jsonArrItem as $key => $val) {
			#echo "IN: ".$key." - ".gettype($val)."<hr>";
			if (is_array($val)) {
				$anz = count($val);
				if ($anz==0) {
					continue;
				}
				if (is_integer($key)) {
					continue;
				}
					if (empty($keynode)) {
						$completenode = $key;		
					} else {
						$completenode = $keynode.".".$key;
					}
					$this->jsonKeysArr4UseBasenode[$completenode] = $anz;
					$this->showJSONkeysItems4UseBasenode($completenode, $val, $showAllNodes);
			} else if ($showAllNodes) {
				if (empty($keynode)) {
					$selkey = $key;
				} else {
					$selkey = $keynode.".".$key;
				}
			}
		}
	}
##### END JSON-Nodes for USE-Set Basenode


	public function showJSONkeys($showAllNodes=TRUE) {
		if (is_array($this->jsonArr)) {
			$this->jsonKeysArr = NULL;
			return NULL;
		}
		$this->showJSONkeysItems("root", $this->jsonArr, $showAllNodes);
	}

	private function showJSONkeysItems($keynode, $jsonArrItem, $showAllNodes=TRUE) {
		if (!is_array($jsonArrItem)) {
			return "";
		}
		foreach($jsonArrItem as $key => $val) {
			if (is_array($val)) {
				$anz = count($val);
				if ($anz==0) {
					continue;
				}
					if (empty($keynode)) {
						$completenode = $key;		
					} else if (is_integer($key)) {
						$completenode = $keynode;
					} else {
						$completenode = $keynode.".".$key;
					}
					#echo "CN: ".$completenode." : ($anz items)<br>";
					$this->jsonKeysArr[$completenode] = $anz;
					#echo "<br>";
					$this->showJSONkeysItems($completenode, $val);
			} else if ($showAllNodes) {
				if (empty($keynode)) {
					$selkey = $key;
				} else {
					$selkey = $keynode.".".$key;
				}
				$this->jsonKeysArr[$selkey] = $key;				
			}
		}
	}

	public function selectJSONnode($json, $seljsonnode) {
		$seljsonnodeArr = explode(".", $seljsonnode);
		$curnode = array_shift($seljsonnodeArr);
		$restnode = join(".", $seljsonnodeArr);
		if (empty($restnode)) {
			#echo "<br>".$seljsonnode." - ".$restnode."<br>";
			#echo print_r($json,TRUE)."<br>";
			#echo $curnode."<br>$restnode<br>";
			#echo json_encode($json[$curnode])."<hr>";
			#$this->jsonselected = 
			$this->basenodeSelectedJSON[$curnode] = $json[$curnode];
		} else {
			$this->selectJSONnode($json[$curnode], $restnode);	
		}		
	}
	
	public function getBasenodeSelectedJSON() {
		return $this->basenodeSelectedJSON;
	}


	public function getOutStr1() {
		$this->outstr .= "</table>";
		return $this->outstr;
	}
	
	public function getJsonTypeList() {
		return $this->jsonf;
	}
	
	public function showJSON($node, $parentpath, $noofshowedlistitems, $level, $openall=TRUE) {
				$out = "";
				foreach ($node as $key => $value) {
					$nodetype = gettype($node[$key]);

					if ($nodetype=="array") {
						//$jtreeopencss = "";
						//if ($key==0) {
						//	$jtreeopencss = ' class="jstree-open" ';
						//}
						$jtreeopencss = ' class="'; #jstree-open jstree-checked" ';
						if ($openall) { 
							$jtreeopencss .= 'jstree-open ';
						}
						$jtreeopencss .= 'jstree-checked" ';
						$out .= '<li $jtreeopencss id="'.$parentpath.$key.'">'.$parentpath.$key." -&gt; ";
#						$out .= '<li $jtreeopencss>'.$parentpath.$key." -&gt; ";
						$newlevel = $level + 1;
						$currentpath = $parentpath.$key.".";
						#$out .= "key: $currentpath<br>";
						$out .= " array with ".(count($node[$key]))." items";
						$out .= $this->showJSON($node[$key], $currentpath, $noofshowedlistitems, $newlevel, $openall);
						$out .= "</li>";
					} else {
						#if (empty($node[$key])) {
							#$out .= "<li data-jstree='{\"icon\":\"/wp-content/plugins/jsoncontentimporterpro3/js/jstree/icons/square.png\"}'>".$parentpath.$key." -&gt; ";
							#$out .= "<li ödata-jstree='{\"icon\":\"//jstree.com/tree.png\"}'>".$parentpath.$key." -&gt; ";
#							$out .= "<li data-jstree='{\"disabled\":true}'>".$parentpath.$key." -&gt; "; // set checkbox to not active
							$keymod = preg_replace("/\,/", "#KOMM##", $key);
							$keymod = preg_replace("/\./", "#DOT##", $keymod);
							if (empty($parentpath)) {
								$pk = $key;
								$pkMod = $keymod;
							} else {
								$pk = $parentpath.$key;
								$pkMod = $parentpath.$keymod;
							}
							$out .= '<li id="'.$pkMod.'"><a href="#">'.$pk." -&gt;";
							$nodeout = htmlentities(($node[$key] ?? ''));
							if (strlen($nodeout)>60) {
								$nodeout = substr($nodeout, 0, 60)."...";
							}
							$out .= "<code>".$nodeout."</code></a>";
							$out .= "</li>";
						#}
					}
				}
				$createbrick = "";
				return "<ul>".$out."</ul>";
				#return "<ul>".@$selJSON.@$lv.$notshown.$out."</ul>";
	}

	private function showNodes($node, $bodyArrin) {
		#global $outp, $jsonf;
		$no = $no ?? '';
		if ($node=="root") {
					$no = "root";
		} else {
					$no .= $node;
		}
		foreach ($bodyArrin as $key => $value) {
		if (is_int($key)) {
				$key = $this->ARRAYLIST;
		}
				$dt = $this->detectDataType($value);
				if ($dt=="array") {
						#$value = $value[0];
						$this->showNodes($no.$this->SEPARATOR.$key, $value);
						#$value1 = print_r($value, TRUE);
						#echo "<tr bgcolor=#aaa><td>$no.$key</td><td>$value1</td><td>".$dt."</td></tr>\n";
						$this->outstr .= "<tr bgcolor=#aaa><td>$no".$this->SEPARATOR."$key</td><td></td><td>".$dt."</td></tr>\n";
				} else if ($dt=="object") {
						$this->showNodes($no.$this->SEPARATOR.$key, $value);
						#echo "object: ".$no.$this->SEPARATOR.$key."<br>";
				} else {
						$value1 = $value;
						$li = $no.$this->SEPARATOR.$key;
						$this->outstr .= "<tr><td>$li</td><td>$value1</td><td>".$dt."</td></tr>\n";
						
						$litmp = $li;
						#$litmp = preg_replace("/\.([0-9]*)\./", $this->SEPARATOR.$this->NUMBERPC.$this->SEPARATOR, $litmp);
						$this->jsonf[$litmp] = $this->jsonf[$litmp] ?? 0;
						$this->jsonf[$litmp]++;
						#echo "N: ".$this->jsonf[$litmp]."<br>";
				}
		}
	}

	public	function detectDataType($datatxt) {
			if (is_array($datatxt)) {
				$dt = "array";
				return $dt;
			}
			if (is_object($datatxt)) {
				$dt = "object";
				return $dt;
			}
			$datatxt = $datatxt ?? '';
			$datatxt = trim($datatxt);
			$dt = "<b>unbekannter typ</b>";
			if (is_numeric($datatxt)) {
				$dt = "number";
				return $dt;
			}
			if (preg_match("/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/", $datatxt)) {
				$dt = "ip-number";
				return $dt;
			}
			
			# check on date
			$timeArr = date_parse ($datatxt);
			#var_Dump($timestr);
			if (""!=$timeArr["year"]) {
				$dt = "date";
				return $dt;
			}

			if (is_String($datatxt)) {
				$dt = "string";
				return $dt;
			}

			return $dt;
		}

}
?>