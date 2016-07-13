<?php
/**
 * Extension.php
 *
 * @author Michal Pospiech <michal@pospiech.cz>
 */

namespace Libs\NetProfit\Thumbnail\DI;


use Libs\NetProfit\Thumbnail\Generator;
use Nette\DI\CompilerExtension;

class Extension extends CompilerExtension
{

	public $defaults = [
		'wwwDir' => '%wwwDir%',
		'httpRequest' => '@httpRequest',
		'thumbPathMask' => 'images/thumbs/{width}x{height}/{filename}.{extension}',
		'placeholder' => 'http://fakeimg.pl/{width}x{height}/ffffff/000000?text=Image+not+found',
		'helperName' => 'thumb',
		'options' => []
	];
	
	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();
		
		$builder->addDefinition($this->prefix('netteThumbnail'))
			->setClass(Generator::class, [
				'wwwDir' => $config['wwwDir'],
				'httpRequest' => $config['httpRequest'],
				'thumbPathMask' => $config['thumbPathMask'],
				'placeholder' => $config['placeholder'],
				'options' => $config['options']
			]);

		if ($builder->hasDefinition('nette.latteFactory')) {
			$definition = $builder->getDefinition('nette.latteFactory');
			$definition->addSetup('addFilter', [$config['helperName'], [$this->prefix('@netteThumbnail'), 'generateThumbnail']]);
		}
	}

}