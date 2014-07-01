<?php
 
/**
 * AyeEmtract a class that crawls pages and
 * then searches for email addresses using curl in parallel.
 *
 * PHP version >= 5.x
 *
 * @category PHP
 * @author   PANTUTS
 * @license  http://www.gnu.org/licenses/gpl.txt
 * @link     http://www.pantuts.com
 */
class AyeEmtract
{
    // variable to store data
    private $_results = array();
    // array of urls
    private $_urls = array();
    // addition curl options
    private $_options = array();
    // errors
    public $errors = array();
 
    /**
     * Creates object and store array of urls and additional options
     *
     * @param array $urls    array of urls
     * @param array $options addition curl options
     */
    public function __construct($urls, $options = array())
    {
        $this->_urls = $urls;
        $this->_options = $options;
    }
 
    /**
     * Sets curl_multi, curl options, and executes curl
     * per url.
     */
    public function start()
    {
        // initialize curl multi handle
        $curlMaster = curl_multi_init();
        // curl singles array
        $curlh = array();
 
        // loop all urls
        foreach ($this->_urls as $i => $url) {
            // curl_init each url
            $curlh[$i] = curl_init();
 
            curl_setopt_array(
                $curlh[$i], array(
                    CURLOPT_URL => htmlentities(trim($url)),
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => self::_setUserAgent(),
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_MAXREDIRS => 7,
                    CURLOPT_CONNECTTIMEOUT => 20,
                    CURLOPT_FRESH_CONNECT => true )
            );
 
            // check additional options
            if (!empty($this->_options)) {
                curl_setopt_array($curlh[$i], $this->_options);
            }
            // now add multi handle
            curl_multi_add_handle($curlMaster, $curlh[$i]);
        }
 
        // execute multi handles
        $running = null;
        do {
            // save errors if encountered
            if (curl_multi_exec($curlMaster, $running) === false) {
                $this->errors[$i] =  'ERROR: url = ' . curl_error($curlh[$i]) .
                    ', code = ' . curl_errno($curlh[$i]);
            }
        } while ($running > 0);
 
        // get content on each url from curlh
        foreach ($curlh as $j => $ch) {
            // save results and remove handle
            $this->_results[$j] = curl_multi_getcontent($ch);
             
            curl_multi_remove_handle($curlMaster, $ch);
        }
 
        // close handle
        curl_multi_close($curlMaster);
    }
 
    /**
     * Returns array of emails
     *
     * @return array_unique(array)
     */
    public function getEmails()
    {
        $emails = array();
        // regex to find valid emails
        $re = "/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i";
 
        foreach ($this->_results as $i => $res) {
            // decode htmlentities like %3C
            $res = html_entity_decode($res);
            preg_match_all($re, $res, $matches);
             
            foreach ($matches[0] as $match) {
                // save to array found emails
                $emails[$i] = trim($match);
            }
        }
        // remove duplicates
        return array_unique($emails);
    }
 
    /**
     * Returns random user-agent
     *
     * @return $ua
     */
    private static function _setUserAgent()
    {
        $userAgents = array(
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:24.0) Gecko/20100101 Firefox/24.0',
            'Mozilla/5.0 (Windows NT 6.2; rv:22.0) Gecko/20130405 Firefox/22.0',
            'Opera/9.80 (J2ME/MIDP; Opera Mini/5.0 (Windows; U; Windows NT 5.1; en) AppleWebKit/886; U; en) Presto/2.4.15',
            'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
            'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))',
            'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1623.0 Safari/537.36',
            'Mozilla/5.0 (X11; CrOS i686 4319.74.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2'
        );
        $ua = $userAgents[array_rand($userAgents)];
        return $ua;
    }
}
?>
