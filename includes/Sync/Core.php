<?php
namespace Sync;

use Redaxscript\Config;
use Redaxscript\Db;
use Redaxscript\Filesystem;
use Redaxscript\Language;

/**
 * parent class for the core
 *
 * @since 4.0.0
 *
 * @package Sync
 * @category Core
 * @author Henry Ruhs
 */

class Core
{
	/**
	 * instance of the language class
	 *
	 * @var Language
	 */

	protected $_language;

	/**
	 * instance of the config class
	 *
	 * @var Config
	 */

	protected $_config;

	/**
	 * constructor of the class
	 *
	 * @since 4.0.0
	 *
	 * @param Language $language instance of the language class
	 * @param Config $config instance of the config class
	 */

	public function __construct(Language $language, Config $config)
	{
		$this->_language = $language;
		$this->_config = $config;
	}

	/**
	 * run
	 *
	 * @since 4.0.0
	 */

	public function run()
	{
		Db::getStatus() === 2 ? exit($this->_process()) : exit($this->_language->get('database_failed') . PHP_EOL);
	}

	/**
	 * process
	 *
	 * @since 4.0.0
	 *
	 * @return int
	 */

	protected function _process() : int
	{
		$parser = new Parser($this->_language);
		$filesystem = new Filesystem\Filesystem();
		$filesystem->init('vendor' . DIRECTORY_SEPARATOR . 'redaxmedia' . DIRECTORY_SEPARATOR . 'ncss-documentation' . DIRECTORY_SEPARATOR . 'documentation', true);
		$filesystemInterator = $filesystem->getIterator();
		$author = 'documentation-sync';
		$categoryCounter = $parentId = 1000;
		$articleCounter = 1000;
		$status = 0;

		/* delete category and article */

		Db::forTablePrefix('categories')->where('author', $author)->deleteMany();
		Db::forTablePrefix('articles')->where('author', $author)->deleteMany();

		/* create category */

		Db::forTablePrefix('categories')
			->create()
			->set(
			[
				'id' => $categoryCounter,
				'title' => 'Documentation',
				'alias' => 'documentation',
				'author' => $author
			])
			->save();

		/* process filesystem */

		foreach ($filesystemInterator as $key => $value)
		{
			$title = $parser->getName($value);
			$alias = $parser->getAlias($value);
			$rank = $parser->getRank($value);

			/* create category */

			if ($value->isDir())
			{
				$createStatus = Db::forTablePrefix('categories')
					->create()
					->set(
					[
						'id' => ++$categoryCounter,
						'title' => $title,
						'alias' => $alias,
						'author' => $author,
						'rank' => $rank,
						'parent' => $parentId
					])
					->save();
			}

			/* else create article */

			else
			{
				$parentAlias = $parser->getParent($value);
				$articleText = $parser->getContent($value);
				$createStatus = Db::forTablePrefix('articles')
					->create()
					->set(
					[
						'id' => $articleCounter++,
						'title' => $title,
						'alias' => $alias . '-' . $articleCounter,
						'author' => $author,
						'text' => $articleText,
						'rank' => $rank,
						'category' => $parentAlias === 'documentation' ? $parentId : $categoryCounter
					])
					->save();
			}

			/* handle status */

			if ($createStatus)
			{
				echo '.';
			}
			else
			{
				$status = 1;
				echo 'F';
			}
		}
		echo PHP_EOL;

		/* auto increment */

		$this->_setAutoIncrement(3000);
		return $status;
	}

	/**
	 * set the auto increment
	 *
	 * @since 4.0.0
	 *
	 * @param int $increment
	 */

	protected function _setAutoIncrement(int $increment = 0)
	{
		Db::rawInstance()->rawExecute('ALTER TABLE ' . $this->_config->get('dbPrefix') . 'categories AUTO_INCREMENT = ' . $increment);
		Db::rawInstance()->rawExecute('ALTER TABLE ' . $this->_config->get('dbPrefix') . 'articles AUTO_INCREMENT = ' . $increment);
	}
}
