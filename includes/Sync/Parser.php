<?php
namespace Sync;

use cebe\markdown\GithubMarkdown as Markdown;
use Redaxscript\Html;
use Redaxscript\Language;
use Redaxscript\Reader;
use SplFileInfo;

/**
 * parent class to parse the documentation
 *
 * @since 4.0.0
 *
 * @package Doc
 * @category Parser
 * @author Henry Ruhs
 */

class Parser
{
	/**
	 * instance of the language class
	 *
	 * @var object
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
	 * get the name
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return string
	 */

	public function getName(SplFileInfo $item = null) : string
	{
		$basenameArray = $this->_getBasenameArray($item);
		return ucwords(str_replace('-', ' ', $basenameArray[1]));
	}

	/**
	 * get the alias
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return string
	 */

	public function getAlias(SplFileInfo $item = null) : string
	{
		$basenameArray = $this->_getBasenameArray($item);
		return $basenameArray[1];
	}

	/**
	 * get the rank
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return int
	 */

	public function getRank(SplFileInfo $item = null) : int
	{
		$basenameArray = $this->_getBasenameArray($item);
		return (int)$basenameArray[0];
	}

	/**
	 * get the parent
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return string
	 */

	public function getParent(SplFileInfo $item = null) : string
	{
		$path = $item->getPathname();
		return trim(strrchr(dirname($path), '/'), '/');
	}

	/**
	 * get the content
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return string
	 */

	public function getContent(SplFileInfo $item = null) : string
	{
		$markdown = new Markdown();
		$path = $item->getPathname();
		$content = file_get_contents($path);
		return $this->_tidyContent($markdown->parse($content)) . $this->_renderLink($path);
	}

	/**
	 * get the basename array
	 *
	 * @since 4.0.0
	 *
	 * @param SplFileInfo $item
	 *
	 * @return array
	 */

	protected function _getBasenameArray(SplFileInfo $item = null) : array
	{
		return explode('.', $item->getBasename());
	}

	/**
	 * tidy the content
	 *
	 * @since 4.0.0
	 *
	 * @param string $content
	 *
	 * @return string
	 */

	protected function _tidyContent(string $content = null) : string
	{
		$reader = new Reader();
		$tidyArray = $reader->loadJSON('tidy.json')->getArray();
		return str_replace($tidyArray['search'], $tidyArray['replace'], $content);
	}

	/**
	 * render the link
	 *
	 * @since 4.0.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */

	protected function _renderLink(string $path = null) : string
	{
		$href = str_replace('vendor' . DIRECTORY_SEPARATOR . 'redaxmedia' . DIRECTORY_SEPARATOR . 'ncss-documentation', 'https://github.com/redaxmedia/ncss-documentation/edit/master', $path);

		/* html elements */

		$linkElement = new Html\Element();
		return $linkElement
			->init('a',
			[
				'class' => 'rs-link-documentation',
				'href' => $href,
				'target' => '_blank'
			])
			->text($this->_language->get('edit_github'));
	}
}
