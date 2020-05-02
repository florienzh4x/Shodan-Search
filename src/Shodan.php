<?php

namespace shodan;

use shodan\shodanSearchApi;

class Shodan
{
    /**
     * @var string $query
     */
    private static $query;
    
    private static $country = '';

    private static $port = '';

    private static $os = '';

    private static $product = '';
    /**
     * Information options usage
     */
    private static function usage()
    {
        echo "---- SHODAN SEARCH REST API ----".PHP_EOL;
        echo "Usage: ".$_SERVER["PHP_SELF"]." [ -q | --query ] [Options...]".PHP_EOL;
        echo "       ".$_SERVER["PHP_SELF"]." [ -f | --query-list ] [Options...]".PHP_EOL . PHP_EOL;
        echo "Example: ".$_SERVER["PHP_SELF"]." -q \"Virtual Web Server\" --page 10".PHP_EOL;
        echo "         ".$_SERVER["PHP_SELF"]." -f query-list.txt -l 10".PHP_EOL . PHP_EOL;
        echo "Options:".PHP_EOL;
        echo "-q,  --query\t\tQuery to use searching on shodan rest api".PHP_EOL;
        echo "-f,  --query-list\tFile of query list for bulk search".PHP_EOL;
        echo "-c,  --country\t\tset this option using country code for spesific result".PHP_EOL;
        echo "-p,  --port\t\tonly get result for a specific port".PHP_EOL;
        echo "-o,  --os\t\tonly get result for a specific operating system ".PHP_EOL;
        echo "-s,  --product\t\tget specific product (like: Apache httpd|nginx|Samba|etc.)".PHP_EOL;
        echo "-l,  --page\t\tset limit page result. Default just get for 1 page".PHP_EOL;
        echo "-r,  --output\t\tset name for result file (without extension)".PHP_EOL;
        echo "-h,  --help\t\tDisplay usage information". PHP_EOL . PHP_EOL;
        echo "bug report or request some feature to <hasanal.bulkiah501@gmail.com>".PHP_EOL;
    }

    /**
     * @param array $param
     * @return mixed
     */
    private static function params($param)
    {
        if ( isset($param["c"]) || isset($param["country"]) ) 
            self::$country = (isset($param["c"])) ? ' country:'.$param["c"] : ' country:'.$param["country"];
        if ( isset($param["p"]) || isset($param["port"]) ) 
            self::$port = (isset($param["p"])) ? ' port:'.$param["p"] : ' port:'.$param["port"];
        if ( isset($param["o"]) || isset($param["os"]) ) 
            self::$os = (isset($param["o"])) ? ' os:'.$param["o"] : ' os:'.$param["os"];
        if ( isset($param["s"]) || isset($param["product"]) ) 
            self::$product = (isset($param["s"])) ? ' product:'.$param["s"] : ' product:'.$param["product"];
        if ( isset($param["r"]) || isset($param["output"]) )
            shodanSearchApi::$output = (isset($param["r"])) ? $param["r"] : $param["output"];
    }

    /**
     * @param array $param
     * validate parameters
     */
    private function validateParams($param)
    {
        self::params($param);
        if ( isset($param["q"]) || isset($param["query"]) ) {
            self::$query = (isset($param["q"])) ? $param["q"] : $param["query"];
            if ( isset($param["l"]) || isset($param["page"]) ) {
                $page = isset($param["l"]) ? $param["l"] : $param["page"];
                self::$query .= self::$country;
                self::$query .= self::$port;
                self::$query .= self::$os;
                self::$query .= self::$product;
                return shodanSearchApi::execute(self::$query, $page);
            } else {
                self::$query .= self::$country;
                self::$query .= self::$port;
                self::$query .= self::$os;
                self::$query .= self::$product;
                return shodanSearchApi::execute(self::$query);
            }
            
        } elseif ( isset($param["f"]) || isset($param["query-list"]) ) {
            $queryFile = (isset($param["f"])) ? $param["f"] : $param["query-list"];
            if (!file_exists($queryFile)){
                echo "File $queryFile Not Exists!!!".PHP_EOL."Exit".PHP_EOL;
                exit;
            }

            $queryList = explode("\r\n", file_get_contents(ROOT."/".$queryFile));
            foreach ($queryList as $query) {
                self::$query = $query;
                if ( isset($param["l"]) || isset($param["page"]) ) {
                    $page = isset($param["l"]) ? $param["l"] : $param["page"];
                    self::$query .= self::$country;
                    self::$query .= self::$port;
                    self::$query .= self::$os;
                    self::$query .= self::$product;
                    return shodanSearchApi::execute(self::$query, $page);
                } else {
                    self::$query .= self::$country;
                    self::$query .= self::$port;
                    self::$query .= self::$os;
                    self::$query .= self::$product;
                    return shodanSearchApi::execute(self::$query);
                }
            }
        } else self::usage();
        
    }

    /**
     * @param string $key
     */
    public function run($key)
    {   
        if ($this->initialize($key) !== NULL) {
            if ($this->initialize($key) === FALSE) {
                echo "Error: Invalid API KEY!".PHP_EOL;
                exit;
            }
        } else {
            echo "Error: API KEY CAN'T BLANK!".PHP_EOL;
            exit;
        }

        shodanSearchApi::$key = $key;
        $options = getopt('q:f:c:p:o:s:l:r:h', array(
                        'query:',
                        'query-list:',
                        'country:',
                        'port:',
                        'os:',
                        'product:',
                        'page:',
                        'output:',
                        'help',
                    ));
        return $this->validateParams($options);
    }

    /**
     * @param string $key
     * validate Api Key
     * @return bool
     */
    private function initialize($Key)
    {
        if (empty($Key)) {
            return NULL;
        } else {
            $filtering = preg_match('/[^a-zA-Z\d]/', $Key);
            $length = strlen($Key);
            if ($filtering) {
                return false;
            } elseif ($length < 32 OR $length > 32) {
                return false;
            } else {
                return true;
            }
            
        }
    }
}