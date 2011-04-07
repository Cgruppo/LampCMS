<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms;


/**
 * Special class that represents html of one rss feed item
 * as an instance of DomDocument
 *
 * This class can also be used to load any submitted
 * html document like document we get after converting
 * MS Word or OpenOffice or Email in html format
 *
 * It's very useful if we need to extract the embedded images
 * and then we need to change the src attributes of such images
 * to point to out extracted/resized/saved images.
 *
 *
 * Being an instance of DomDocument makes it easy to
 * manipulate the html tags, like adding the nofollow to all links,
 * extracting all images, getting number of links in item
 * as well as doing some extra non-dom related manipulation
 * like adding our own links to it, etc.
 *
 * @author Dmitri Snytkine
 *
 */

class DomFeedItem extends \DOMDocument
{

	/**
	 * Number of links this element has
	 * @var int
	 */
	protected $intLinksCount = 0;

	/**
	 * Number of image nodes this element has
	 * @var int
	 */
	protected $intImgCount = 0;

	/**
	 * Array of extracted image nodes data
	 * each element is an array
	 * that contains the attribute name => value
	 * example: array('src' => '/image/cool.jpg', 'width'=>'250px')
	 *
	 * @var array
	 */
	protected $aImages = array();

	/**
	 * Flag indicates to add rel=nofollow to all links
	 *
	 * @var bool
	 */
	protected $bNofollow = true;

	/**
	 * If img tags or link tags in the feed item
	 * are not absolute (don't start with http)
	 * then prefix them with this baseUri
	 * but only if baseUri itself starts with http://
	 *
	 * @var string
	 */
	public $baseUri = '';

	/*	public function __construct($v = '1.0', $enc = 'UTF-8'){
		parent::__construct('1.0', 'UTF-8');

		$this->recover = true;
		}
		*/
	/**
	 * Getter for this->intLinksCount
	 * @return int
	 */
	public function getLinksCount(){

		d(' $this->intLinksCount: '.$this->intLinksCount);

		return $this->intLinksCount;
	}

	/**
	 * Getter for intImgCount
	 * @return int
	 */
	public function getImgCount(){

		return $this->intImgCount;
	}

	public function setNoFollow($bNoFollow = true){
		$this->bNofollow = $bNoFollow;
	}

	/**
	 * Factory method
	 * Makes the object is this class
	 * and load the html string, first wraps the html
	 * string into the <div class="newsItem">
	 *
	 * @param object of type Utf8String $sHtml html string to load
	 * usually this is the feed item from rss feed.
	 * by being an object of type Utf8String it's guaranteed
	 * to be an already in utf-8 charset
	 *
	 * @return object of this class
	 *
	 * @throws LampcmsDevException is unable to load the string
	 */
	public static function loadFeedItem(Utf8String $oHtml, $sBaseUri = '', $bAddNoFollow = true, $parseCodeTags = true)
	{

		$oDom = new self('1.0', 'utf-8');
		$oDom->encoding = 'UTF-8';
		$oDom->preserveWhiteSpace = true;
		$oDom->recover = true;
		$oDom->setNofollow($bAddNoFollow);

		$sHtml = $oHtml->valueOf();

		/**
		 * @todo
		 * maybe we should add class to this div and
		 * then in the getFeedItem() don't remove the div at all,
		 * so it will always be part of feed item's html,
		 * it's just going to wrap the entire item.
		 * So when we add item to a page we know it will always be wrapped
		 * in this additional div
		 *
		 */

		/**
		 * Extremely important to add the
		 * <META CONTENT="text/html; charset=utf-8">
		 * This is the ONLY way to tell the DOM (more spefically
		 * the libxml) that input is in utf-8 encoding
		 * Whithout this the DOM will assume that input is in the
		 * default ISO-8859-1 format and then
		 * will try to recode it to utf8
		 * essentially it will do its own conversion to utf8,
		 * messing up the string because it's already in utf8 and does not
		 * need converting
		 *
		 * IMPORTANT: we are also wrapping the whole string in <div>
		 * so that it will be easy to get back just the contents of
		 * the first div
		 *
		 */
		$sHtml = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
                      "http://www.w3.org/TR/REC-html40/loose.dtd">
			<head>
  			<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
			</head>
			<body><div>'.$sHtml.'</div></body></html>';

		$ER = error_reporting(0);
		if(false === @$oDom->loadHTML($sHtml)){
			throw new DevException('Error. Unable to load html string: '.$sHtml);
		}
		error_reporting($ER);


		/**
		 * If $sBaseUrl begins with http
		 * then set the $this->baseUri value to this value
		 * and make sure it always ends with forward slash
		 */
		if(!empty($sBaseUri) && ('http' === substr($sBaseUri, 0, 4)) && (strlen($sBaseUri) > 12) ){
			$oDom->baseUri = rtrim($sBaseUri, '/').'/';

		}

		$oDom->setRelNofollow();

		if($parseCodeTags){
			$oDom->parseCodeTags();
		}

		$oDom->fixImgBaseUri();//->getImages();


		return $oDom;
	}

