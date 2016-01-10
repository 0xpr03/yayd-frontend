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

/*http://jsoptimizer.com/
 * Get query list, date sorted
 * actualize all with last-change date > last request date
 * 
 * hints:
 * let the server return list -> new 1/0 based on sended date
 * [current sol.] let the client handle the date comparison
 * let the server send only the rows changed since the client date
 * -> spare the rest
 * 
 * #filelist -> div: file list
 * #querylist -> div: query list
 * #filelistTBL -> table: file list
 * #querylistTBL -> table: file list
 * 
 * the lists are generated with a table
 * each tr does have the request / file id as tr id
 * -> <tr id="fid5">.. => is the tr containting the query details for
 * query nr. 5 in the DB
 * 
 * fid + NR -> file id
 * qry + NR -> query id
 */


var lastQueryUpate;
const FILELIST_TBL = '#filetable';
const TOOLTIP_CLASS = '.ttInfo';
var ownQueries = [];
var timer;
const DOWNLOAD_LINK = 'http://my.domain.com/vids/'; // CHANGE this !
const QUERYLIST_TBL = '#querytable';
const CODE_WAITING = -1;
const CODE_FINISHED = 2;
const CODE_FINISHED_WARNING = 3;
const CODE_FAILED = 10;
const CODE_ERROR_QUALITY = 11;
const CODE_ERROR_SOURCE = 12;
const default_timer = 3500;
const slow_timer = 10000;
const fast_timer = 900;
const TYPE_YTVID = 0;
const TYPE_YTPL = 1;
const TYPE_TWITCH = 2;
var idle_counter = 0;
var current_mode = 0; //0 normal, 1 speed, -1 slow

$(document).ready(function() {
	$('#maintabs a').click(function (e) {
		  e.preventDefault();
		  $(this).tab('show');
		  console.log($(e.target).attr('href'));
		  if($(e.target).attr('href') == '#playlist')
			  $( '#pl_ytdownllink' ).focus();
		  else
			  $( '#ytdownllink' ).focus();
	});
	$("#table").tablesorter();
	generateQueryList();
	generateFileList();
	setUpdateTimer(default_timer);
});

$(document).ready(function() {
	$('input[id=pl_zip]').attr('checked', true); // atm not implemented
	$( "#ytFEncoded" ).show(0);
	$( "#ytFOriginal" ).hide(0);

	$("#ytdownllink, #twdownllink").keyup(function(event){
	    if(event.keyCode == 13){
	        $("#GYT").click();
	    }
	});
	$("#ytdownllink").change(function(){
		$("#twdownllink").val("");
	});
	$("#twdownllink").change(function(){
		$("#ytdownllink").val("");
	});
	
	$("#vidClear").click(function() {
		$("#ytdownllink").val("");
		$("#twdownllink").val("");
	});
	
	$("#GYT").click(function() {
		$('#ytcont').hide();
		var yt_link = $("#ytdownllink").val();
		var tw_link = $("#twdownllink").val();
		if( yt_link !== '' || tw_link !== '' ){
			if(tw_link !== ''){
				var qual = $("#twFEncoded").val();
				var type = TYPE_TWITCH;
			}else{
				var qual = $("#ytFEncoded").val();
				var type = TYPE_YTVID;
			}
			$('#ytcont').show();
			$('#ytcont').html('<p><img src="css/images/ajax-loader.gif" width="32px" height="32px" /></p>');
			
			var query = addQueryVid(type == 2 ? tw_link : yt_link, qual, type);
			$.when(query).then(function(result){
				ownQueries.push(result[4]);
				current_mode = 1;
				setUpdateTimer(900);
				
				$('#ytcont').html('added');
				$("#ytdownllink").val('');
			}).fail(function(result){
				$('#ytcont').html('failed! '+result.responseText);
			});
		}
	});
	$("#pl_GYT").click(function() {
		$('#plcont').hide();
		var link = $("#pl_ytdownllink").val();
		if( link !== ''){
			var ytqual = $("#pl_ytFEncoded").val();
			var from = $("#pl_from").val();
			var to = $("#pl_to").val();
			$('#plcont').show();
			$('#plcont').html('<p><img src="css/images/ajax-loader.gif" width="32px" height="32px" /></p>');
			
			var query = addQueryPlaylist(link, ytqual,from, to, $("#pl_zip").is(':checked') ? 1 : 0);
			$.when(query).then(function(result){
				ownQueries.push(result[4]);
				current_mode = 1;
				setUpdateTimer(900);
				
				$('#plcont').html('added');
				$("#pl_ytdownllink").val('');
			}).fail(function(result){
				$('#plcont').html('failed! '+result.responseText);
			});
		}
	});
});

