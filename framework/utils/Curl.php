<?php
namespace Sky\utils;
/**
 * 
 * Curl wrapper
 * 
 * Usage:
 *   'curl' => array(
 *           'class' => 'Sky\utils\curl',
 *          'options' => array(/.. additional curl options ../)
 *       );
 *       
 * $output =  \Sky\Sky::$app->curl->get($url);
 * $output =  \Sky\Sky::$app->curl->post($url);
 * $output =  \Sky\Sky::$app->curl->setOption($name, $value)->get($url, $params);
 * v - 1.2
 * 
 * @author Jiangyumeng
 */
class Curl extends \Sky\base\Component {

	private $_fp;
	
    private $_ch;
    // config from config.php
    public $options;
    // default config
    private $_config = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20110619 Firefox/5.0'
    );

    private function _exec($url) {

        $this->setOption(CURLOPT_URL, $url);
        $c = curl_exec($this->_ch);
        if (!curl_errno($this->_ch))
            return $c;
        else
            throw new \Exception(curl_error($this->_ch)."@$url");
    }
    
    public function getRequestInfo($info){
    	return curl_getinfo($this->_ch,$info);
    }

    public function get($url, $method='path',$params = array(),$header=false) {
        $this->setOption(CURLOPT_HTTPGET, true);
        $this->setOption(CURLOPT_HEADER, $header);
        if($method=='path')
        	return $this->_exec($url);
        return $this->_exec($this->buildUrl($url, $params));
    }

    public function post($url, $data = array(),$header=false) {
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_HEADER, $header);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        return $this->_exec($url);
    }

    public function delete($url,$header = false) {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->setOption(CURLOPT_HEADER, $header);
        return $this->_exec($url);
    }

    public function put($url,$data,$header = false) {
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->setOption(CURLOPT_HEADER, $header);
        $this->setOption(CURLOPT_POSTFIELDS, $data);
        return $this->_exec($url);
    }

    public function putFile($url, $data, $params = array(),$header=false) {

        // write to memory/temp
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);
		
        $this->setOption(CURLOPT_HEADER, $header);
        $this->setOption(CURLOPT_PUT, true);
        $this->setOption(CURLOPT_INFILE, $f);
        $this->setOption(CURLOPT_INFILESIZE, strlen($data));

        return $this->_exec($this->buildUrl($url, $params));
    }

    public function buildUrl($url, $data = array()) {
        $parsed = parse_url($url);
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();
        $params = isset($parsed['query']) ? array_merge($parsed['query'], $data) : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path']))
            $parsed['path'] = '/';
        
        $port = '';
        if(isset($parsed['port'])){
            $port = ':' . $parsed['port'];
        }
        
        return $parsed['scheme'] . '://' . $parsed['host'] .$port. $parsed['path'] . $parsed['query'];
    }

    public function setOptions($options = array()) {
        curl_setopt_array($this->_ch, $options);
        return $this;
    }

    public function setOption($option, $value) {
        curl_setopt($this->_ch, $option, $value);
        return $this;
    }
    
    function downloadFile($remote, $local) {
    	$this->_fp = fopen($local, "wb");
    	if ($this->_fp === FALSE) {
    		return false;
    	}
    	$this->setOption(CURLOPT_FILE, $this->_fp);
    	$this->setOption(CURLOPT_TIMEOUT, 0);
		$this->setOption(CURLOPT_HEADER, false);

    	return $this->_exec($remote);
    }

    // initialize curl
    public function init() {
		$this->_ch = curl_init();
		$options = is_array($this->options) ? ($this->options + $this->_config) : $this->_config;
		$this->setOptions($options);
    }

    public function close(){
    	if($this->_ch)
    		curl_close($this->_ch);
    	if($this->_fp)
    		fclose($this->_fp);
    }
    
    public function __destruct(){
    	return $this->close();
    }
}
