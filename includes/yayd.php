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

 
const REDIRECT_URL = 'https://www.youtube.com/redirect?q=';
const PATTERN_PLAYLIST = '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*playlist\\?list=[a-zA-Z0-9_-]+/';
const PATTERN_VID_PLAYLIST = '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*watch\\?v=[a-zA-Z0-9_-]+.*\\&list=([a-zA-Z0-9_-]+)/';
const PATTERN_VIDEO = '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*watch\\?v=[a-zA-Z0-9_-]+/';
const PATTERN_VIDEO_SHORT = '/\\Ahttps?:\\/\\/youtu\\.be\\/[a-zA-Z0-9]+/';
const PATTERN_TWITCH = '/\\A((https:\\/\\/|http:\\/\\/)|)((www.|m\\.))twitch\\.tv\\/.*[a-zA-Z0-9_-]+\\/v\\/[a-zA-Z0-9_-]+/';
const TYPE_PLAYLIST = 1;
const TYPE_YTVID = 0;
const TYPE_TWITCH = 2;
const TYPE_SOUNDCLOUD = 3;
const TIMEZONE = 'Europe/Berlin';
const PERM_ADMIN = 'yayd_admin';

const LUC = 'yayd_LUC';

// --- Functions to be implemented per use case ---

/**
 * Retrieve user ID, use a const if no multiuser support is desired
 * @return unknown
 */
function getUserID(){
	return -1;
}

/**
 * Check wether a user has the permissions or not
 * @return true if user has permission
 */
function checkPerm($perm){
	return true;
}

// --- General Code ---

/**
 * Content function
 */
//@Override
function getContent() {
	getYTTemplate();
}

/**
 * Template generator
 */