	/**
	 * Setter for this->baseUrk
	 * @param string $s
	 * @return object $this
	 */
	public function setBaseUri($s)
	{
		$this->baseUri = $s;

		return $this;
	}

	/**
	 * Add attributes rel="code" class="c"
	 * to all 'code' tags
	 * this way the html with code tags can be
	 * parsed by syntax highlighter
	 *
	 * @return object $this
	 */
	public function parseCodeTags(){
		$aCode = $this->getElementsByTagName('code');
		$numItems = $aCode->length;
		if(0 == $numItems){
			return $this;
		}

		for($i = 0; $i < $numItems; $i += 1){
			$node = $aCode->item($i);

			/**
			 * Remove em tags by doing this:
			 * replace 'em' nodes with their text values
			 */

			/*$aEmNodes = $node->getElementsByTagName('em');
			 $numEm = $aEmNodes->length;
			 d('number of em elements inside this code: '.$numEm);
			 if($numEm > 0){
				$tempEm = array();
				for($j =0; $j< $numEm; $j += 1){
				$nodeEm = $aEmNodes->item($j);
				$textVal = $nodeEm->nodeValue;
				e('value of em tag: '.$textVal);
				$textNode = $this->createTextNode($textVal);
				}
				}*/

			if(!$node->hasAttribute('rel')){
				$node->setAttribute('rel', 'code');
				$node->setAttribute('class', 'c');
			}
		}

		return $this;
	}

	/**
	 * Get HTML of the feedItem's root element,
	 * which is <div class="newsItem">
	 *
	 * @return string HTML string, usually with html special
	 * entities (like &nbsp;) replaced with special UTF8 XML entities
	 *
	 * @throws LampcmsDevException if document doesn't look
	 * like its a feed item document (if does not have
	 * the first element <div class="newsItem">
	 *
	 */
	public function getFeedItem()
	{

		$html = substr($this->saveXML($this->getElementsByTagName('div')->item(0)), 5, -6);

		return $html;
	}



	/**
	 * Get all 'a' element child nodes
	 * of this element
	 *
	 * @return mixed object nodeSet | null if no 'a' elements found
	 */
	public function getAllLinks()
	{
		$aLinks = $this->getElementsByTagName('a');
		$this->intLinksCount = $aLinks->length;

		if (0 === $this->intLinksCount) {

			return null;
		}

		return $aLinks;
	}

	/**
	 * Set attributes of ALL links (a nodes)
	 * child nodes of this node to 'nofollow'
	 * This is useful when parsing the external feed
	 * and want to add rel="nofollow" to all links
	 *
	 * @return object $this
	 */
	public function setRelNofollow($bSetTargetBlank = true)
	{
		if($this->bNofollow){
			if(null !== $aLinks = $this->getAllLinks()){
				for($i = 0; $i < $this->intLinksCount; $i += 1){
					if($aLinks->item($i)->hasAttribute('href')){
						$aLinks->item($i)->setAttribute('rel', 'nofollow');
						if($bSetTargetBlank){
							$aLinks->item($i)->setAttribute('target', '_blank');
						}
					}
				}
			}
		}
		
		return $this;
	}

	/**
	 * Find all image elements, then one by one
	 * change their 'src' attribute to prefix it with $this->sBaseUri
	 * if necessary
	 *
	 * @return object $this
	 */
	public function fixImgBaseUri()
	{
		$Images = $this->getImages();

		if(empty($this->baseUri)){

			return $this;
		}

		if(0 === $this->intImgCount){

			return $this;
		}

		for($i = 0; $i < $Images->length; $i += 1){
			$src = $Images->item($i)->getAttribute('src');
			if(preg_match('/(http|ftp)([s]{0,1}):\/\//', $src)){
				continue;
			} else {
				$src = ltrim($src, '/');
				$src = $this->baseUri.$src;
				$Images->item($i)->setAttribute('src', $src);
			}
		}


		return $this;
	}


