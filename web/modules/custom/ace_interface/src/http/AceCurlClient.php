<?php

namespace Drupal\ace_interface\http;

use Drupal\ace_interface\wrapper\AceCurlWrapper;
use Drupal\Core\Config\ConfigFactoryInterface;

class AceCurlClient {

    /**
     * @var Curl client
     */
    protected $curl;

    /**
     * @var base uri
     */
    protected $base_uri;

    /**
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    protected $username;

    protected $password;

    public function __construct(ConfigFactoryInterface $config_factory) {

        $this->configFactory = $config_factory;

        $this->base_uri = $this->configFactory->get('ace_dynamics.settings')->get('dynamics_resource');

        $this->curl = curl_init();

        curl_setopt_array($this->curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            )
        );

        $this->username = 'dbishop';
        $this->password = 'TPnc6zfJBHooTtXT';
    }

    public function get($uri, $headers, $debug = false) {

        $responseHeaders['header-length'] = 0;

        curl_setopt($this->curl, CURLOPT_URL, $this->base_uri . $uri);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->curl, CURLOPT_USERPWD, "dbishop:TPnc6zfJBHooTtXT");

        if ($debug) {
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
        }

        //Only returns body in response. Headers returned separately in $headers array
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        //This is called by curl for each header received
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$responseHeaders) {
                $headerLength = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $headerLength;

                $responseHeaders['header-length'] += $headerLength;
                $name = strtolower(trim($header[0]));
                if (!array_key_exists($name, $responseHeaders))
                    $responseHeaders[$name] = [trim($header[1])];
                else
                    $responseHeaders[$name][] = trim($header[1]);

                return $headerLength;
            }
        );

        try {

            $response = curl_exec($this->curl);

            $err = curl_error($this->curl);

            curl_close($this->curl);

            $wrappedCurl = new AceCurlWrapper($response, $responseHeaders);

            if ($err) {
                return $err;
            } else {
                return $wrappedCurl;
            }

        } catch (\Throwable $e) {
            return $e;
        }

    }

    public function post($uri, $headers, $body, $debug = false, $secure = true, $port = '80') {

        $responseHeaders['header-length'] = 0;

        curl_setopt($this->curl, CURLOPT_URL, $this->base_uri . $uri);
//        curl_setopt($this->curl, CURLOPT_PORT, $port);

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->curl, CURLOPT_USERPWD, "dbishop:TPnc6zfJBHooTtXT");

        if (!$secure) {
            // connect to the link via SSL without checking certificate
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); //false will prevent curl from verifying the SSL certificate
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false); //false will prevent curl from verifying the SSL certificate
        }

        //Only returns body in response. Headers returned separately in $headers array
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        //This is called by curl for each header received
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$responseHeaders) {
                $headerLength = strlen($header);
                $responseHeaders['header-length'] += $headerLength;
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $headerLength;

                $name = strtolower(trim($header[0]));
                if (!array_key_exists($name, $responseHeaders))
                    $responseHeaders[$name] = [trim($header[1])];
                else
                    $responseHeaders[$name][] = trim($header[1]);

                return $headerLength;
            }
        );

        try {

            $response = curl_exec($this->curl);

            $err = curl_error($this->curl);

            $code = curl_errno($this->curl);

            curl_close($this->curl);
            if ($err) {
                return $err;
            } else {
                return $response;
            }

        } catch (\Throwable $e) {
            \Drupal::messenger()->addError('AceCurlClient: ' . $e->getMessage());
            return $e;
        }
    }

    public function patch($uri, $headers, $body, $debug = false, $secure = true, $port = '80') {

        $responseHeaders['header-length'] = 0;

        curl_setopt($this->curl, CURLOPT_URL, $this->base_uri . $uri);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->curl, CURLOPT_USERPWD, "dbishop:TPnc6zfJBHooTtXT");

        if (!$secure) {
            // connect to the link via SSL without checking certificate
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); //false will prevent curl from verifying the SSL certificate
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false); //false will prevent curl from verifying the SSL certificate
        }

        //Only returns body in response. Headers returned separately in $headers array
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

        //This is called by curl for each header received
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $headerLength = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $headerLength;

                $name = strtolower(trim($header[0]));
                if (!array_key_exists($name, $headers))
                    $headers[$name] = [trim($header[1])];
                else
                    $headers[$name][] = trim($header[1]);

                return $headerLength;
            }
        );

        try {

            $response = curl_exec($this->curl);

            $err = curl_error($this->curl);

            $code = curl_errno($this->curl);

            curl_close($this->curl);

            if ($err) {
                return $err;
            } else {
                return $response;
            }

        } catch (\Throwable $e) {
            \Drupal::messenger()->addError('AceCurlClient: ' . $e->getMessage());
            return $e;
        }
    }

    public function buildBody($params) {
        $body = '';
        $counter = 1;
        $count = count($params);
        foreach ($params as $key => $value) {
            $body .= $key . '=' . $value;
            if ($counter < $count) {
                $body .= '&';
            }
            $counter++;
        }
        return $body;
    }

}
