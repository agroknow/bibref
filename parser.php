<?php
$mode = $_GET['mode'];
if (!isset($mode)) {

	$handle = fopen("references.bib", 'a+');
	$csv_handle = fopen("progress.csv", 'a+');
	$csv_ref_handle = fopen("refs.csv", 'a+');
	$lines = file('progress.csv'); 
	$last_page = array_pop($lines);
	$apfrom='&apfrom='.$last_page;
	$apfrom=str_replace('"', '', $apfrom);
}
else {
	if ($mode = 'w') {
		$handle = fopen("references.bib", 'w');
		$csv_handle = fopen("progress.csv", 'w');
		$csv_ref_handle = fopen("refs.csv", 'w');
	}
	$apfrom = '';

}

$titles_call = 'http://qmrawiki.canr.msu.edu/api.php?action=query&list=allpages&aplimit=1000&format=json'.$apfrom;
print $titles_call.'<br/>';
//(?:\|(?:reference=))(.*?)(?=\|)
$json = file_get_contents($titles_call);

$results = json_decode($json);

$pages = $results->query->allpages;
if ($mode != 'w') {
	array_shift($pages);
}
foreach ($pages as $page) {
	print '<br/>'.$page->title.'<br/>';

	$url = 'http://qmrawiki.canr.msu.edu/api.php?action=query&titles='.$page->title.'&prop=revisions&rvprop=content&format=xml';
	print '<br/>'.$url.'</br>';
	//$url = 'http://qmrawiki.canr.msu.edu/api.php?action=query&titles=Bacillus anthracis: Dose Response Models&prop=revisions&rvprop=content&format=xml';
	$xml = simplexml_load_file($url) 
		or die("feed not loading");
	$revtext = $xml->query->pages->page->revisions->rev; 

	$revtext = '<html><body>'.$revtext.'</body></html>';
	//echo $revtext;

	libxml_use_internal_errors(true);
	$DOM = new DOMDocument;
	$DOM->loadHTML($revtext);

	//Retrieve all the <ref> elements 
	$items = $DOM->getElementsByTagName('ref');
	libxml_use_internal_errors(false);
	print '<br/>';
	print_r($items);
	fputcsv($csv_handle, array(str_replace('"', '', $page->title)));
	for ($i = 0; $i < $items->length; $i++) {
		print '<br/>'.$items->item($i)->nodeValue;
		if ($items->item($i)->nodeValue) {
			fputcsv($csv_ref_handle, array($page->title, $i+1, $items->item($i)->nodeValue));
			print './scholar.py -p "'.$items->item($i)->nodeValue.'" --citation=bt';
		    $output = shell_exec('./scholar.py -p "'.$items->item($i)->nodeValue.'" --citation=bt');
		    if (isset($output)) {
		    	print '<br/>'.$output;
				fputs($handle,$output);
			}
		}
	}
    preg_match_all("/(?:\|(?:refer\s?=\s?))(.*?)(?:\|)(?:\s?reference\s?=\s?)(.*?)(?=\|)/", $revtext, $matches);
	$refer_texts = array_unique($matches[2]);
	$k=0;
	foreach ($q as $refer_texts) {
		
		fputcsv($csv_ref_handle, array($page->title, $k+1, $q));
		$output = shell_exec('./scholar.py -p "'.$q.'" --citation=bt');
		    if (isset($output)) {
				fputs($handle,$output);
			}
	}

}
fputcsv($csv_handle, array($results->continue->apcontinue));
fclose($csv_handle);
fclose($handle);


?>