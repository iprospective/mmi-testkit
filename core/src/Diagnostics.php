<?php
namespace Mmi\TestKit;

class Diagnostics
{
	/** @var array */
	private $hints = array();

	public function __construct($file = null)
	{
		if ($file === null) {
			$file = dirname(__DIR__).'/known-issues.yml';
		}
		if (!is_file($file)) {
			return;
		}
		$content = file_get_contents($file);
		$data = null;
		if (function_exists('yaml_parse')) {
			$data = yaml_parse($content);
		} elseif (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
			$data = \Symfony\Component\Yaml\Yaml::parse($content);
		}
		if (is_array($data)) {
			$this->hints = $data;
		}
	}

	/**
	 * @param string $suite
	 * @param string $label
	 * @return string
	 */
	public function hint($suite, $label)
	{
		if (isset($this->hints[$suite][$label]['hint'])) {
			return (string) $this->hints[$suite][$label]['hint'];
		}
		if (isset($this->hints[$suite]['hint'])) {
			return (string) $this->hints[$suite]['hint'];
		}
		return '';
	}
}
