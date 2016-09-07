/**
 * Created by richard on 16.11.15.
 */
(function ($) {
	$(document).ready(function () {
		$('#knopf').click(function () {
			$.ajax({
				type: 'POST',
				url: 'SimpleAjax.php',
				data: {type: 'fetch_editing_form', mid: 2},
				success: function (result) {
					tags = $.parseJSON(result);
					if (tags['code'] == "0")   // nur als Beispiel
					{
						// benötigte Aktionen
						alert(tags["msg"]);
					}
				}
			});
		});
	});
})(jQuery);
