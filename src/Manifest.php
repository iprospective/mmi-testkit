<?php
namespace Mmi\TestKit;

class Manifest
{
	/** @var array */
	public $data;

	/**
	 * @param string $file
	 * @return Manifest
	 */
	public static function load($file)
	{
		if (!is_file($file)) {
			throw new \RuntimeException('Manifeste introuvable : '.$file);
		}
		$content = file_get_contents($file);
		if ($content === false) {
			throw new \RuntimeException('Lecture impossible : '.$file);
		}

		$data = null;
		if (function_exists('yaml_parse')) {
			$data = yaml_parse($content);
		} elseif (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
			$data = \Symfony\Component\Yaml\Yaml::parse($content);
		}
		if (!is_array($data)) {
			throw new \RuntimeException('Manifeste YAML invalide (extension yaml ou symfony/yaml requis) : '.$file);
		}

		$manifest = new self();
		$manifest->data = $data;
		if (empty($data['suites']) || !is_array($data['suites'])) {
			throw new \RuntimeException('Manifeste sans suites : '.$file);
		}
		return $manifest;
	}

	/**
	 * @return array
	 */
	public function suites()
	{
		return $this->data['suites'];
	}

	/**
	 * @return string
	 */
	public function site()
	{
		return isset($this->data['site']) ? (string) $this->data['site'] : 'unknown';
	}
}
