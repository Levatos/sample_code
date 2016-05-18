<?php
class Request
{
    // Parse cookies from cURL result
    public function get_cookies($result) {
        $cookie_array = explode("Set-Cookie:", $result);
        $cookies = "";
        $count = 1;

        while ($count < count($cookie_array)) {
            $cookies .= substr($cookie_array[$count] . ";", 0, strpos($cookie_array[$count] . ";", ";") + 1);
            $count++;
        }

        return $cookies;
    }

    // Parse location from cURL result
    public function get_location($result) {
        $location = explode("Location:", $result);
        $location = trim(substr($location[1], 0, strpos($location[1], "\n") + 1));

        return $location;
    }

    // cURL connect function
    public function curl_connect($params) {
        // Check required params
        if (!@$params['url'] || !@$params['timeout']) {
            return "RequestIO error: URL and TIMEOUT params are required";
        }

        // Start connection
        $curl_connect = curl_init();

        // Destination URL to connect
        curl_setopt($curl_connect, CURLOPT_URL, trim($params['url']));
        // Tell destination site that we use Mozilla client
        curl_setopt($curl_connect, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0');
        // Timeout settings
        curl_setopt($curl_connect, CURLOPT_CONNECTTIMEOUT, $params['timeout']);
        curl_setopt($curl_connect, CURLOPT_TIMEOUT, $params['timeout']);
        // Output content as the result of "curl_exec"
        curl_setopt($curl_connect, CURLOPT_RETURNTRANSFER, 1);

        // If "follow location" flag is turned - enable auto location following with cookies sending
        if(@$params['follow_location']) {
            curl_setopt($curl_connect, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl_connect, CURLOPT_COOKIEFILE, "");
        }
        // Turn off headers output if we don't need them
        if(@$params['show_headers']) curl_setopt($curl_connect, CURLOPT_HEADER, true);
        // Set referrer if it's necessary
        if(@$params['referrer']) curl_setopt($curl_connect, CURLOPT_REFERER, trim($params['referrer']));
        // Check if necessary and set post parameters to cURL request
        if (@$params['post_data']) {
            curl_setopt($curl_connect, CURLOPT_POST, true);
            curl_setopt($curl_connect, CURLOPT_POSTFIELDS, $params['post_data']);
        }

        // Turn off SSL verification
        curl_setopt($curl_connect, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_connect, CURLOPT_SSL_VERIFYHOST, false);

        // Create array for custom headers
        $custom_headers = array();
        // Add custom headers from cURL request
        if(!@empty($params['custom_headers'])) {
            foreach($params['custom_headers'] as $custom_header) {
                $custom_headers[] = $custom_header;
            }
        }

        // Check if necessary and add cookies to headers array
        if (@$params['cookies']) {
            $custom_headers[] = "Cookie: " . $params['cookies'];
        }
        // Setting headers
        curl_setopt($curl_connect, CURLOPT_HTTPHEADER, $custom_headers);

        // Executing cURL request and preparing result for returning
        $result = curl_exec($curl_connect);

        // Check request for errors and rewrite result with cURL error if it's necessary
        if (curl_errno($curl_connect)) {
            $result = "RequestIO error: " . curl_error($curl_connect);
        }

        // Close connection
        curl_close($curl_connect);

        // Return result
        return $result;
    }
}