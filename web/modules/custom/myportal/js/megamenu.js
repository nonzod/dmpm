/**
 * @file
 * Defines Javascript behaviors for the myaccess module.
 */
(function ($, Drupal) {
    Drupal.behaviors.myportal = {
        attach: (context) => {
            const body = $('body');
            const header = $('.men-header--region');
            const closeIcon = $('.close_megamenu');
            const mainContent = $('.main-container');

            body.once('megamenu-wrapper').each(() => {
                $('.men-header').append('<div id="megamenu-wrapper"></div>');
            });

            $('.navbar-nav--item', context)
                .once('myportal')
                .click(function (e) {
                    e.preventDefault();
                    let tid = $(this).attr('data-drupal-tid');
                    if ($(this).hasClass('is-active')) {
                        $(this).removeClass('is-active');
                        header.removeClass('men-header-open_mega');
                        mainContent.removeClass('men-nav_is-open');
                        $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
                    } else {
                        $(this).addClass('is-active').parent().siblings().find('a').removeClass('is-active');
                        header.addClass('men-header-open_mega');
                        mainContent.addClass('men-nav_is-open');
                        Drupal.ajax({
                            url: Drupal.url('megamenu-generate/' + tid),
                            progress: { type: 'none' },
                        }).execute();
                    }
                });
            
            closeIcon.once('myportal').click(function () {
                $('#megamenu-wrapper').removeClass('megamenu_visible');
                $('.navbar-nav--item').removeClass('is-active');
                header.removeClass('men-header-open_mega');
            });

            if (header.hasClass('men-header-open_mega')) {
                $('.main-container, .region--toolbar').once('listenerMainMenu').on('click', function(e) {
                    $('.navbar-nav--item').removeClass('is-active');
                    header.removeClass('men-header-open_mega men-header-open');
                    mainContent.removeClass('men-nav_is-open');
                    $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
                    mainContent.removeClass('men-nav_is-open');
                    if ($(".navbar-collapse").hasClass("in")) {      
                        $(".navbar-collapse").collapse('hide');
                    }
                });
            }

        },
    };

}(jQuery, Drupal));