	public function fixLinkBaseUri()
	{

	}

	/**
	 * Get all img elements children of this element
	 *
	 * @param $bAsArray is set to true (default is false)
	 * then return value is array where each element is array
	 * of attribute name => attribute value of image
	 * This can be useful if we want to extract all images
	 * from the item, like if we want to resize it or
	 * use it as thumbnail or something.
	 *
	 *
	 * @return mixed nodeSet object of array of image nodes
	 * or null if no image tags found
	 *
	 */
	public function getImages($bAsArray = false)
	{
		$Images = $this->getElementsByTagName('img');

		$this->intImgCount = $Images->length;

		if (0 === $this->intImgCount) {

			return null;
		}

		if(!$bAsArray){
			return $Images;
		}


		for ($i = 0; $i < $this->intImgCount; $i += 1) {
			foreach ($Images->item($i)->attributes as $attrName=>$attrNode) {
				if (!array_key_exists($i, $arrRes)) {
					$this->aImages[$i] = array();
				}

				$this->aImages[$i][$attrName] = $Images->item($i)->getAttribute($attrName);
			}
		}

		return $this->aImages;

	}


	public function makeClickable(\DomNode $o = null){
		$o = ($o) ? $o : $this;
		d("\n<br>Node name: ".$o->nodeName.' type: '.$o->nodeType.' value: '.$o->nodeValue);

		$nodeName = strtolower($o->nodeName);
		/**
		 * Skip nodes that are already the "a" node (link)
		 * and skip img tags just because they could not possibly
		 * contain text nodes
		 */
		if(!in_array($nodeName, array('a', 'img'))){
			if(XML_TEXT_NODE == $o->nodeType){
				$this->linkifyTextNode($o);
			}

			$oChildred = $o->childNodes;
			$len = ($oChildred) ? $oChildred->length : 0;
			d(" len: $len ");
			if($len > 0){
				for($i = 0; $i<$len; $i+=1){
					$this->makeClickable($oChildred->item($i));
				}
			}
		}
	}


	protected function linkifyTextNode(\DomNode $o){
		$text = $o->nodeValue;
		$maxurl_len = 50;
		$target = "_blank";

		/**
		 * If this value has something to linkify, then do it
		 * and then replace this actual node with the new CDATASection
		 * which will contain the parsed text
		 */

		/**
		 * Do NOT match urls that may already be
		 * the value of src or href inside the a or img tag
		 * For simplicity we we match ONLY strings thank look like
		 * url and DO NOT have " or ' before it.
		 *
		 */
		if (preg_match_all('/((?<!([\"|\'\>]{1}))(ht|f)tps?:\/\/([\w\.]+\.)?[\w\-?&=;%#+]+(\.[a-zA-Z]{2,6})?[^\s\r\n\(\)"\'<>\,\!]+)/smi', $text, $urls))
		{
			$offset1 = ceil(0.65 * $maxurl_len) - 2;
			$offset2 = ceil(0.30 * $maxurl_len) - 1;

			$nofollow = (true === $this->bNofollow) ? ' rel="nofollow"' : '';
			$aUrls = (array_unique($urls[1]));
			d('aUrls: '.print_r($aUrls, 1));

			foreach ($aUrls as $url){
				$urltext = urldecode($url);
				d('decoded: '.$urltext);
				if ($maxurl_len && (strlen($decoded) > $maxurl_len)){
					$urltext = substr($urltext, 0, $offset1) . '...' . substr($urltext, -$offset2);
				}

				$pattern = '~([^\w]+)'.preg_quote($url).'([^\w]+)~';

				$text = preg_replace($pattern, '\\1<a href="'. $url .'" '.$nofollow.' target="'. $target .'">'. $urltext .'</a>\\2', $text);

			}

			$CDATA = $o->ownerDocument->createCDATASection($text);
			$o->parentNode->appendChild($CDATA);
			$o->parentNode->removeChild($o);

		}
	}


	public function __toString()
	{
		return $this->getFeedItem();
	}
}
