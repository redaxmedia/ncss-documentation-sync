<?php
namespace Redaxscript;

use Doc;
use Redaxscript\Filesystem;

error_reporting(E_ERROR || E_PARSE);

/* autoload */

include_once('vendor/redaxmedia/redaxscript/includes/Autoloader.php');

/* init */

$autoloader = new Autoloader();
$autoloader->init(
[
	'Doc' => 'includes',
	'Redaxscript' => 'vendor/redaxmedia/redaxscript/includes',
	'cebe\markdown' => 'vendor/cebe/markdown',
	'vendor/redaxmedia/redaxscript/libraries'
]);

/* get instance */

$config = Config::getInstance();

/* status and config */

$status = 1;
$dbUrl = getenv('DB_URL');
$config->parse($dbUrl);

/* database */

Db::construct($config);
Db::init();

/* language */

$language = Language::getInstance();
$language->init();

/* sync documentation */

if (Db::getStatus() === 2)
{
	$status = 0;
	$docParser = new Doc\Parser($language);
	$docFilesystem = new Filesystem\Filesystem();
	$docFilesystem->init('vendor/redaxmedia/ncss-documentation/documentation', true);
	$docFilesystemInterator = $docFilesystem->getIterator();
	$author = 'documentation-sync';
	$categoryCounter = $parentId = 1000;
	$articleCounter = 1000;

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

	foreach ($docFilesystemInterator as $key => $value)
	{
		$title = $docParser->getName($value);
		$alias = $docParser->getAlias($value);
		$rank = $docParser->getRank($value);

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
			$parentAlias = $docParser->getParent($value);
			$articleText = $docParser->getContent($value);
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

	Db::rawInstance()->rawExecute('ALTER TABLE ' . $config->get('dbPrefix') . 'categories AUTO_INCREMENT = 3000');
	Db::rawInstance()->rawExecute('ALTER TABLE ' . $config->get('dbPrefix') . 'articles AUTO_INCREMENT = 3000');
}
exit($status);
