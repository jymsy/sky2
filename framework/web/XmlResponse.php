<?php
namespace Sky\web;

use Sky\base\IResponseFormatter;
use Sky\base\Component;
/**
 * 将响应的内容格式化为xml格式。
 * @author Jiangyumeng
 *
 */
class XmlResponse extends Component implements IResponseFormatter{
	/**
	 * @var string 响应头的Content-Type
	 */
	public $contentType = 'application/xml';
	/**
	 * @var string XML版本
	 */
	public $version = '1.0';
	/**
	 * @var string XML编码. 如果没设置的话使用[[Response::charset]].
	 */
	public $encoding;
	/**
	 * @var string 根元素名
	 */
	public $rootTag = 'response';
	/**
	 * @var string 当key是数字的时候用来显示的元素名。
	 */
	public $itemTag = 'item';
	
	/**
	 * 格式化
	 * @param Response $response 要格式化的响应实例。
	 */
	public function format($response)
	{
		$response->setHeader('Content-Type', $this->contentType);
		$dom = new \DOMDocument($this->version, $this->encoding === null ? $response->charset : $this->encoding);
		$root = new \DOMElement($this->rootTag);
		$dom->appendChild($root);
		$this->buildXml($root, $response->data);
		$response->content = $dom->saveXML();
	}
	
	/**
	 * @param DOMElement $element
	 * @param mixed $data
	 */
	protected function buildXml($element, $data)
	{
		if (is_object($data)) {
			$child = new \DOMElement(get_class($data));
			$element->appendChild($child);
			if ($data instanceof \Arrayable) {
				$this->buildXml($child, $data->toArray());
			} else {
				$array = array();
				foreach ($data as $name => $value) {
					$array[$name] = $value;
				}
				$this->buildXml($child, $array);
			}
		} elseif (is_array($data)) {
			foreach ($data as $name => $value) {
				if (is_int($name) && is_object($value)) {
					$this->buildXml($element, $value);
				} elseif (is_array($value) || is_object($value)) {
					$child = new \DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$this->buildXml($child, $value);
				} else {
					$child = new \DOMElement(is_int($name) ? $this->itemTag : $name);
					$element->appendChild($child);
					$child->appendChild(new \DOMText((string)$value));
				}
			}
		} else {
			$element->appendChild(new \DOMText((string)$data));
		}
	}
}