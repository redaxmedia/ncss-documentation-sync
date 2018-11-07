<?php
namespace Sync;

use Redaxscript\Admin;
use Redaxscript\Dater;
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
	 * constructor of the class
	 *
	 * @since 4.0.0
	 *
	 * @param Language $language instance of the language class
	 */

	public function __construct(Language $language)
	{
		$this->_language = $language;
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
		$dater = new Dater();
		$dater->init();
		$now = $dater->getDateTime()->getTimestamp();
		$categoryModel = new Admin\Model\Category();
		$articleModel = new Admin\Model\Article();
		$parser = new Parser($this->_language);
		$filesystem = new Filesystem\Filesystem();
		$filesystem->init('vendor' . DIRECTORY_SEPARATOR . 'redaxmedia' . DIRECTORY_SEPARATOR . 'ncss-documentation' . DIRECTORY_SEPARATOR . 'documentation', true);
		$filesystemInterator = $filesystem->getIterator();
		$author = 'documentation-sync';
		$categoryCounter = 1000;
		$parentId = 1000;
		$articleCounter = 1000;
		$status = 0;

		/* delete first */

		$categoryModel->query()->where('author', $author)->deleteMany();
		$articleModel->query()->where('author', $author)->deleteMany();

		/* create category */

		$categoryModel->createByArray(
		[
			'id' => $categoryCounter,
			'title' => 'Documentation',
			'alias' => 'documentation',
			'author' => $author,
			'date' => $now
		]);

		/* process filesystem */

		foreach ($filesystemInterator as $key => $value)
		{
			$title = $parser->getName($value);
			$alias = $parser->getAlias($value);
			$rank = $parser->getRank($value);

			/* create category */

			if ($value->isDir())
			{
				$createStatus = $categoryModel->createByArray(
				[
					'id' => ++$categoryCounter,
					'title' => $title,
					'alias' => $alias,
					'author' => $author,
					'rank' => $rank,
					'parent' => $parentId,
					'date' => $now
				]);
			}

			/* else create article */

			else
			{
				$parentAlias = $parser->getParent($value);
				$articleText = $parser->getContent($value);
				$createStatus = $articleModel->createByArray(
				[
					'id' => $articleCounter++,
					'title' => $title,
					'alias' => $alias . '-' . $articleCounter,
					'author' => $author,
					'text' => $articleText,
					'rank' => $rank,
					'category' => $parentAlias === 'documentation' ? $parentId : $categoryCounter,
					'date' => $now
				]);
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

		Db::setAutoIncrement('categories', 2000);
		Db::setAutoIncrement('articles', 2000);
		return $status;
	}
}
