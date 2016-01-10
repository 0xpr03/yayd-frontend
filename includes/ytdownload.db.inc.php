<?php
/*****************************************************************************
* Copyright (c) 2015, Aron Heinecke
* All rights reserved.
* 
* Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
* 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
******************************************************************************/

class dbException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct ( $message, $code, $previous );
	}
	
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
class ytdownlDB extends dbException {
	private $db;
	private $CODE_WAITING = -1;
	private $downl_folder;
	
	/**
	 * Load config, open mysqli conection
	 * Write uid session var (-1 if non existent)
	 */
	public function __construct() {
		require 'includes/config.ytdownl.db.inc.php';
		$_access = getYTDOWNLConf();
		$this->downl_folder = $_access ['folder'];
		$this->db = new db_mysqli ( $_access ["host"], $_access ["user"], $_access ["pass"], $_access ["db"] );
		if(!isset($_SESSION['uid'])){
			$_SESSION['uid'] = $this->getUID();
		}
	}
	private function escapeData(&$data) {
		$data = $this->db->real_escape_string ( $data );
	}
	
	/**
	 * *
	 * Sets a current session variable
	 * 
	 * @param string $var        	
	 * @param unknown $val        	
	 * @param int $is_int
	 *        	bind as int, default is string
	 */
	private function setVariable($var, $val, $is_int) {
		$stmt = $this->db->prepare ( 'SET ' . $var . ' := ?' );
		if ($is_int)
			$stmt->bind_param ( 'i', $val );
		else
			$stmt->bind_param ( 's', $val );
		$result = $stmt->execute ();
	}
	
	/**
	 * *
	 * Defines an var usable for procedure outputs, no security checks!
	 * 
	 * @param unknown $var
	 *        	var name
	 * @param unknown $type
	 *        	type
	 */
	private function setOutputVar($var, $type) {
		$stmt = $this->db->query ( 'SET ' . $var . ' ' . $type . ';' );
	}
	public function addQueryEntry($link, $user, $type, $quality, $from, $to, $zip) {
		// set vars
		$this->setVariable ( '@yt_link', $link, 0 );
		$this->setVariable ( '@yt_user', $user, 0 );
		$this->setVariable ( '@yt_type', $type, 1 );
		$this->setVariable ( '@yt_quality', $quality, 1 );
		$this->setVariable ( '@yt_from', $from, 1 );
		$this->setVariable ( '@yt_to', $to, 1 );
		$this->setVariable ( '@yt_zip', $zip, 1 );
		
		// call procedure
		$result = $this->db->query ( 'CALL crQuery(@yt_link,@yt_user,@yt_type,@yt_quality,@yt_from,@yt_to,@yt_zip,@yt_output);' );
		if (! $result) {
			throw new dbException ( $this->db->error, '500' );
		}
		
		// select output
		$result = $this->db->query ( 'SELECT @yt_output as ytouput;' );
		if ($result) {
			$row = $result->fetch_assoc ();
			$return [0] = $link;
			$return [1] = $user;
			$return [2] = $this->translateStatus ( $this->CODE_WAITING, null );
			$return [3] = $this->quality2Name ( $quality );
			$return [4] = ( int ) $row ['ytouput'];
			$resutn [5] = '-';
			$result->close ();
		} else {
			throw new dbException ( $this->db->error, 500 );
		}
		return $return;
	}
	public function translateStatus($code, $status) {
		if ($code === -1) {
			return 'Waiting..';
		} else {
			return $status;
		}
	}
	public function deleteFile($fid) {
		if ($query = $this->db->prepare ( 'SELECT `name`,queries.uid FROM `files` INNER JOIN queries ON queries.qid = files.fid WHERE files.fid = ?' )) {
			$query->bind_param ( 'i', $fid );
			$query->execute ();
			$result = $query->get_result();
			if (! $result) {
				echo $this->db->error;
				throw new dbException ( $this->db->error,'500' );
			}
			if ($result->num_rows == 0) {
				throw new dbException ( '500' );
			}
			$row = $result->fetch_assoc ();
			$result->close();
// 			if($row['uid'] == $_SESSION['uid'] || hasPerm(PERM_ADMIN)){
				if ($query_2 = $this->db->prepare ( "UPDATE `files` SET `valid` = 0 WHERE `fid` = ?;" )) {
					$query_2->bind_param ( 'i', $fid );
					if (! $query_2->execute ()) {
						throw new dbException ( '500' );
					}
					unlink ( $this->downl_folder . '/' . $row['name'] );
				} else {
					throw new dbException ( '500' );
				}
// 			}else{
// 				throw Exception('permission error');
// 			}
		}else {
			throw new dbException ( '500' );
		}
	}
	
	function quality2Name($quality) {
		switch ($quality) {
			case -1 :
				return 'Music-mp3';
			case -2 :
				return 'Music-AAC';
			case -3 :
				return 'Music-AAC-HQ';
			case 303 :
			case 299 :
				return '1080@60fps';
			case 298 :
				return '720@60fps';
			case 137 :
			case 85 :
				return '1080p';
			case 136 :
			case 84 :
				return '720p';
			case 135 :
				return '480p';
			case 134 :
			case 82 :
				return '360p';
			case 133 :
			case 83 :
				return '240p';
			case -10:
				return 'Mobile';
			case -11:
				return 'Low';
			case -12:
				return 'Medium';
			case -13:
				return 'High';
			case -14:
				return 'Source';
			default :
				return 'error';
		}
	}
	
