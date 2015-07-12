function dialog(imghash, untagurl) {
  var mytext = "Are you sure you want to untag this photo? This will also revoke the attendance.";

    $('<div id="dialog'+imghash+'">'+mytext+'<form method="post" action="'+untagurl +'"><button type="submit" name="untaghash" value="'+imghash+'">Untag</button></form></div>').appendTo('body');    	

		$('#dialog'+imghash).dialog({					
			close: function(event, ui) {
				$('#dialog'+imghash).remove();
				}
			});
}
