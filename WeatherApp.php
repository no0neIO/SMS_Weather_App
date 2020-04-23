<?php

declare(strict_types=1);

class WeatherApp
{
    private $cityId;
    private $weatherAppId = 'yourkeyhere';
    private $url;
    public $temperature;

    private $phoneNum = '+3069xxxxxxxx'; // your phonenumber here

    public function __construct(int $cid)
    {
        $this->cityId = $cid;
        $this->url = "http://api.openweathermap.org/data/2.5/weather?id=" . $this->cityId . "&appid=" . $this->weatherAppId;
    }

    // ********** GET WEATHER **********

    public function getWeather()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($result);

        // if response status code is different than "success", return false
        if ($result->cod !== 200)
            return false;

        $this->temperature = round($result->main->temp - 273.15, 1);
        $str = ($this->temperature > 20) ? 'Temperature more than 20C. ' . $this->temperature . ' Celsius' : 'Temperature less than 20C. ' . $this->temperature . ' Celsius';

        $this->sendSms($str);
    }

    private function sendSms(string $str)
    {
        // ********** Get Access Token **********
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://auth.routee.net/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => array(
                "authorization: Basic youraccesstoken", // add your access token here
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }
        $accessToken = json_decode($response);
        $accessToken = $accessToken->access_token;

        // ********** SEND SMS **********
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://connect.routee.net/sms",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ \"body\": \"$str\",\"to\" : \"$this->phoneNum\",\"from\": \"Jim\"}",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $accessToken,
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }

        echo "Check your smartphone!";
    }
}

// make a new instance of WeatherApp and pass Thessaloniki's city ID. Then call getWeather method.
$weatherApp = new WeatherApp(734077);
$weatherApp->getWeather();
