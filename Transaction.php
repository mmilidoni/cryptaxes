<?php
class Transaction {

    public $quantity = 0;
    private $balance = 0;
    public $price = 0;
    public $operation = "in";
    public $datetime;
	private $gain = 0;

    function __construct($quantity, $price, $operation, $datetime) {
        $this->quantity = $quantity;
        $this->price = $price;
        $this->operation = $operation;
        $this->datetime = $datetime;
		if ($this->operation == "in") {
			$this->balance = $quantity;
		}
	}

	function decrease($quantity, $price) {
		$this->gain += $quantity * ($price - $this->price);
		$this->balance -= $quantity;
	}

	function reset($price) {
		$this->gain += $this->quantity * ($price - $this->price);
		$this->balance = 0;
	}

	function getBalance() {
		return $this->balance;
	}

	function getGain() {
		return $this->gain;
	}

	function __toString() {
		return "qty: ".$this->quantity.", price: ".$this->price.", operation: ".$this->operation.", datetime: ".$this->datetime.", gain: ".$this->gain.", balance: ".$this->balance;
	}

	function getCSV($symbol) {
		return "$symbol,$this->quantity,$this->price,$this->operation,$this->datetime,$this->gain,$this->balance,".($this->balance * $this->price)."\n";
	}
}

?>