function setUpdateTimer(ms){
	if(timer != null)
		clearTimeout(timer);
	timer = setInterval(function() {
		checkQueryUpdate();
	},ms);
}

/**
 * First function after site load
 * Generates the list with current queries
 */
function generateQueryList(){
	$.when(loadQueryList()).then(function(result){
		$(QUERYLIST_TBL+' tr').remove();
		result.reverse(); 
		$.each(result, function(index, value){
			qtableAddElement(false,QUERYLIST_TBL, value[0], value[1], value[2], value[3], value[4], value[5], value[6]);
		});
		initializeTooltip();
	}).fail(function(result){
		console.log(result.responseText);
	});
}

function initializeTooltip(){
	$(TOOLTIP_CLASS).tooltip();
}

/***
 * checks for query updates and updates the query list
 * if necessary also the file list
 * @returns
 */
function checkQueryUpdate() {
	$.when(loadQueryListUpdate()).then(function(result){
		if(result == null){
			if(current_mode == 0){
				if(ownQueries.length == 0){
					idle_counter++;
				}
				if(idle_counter >= 5){
					current_mode = -1;
					setUpdateTimer(slow_timer);
				}
			}
		}else{
			updateQueryList(result);
			initializeTooltip();
		}
		
	}).fail(function(result){
		console.log(result.responseText);
	});
}

/***
 * updates missing changes & adds missing rows
 */
function updateQueryList(array){
	var changed_filelist = false;
	var elem_id_temp = -1;
	$.each(array, function( index, value ) {
		if($('#qe'+value[4]).length){
			
			if(value[6] == CODE_FINISHED || value[6] == CODE_FINISHED_WARNING ){
				changed_filelist = true;
				elem_id_temp = $.inArray(value[4], ownQueries);
				if(elem_id_temp != -1){
					ownQueries.splice(elem_id_temp, 1);
				}
			}
			
			qtableUpdateElement(QUERYLIST_TBL,value[0], value[1], value[2], value[3], value[4], value[5], value[6]);
		}else{
			qtableAddElement(true,QUERYLIST_TBL,value[0], value[1], value[2], value[3], value[4], value[5], value[6]);
		}
	});
	if(changed_filelist){
		generateFileList();
	}
	
	//dynamic idle setter
	if(ownQueries.length == 0){
		if(current_mode == 1){
			setUpdateTimer(default_timer);
			current_mode = 0;
		}
	}
	idle_counter = 0;
}

function generateFileList(){
	$.when(loadFileList()).then(function(result){
		$(FILELIST_TBL+' tr').remove();
		$.each(result, function(index, value){
			ftableAddElement(value[0],value[1],value[2]);
		});
	}).fail(function(result){
		console.log(result.responseText);
	});
}

function compareLUC(date){
	return date > lastQueryUpate;
}

function fileID(id){
	return 'fid'+id;
}

function queryID(id){
	return 'qry'+id;
}

function deleteFile(fid){
	$.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "text",
		data: {
			'site' : 'ytdownl',
			'ajaxCont' : 'delete_file',
			'fid' : fid,
		}
	}).done(function(data){
		console.debug("deleted file");
		ftableDeleteElement(fid);
	}).fail(function(data){
		console.log("Failed deleting file");
		console.log(data);
	});
}

function showWarningWindow(qid){
	$('#errDiaBody').html('loading');
	$('#errDialog').modal({ show: true});
	$.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "text",
		data: {
			'site' : 'ytdownl',
			'ajaxCont' : 'errorDetails',
			'qid' : qid,
		}
	}).done(function(data){
		$('#errDiaBody').html(data);
	}).fail(function(data){
		console.log("Failed loading error details:");
		console.log(data);
	});
}

/***
 * Add an query entry which isn't a playlist
 * @param url
 * @param quality
 */
function addQueryVid(url, quality, type) {
	return addQuery(url, type, quality, -1, -1, false);
}

function addQueryPlaylist(url, quality, from, to, zip){
	return addQuery(url, TYPE_YTPL, quality, from, to, zip);
}

/***
 * Add an query entry
 * @param url
 * @param quality
 */
function addQuery(url, type, quality, from, to, zip) {
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "json",
		data: {
			'site' : 'ytdownl',
			'ajaxCont' : 'addquery',
			'url' : url,
			'quality' : quality,
			'type' : type,
			'from' : from,
			'to' : to,
			'zip' : zip,
		},
	}).done(function(data){
		console.debug("added query");
		qtableAddElement(true,QUERYLIST_TBL,data[0],data[1],data[2],data[3],data[4],data[5], CODE_WAITING);
	}).fail(function(data){
		console.log(data);
	});
}

