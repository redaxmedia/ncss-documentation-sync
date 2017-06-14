<?php
namespace Doc;

use cebe\markdown\GithubMarkdown as Markdown;
use Redaxscript\Html;
use Redaxscript\Language;
use Redaxscript\Reader;

/**
 * parent class to parse the documentation
 *
 * @since 3.0.0
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
	 * @since 3.0.0
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
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return string
	 */

	public function getName($item = null)
	{
		$basenameArray = $this->_getBasenameArray($item);
		return ucwords(str_replace('-', ' ', $basenameArray[1]));
	}

	/**
	 * get the alias
	 *
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return string
	 */

	public function getAlias($item = null)
	{
		$basenameArray = $this->_getBasenameArray($item);
		return $basenameArray[1];
	}

	/**
	 * get the rank
	 *
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return integer
	 */

	public function getRank($item = null)
	{
		$basenameArray = $this->_getBasenameArray($item);
		return intval($basenameArray[0]);
	}

	/**
	 * get the parent
	 *
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return string
	 */

	public function getParent($item = null)
	{
		$path = $item->getPathname();
		return trim(strrchr(dirname($path), '/'), '/');
	}

	/**
	 * get the content
	 *
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return string
	 */

	public function getContent($item = null)
	{
		$markdown = new Markdown();
		$path = $item->getPathname();
		$content = file_get_contents($path);
		$output = $this->_tidyContent($markdown->parse($content));
		$output .= $this->_renderLink($path);
		return $output;
	}

	/**
	 * tidy the content
	 *
	 * @since 3.0.0
	 *
	 * @param string $content
	 *
	 * @return string
	 */

	protected function _tidyContent($content = null)
	{
		$reader = new Reader();
		$tidyArray = $reader->loadJSON('tidy.json', true)->getArray();
		$output = str_replace($tidyArray['search'], $tidyArray['replace'], $content);
		return $output;
	}

	/**
	 * render the link
	 *
	 * @since 3.0.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */

	protected function _renderLink($path = null)
	{
		$href = str_replace('vendor/redaxmedia/ncss-documentation', 'https://github.com/redaxmedia/ncss-documentation/edit/master', $path);

		/* html elements */

		$linkElement = new Html\Element();
		$linkElement
			->init('a',
			[
				'class' => 'rs-link-documentation',
				'href' => $href,
				'target' => '_blank'
			])
			->text($this->_language->get('edit_github'));

		/* collect output */

		$output = $linkElement;
		return $output;
	}

	/**
	 * get the basename array
	 *
	 * @since 3.0.0
	 *
	 * @param object $item
	 *
	 * @return array
	 */

	protected function _getBasenameArray($item = null)
	{
		return explode('.', $item->getBasename());
	}
}
