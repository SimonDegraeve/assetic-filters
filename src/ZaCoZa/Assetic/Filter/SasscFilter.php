<?php
namespace AsseticAdditions\Filter;

/*
 * Copyright (c) 2013 Daniel Corn
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

use Assetic\Asset\AssetInterface;
use Assetic\Filter\DependencyExtractorInterface;
use Assetic\Filter\BaseProcessFilter;
use Assetic\Filter\Sass\SassFilter;
use Assetic\Exception\FilterException;
use Assetic\Factory\AssetFactory;
use Assetic\Util\CssUtils;


/**
 * Loads SCSS files using the C implementation sassc and libsass.
 */
class SasscFilter extends BaseProcessFilter implements DependencyExtractorInterface {

	const STYLE_NESTED = 'nested';
	const STYLE_EXPANDED = 'expanded';
	const STYLE_COMPACT = 'compact';
	const STYLE_COMPRESSED = 'compressed';

	protected $binaryPath;
	protected $style;
	protected $lineNumbers;
	protected $emitSourceMap;
	protected $loadPaths = array();


	public function __construct($binaryPath = '/usr/bin/sassc') {
		$this->binaryPath = $binaryPath;
	}

	public function filterLoad(AssetInterface $asset) {
		$sassProcessArgs = array($this->binaryPath);
		$pb = $this->createProcessBuilder($sassProcessArgs);

		$assetDirectory = '';
		if (method_exists($asset, 'getSourceDirectory')) {
			$assetDirectory = $asset->getSourceDirectory();
		} else {
			$root = $asset->getSourceRoot();
			$path = $asset->getSourcePath();
			$assetDirectory = dirname($root . '/' . $path);
		}

		$allLoadPaths = $this->loadPaths;
		array_unshift($allLoadPaths, $assetDirectory);
		$pb->add('-I')->add(implode(':', $allLoadPaths));

		if ($this->style) {
			$pb->add('-t')->add($this->style);
		}
		if ($this->lineNumbers) {
			$pb->add('-l');
		}
		if ($this->emitSourceMap) {
			$pb->add('-g');
		}

		// input
		// $pb->add($input = tempnam(sys_get_temp_dir(), 'assetic_sass'));
		// file_put_contents($input, $asset->getContent());

		$pb->add($asset->getSourceRoot() . '/' . $asset->getSourcePath());

		$process = $pb->getProcess();
		$code = $process->run();
		// unlink($input);

		if (0 !== $code) {
			throw FilterException::fromProcess($process); //->setInput($asset->getContent());
		}

		$asset->setContent($process->getOutput());
	}

	/**
	 * Sets the import paths for the compiler to use
	 *
	 * @param array $paths Array of directory paths
	 */
	public function setImportPaths(array $paths) {
		$this->loadPaths = $paths;
	}

	/**
	 * @see setImportPaths()
	 */
	public function setLoadPaths(array $loadPaths) {
		$this->setImportPaths($loadPaths);
	}

	/**
	 * Add an import path for the compiler to use
	 *
	 * @param string $path
	 */
	public function addImportPath($path) {
		$this->loadPaths[] = $path;
	}

	/**
	 * @see addImportPath()
	 */
	public function addLoadPath($loadPath) {
		$this->addImportPath($loadPath);
	}


	public function setStyle($style) {
		$this->style = $style;
	}

	/**
	 * @param boolean $emitSourceMap
	 */
	public function setEmitSourceMap($emitSourceMap) {
		$this->emitSourceMap = $emitSourceMap;
	}


	public function setLineNumbers($lineNumbers) {
		$this->lineNumbers = $lineNumbers;
	}


	public function filterDump(AssetInterface $asset) {
	}

	public function getChildren(AssetFactory $factory, $content, $loadPath = null) {
		$loadPaths = $this->loadPaths;
		if ($loadPath) {
			array_unshift($loadPaths, $loadPath);
		}

		if (!$loadPaths) {
			return array();
		}

		$children = array();
		foreach (CssUtils::extractImports($content) as $reference) {
			if ('.css' === substr($reference, -4)) {
				// skip normal css imports
				// todo: skip imports with media queries
				continue;
			}

			// the reference may or may not have an extension or be a partial
			if (pathinfo($reference, PATHINFO_EXTENSION)) {
				$needles = array(
					$reference,
					self::partialize($reference),
				);
			} else {
				$needles = array(
					$reference . '.scss',
					$reference . '.sass',
					self::partialize($reference) . '.scss',
					self::partialize($reference) . '.sass',
				);
			}

			foreach ($loadPaths as $loadPath) {
				foreach ($needles as $needle) {
					if (file_exists($file = $loadPath . '/' . $needle)) {
						$coll = $factory->createAsset($file, array(), array('root' => $loadPath));
						foreach ($coll as $leaf) {
							/** @var AssetInterface $leaf */
							$leaf->ensureFilter($this);
							$children[] = $leaf;
							goto next_reference;
						}
					}
				}
			}

			next_reference:
		}

		return $children;
	}

	private static function partialize($reference) {
		$parts = pathinfo($reference);

		if ('.' === $parts['dirname']) {
			$partial = '_' . $parts['filename'];
		} else {
			$partial = $parts['dirname'] . DIRECTORY_SEPARATOR . '_' . $parts['filename'];
		}

		if (isset($parts['extension'])) {
			$partial .= '.' . $parts['extension'];
		}

		return $partial;
	}
}
