/*! (c) Aron Heinecke 2015-2016 v. 1.2
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Attribution-NonCommercial-NoDerivatives 4.0 International
 * which accompanies this distribution, and is available at
 * http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

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
const VAR_SITE = "yayd";
const downloadLink = 'http://downloads.proctet.net/vids/';
const QUERYLIST_TBL = '#querytable';
const CODE_WAITING = -1;
const CODE_FINISHED = 2;
const CODE_FINISHED_WARNING = 3;
const CODE_FAILED = 10;
const CODE_ERROR_QUALITY = 11;
const CODE_ERROR_SOURCE = 12;
const CODE_ERROR_URL = 13;
const default_timer = 3500;
const slow_timer = 20000;
const fast_timer = 900;
const TYPE_YTVID = 0;
const TYPE_YTPL = 1;
const TYPE_TWITCH = 2;
var idle_counter = 0;
var current_mode = 0; //0 normal, 1 speed, -1 slow

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
	$('input[id=pl_zip]').attr('checked', true);
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
				ownQueries.push(result[3]);
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
			
			var query = addQueryPlaylist(link, ytqual,from, to, $("#pl_split").is(':checked') ? 1 : 0);
			$.when(query).then(function(result){
				ownQueries.push(result[3]);
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

/**
 * Set update check timer
 * @param ms
 */
function setUpdateTimer(ms){
	if(timer != null)
		clearTimeout(timer);
	timer = setInterval(function() {
		checkQueryUpdate();
	},ms);
}

/**
 * Query list init<br>
 * Generate query list
 */
function generateQueryList(){
	$.when(loadQueryList()).then(function(result){
		$(QUERYLIST_TBL+' tr').remove();
		result.reverse(); 
		$.each(result, function(index, data){
			qtableAddElement(false,QUERYLIST_TBL, data);
		});
		initializeTooltip();
	}).fail(function(result){
		console.log(result.responseText);
	});
}

/**
 * Init tool tips
 */
function initializeTooltip(){
	$(TOOLTIP_CLASS).tooltip();
}

/***
 * Check for query updates and perform table update if necessary
 * Same applies to the file table
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
 * Update missing changes & add missing rows in the query list
 */
