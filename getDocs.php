<?php
	/**
	 * 	Script to download all House bills (113th Congress session 1)
	 */
	
	$base_url = 'http://www.gpo.gov/fdsys/bulkdata/BILLS/113/1';
	$bill_categories = array('hconres', 'hjres', 'hr', 'hres');
	
	$html = file_get_contents($base_url);
	
	$doc = new DomDocument();
	$doc->loadHTML($html);
	
	echo "Retrieved 113th Congressional Bills HTML\n";
	
	//Get bills from each category
	foreach($bill_categories as $category){
		$category_url = $base_url . "/$category";
		$doc = new DomDocument();
		
		//Load html from the category
		echo "Loading $category_url\n";
		$doc->loadHTML(file_get_contents($category_url));
		
		//Create category directory
		echo "Creating $category/\n";
		if(!is_dir($category)){
			if(!mkdir($category)){
				die('Could not make directory ' . $category);
			}
		}
		
		//Retrieve bill xml files
		echo "Grabbing '$category' Bills\n";
		foreach($doc->getElementsByTagName('a') as $link){
			
			if(preg_match('|.*\.xml|', $link->nodeValue) == 1){
				$url = $category_url . "/" . trim($link->nodeValue);
				echo "-- Saving " . $category . "/" . trim($link->textContent) . " :: " . $url . " --\n";
				$xml_content = file_get_contents($url);
				file_put_contents("$category/" . trim($link->textContent), $xml_content);
			}
			
		}
		
	}
?>