<?php

require_once("Transaction.php");
if (!$_FILES['uploadedfile']) {
?>

<html>

	<head>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>

	</head>

	<body>

		<div class="container">
			<!-- <img class="d-block mx-auto mb-4" src="/docs/5.0/assets/brand/bootstrap-logo.svg" alt="" width="72" height="57"> -->
			<div class="row">
				<div class="col-2">
				</div>
				<div class="col">
					<h1 class="fw-bold">Cryptaxes</h1>
					<p>This tool processes data from your Coinbase report to calculate the taxable gain</p>

					<form enctype="multipart/form-data" action="" method="POST">
						<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
						Upload your Coinbase Report (CSV format): <input name="uploadedfile" type="file" />
						<input type="submit" value="Upload" />
					</form>

					<h3>Notes</h3>
					<ul>
						<li>FIFO method</li>
						<li>Only Coinbase Report (Coinbase website -> your user -> Reports -> Generate report)</li>
						<li>Gain is only calculated when either a currency is converted to another, or a value is sent to another wallet</li>
						<li>Your report is processed in-memory, no data is stored.</li>
						<li>You can find the source code on 
							<a href="https://github.com/mmilidoni/cryptaxes" target="_blank"><img src="img/github-logo.png" width="24" />Github</a>
						</li>
					</ul>
				</div>

				<div class="col-2">
				</div>
			</div>
		</div>

	</body>
</html>


	<?php

} else {
	$debug=false;


	$file_info = pathinfo($_FILES["uploadedfile"]['name']);
	$name = $file_info['filename'];
	$ext = $file_info['extension'];

	if ($ext !== "csv") {
		die("Invalid format. Use .csv only.");
		return;
	}

	$mimes = array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
	if (!in_array($_FILES['uploadedfile']["type"], $mimes)) {
		die("mime type not supported.");
		return;
	}

	if ($_FILES['uploadedfile']["size"] > 30000) {
		die("File is too big.");
		return;
	}

	if (!$debug) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=file.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
	}

	$lines = explode( "\n", file_get_contents($_FILES['uploadedfile']['tmp_name']));
	$lines = array_slice($lines, 7);
	$headers = str_getcsv( array_shift($lines));
	$data = array();
	foreach ( $lines as $line ) {
		$row = array();
		foreach ( str_getcsv( $line ) as $key => $field ) {
			if(!empty($field)){
				$row[ $headers[ $key ] ] = $field;
			}
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

	ob_clean();
	echo 'Symbol,Quantity,Price,Operation,Date Time,Gain,Balance Q.ty,Capital';
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

}

?>

