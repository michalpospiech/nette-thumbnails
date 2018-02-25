<?php
/**
 * Generator.php
 *
 * @author Michal Pospiech <michal@pospiech.cz>
 */

namespace Thumbnails;


use Nette;

class Generator
{

	use Nette\SmartObject;

	/** @var string */
	protected $wwwDir;

	/** @var string */
	protected $src;
	
	/** @var string */
	protected $destination;
	
	/** @var int */
	protected $width;
	
	/** @var int */
	protected $height;

	/** @var string */
	protected $subDir;
	
	/** @var Nette\Http\IRequest */
	private $httpRequest;
	
	/** @var string */
	private $thumbPathMask;
	
	/** @var string */
	private $placeholder;
	
	/** @var array */
	private $options = array();
	
	public function __construct(Nette\Http\IRequest $httpRequest, $wwwDir, $thumbPathMask, $placeholder, $options = array())
	{
		$this->wwwDir = $wwwDir;
		$this->httpRequest = $httpRequest;
		$this->thumbPathMask = $thumbPathMask;
		$this->placeholder = $placeholder;
		$this->options = $options;
	}

	/**
	 * @param string $src
	 * @param int $width
	 * @param int $height
	 * @param string|null $subDir
	 * @return mixed|string
	 */
	public function generateThumbnail($src, $width, $height, $subDir = null)
	{
		$this->src = $this->wwwDir . '/' . $src;
		$this->width = $width;
		$this->height = $height;
		$this->subDir = $subDir;

		if (!is_file($this->src)) {
			return $this->createPlaceholderPath();
		}

		if ($this->subDir && !preg_match('~\/|\\$~', $this->subDir)) {
			$this->subDir .= DIRECTORY_SEPARATOR;
		}

		$thumbPath = $this->createThumbPath();
		$this->destination = $this->wwwDir . '/' . $thumbPath;

		if (!file_exists($this->destination) || (filemtime($this->destination) < filemtime($this->src))) {
			$this->createDir();
			$this->createThumb();
			clearstatcache();
		}

		return $this->httpRequest->getUrl()->getBasePath() . $thumbPath;
	}

	/**
	 * @throws \Nette\Utils\UnknownImageFileException
	 */
	private function createThumb()
	{
		$rawImage = Nette\Utils\Image::fromFile($this->src);
		$rawImage->resize($this->width, $this->height, Nette\Utils\Image::SHRINK_ONLY);

		if ($this->getOption('sharpen', true)) {
			$rawImage->sharpen();
		}

		if ($this->getOption('place', true)) {
			$image = Nette\Utils\Image::fromBlank($this->width, $this->height, $this->getOption('background', Nette\Utils\Image::rgb(255, 255, 255, 127)));
			$image->place($rawImage, $this->getOption('placeLeft', '50%'), $this->getOption('placeTop', '50%'), $this->getOption('opacity', 100));
		} else {
			$image = $rawImage;
		}

		$image->save($this->destination);
	}

	/**
	 * @param string $name
	 * @param string|null $default
	 * @return string|null
	 */
	private function getOption($name, $default = null)
	{
		if (!array_key_exists($this->width . 'x' . $this->height, $this->options)) {
			return $default;
		}

		$options = $this->options[$this->width . 'x' . $this->height];

		if (!array_key_exists($name, $options)) {
			return $default;
		}

		return $options[$name];
	}

	/**
	 * @return void
	 */
	private function createDir()
	{
		$dir = dirname($this->destination);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
	}

	/**
	 * @return string
	 */
	private function createThumbPath()
	{
		$pathInfo = pathinfo($this->src);
		$search = array('{subDir}', '{width}', '{height}', '{filename}', '{extension}');
		$replace = array($this->subDir, $this->width, $this->height, $pathInfo['filename'], $pathInfo['extension']);
		return str_replace($search, $replace, $this->thumbPathMask);
	}

	/**
	 * @return string
	 */
	private function createPlaceholderPath()
	{
		$width = $this->width===NULL ? $this->height : $this->width;
		$height = $this->height===NULL ? $this->width : $this->height;
		$search = array('{width}', '{height}');
		$replace = array($width, $height);
		return str_replace($search, $replace, $this->placeholder);
	}

}