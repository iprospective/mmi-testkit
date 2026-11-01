<?php
namespace Mmi\TestKit\View;

require_once __DIR__.'/helpers.php';

class View
{
	/** @var string */
	private $dir;

	/** @var array */
	private $config;

	public function __construct($dir, $config)
	{
		$this->dir = rtrim($dir, '/');
		$this->config = $config;
	}

	/**
	 * @param string $template
	 * @param array  $data
	 * @param string $layout
	 * @return string
	 */
	public function render($template, $data = array(), $layout = 'layout')
	{
		$data['config'] = $this->config;
		$content = $this->renderFile($template, $data);
		$data['content'] = $content;
		return $this->renderFile($layout, $data);
	}

	private function renderFile($template, $data)
	{
		$file = $this->dir.'/'.$template.'.php';
		if (!is_file($file)) {
			throw new \RuntimeException('Vue introuvable : '.$file);
		}
		extract($data, EXTR_SKIP);
		ob_start();
		require $file;
		return ob_get_clean();
	}
}
