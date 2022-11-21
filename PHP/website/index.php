<?php 

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$url = 'https://api.tradingeconomics.com/historical/country/mexico,sweden/indicator/gdp?c=guest:guest&f=json';
$headers = array(
    "Accept: application/json",
    "Authorization: Client guest:guest"
);
$handle = curl_init(); 
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

    $data = curl_exec($handle);
curl_close($handle);
$items = json_decode($data, true);

/* Couldn't access because api limitations so added this*/
$population = array(
    "Mexico" => "57420677",
    "Sweden" => "5190000"
);


class Country {
    public string $name;
    public float $gdp;
    public int $gdp_in_dollars;
    public int $employed_population;
    public int $gdppep; //my own unique gdp per EMPLOYED capita 

    public function __construct(array $country) {
        if ($this->isCurrent($country["DateTime"])) {
            $this->setName($country["Country"]);
            $this->setGdp($country["Value"]);
        }
    }

    private function setName(string $value) {
        $this->name = $value;
    }

    private function setGdp(float $value) {
        $this->gdp = $value;
    }

    private function setGdppep() {
        $this->gdp_in_dollars = (int) $this->gdp * 1000000000;
        $this->gdppep = (int)   round($this->gdp_in_dollars / $this->employed_population);
    }

    public function setEmployedPopulation(string $value) {
        $this->employed_population = (int) $value;
        $this->setGdppep();
    }

    public static function isCurrent(string $datetime): bool {
        if (substr($datetime, 0, 4) == "2021") {
            return true;
        }

        return false;
    }

    public static function exists(string $name, array $list_of_countries) {
        if (in_array($name, $list_of_countries)) {
            return true;
        }

        return false;
    }
}

$countries = [];
foreach ($items as $item) {
    if (! Country::exists($item["Country"], $countries)) {
        if (Country::isCurrent($item["DateTime"])) {
            $country = new Country($item);

            /** AGAIN because I couldn't reach the data */
            $country->setEmployedPopulation($population[$country->name]);
            $countries[] = array(
                "name" => $country->name,
                "gdp" => $country->gdp,
                "gdppep" => $country->gdppep,
                "gdppep_readable" => number_format($country->gdppep) //I CAN NEVER READ TRADINGECONOMICS numbers so added this.
            );
        }
    }
}

//$country2 = new Country($items[0]);

//var_dump($countries);
?>

<h1>GDP per employed capita</h1>

<ul>
<?php foreach($countries as $item): ?>
    <li><?=$item["name"]?> (2021): <?=($item["gdppep_readable"])?> per person</li>
<?php endforeach; ?>
</ul>