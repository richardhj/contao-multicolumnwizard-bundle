/* ModalEditing, (c) 2016-2017 Richard Henkenjohann */
(function ($) {
    $.fn.modalEditing = function (options) {
        options = $.extend({

            /**
             * The container
             *
             * @var null|string The selector, e.g. #id or .class
             */
            container: null,

            /**
             * The trigger (child item of container), which is needed to click to open the modal
             *
             * @var null|string The selector, e.g. `a.edit`
             */
            trigger: null,

            /**
             * The container to get updated (via ajax), after closing the modal
             *
             * @var null|false|string Use false, if you don't want to update anything. Use null, to update the parent
             * element (the closest `ce_` or `mod_` element of the container). Otherwise, define an explicit selector.
             */
            containerToUpdate: null,

            /**
             * The identifier to find the element (the content of the modal) in the Contao database
             *
             * @var null|string E.g. `mod::11` for the frontend module with ID 11 or `ce::4` for the content element
             * with ID 4
             */
            element: null,

            /**
             * The text for the close button
             *
             * @var string
             */
            closeText: 'close',

            /**
             * The title of the modal, which is displayed above the form
             *
             * @var null|string
             */
            title: null,
        }, options);

        $(document).ready(function () {
            let modal, modalContainer, randString;
            randString = Math.random().toString(36).substr(2, 5);
            $(options.container).append('<div id="' + randString + '-editing-modal"></div>');
            modalContainer = $('#' + randString + '-editing-modal');
            modal = modalContainer.dialog({
                autoOpen: false,
                width: 500,
                clickOut: false,
                closeText: options.closeText,
                title: options.title,
                show: true,
                hide: true,
                modal: true,
                buttons: [
                    {
                        text: options.closeText,
                        click: function () {
                            $(this).dialog('close');
                        }
                    }
                ],
                close: function () {
                    /*
                     * Update container after closing the modal
                     */
                    if (false !== options.containerToUpdate) {
                        let element;

                        modalContainer.empty();
                        modal.dialog('option', 'buttons', modal.dialog('option', 'buttons').slice(-1));
                        element = (null === options.containerToUpdate)
                            ? $(options.container).closest('[class^="ce_"],[class^="mod_"]')
                            : $(options.containerToUpdate);
                        element.addClass('ajax-reload-element-overlay');

                        // Update list view
                        $.ajax({
                            method: 'POST',
                            url: location.href,
                            data: {
                                ajax_reload_element: element.attr('data-ajax-reload-element')
                            }
                        })
                            .done(function (response) {
                                if ('ok' === response.status) {
                                    element.replaceWith(response.html);
                                }
                                else {
                                    location.reload();
                                }
                            });
                    }
                }
            });
            $(document).on('click', options.container + ' ' + options.trigger, function (event) {
                /*
                 * Open modal (after click on trigger)
                 */
                event.preventDefault();
                modalContainer.addClass('modal-loading');

                // Load the content for the modal container
                $.ajax({
                    method: 'GET',
                    url: $(this).attr('href'),
                    data: {
                        ajax_reload_element: options.element
                    }
                })
                    .done(function (response) {
                        if ('ok' === response.status) {
                            let submit, buttons;

                            modalContainer.html(response.html);

                            submit = modalContainer.find('form input[type=submit]');
                            buttons = modal.dialog('option', 'buttons');
                            buttons.unshift({
                                text: submit.attr('value'),
                                'class': 'origin-' + submit.attr('class'),
                                click: function () {
                                    modalContainer.find('form').submit();
                                    //$(this).dialog('close');
                                }
                            });

                            modal.dialog('option', 'buttons', buttons);
                            modalContainer.removeClass('modal-loading');
                        }
                        else {
                            location.reload();
                        }
                    });

                modal.dialog('open');
            });
        });
    };
}(jQuery));
