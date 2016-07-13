<?php
/**
 * Generator.php
 *
 * @author Michal Pospiech <michal@pospiech.cz>
 */

namespace Libs\NetProfit\Thumbnail;


use Nette\Http\IRequest;
use Nette\Object;
use Nette\Utils\Image;

class Generator extends Object
{
	
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
	
	/** @var IRequest */
	private $httpRequest;
	
	/** @var string */
	private $thumbPathMask;
	
	/** @var string */
	private $placeholder;
	
	/** @var array */
	private $options = array();
	
	public function __construct(IRequest $httpRequest, $wwwDir, $thumbPathMask, $placeholder, $options = array())
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
	 * @return mixed|string
	 */
	public function generateThumbnail($src, $width, $height)
	{
		$this->src = $this->wwwDir . '/' . $src;
		$this->width = $width;
		$this->height = $height;

		if (!is_file($this->src)) {
			return $this->createPlaceholderPath();
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
		$rawImage = Image::fromFile($this->src);
		$rawImage->resize($this->width, $this->height, Image::SHRINK_ONLY);
		$rawImage->sharpen();

		if ($this->getOption('place', true)) {
			$image = Image::fromBlank($this->width, $this->height, $this->getOption('background', Image::rgb(255, 255, 255, 127)));
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
		$search = array('{width}', '{height}', '{filename}', '{extension}');
		$replace = array($this->width, $this->height, $pathInfo['filename'], $pathInfo['extension']);
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