	private function getSQL($active){
		if($active){
			$op = '>=';
		}else{
			$op = '<=';
		}
		return 'SELECT queries.qid,`code`,`status`, progress,url,quality,name,queries.uid FROM querydetails
				INNER JOIN queries
				ON querydetails.qid = queries.qid
				INNER JOIN users
				ON queries.uid = users.uid
				WHERE luc '. $op .' ?
				ORDER BY luc DESC
				LIMIT 20';
	}
	
	public function getQueries($datetime_stamp,$show_updates) {
		if ($query = $this->db->prepare ( $this->getSQL($show_updates) )) { // Y-m-d G:i:s Y-m-d h:i:s
			$date = date ( 'Y-m-d H:i:s', $datetime_stamp );
			
			$query->bind_param ( 's', $date );
			$query->execute ();
			$result = $query->get_result ();
			
			if (! $result) {
				throw new dbException ( $this->db->error, 500 );
			}
			
			if ($result->num_rows == 0) {
				$resultset = null;
			} else {
				$resultset = array ();
// 				$is_admin = hasPerm(PERM_ADMIN);
				while ( $row = $result->fetch_assoc () ) {
					$resultset [] = [
// 							($row ['uid'] === $_SESSION["uid"] || $is_admin) ? $row['url'] : '',
							$row['url'],
							
							$row ['name'],
							$this->translateStatus ( $row ['code'], $row ['status'] ),
							$this->quality2Name ( $row ['quality'] ),
							$row ['qid'],
							$row ['progress'],
							$row ['code'] 
					];
				}
			}
			
			$result->close ();
			
			return $resultset;
		} else {
			throw new dbException ( '500' );
		}
	}
	
	private function getUID() {
		if ($query = $this->db->prepare ('SELECT uid FROM users WHERE name = ?')) { // Y-m-d G:i:s Y-m-d h:i:s
			$query->bind_param ( 's', $_SESSION['user'] );
			$query->execute ();
			$result = $query->get_result ();
				
			if (! $result) {
				throw new dbException ( $this->db->error, 500 );
			}
				
			if ($result->num_rows == 0) {
				$uid = -1;
			} else {
				$uid = $result->fetch_assoc()['uid'];
			}
				
			$result->close ();
				
			return $uid;
		} else {
			throw new dbException ( '500' );
		}
	}
	
	public function getFiles() {
		$query = 'SELECT files.name,rname,fid,queries.uid FROM `files`
				INNER JOIN queries
				ON files.fid = queries.qid
				WHERE files.valid = 1 order by `rname`';
		$result = $this->db->query ( $query );
		
		if (! $result) {
			throw new dbException ( '500' );
		}
		
		$array = array ();
		if ($result->num_rows == 0) {
			return $array;
		}
		
// 		$is_admin = hasPerm(PERM_ADMIN); // speed increase
		while ( $row = $result->fetch_assoc () ) {
// 			if($row['uid'] == $_SESSION['uid'] || $is_admin){
				$array [] = [ 
						rawurlencode($row ['name']),
						$row ['rname'],
						$row ['fid'] 
				];
// 			}
		}
		
		$result->close ();
		
		return $array;
	}
	
	/**
	 * DB Connector to search in the quey & file history for matching entries
	 * @param unknown $searchName bool, set to true to search in the file names
	 * @param unknown $input search input
	 * @throws dbException
	 * @return array of matches, null on error or exception
	 */
	public function searchHistory($searchName,$input) {
		$query = 'SELECT queries.qid, `code`, `status`, url, quality, files.rname FROM queries
				INNER JOIN querydetails
				ON queries.qid = querydetails.qid
				INNER JOIN files
				ON queries.qid = files.fid '
				. $searchName === true ? 'WHERE queries.url LIKE %?% ' : 'WHERE files.rname LIKE %?% '
				. 'AND queries.uid = ? '
				. 'ORDER BY luc DESC;';
		if($query = $this->db->prepare( $query )){
			$query->bind_param ( 'si', $input, $_SESSION['uid'] );
			$query->execute ();
			$result = $query->get_result ();
			
			if (! $result) {
				throw new dbException ( '500' );
			}
		
			if ($result->num_rows == 0) {
				throw new dbException ( '404' );
			}
		
			$array = array ();
			while ( $row = $result->fetch_assoc () ) {
				$array [] = [
						$row ['qid'],
						$row ['url'],
						$row ['rname'],
						$row ['code'],
						$row ['status'],
						$row ['quality'],
				];
			}
			
			$result->close ();
			$query->close();
			return $array;
		}
		return null;
	}
	
	public function getErrorDetails($qid) {
		if ($query = $this->db->prepare ('SELECT msg FROM querystatus WHERE qid = ?')) {
			$query->bind_param ( 'i', $qid );
			$query->execute ();
			$result = $query->get_result ();
			
			if (! $result) {
				throw new dbException ( $this->db->error, 500 );
			}
			
			if ($result->num_rows == 0) {
				$msg = 'Error: no details!';
			} else {
				$msg = nl2br($result->fetch_assoc()['msg']);
			}
	
			$result->close ();
	
			return $msg;
		} else {
			throw new dbException ( '500' );
		}
	}
	
	public function closeDB() {
		$this->db->close ();
	}
}