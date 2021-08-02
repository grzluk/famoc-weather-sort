<?php
$apiKeys = ["1"];

header('Content-type: application/json');
$output = (object) [
    "date" => date(DATE_W3C,time()),
    "cities" => []];

class openweathermap
{
    private $appid;
    public $scoreModifiers = [
        "TEMP" => 0.6,
        "WIND" => 0.3,
        "HUMIDITY" => 0.1];

    function __construct($appid)
    {
        $this->appid = $appid;
    }

    function getWeather($city)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://api.openweathermap.org/data/2.5/weather?q=' . $city . '&appid=' . $this->appid,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }
}
if(!isset($_GET['api_key']) OR array_search($_GET['api_key'],$apiKeys)) die(json_encode((object) ["error" => "Niepoprawny kod api"]));

if(isset($_GET['city_list'])) {
    $cityList = explode(";", $_GET['city_list']);
}elseif (isset($argv[1])) {
    $cityList = explode(";", $argv[1]);
}else{
    die(json_encode((object) ["error" => "Liczba miast do porownania powinna być w przedziale od 2 do 4"]));
}
if(sizeof($cityList) < 2 OR sizeof($cityList) > 4) die(json_encode((object) ["error" => "Liczba miast do porownania powinna być w przedziale od 2 do 4"]));

$weather = new openweathermap("63b6210d02fac39eee8c5bb563522dfe");

$citiesData = [];

foreach ($cityList as $key => $cityName) {
    $weatherData = $weather->getWeather($cityName);
    $citiesData['temp'][$cityName] = $weatherData->main->temp;
    $citiesData['wind'][$cityName] = $weatherData->wind->speed;
    $citiesData['humidity'][$cityName] = $weatherData->main->humidity;
}

/* Sortowanie */
arsort($citiesData['temp']);
arsort($citiesData['wind']);
arsort($citiesData['humidity']);

$scores = [];

$position = 1;
foreach ($citiesData['temp'] as $cityName => $paramValue) {
    $scores['temp'][$cityName] = (100 - 10 * ($position - 1) * 0.6);
    $position++;
}

$position = 1;
foreach ($citiesData['wind'] as $cityName => $paramValue) {
    $scores['wind'][$cityName] = (100 - 10 * ($position - 1) * 0.3);
    $position++;
}

$position = 1;
foreach ($citiesData['humidity'] as $cityName => $paramValue) {
    $scores['humidity'][$cityName] = (100 - 10 * ($position - 1) * 0.1);
    $position++;
}

$scoresSum = [];

foreach ($cityList as $key => $cityName) {
    $scoresSum[$cityName] = $citiesData['temp'][$cityName] + $citiesData['wind'][$cityName] + $citiesData['humidity'][$cityName];
}

/** Sortowanie wynikow */

arsort($scoresSum);

foreach ($scoresSum as $cityName => $score) {
    $output->cities[] = (object) [
        "name" => $cityName,
        "score" => $score,
        "temp" => $citiesData['temp'][$cityName],
        "wind" => $citiesData['wind'][$cityName],
        "humidity" => $citiesData['humidity'][$cityName]
    ];
}

echo json_encode($output);
