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
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
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

class ShowFollowers extends LampcmsObject
{

	/**
	 * Mongo Cursor
	 *
	 * @var object of type MongoCursor
	 */
	protected $oCursor;


	/**
	 * Maximum number of users
	 * in one block
	 * The block is the block with small
	 * avatars shown in the column to show
	 * "Some" followers of the question or a user
	 *
	 * @var int
	 */
	protected $maxPerBlock = 16;


	/**
	 * Constructor
	 *
	 * @param Registry $oRegistry
	 */
	public function __construct(Registry $oRegistry){
		$this->oRegistry = $oRegistry;
	}


	/**
	 * Setter of maxPerBlock var
	 *
	 * @param int $max
	 * @throws \InvalidArgumentException if $max is not numeric
	 */
	public function setMaxPerBlock($max){
		if(!is_numeric($max) || $max < 1){
			throw new \InvalidArgumentException('$max must be numeric value greater than 0. was: '.$max);
		}

		$this->maxPerBlock = (int)$max;

		return $this;
	}


	/**
	 *
	 * Get parsed html with divs
	 * of followers, one such div includes
	 * an avatar and some info in the title of the div
	 *
	 * @param array $aUids array of userIDs. The divs with
	 * info for these users will be returned
	 *
	 * @return string html
	 */
	public function getQuestionFollowers(array $aUids, $total = null){
		d('aUids: '.print_r($aUids, 1));

		if(count($aUids) > $this->maxPerBlock){
			$aUids = array_splice($aUids, 0, $this->maxPerBlock);
		}

		$where = array('_id' => array('$in' => $aUids), 'role' => array('$ne' => 'deleted'));

		$this->oCursor = $this->oRegistry->Mongo->USERS->find(
		$where, array(
			'_id', 
			'i_rep', 
			'username', 
			'fn', 
			'mn', 
			'ln',  
			'email', 
			'avatar', 
			'avatar_external')
		);

		$total = ($total) ? $total : $this->oCursor->count();

		if(0 === $total){
			return '';
		}

		/**
		 * @todo translate string title
		 *
		 */
		$title = $total.' ';
		$title .= (1 === $total) ? 'person' : 'people';
		$title .= ' following this question';

		d('followers title: '.$title);

		$func = null;
		$aGravatar = $this->oRegistry->Ini->getSection('GRAVATAR');

		if(count($aGravatar) > 0){
			$func = function(&$a) use ($aGravatar){
				$a['gravatar'] = $aGravatar;
			};
		}

		$followers = \tplOneFollower::loop($this->oCursor, true, $func);

		/**
		 * @todo translate title string
		 */
		return \tplFollowers::parse(array($title, $followers, $total), false);
	}



	/**
	 * Generate html block with
	 * title like '3 people following this tag'
	 * and divs with followers' avatars/links to profiles
	 *
	 * @param string $tag name of tag for which to
	 * add followers block
	 *
	 * @return string html
	 */
	public function getTagFollowers($tag){
		$where = array('a_f_t' => $tag, 'role' => array('$ne' => 'deleted'));
		$this->oCursor = $this->oRegistry->Mongo->USERS->find(
		$where, array(
			'_id', 
			'i_rep', 
			'username', 
			'fn', 
			'mn', 
			'ln',  
			'email', 
			'avatar', 
			'avatar_external')
		);

		$total = $this->oCursor->count();

		d('total tag followers: '.$total);

		if(0 === $total){
			return '';
		}

		$this->oCursor->limit($this->maxPerBlock);
		d('total tag followers: '.$total);

		$title = $total.' ';
		$title .= (1 === $total) ? 'person' : 'people';
		$title .= ' following this tag';

		d('followers title: '.$title);

		$func = null;
		$aGravatar = $this->oRegistry->Ini->getSection('GRAVATAR');

		if(count($aGravatar) > 0){
			$func = function(&$a) use ($aGravatar){
				$a['gravatar'] = $aGravatar;
			};
		}

		$followers = \tplOneFollower::loop($this->oCursor, true, $func);

		/**
		 * @todo translate title string
		 */
		return \tplFollowers::parse(array($title, $followers, $total), false);

	}


	/**
	 * Get html block with User's followers
	 *
	 * @param User $oUser
	 *
	 * @return string html
	 */
	public function getUserFollowers(User $oUser){

		$uid = $oUser->getUid();
		d('uid: '.$uid);
		$where = array('a_f_u' => $uid);

		$this->oCursor = $this->oRegistry->Mongo->USERS->find(
		$where, array(
			'_id', 
			'i_rep', 
			'username', 
			'fn', 
			'mn', 
			'ln',  
			'email', 
			'avatar', 
			'avatar_external')
		);

		$total = $this->oCursor->count();
		d('total followers: '.$total);

		if(0 === $total){
			d('no followers');

			return '';
		}

		/**
		 * @todo translate string title
		 *
		 */
		$title = $total.' ';
		$title .= (1 === $total) ? 'Follower' : 'Followers';

		d('followers title: '.$title);

		$func = null;
		$aGravatar = $this->oRegistry->Ini->getSection('GRAVATAR');

		if(count($aGravatar) > 0){
			$func = function(&$a) use ($aGravatar){
				$a['gravatar'] = $aGravatar;
			};
		}

		$followers = \tplOneFollower::loop($this->oCursor, true, $func);

		/**
		 * @todo translate title string
		 */
		return \tplFollowers::parse(array($title, $followers, $total), false);

	}


	/**
	 * Get div with users that this use is following
	 *
	 * @param User $oUser
	 *
	 * @return string html
	 */
	public function getUserFollowing(User $oUser){

		$aFollowing = $oUser['a_f_u'];

		if(empty($aFollowing)){
			d('user not following anyone');
			return '';
		}

		$total = count($aFollowing);
		if($total > $this->maxPerBlock){
			$aFollowing = array_splice($aFollowing, 0, $this->maxPerBlock);
		}

		$where = array('_id' => array('$in' => $aFollowing));

		/**
		 * Find all users that has this user has in the
		 * 'a_f_u' array
		 */
		$this->oCursor = $this->oRegistry->Mongo->USERS->find(
		$where, array(
			'_id', 
			'i_rep', 
			'username', 
			'fn', 
			'mn', 
			'ln',  
			'email', 
			'avatar', 
			'avatar_external')
		);

		if(0 === $this->oCursor->count()){
			d('no following users found');
			
			return '';
		}

		/**
		 * @todo translate string title
		 *
		 */
		$title = 'Following ' .$total.' user'.(((1 === $total)) ? '' : 's');
		d('followers title: '.$title);

		$func = null;
		$aGravatar = $this->oRegistry->Ini->getSection('GRAVATAR');

		if(count($aGravatar) > 0){
			$func = function(&$a) use ($aGravatar){
				$a['gravatar'] = $aGravatar;
			};
		}

		$followees = \tplOneFollowee::loop($this->oCursor, true, $func);

		/**
		 * @todo translate title string
		 */
		return \tplFollowers::parse(array($title, $followees, $total), false);

	}

}
