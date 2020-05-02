<?php

namespace shodan;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class shodanSearchApi
{
    /**
     * @var url $base_url
     */
    private static $base_url = 'https://api.shodan.io/shodan/host/';

    /**
     * @var string $key
     */
    public static $key;

    /**
     * @var string $queries
     */
    private static $queries;

    /**
     * @var string $output
     */
    public static $output = NULL;

    /**
     * @var string $fileResult
     */
    public static $fileResult;

    /**
     * @param string $name
     * @param string $string
     * @param string $mode
     */
    public static function save($name, $string, $mode="a")
    {
        $open = fopen($name, $mode);
                fwrite($open, $string.PHP_EOL);
                fclose($open);
    }

    /**
     * @param string $code
     * @return bool
     */
    public static function isJson($code){
		json_decode($code);
		return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
    * @param int $page
    * @return string
    */
    private static function single()
    {
        echo "[+] Query: ".self::$queries. PHP_EOL;
        $client = new Client(['base_uri' => self::$base_url]);
        try {
            $response = $client->request(
                'GET',
                'search',
                ['query' => [ 
                    'query' => self::$queries,
                    'key'   => self::$key,
                  ],
                  'headers' => [
                    'Connection'    => 'keep-alive',
                    'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36',
                    'Cache-Control' => 'max-age=0',
                  ],  
                ])->getBody()->getContents();
            if (self::isJson($response) === true) {
                $resp = json_decode($response, TRUE);
                $total = count($resp["matches"]);
                if ($total === 0) {
                    echo "    [!] No results found". PHP_EOL;
                } else {
                    if ( self::$output === NULL)
                        self::save(self::$fileResult, "[+] Query: ".self::$queries);
                    echo "    Total: $total Result". PHP_EOL;
                    for ($result=0; $result < $total; $result++) { 
                        $object = $resp["matches"];
                        if (array_key_exists("http", $object[$result])) {
                            $host = "http://".$object[$result]["http"]["host"].$object[$result]["http"]["location"];
                            self::save(self::$fileResult, $host);
                        } else {
                            $host = $object[$result]["ip_str"].":".$object[$result]["port"];
                            self::save(self::$fileResult, $host);
                        }
                    }
                    echo PHP_EOL . "Result saved to ".self::$fileResult. PHP_EOL;
                }
            } else {
                echo "    Failed get result for query: ". self::$queries . PHP_EOL;
            }
        } catch (ConnectException $e) {
            echo PHP_EOL . "Uh! " . $e->getMessage() . PHP_EOL;
        } catch (ClientException $e) {
            $content = $e->getResponse()->getBody()->getContents();
            $code = $e->getCode();
            if ($code == 404) {
                echo PHP_EOL . "Uh! Page Not Found for url ".self::$base_url . PHP_EOL;
            }
            elseif ($code == 401) {
                if (preg_match("/Please upgrade your API plan/", $content)) {
                    echo PHP_EOL . json_decode($content)->error . PHP_EOL;
                } 
                if (preg_match("/not verify that you are authorized/", $content)) {
                    echo PHP_EOL . "Error: Invalid API KEY!!!" . PHP_EOL;
                }
                else {
                    echo PHP_EOL. $e->getMessage() . PHP_EOL;
                    exit();
                }
            } 
            else {
                echo PHP_EOL. $e->getMessage() . PHP_EOL;
            }
        } catch (RequestException $e) {
            echo PHP_EOL . "Uh! " . $e->getMessage() . PHP_EOL;
            exit();
        }        
    }

    /**
    * @param int $page
    * @return string 
    */
    private static function mass($pages)
    {
        echo "[+] Query: ".self::$queries."\n";
        $client = new Client(['base_uri' => self::$base_url]);
        $saved = '';
        for ($page=1; $page <= $pages ; $page++) { 
            try {
                $response = $client->request(
                    'GET',
                    'search',
                    ['query' => [ 
                        'query' => self::$queries,
                        'key'   => self::$key,
                        'page'  => $page,
                     ],
                     'headers' => [
                        'Connection'    => 'keep-alive',
                        'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36',
                        'Cache-Control' => 'max-age=0',
                      ], 
                    ])->getBody()->getContents();
                if (self::isJson($response) === true) {
                    $resp = json_decode($response, TRUE);
                    $total = count($resp["matches"]);
                    if ($total === 0) {
                        echo "    [!] No results found from page ". $page. PHP_EOL;
                    } else {
                        if ( self::$output === NULL)
                            self::save(self::$fileResult, "[+] Query: ".self::$queries." Page ".$page);
                        echo "    Total: $total result from page ". $page . PHP_EOL;
                        for ($result=0; $result < $total; $result++) { 
                            $object = $resp["matches"];
                            if (array_key_exists("http", $object[$result])) {
                                $host = "http://".$object[$result]["http"]["host"].$object[$result]["http"]["location"];
                                self::save(self::$fileResult, $host);
                            } else {
                                $host = $object[$result]["ip_str"].":".$object[$result]["port"];
                                self::save(self::$fileResult, $host);
                            }
                        }
                        $saved = PHP_EOL . "Result saved to ".self::$fileResult. PHP_EOL;
                    }
                } else {
                    echo "    Failed get result for query: ". self::$queries . PHP_EOL;
                }
            } catch (ConnectException $e) {
                echo "    [!] Uh! " . $e->getMessage() . PHP_EOL;
            } catch (ClientException $e) {
                $content = $e->getResponse()->getBody()->getContents();
                $code = $e->getCode();
                if ($code == 404) {
                    echo PHP_EOL . "Uh! Page Not Found for url ".self::$base_url . PHP_EOL;
                    exit();
                }
                elseif ($code == 401) {
                    if (preg_match("/Please upgrade your API plan/", $content)) {
                        echo PHP_EOL . json_decode($content)->error . PHP_EOL;
                        exit();
                    } 
                    if (preg_match("/not verify that you are authorized/", $content)) {
                        echo PHP_EOL . "Error: Invalid API KEY!!!" . PHP_EOL;
                    }
                    else {
                        echo PHP_EOL. $e->getMessage() . PHP_EOL;
                        exit();
                    }
                } 
                else {
                    echo PHP_EOL. $e->getMessage() . PHP_EOL;
                    exit();
                }
            } catch (RequestException $e) {
                echo PHP_EOL . "Uh! " . $e->getMessage() . PHP_EOL;
                exit(); 
            }
        }
        echo $saved;
    }


    /**
    * @param string $query
    * @param int $page
    * @return string 
    */
    public static function execute($query, $page=1)
    {
        if (!is_dir("Result")) {
            mkdir("Result");
        }
        self::$queries = $query;
        self::$fileResult = (self::$output !== NULL ) ? "./Result/Result_".self::$output.".txt" : "./Result/Result_".date("d-m-y_H-i-s").".txt" ;
        if ($page > 1) {
            self::mass($page);
        } else {
            self::single();
        }
    }
}
