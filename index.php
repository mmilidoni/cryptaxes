<?php

require_once("Transaction.php");
if (!$_FILES['uploadedfile']) {
	echo "Cryptaxes";
	
	echo '<form enctype="multipart/form-data" action="" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
    Upload CSV: <input name="uploadedfile" type="file" />
    <input type="submit" value="Upload" />
</form>';

} else {
	$debug=false;

	if (!$debug) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=file.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
	}

	$lines = explode( "\n", file_get_contents($_FILES['uploadedfile']['tmp_name']) );
	$headers = str_getcsv( array_shift( $lines ) );
	$data = array();
	foreach ( $lines as $line ) {
		$row = array();
		foreach ( str_getcsv( $line ) as $key => $field ) {
			$row[ $headers[ $key ] ] = $field;
		}
	
		$row = array_filter( $row );
		if (!empty($row)) {
			$data[] = $row;
		}
	
	}

	$transactions = array();
	foreach($data as $el) {
		if(!is_array($transactions)) {
			$transactions[$el["Asset"]] = array();
		}
		$operation = "in";
		if ($el["Transaction Type"] == "Convert" || $el["Transaction Type"] == "Send") {
			$operation = "out";
		}
		$transactions[$el["Asset"]][] = new Transaction(
			$el["Quantity Transacted"], 
			$el["EUR Spot Price at Transaction"],
			$operation,
			$el["Timestamp"]
		);

		if ($el["Transaction Type"] == "Convert") {
			$a = explode(" to ", $el["Notes"]);
			$new = explode(" ", $a[1]);
			$transactions[$new[1]][] = new Transaction(
	            $new[0],
    	        floatval($el["EUR Subtotal"])/floatval($new[0]),
        	    "in",
            	$el["Timestamp"]
			);
		}
	}

	echo '"Symbol", "Quantity", "Price", "Operation", "Date Time", "Gain", "Balance Q.ty", "Capital"';
	echo "\n";
	foreach($transactions as $symbol => $list) {
		$symbolGain = 0;	
		$symbolBalance = 0;
		for ($i=0; $i<count($list); $i++) {
	    	$curr = $list[$i];
	    	if ($curr->operation == "out") {
			$bal = $curr->quantity;
			for ($j=0; $bal > 0 && $j<count($list); $j++) {
		    	$el = $list[$j];
			    if ($el->operation == "in" && $el->getBalance() > 0) {
			        if ($bal < $el->getBalance()) {
		    	        $el->decrease($bal, $curr->price);
		        	    $bal = 0;
		        	} else {
		            	$bal -= $el->getBalance();
			            $el->reset($curr->price);
	    	            }
		    	}
					$symbolGain += $el->getGain();
				}
		    }
		}
		if(!$debug) {
			foreach($list as $e) {
				echo $e->getCSV($symbol);
			}
		}
	}

	function printList($l, $symbol) {
		foreach($l as $e) {
			echo $e->getCSV($symbol);
		}
	}

}

?>