function updateQueryList(array){
	var changed_filelist = false;
	var elem_id_temp = -1;
	$.each(array, function( index, data ) {
		if($('#qe'+data[C_QID]).length){
			
			if(data[C_Code] == CODE_FINISHED || data[C_Code] == CODE_FINISHED_WARNING ){
				changed_filelist = true;
				elem_id_temp = $.inArray(data[C_QID], ownQueries);
				if(elem_id_temp != -1){
					ownQueries.splice(elem_id_temp, 1);
				}
			}
			
			qtableUpdateElement(data);
		}else{
			qtableAddElement(true,QUERYLIST_TBL,data);
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

/**
 * Generate file list
 */
function generateFileList(){
	$.when(loadFileList()).then(function(result){
		$(FILELIST_TBL+' tr').remove();
		$.each(result, function(index, data){
			ftableAddElement(data[C_FILE_Name],data[C_FILE_RName],data[C_FILE_FID]);
		});
	}).fail(function(result){
		console.log(result.responseText);
	});
}

/**
 * Compare LUC
 * @param date
 * @returns true if date is greater then LUC
 */
function compareLUC(date){
	return date > lastQueryUpate;
}

/**
 * Format file id
 * @param id
 * @returns {String}
 */
function fileID(id){
	return 'fid'+id;
}

/**
 * Format query id
 * @param id
 * @returns {String}
 */
function queryID(id){
	return 'qry'+id;
}

/**
 * Request file deletion
 * @param fid
 */
function deleteFile(fid){
	$.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "text",
		data: {
			'site' : VAR_SITE,
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

/**
 * Show warn dialog
 * @param qid
 */
function showWarningWindow(qid){
	$('#errDiaBody').html('loading');
	$('#errDialog').modal({ show: true});
	$.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "text",
		data: {
			'site' : VAR_SITE,
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
 * Add single file query entry
 * @param url
 * @param quality
 */
function addQueryVid(url, quality, type) {
	return addQuery(url, type, quality, -2, -2, false);
}

/**
 * Add playlist query entry
 * @param url
 * @param quality
 * @param from
 * @param to
 * @param zip
 */
function addQueryPlaylist(url, quality, from, to, split){
	return addQuery(url, TYPE_YTPL, quality, from, to, split);
}

/***
 * Add an query entry
 * @param url
 * @param quality
 */
function addQuery(url, type, quality, from, to, split) {
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "json",
		data: {
			'site' : VAR_SITE,
			'ajaxCont' : 'addquery',
			'url' : url,
			'quality' : quality,
			'type' : type,
			'from' : from,
			'to' : to,
			'split' : split,
		},
	}).done(function(data){
		console.debug("added query");
		qtableAddElement(true,QUERYLIST_TBL,data);
	}).fail(function(data){
		console.log(data);
	});
}

/**
 * Load file list
 */
function loadFileList(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: 'json',
		data: {'site' : VAR_SITE, 'ajaxCont' : 'get_files' },
	});
}

/***
 * Load query list
 * @returns data
 */
function loadQueryList(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: 'json',
		data: {'site' : VAR_SITE, 'ajaxCont' : 'querylist' },
	});
}

/***
 * Load query list changes since last check
 * @returns data
 */
function loadQueryListUpdate(){
	return $.ajax({
		url: 'index.php',
		type: 'post',
		dataType: "json",
		data: {'site' : VAR_SITE, 'ajaxCont' : 'LCQueries' },
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
 * Add file table element
 * @param name URL name
 * @param rname real name
 * @param id
 * @returns
 */
function ftableAddElement(name,rname,id){
	$('<tr id="f'+id+'"><td><a href="'+downloadLink+name+'" download="'+rname+'">'+rname+'</a></td><td>'
			+generateDeleteButton(id)+'</td></tr>').prependTo(FILELIST_TBL+" > tbody");
}

/**
 * Generate file table delete button
 * @param id
 * @returns {String}
 */
function generateDeleteButton(id){
	return '<a href="#" onclick="deleteFile('+id+'); return false;"><i class="fa fa-trash-o fa-lg"></i> Delete</a>';
}

/**
 * Delete file table element
 * @param id
 */
function ftableDeleteElement(id){
	$('#f'+id).fadeToggle("slow", function() {
		$('#f'+id).remove();
	});
}

/***
 * Add element to query table, animated
 * @param id table id with #
 * @param Object data: {
 * id
 * url
 * name escaped name input
 * status
 * quality
 * progress
 * code
 * {String}
 * }
 */
function qtableAddElement(FADE_IN,tableid, data){
	if (FADE_IN)
		$('<tr style="display: none;" id="qe'+data[C_QID]+'">'+trInnerGenerator(data))
				.prependTo(tableid+" > tbody").fadeToggle("slow");
	else
		$('<tr id="qe'+data[C_QID]+'">'+trInnerGenerator(data)).prependTo(tableid+" > tbody");
}

/**
 * Update query table element
 * @param tableid
 * @param Object data: {
 * id
 * url
 * name escaped name input
 * status
 * quality
 * progress
 * code
 * {String}
 * }
 */
function qtableUpdateElement(data){
	$('#qe'+data[C_QID]).html(trInnerGenerator(data));
}

/**
 * query list <TR>-content generator
 * @param Object data: {
 * id
 * url
 * name escaped name input
 * status
 * quality
 * progress
 * code
 * {String}
 * }
 */
function trInnerGenerator(data){
	if (data[C_Progress] == null){
		data[C_Progress] = '?';
	}
	var statusCell;
	switch (data[C_Code]){
	case CODE_FAILED:
		statusCell = '<td onclick="showWarningWindow('+data[C_QID]+')" title="click for details" class="tdErrbtn" ><i class="fa fa-exclamation-circle"></i>';
		break;
	case CODE_FINISHED_WARNING:
		statusCell = '<td onclick="showWarningWindow('+data[C_QID]+')" title="click for details" class="tdErrbtn" ><i class="fa fa-check"><i class="fa fa-exclamation-triangle"></i></i>';
		break;
	case CODE_ERROR_QUALITY:
		statusCell = '<td><div class="ttInfo" data-toggle="tooltip" title="Quality not available!" data-placement="top"><i class="fa fa-exclamation-circle"></i></div>';
		break;
	case CODE_ERROR_SOURCE:
		statusCell = '<td><div class="ttInfo" data-toggle="tooltip" title="Private / Deleted source!" data-placement="top" ><i class="fa fa-exclamation-circle"></i></div>';
		break;
	case CODE_ERROR_URL:
		statusCell = '<td><div class="ttInfo" data-toggle="tooltip" title="Invalid URL!" data-placement="top" ><i class="fa fa-exclamation-circle"></i></div>';
		break;
	case CODE_WAITING:
		statusCell = '<td><i class="fa fa-spinner"></i>';
		break;
	case CODE_FINISHED:
		statusCell = '<td><i class="fa fa-check"></i>';
		break;
	case CODE_WAITING:
		statusCell = '<td><i class=""></i>';
		break;
	default:
		statusCell = '<td>'+data[C_Status];
	}
	var td_name = '<td style="white-space: wrap; max-width: 600px; overflow: hidden;"';
	td_name += data[C_Name] == null ? '>' : 'data-toggle="tooltip" title="'+data[C_Name]+'">';
	return td_name+data[C_URL]+'</td><td>'+data[C_Quality]+'</td>'+statusCell+'</td><td>'+data[C_Progress]+'%</td></tr>';
}

/**
 * DEV function for cache reloading
 */
function reloadCache() {
	$('#filelist').html('<p><img src="css/images/ajax-loader.gif" width="32px" height="32px" /></p>');
	loadFileList();
}