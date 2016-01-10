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
define('REDIRECT_URL', 'https://www.youtube.com/redirect?q=');
define('PATTERN_PLAYLIST', '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*playlist\\?list=[a-zA-Z0-9_-]+/');
define('PATTERN_VID_PLAYLIST', '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*watch\\?v=[a-zA-Z0-9_-]+.*\\&list=([a-zA-Z0-9_-]+)/');
define('PATTERN_VIDEO', '/\\A((https:\\/\\/|http:\\/\\/)|)((www\\.|m\\.)|)youtube\\.(com|de)\\/.*watch\\?v=[a-zA-Z0-9_-]+/');
define('PATTERN_VIDEO_SHORT', '/\\Ahttps?:\\/\\/youtu\\.be\\/[a-zA-Z0-9]+/');
define('PATTERN_TWITCH', '/\\A((https:\\/\\/|http:\\/\\/)|)((www.|m\\.))twitch\\.tv\\/.*[a-zA-Z0-9_-]+\\/v\\/[a-zA-Z0-9_-]+/');
define('TYPE_PLAYLIST', 1);
define('TYPE_YTVID', 0);
define('TYPE_TWITCH', 2);
define('TYPE_SOUNDCLOUD', 3);
define('TIMEZONE', 'Europe/Berlin');

//@Override
function getContent() {
	getYTTemplate();
}

function getYTTemplate() {
	updateLUC(); ?>
	<div class="container">
		<div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header" style="margin-top: 0px;">YT-Downloader</h1>
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
					<fieldset style="width: auto;text-align:center;margin-left: auto;margin-right: auto;">
						<div class="form-group input-group">
							<span class="input-group-addon">
								<select id="ytFEncoded">
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
							</span>
							<input class="form-control" type="text" placeholder="https://www.youtube.com/watch?v=" id="ytdownllink" autocomplete="off" required autofocus>
						</div>
						<div class="form-group input-group">
							<span class="input-group-addon">
								<select id="twFEncoded">
									<option value="-14">Source
									<option value="-13" selected>High
									<option value="-12">Medium
									<option value="-11">Low
									<option value="-10">Mobile
								</select>
							</span>
							<input class="form-control" type="text" placeholder="http://www.twitch.tv/canal/v/" id="twdownllink" autocomplete="off" required autofocus>
						</div>
						<button type="button" id="GYT" class="btn btn-default" >
							<i class="fa fa-plus"></i> Add
						</button>
						<input type="button" id="vidClear" class="btn btn-default" value="Clear">
						<div id="ytcont" style="margin-top: 10px;"></div>
					</fieldset>
				</div>
				<div id="playlist" role="tabpanel" class="tab-pane" >
					<fieldset style="width: auto;text-align:center;margin-left: auto;margin-right: auto;">
						<div class="form-group input-group">
							<span class="input-group-addon">
								<select id="pl_ytFEncoded">
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
							</span>
							<input class="form-control" type="text" placeholder="https://www.youtube.com/playlist?list=" id="pl_ytdownllink" autocomplete="off" required autofocus>
						</div>
						<div class="form-group input-group">
<!-- 						<div class="form-group input-group"> -->
						<div class="form-group col-sm-3">
							<label for="pl_from">From</label>
							<input type="number" id="pl_from" value="-1" class="form-control col-sm-2" min="-1" step="1" data-bind="value:plFrom" />
						</div>
<!-- 						<div class="form-group input-group"> -->
						<div class="form-group col-sm-3">
							<label for="pl_to">To</label>
							<input type="number" id="pl_to" value="-1" class="form-control col-sm-2" min="-1" step="1" data-bind="value:plTo" />
						</div>
						<div class="col-sm-3 checkbox">
								<input type="checkbox" id="pl_zip" disabled><i class="fa fa-compress"></i> zip
							</div>
						</div>
						
						<div class="form-group">
						<button type="button" id="pl_GYT" class="btn btn-default">
							<i class="fa fa-plus"></i> Add
						</button>
						<input type="button" id="pl_ytClear" class="btn btn-default" value="Clear">
						</div>
						<div id="plcont" style="margin-top: 10px;"></div>
					</fieldset>
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
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
<?php }

//@Override
function getAjax(){
	require 'includes/ytdownload.db.inc.php';
	$ytdb = new ytdownlDB();
	switch($_POST['ajaxCont']){
		case 'querylist':
			date_default_timezone_set(TIMEZONE);
			echo json_encode($ytdb->getQueries(strtotime("now"),false));
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
				echo json_encode($ytdb->addQueryEntry($Vurl, $_SESSION["user"], $_POST['type'], $_POST['quality'], $_POST['from'],$_POST['to'],$_POST['zip']));
			}
			break;
		case 'LCQueries':
			echo json_encode($ytdb->getQueries($_SESSION['ytdownl_luc'],true));
			updateLUC();
			break;
		case 'get_files':
			echo json_encode($ytdb->getFiles());
			break;
		case 'delete_file':
			$ytdb->deleteFile($_POST['fid']);
			break;
		case 'errorDetails':
			echo $ytdb->getErrorDetails($_POST['qid']);
			break;
		default:
			http_response_code(404);
			echo 'Case not found!';
			break;
	}
	$ytdb->closeDB();
}

function updateLUC(){
	date_default_timezone_set(TIMEZONE);
	$_SESSION['ytdownl_luc'] = strtotime("now");
}

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
function getHead() {?>
	<script src="js/jquery-ui-1.11.1.min.js" type="text/javascript"></script>
	<link rel="Stylesheet" media="all" type="text/css" href="css/jquery-ui-1.11.1.min.css">
	<script src="js/jquery.tablesorter.min.js" type="text/javascript"></script>
	<script src="js/ytdownloader.js" type="text/javascript"></script>
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
	</style>
<?php }

function fileTypeMatch($input){ // returns true by mp3,mp4,flv,m4a
	// \A(mp4|m4a|mp3|flv)\Z
	return preg_match('/\\A(mp4|m4a|mp3|flv)\\Z/', $input) === 1;
}