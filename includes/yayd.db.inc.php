<?php
 - /*****************************************************************************
 - * Copyright (c) 2015, Aron Heinecke
 - * All rights reserved.
 - * 
 - * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 - * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 - * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 - * 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 - * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 - ******************************************************************************/

namespace yayd;

const C_URL = 'url';
const C_Status = 'status';
const C_Quality = 'quality';
const C_QID = 'qid';
const C_Progress = 'progr';
const C_Code = 'code';
const C_Name = 'name';

const C_FILE_Name = 'name';
const C_FILE_RName = 'rname';
const C_FILE_FID = 'fid';

const CODE_WAITING = -1;
class dbException extends \Exception {
	// Redefine the exception so message isn't optional
	public function __construct($message, $code = 0, Exception $previous = null) {
		// make sure everything is assigned properly
		parent::__construct ( $message, $code, $previous );
	}
	
	// custom string representation of object
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
	public function customFunction() {
		echo "A custom function for this type of exception\n";
	}
}
class yaydDB extends dbException {
	private $db;
	private $DEFAULT_STATE;
	
	/**
	 * Load config, open mysqli conection
	 * Write uid session var (-1 if non existent)
	 */
	public function __construct() {
		require 'includes/config.yayd.db.inc.php';
		$_access = getYTDOWNLConf();
		$this->DEFAULT_STATE = $_access['DEFAULT_STATE'];
		$this->db = new \mysqli ( $_access ["host"], $_access ["user"], $_access ["pass"], $_access ["db"] );
	}
	
	/**
	 * Handle error
	 * @throws DBException
	 */
	private function handleError(){
		$dberr = $this->db->error;
		if($dberr == '' ){
			throw new DBException('Exception at execution!');
		}else{
			throw new DBException('Database exception at execution! '.$dberr);
		}
	}
	
	/**
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
	
	/**
	 * Add entry to db
	 * @param unknown $link
	 * @param unknown $uID
	 * @param unknown $quality
	 * @param unknown $from
	 * @param unknown $to
	 * @param unknown $split
	 * @param unknown $type
	 * @return number > 0 on success, containing the new qID
	 */
	public function addQueryEntry($link,$uID,$quality,$from,$to,$split,$type){
		if ($query = $this->db->prepare ( 'INSERT INTO `queries` (`url`,`quality`,`type`,`created`,`uid`) VALUES (?,?,?,now(),?)') ) {
			$query->bind_param ( 'siii', $link,$quality,$type,$uID );
			if($query->execute()){
				if($query->affected_rows === 1){
					$newID = $query->insert_id;
					if($this->insertDetailsEntry($newID)){
						if($from == -2 && $to == -2){
							return $query->insert_id;
						}else{
							if($this->insertPlaylistEntry($query->insert_id,$from,$to,$split)){
								return $query->insert_id;
							}else{
								return -1;
							}
						}
					}else{
						return -1;
					}
				}
			}
		}
		$this->handleError();
	}
	
	/**
	 * Insert playlist table entry
	 * @param unknown $qID
	 * @param unknown $from
	 * @param unknown $to
	 * @param unknown $split
	 * @return boolean true on success
	 */
	private function insertPlaylistEntry($qID,$from,$to,$split){
		if ($query = $this->db->prepare ( 'INSERT INTO `playlists` (`qid`,`from`,`to`,`split`) VALUES (?,?,?,?)') ) {
			$query->bind_param ( 'iiii', $qID,$from,$to,$split );
			if($query->execute()){
				if($query->affected_rows === 1){
					return true;
				}else{
					return false;
				}
			}
		}
		$this->handleError();
	}
	
	/**
	 * Insert entry into querydetails table
	 * @param unknown $qID
	 * @return boolean true on success
	 */
	private function insertDetailsEntry($qID){
		if ($query = $this->db->prepare ( 'INSERT INTO `querydetails` (`qid`,`code`,`status`) VALUES (?,?,?)') ) {
			$code_waiting = CODE_WAITING;
			$query->bind_param ( 'iis', $qID,$code_waiting,$this->DEFAULT_STATE );
			if($query->execute()){
				if($query->affected_rows === 1){
					return true;
				}else{
					return false;
				}
			}
		}
		$this->handleError();
	}
	
	/**
	 * Translate code & status to an status
	 * @param unknown $code
	 * @param unknown $status
	 * @return string|unknown
	 */
	public static function translateStatus($code, $status) {
		if ($code === -1) {
			return 'Waiting..';
		} else {
			return $status;
		}
	}
	