/**
 * Load the current filelist
 */
function loadFileList(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: 'json',
		data: {'site' : 'ytdownl', 'ajaxCont' : 'get_files' },
	});
}

/***
 * Load the query list
 * @returns data
 */
function loadQueryList(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: 'json',
		data: {'site' : 'ytdownl', 'ajaxCont' : 'querylist' },
	});
}

/***
 * load the updates for the query list since the last check
 * @returns data
 */
function loadQueryListUpdate(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "json",
		data: {'site' : 'ytdownl', 'ajaxCont' : 'LCQueries' },
	});
}

/***
 * Generates a data chunk for the tableAddElement, bases on the query list row
 * @param data data row from the querylist data
 * @returns data for usage in tableAddElement
 */
function readQueryRowData(data){
	return '<td>'+data.id+'</td><td>'+data.url+'</td><td>'+data.status+'</td>';
}

/**
 * Add file row to filetable
 * @param name
 * @param rname
 * @param id
 * @returns
 */
function ftableAddElement(name,rname,id){
	$('<tr id="f'+id+'"><td><a href="'+DOWNLOAD_LINK+name+'" download="'+rname+'">'+rname+'</a></td><td>'+generateDeleteButton(id)+'</td></tr>').prependTo(FILELIST_TBL+" > tbody");
}

function generateDeleteButton(id){
	return '<a href="#" onclick="deleteFile('+id+'); return false;"><i class="fa fa-trash-o fa-lg"></i> Delete</a>';
}

function ftableDeleteElement(id){
	$('#f'+id).fadeToggle("slow", function() {
		$('#f'+id).remove();
	});
}

/***
 * Adds an element, fading it in
 * @param id table id with #
 * @param data data to insert, including the TD
 */
function qtableAddElement(FADE_IN,tableid, url, user, status,quality, trid, progress, code){
	if (FADE_IN) $('<tr style="display: none;" id="qe'+trid+'">'+trInnerGenerator(trid, url,user,status,quality,progress, code)).prependTo(tableid+" > tbody").fadeToggle("slow");
	else
		$('<tr id="qe'+trid+'">'+trInnerGenerator(trid, url,user,status,quality,progress, code)).prependTo(tableid+" > tbody");
}

function qtableUpdateElement(tableid,url,user,status,quality,trid, progress, code){
	$('#qe'+trid).html(trInnerGenerator(trid, url,user,status,quality,progress,code));
}

/**
 * query list TR-content generator
 * @param url
 * @param user
 * @param status
 * @param quality
 * @param progress
 * @param code
 * @returns {String} with TD-elements
 */
function trInnerGenerator(id,url, user, status,quality,progress, code){
	if (progress == null){
		progress = '?';
	}
	var statusCell;
	switch (code){
	case CODE_FAILED:
		statusCell = '<td onclick="showWarningWindow('+id+')" title="click for details" class="tdErrbtn" ><i class="fa fa-exclamation-circle"></i>';
		break;
	case CODE_FINISHED_WARNING:
		statusCell = '<td onclick="showWarningWindow('+id+')" title="click for details" class="tdErrbtn" ><i class="fa fa-check"><i class="fa fa-exclamation-triangle"></i></i>';
		break;
	case CODE_ERROR_QUALITY:
		statusCell = '<td><div class="ttInfo" data-toggle="tooltip" title="Quality not available!" data-placement="top"><i class="fa fa-exclamation-circle"></i></div>';
		break;
	case CODE_ERROR_SOURCE:
		statusCell = '<td><div class="ttInfo" data-toggle="tooltip" title="Private / Deleted source!" data-placement="top" ><i class="fa fa-exclamation-circle"></i></div>';
		break;
	case CODE_WAITING:
		statusCell = '<td><i class="fa fa-spinner"></i>';
		break;
	case CODE_FINISHED:
		statusCell = '<td><i class="fa fa-check"></i>';
		break;
	case CODE_WAITING:
		statusCell = '<td><i class=""></i>';
	default:
		statusCell = '<td>'+status;
	}
	return '<td style="white-space: wrap; max-width: 600px; overflow: hidden;">'+url+'</td><td>'+user+'</td><td>'+quality+'</td>'+statusCell+'</td><td>'+progress+'%</td></tr>';
}

function reloadCache() {
	$('#filelist').html('<p><img src="css/images/ajax-loader.gif" width="32px" height="32px" /></p>');
	loadFileList();
}