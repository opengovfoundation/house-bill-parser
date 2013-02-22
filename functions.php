<?php
/**
 * 	Helper functions for bill parser script
 */

	/**
	 * 	Print correct script usage and exit
	 */
	function printHelp(){
		echo "-----------------------------------\n";
		echo "Usage: php parse <filename>\n";
		echo "-----------------------------------\n";
		exit;
	}

	/**
	 * 	Print Errors
	 */
	function printError($err){
		echo "-----------------------------------\n";
		echo "Error: $err\n";
		echo "-----------------------------------\n";
		exit;
	}
	
	/**
	 * 	Print Messages
	 */
	function printMessage($msg){
		echo "-----------------------------------\n";
		echo "$msg\n";
		echo "-----------------------------------\n";
	}
	
	/**
	 *	 Get input from the command line
	 */
	function getInput($msg){
		echo "$msg ...\n";
		$ret = fgets(STDIN);
		return $ret;
	}
	
	/**
	 * 	Get Bill values where only one tag exists
	 */	
	function getBillMeta($doc, $tag){
		$bill_meta = $doc->getElementsByTagName($tag);
		$bill_meta = trim($bill_meta->item(0)->nodeValue);
		$bill_meta = preg_replace('/(\s)+/', ' ', $bill_meta);
		return $bill_meta;
	}