function getYTTemplate() {
	updateLUC(); ?>
	<div class="container">
		<div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header" style="margin-top: 0px;">YT-Downloader</h1>
					<h3>Version 0.6.3 Beta</h3>
                </div>
		</div>
		AAC-HQ requires 720p<br>
		Music-AAC is ~125 Kbps see <a href="http://soundexpert.org/encoders-128-kbps">here</a>
		<ul id="maintabs" class="nav nav-tabs" role="tablist" style="border-bottom: none;">
			<li class="active"><a href="#file" role="tab" data-toggle="tab"><i class="fa fa-file"></i> File</a></li>
			<li><a href="#playlist" role="tab" data-toggle="tab"><i class="fa fa-list"></i> Playlist</a></li>
		</ul>
		<div class="panel panel-default">
			<div class="panel-body">
			<div class="tab-content">
				<div id="file" role="tabpanel" class="tab-pane active">
					<div class="row">
						<div class="col-sm-4 col-xs-12 col-margin">
							<select id="ytFEncoded" class="form-control">
								<option value="-1">Music-mp3
								<option value="-2">Music-AAC
								<option value="-3">Music-AAC-HQ
								<option value="303">1080@60fps-webm
								<option value="298">720@60fps-mp4
								<option value="137">1080-MP4
								<option value="136">720-MP4
								<option value="135" selected>480-MP4
								<option value="134">360-MP4
								<option value="133">240-MP4
							</select>
						</div>
						<div class="col-sm-8 col-xs-12 col-margin">
							<input class="form-control" type="text" placeholder="https://www.youtube.com/watch?v=" id="ytdownllink" autocomplete="off" required autofocus>
						</div>
					</div>
					<div class="row">
							<div class="col-sm-4 col-xs-12 col-margin">
								<select id="twFEncoded" class="form-control">
									<option value="-14">Source
									<option value="-13" selected>High
									<option value="-12">Medium
									<option value="-11">Low
									<option value="-10">Mobile
								</select>
							</div>
							<div class="col-sm-8 col-xs-12 col-margin">
								<input class="form-control" type="text" placeholder="http://www.twitch.tv/channel/v/" id="twdownllink" autocomplete="off" required autofocus>
							</div>
					</div>
					<div class="row">
						<div class="col-sm-4 col-xs-12 col-margin">
						<button type="button" id="GYT" class="btn btn-default form-control" >
								<i class="fa fa-plus"></i> Add
						</button>
						</div>
						<div class="col-sm-3 col-xs-12 col-margin">
						<input type="button" id="vidClear" class="btn btn-default form-control" value="Clear">
						</div>
					</div>
					<div id="ytcont" class="row"></div>
				</div>
				<div id="playlist" role="tabpanel" class="tab-pane" >
					<!--<fieldset style="width: auto;text-align:center;margin-left: auto;margin-right: auto;">-->
						<div class="row">
							<div class="col-sm-4 col-xs-12 col-margin">
								<select id="pl_ytFEncoded" class="form-control">
									<option value="-1">Music-mp3
									<option value="-2">Music-AAC
									<option value="-3">Music-AAC-HQ
									<option value="303">1080@60fps-MP4
									<option value="298">720@60fps-MP4
									<option value="137">1080-MP4
									<option value="136">720-MP4
									<option value="135" selected>480-MP4
									<option value="134">360-MP4
									<option value="133">240-MP4
								</select>
							</div>
							<div class="col-sm-8 col-xs-12 col-margin">
								<input class="form-control" type="text" placeholder="https://www.youtube.com/playlist?list=" id="pl_ytdownllink" autocomplete="off" required autofocus>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-2 col-xs-12 col-margin">
								<div class="form-group">
									<label for="pl_from" class="col-sm-2">From</label>
									<input type="number" id="pl_from" value="-1" class="form-control col-sm-2" min="-1" step="1" data-bind="value:plFrom" />
								</div>
							</div>
							<div class="col-sm-2 col-xs-12 col-margin">
								<div class="form-group">
									<label for="pl_to" class="col-sm-2">To</label>
									<input type="number" id="pl_to" value="-1" class="form-control col-sm-2" min="-1" step="1" data-bind="value:plTo" />
								</div>
							</div>
							<div class="col-sm-2 col-xs-12 col-margin">
								<label>
									<input type="checkbox" id="pl_split"><i class="fa fa-compress"></i>split jobs
								</label>
							</div>
						</div>
						
						<div class="row">
							<div class="col-sm-4 col-xs-12 col-margin">
							<button type="button" id="pl_GYT" class="btn btn-default form-control">
								<i class="fa fa-plus"></i> Add
							</button>
							</div>
							<div class="col-sm-4 col-xs-12 col-margin">
							<input type="button" id="pl_ytClear" class="btn btn-default form-control" value="Clear">
							</div>
						</div>
						<div id="plcont" class="row"></div>
					<!--</fieldset>-->
				</div>
			</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-tasks"></i> Query list
			</div>
			<div class="panel-body" style="padding: 0px;">
				<fieldset style="width: auto;text-align:center;margin-left: auto;margin-right: auto; max-height: 250px; overflow-y: scroll; overflow-x: hidden;">
					<div id="requestList">
						<table id="querytable" class="table table-striped table-bordered" style="margin-bottom: 0px;">
							<tbody>
							</tbody>
						</table>
					</div>
				</fieldset>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-download"></i> File list
			</div>
			<div class="panel-body" style="padding: 0px;">
				<fieldset style="width: auto;text-align:center;margin-left: auto;margin-right: auto;">
					<div id="filelist" ><!-- style="overflow-y: hidden !important; overflow-x: hidden !important; width: 100%;"  -->
						<table id="filetable" class="table" style="margin-bottom: 0px;" >
							<tbody>
							</tbody>
						</table>
					</div>
				</fieldset>
			</div>
			<div class="panel-footer">
				<span class="ttInfo" data-toggle="tooltip" title="Right-click on the link -> Save target as" data-placement="top"><i class="fa fa-info"></i> Save instructions</span>
			</div>
		</div>
	</div>
	<div class="modal fade" id="errDialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Error details</h4>
	      </div>
	      <div class="modal-body" id="errDiaBody">
	        
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
<?php }

/**
 * Ajax request handler function
 */
