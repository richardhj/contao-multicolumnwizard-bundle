/**
 * Created by richard on 03.05.15.
 */
/**
 * Provide methods to handle Ajax requests.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
var AjaxRequestFepa =
{
	/**
	 * Toggle the visibility of an element
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} table The table name
	 *
	 * @returns {boolean}
	 */
	toggleLocked: function (el, id, table) {
		el.blur();

		var img = null,
			image = $(el).getFirst('img'),
			unlocked = (image.src.indexOf('lock_open_grey') != -1),
			div = el.getParent('div'),
			next;

		// Send request
		if (unlocked) {
			image.src = image.src.replace('lock_open_grey.png', 'lock.png');
			new Request.Contao({'url': window.location.href, 'followRedirects': false}).get({
				'tid': id,
				'state': 1,
				'rt': Contao.request_token
			});
		} else {
			image.src = image.src.replace('lock.png', 'lock_open_grey.png');
			new Request.Contao({'url': window.location.href, 'followRedirects': false}).get({
				'tid': id,
				'state': 0,
				'rt': Contao.request_token
			});
		}

		return false;
	}
};