	/**
	 * Mark file as to be deleted
	 * @param int $fid
	 * @param int $uID
	 * @throws dbException on errors or permissions errors
	 */
	public function deleteFile($fid,$uID) {
		if ($query = $this->db->prepare ( 'SELECT `queries`.`uid` FROM `query_files`
				JOIN queries ON query_files.qid = queries.qid
				WHERE query_files.fid = ?' )) {
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
			if($result->fetch_assoc()['uid'] == $uID || checkPerm(PERM_ADMIN)){
				if ($query_2 = $this->db->prepare ( "UPDATE `files` SET `delete` = 1 WHERE `fid` = ?;" )) {
					$query_2->bind_param ( 'i', $fid );
					if (! $query_2->execute ()) {
						throw new dbException ( '500' );
					}
				} else {
					throw new dbException ( '500' );
				}
			}else{
				throw Exception('permission error');
			}
		}else {
			throw new dbException ( '500' );
		}
	}
	
	/**
	 * Translate quality to string
	 * @param int $quality
	 * @return string
	 */
	public static function quality2Name($quality) {
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
	
	/**
	 * Generate getQueries SQL String
	 * @param bool $active
	 * @return string
	 */
	private function getSQL($active){
		if($active){
			$op = '>=';
		}else{
			$op = '<=';
		}
		return 'SELECT `queries`.`qid`,`code`,`status`, `progress`,`url`,`quality`,`files`.`rname` as fileName FROM querydetails
				INNER JOIN queries
				ON querydetails.qid = queries.qid
				LEFT JOIN files
				ON querydetails.qid = files.fid
				WHERE luc '. $op .' ? AND uid = ?
				ORDER BY luc DESC
				LIMIT 20';
	}
	
	/**
	 * Get queries (changes)
	 * @param date $datetime_stamp 
	 * @param bool $show_updates if true $datetime_stamp will be used to retrieve only changes
	 * @param int $uID
	 * @throws dbException
	 * @return string[][]|\yayd\unknown[][]|unknown[][]
	 */
	public function getQueries($datetime_stamp,$show_updates,$uID) {
		if ($query = $this->db->prepare ( $this->getSQL($show_updates) )) { // Y-m-d G:i:s Y-m-d h:i:s
			$date = date ( 'Y-m-d H:i:s', $datetime_stamp );
			
			$query->bind_param ( 'si', $date,$uID );
			$query->execute ();
			$result = $query->get_result ();
			
			if (! $result) {
				throw new dbException ( $this->db->error, 500 );
			}
			
			if ($result->num_rows == 0) {
				$resultset = null;
			} else {
				$resultset = array ();
				while ( $row = $result->fetch_assoc () ) {
					$temparray = array();
					$temparray[C_URL] = $row['url'];
					$temparray[C_Status] = $this->translateStatus ( $row ['code'], $row ['status'] );
					$temparray[C_Quality] = $this->quality2Name ( $row ['quality'] );
					$temparray[C_QID] = $row ['qid'];
					$temparray[C_Progress] = $row ['progress'];
					$temparray[C_Code] = $row ['code'];
					$temparray[C_Name] = $row ['fileName'];
					$resultset[] = $temparray;
				}
			}
			
			$result->close ();
			
			return $resultset;
		} else {
			throw new dbException ( $this->db->error.'500' );
		}
	}
	
	/***
	 * Retrieve valid files, according to the user permissions
	 * @throws dbException
	 * @return string[][]|NULL
	 */
	public function getFiles($uID) {
		$user_sql = checkPerm(PERM_ADMIN) ? '' : 'AND queries.uid = ? ';
		$query = 'SELECT files.name,files.rname,files.fid,queries.uid FROM `query_files` 
				INNER JOIN queries 
				ON queries.qid = query_files.qid 
				INNER JOIN files
				ON files.fid = query_files.fid
				WHERE files.valid = 1 AND files.delete = 0 ' . $user_sql .
				'order by `rname`';
		if($query = $this->db->prepare( $query )){
			if ($user_sql !== '') {
				$query->bind_param ( 'i', $uID );
			}
			$query->execute ();
			$result = $query->get_result ();
			
			if (! $result) {
				throw new dbException ( '500' );
			}
			
			$array = array ();
			if ($result->num_rows == 0) {
				return $array;
			}
			
			$is_admin = checkPerm(PERM_ADMIN); // speed increase
			while ( $row = $result->fetch_assoc () ) {
				$temparray = array();
				$temparray[C_FILE_Name] = $this->customUrlEncode($row['name']);
				$temparray[C_FILE_RName] = $row['rname'];
				$temparray[C_FILE_FID] = $row['fid'];
				$array[] = $temparray;
			}
			
			$result->close ();
			
			return $array;
		}else{
			throw new dbException('500 '.$this->db->error);
		}
		return null;
	}
	
	private function customUrlEncode($input){
		//$out = str_replace('#','%23',$input);
		$out = rawurlencode($input);
		/*$out = str_replace('+','%20',$out);
		$out = str_replace('_','%5F',$out);
		$out = str_replace('.','%2E',$out);
		$out = str_replace('-','%2D',$out);*/
		return $out;
	}
	
	/**
	 * DB Connector to search in the quey & file history for matching entries
	 * @param unknown $searchName bool, set to true to search in the file names
	 * @param unknown $input search input
	 * @throws dbException
	 * @return array of matches, null on error or exception
	 */
	public function searchHistory($searchName,$input,$uID) {
		$query = 'SELECT queries.qid, `code`, `status`, url, quality, files.rname FROM queries ';
		$query .= 'INNER JOIN querydetails ';
		$query .= 'ON queries.qid = querydetails.qid ';
		$query .= 'INNER JOIN files ';
		$query .= 'ON queries.qid = files.fid ';
		$query .= $searchName === true ? 'WHERE queries.url LIKE %?% ' : 'WHERE files.rname LIKE %?% ';
		$query .= 'AND queries.uid = ? ';
		$query .= 'ORDER BY luc DESC;';
		if($query = $this->db->prepare( $query )){
			$query->bind_param ( 'si', $input, $uID );
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
	
	/**
	 * Retrieve error for qID in table queryerror
	 * @param int $qid
	 * @throws dbException
	 * @return string
	 */
	public function getErrorDetails($qid) {
		if ($query = $this->db->prepare ('SELECT msg FROM queryerror WHERE qid = ?')) {
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
	
	public function __destruct() {
		$this->db->close ();
	}
}