//@Override
function getAjax(){
	require 'includes/yayd.db.inc.php';
	$ytdb = new \yayd\yaydDB();
	switch($_POST['ajaxCont']){
		case 'querylist':
			date_default_timezone_set(TIMEZONE);
			echo json_encode($ytdb->getQueries(strtotime("now"),false,getUserID()));
			break;
		case 'addquery':
			$Vurl = null;
			$Vurl = verifyUrl($_POST['url'],$_POST['type']);
			
			if($Vurl === null){
				http_response_code(403);
				echo 'Invalid URL!';
			}else{
				$Vurl = str_replace("\"", "", $Vurl);
				$Vurl = str_replace("\\", "", $Vurl);
				$Vurl = str_replace("'", "", $Vurl);
				$qID = $ytdb->addQueryEntry($Vurl, getUserID(),$_POST['quality'], $_POST['from'],$_POST['to'],$_POST['split'],0);
				if($qID > 0 ){
					$return = array();
					$return[\yayd\C_URL] = $Vurl;
					$return[\yayd\C_Status] = \yayd\yaydDB::translateStatus(\yayd\CODE_WAITING,null);
					$return[\yayd\C_Quality] = \yayd\yaydDB::quality2Name($_POST['quality']);
					$return[\yayd\C_QID] = ( int ) $qID;
					$return[\yayd\C_Progress] = '-';
					$return[\yayd\C_Code] = null;
					$return[\yayd\C_Name] = null;
					echo json_encode($return);
				}
			}
			break;
		case 'LCQueries':
			echo json_encode($ytdb->getQueries($_SESSION[LUC],true,getUserID()));
			updateLUC();
			break;
		case 'get_files':
			echo json_encode($ytdb->getFiles(getUserID()));
			break;
		case 'delete_file':
			$ytdb->deleteFile($_POST['fid'],getUserID());
			break;
		case 'clearCache':
			global $vidfolder;
			if(clearfolder($vidfolder)){
				echo 'Cache cleared.';
			}else{
				echo 'Error by clearing cache!';
			}
			break;
		case 'errorDetails':
			echo $ytdb->getErrorDetails($_POST['qid']);
			break;
		default:
			http_response_code(404);
			echo 'Case not found!';
			break;
	}
}

/**
 * Update last update checked value
 */
function updateLUC(){
	date_default_timezone_set(TIMEZONE);
	$_SESSION[LUC] = strtotime("now");
}

/**
 * Verify an url
 * @param unknown $input input url
 * @param unknown $type specified type
 * @return string|NULL null on verification failure, otherwise the sanitized url
 */
function verifyUrl($input, $type){ // returns null when the verification fails
	$input = str_replace('feature=player_embedded&','',$input);
	if(strpos($input,REDIRECT_URL) !== false){
		$input = str_replace(REDIRECT_URL, '', $input);
		$input = urldecode($input);
	}
	switch($type){
	case TYPE_PLAYLIST:
		// \A((https:\/\/|http:\/\/)|)((www\.|m\.)|)youtube\.(com|de)\/.*playlist\?list=[a-zA-Z0-9_-]+
		// \A((https:\/\/|http:\/\/)|)(www\.|m\.)youtube\.(com|de)\/.*watch\?v=[a-zA-Z0-9_-]+.*\&list=([a-zA-Z0-9_-]+)
		if(preg_match(PATTERN_PLAYLIST, $input, $matches ) === 1){
			return $matches[0];
		}
		if(preg_match(PATTERN_VID_PLAYLIST, $input, $matches ) === 1){
			return 'https://www.youtube.com/playlist?list=' . $matches[6] ;
		}
		break;
	case TYPE_YTVID:
		// regex101.com \A((https:\/\/|http:\/\/)|)((www.|m\.))youtube\.(com|de)\/.*watch\?v=[a-zA-Z0-9_-]+
		// \Ahttps?:\/\/youtu\.be\/[a-zA-Z0-9]+
		// \A((https:\/\/|http:\/\/)|)((www.|m\.))twitch\.tv\/.*[a-zA-Z0-9_-]+\/v\/[a-zA-Z0-9_-]+
		if(preg_match(PATTERN_VIDEO, $input, $matches ) === 1){
			return $matches[0];
		}
		if(preg_match(PATTERN_VIDEO_SHORT, $input, $matches ) === 1){
			return $matches[0];
		}
		break;
	case TYPE_TWITCH:
		if(preg_match(PATTERN_TWITCH, $input, $matches) === 1){
			return $matches[0];
		}
		break;
	default:
	}
	return null;
}

//@Override
function getTitle() {
	return 'YT-Downloader';
}

/**
 * Header content
 */
//@Override
function getHead() {?>
	<script src="js/jquery-ui-1.11.1.min.js" type="text/javascript"></script>
	<link rel="Stylesheet" media="all" type="text/css" href="css/jquery-ui-1.11.1.min.css">
	<script src="js/jquery.tablesorter.min.js" type="text/javascript"></script>
	<script src="js/yayd.js" type="text/javascript"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
	<style>
	.progress {
	    position: absolute;
	    left: -100%;
	    width: 100%;
	    height: 0px;
	    z-index: -1;    
	    background: #8F8;    
	    transition:left 1s;
	}
	.tdErrbtn {
		background-color: #D8D8D8;
	}
	.tdErrbtn:hover {
		background-color: #E8E8E8;
	}
	.tdErrbtn:active {
		background-color: #E0E0E0;
	}
	.col-margin {
		margin-bottom: 1em;
	}
	
	</style>
<?php }