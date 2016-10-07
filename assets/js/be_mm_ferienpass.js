/**
 * Created by richard on 21.03.16.
 */
(function () {
    window.addEvent('domready', function () {
        $$('table_mm_ferienpass .item .field.applicationlist_active').each(function (item) {
                item.addClass(
                    (item.getElements('.text').get('text')[0] == '1')
                        ? 'active'
                        : 'inactive'
                );

                if (item.getElements('.text').get('text')[0] != '1') {
                    item.getSiblings('.applicationlist_max').addClass('invisible');
                }
            }
        );
        $$('table_mm_ferienpass .item .field.age').each(function (item) {
                if (!item.getElements('span').get('text')[0]) {
                    item.addClass('invisible');
                }
            }
        );
    });
})();
