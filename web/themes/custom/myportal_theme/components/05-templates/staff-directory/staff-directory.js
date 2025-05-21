/**
 * @file
 * JavaScript for member details popup in staff directory.
 * 
 * 
 */

(function ($, Drupal, cookies) {

  'use strict';

  Drupal.behaviors.MyPortalStaffDirectory = {
    attach: function attach(context) {
      const $body = $('body'),
            $view = $('.view-staff-directory'),
            $rows = $('.view-staff-directory').find('tbody tr'),
            $member_ref = $('a.member-details-ref'),
            $details = $('aside.member-details .member-details-content');

      $('.region--complementary-top').addClass('men-filter-home');

      $rows.once('selectMember').on('click', function(e) {
        var smid = $(this).find('.views-field-name span').data('smid');
        const ajax = Drupal.ajax({
          url: `/myportal-staff-directory-jmd/${smid}`,
          method: 'GET',
          success: function(response) {
            memberDetails(response);
          },
          error: function(xhr, status, error) {
            console.log('Error:', error);
          },
        });
        ajax.execute();

        return false;
      });

      $(document).once('selectMember').on('click', 'a.member-details-ref', function(e) {
        e.preventDefault();

        var smid = $(this).data('smid');
        const ajax = Drupal.ajax({
          url: `/myportal-staff-directory-jmd/${smid}`,
          method: 'GET',
          success: function(response) {
            memberDetails(response);
          },
          error: function(xhr, status, error) {
            console.log('Error:', error);
          },
        });
        ajax.execute();

        return false;
      });

      $(document).ajaxComplete(function (event, xhr, settings) {    
        const sel = $('.men-filter-home input[type="checkbox"]').filter(':checked').each(function(e) {
          $(this).parents('.facets-widget-checkbox').find('h3').addClass('facet-open');
          $(this).parents('.facets-widget-checkbox').find('ul').addClass('facet-open--content');
        });
      });

      const sel = $('.men-filter-home input[type="checkbox"]').filter(':checked').each(function(e) {
        $(this).parents('.facets-widget-checkbox').find('h3').addClass('facet-open');
        $(this).parents('.facets-widget-checkbox').find('ul').addClass('facet-open--content');
      });

      /**
       * 
       * @param {*} response 
       */
      const memberDetails = function(response) {
        const staff_member = response;

        $details.find('.json-name').text(staff_member.name[0].value);
        $details.find('.json-title').text(staff_member.position_title[0].value);

        $details.find('.json-direct-line').text(staff_member.directline_number.length > 0 ? staff_member.directline_number[0].value : '');
        $details.find('.json-mobile').text(staff_member.mobile_number.length > 0 ? staff_member.mobile_number[0].value : '');
        $details.find('.json-email').text(staff_member.email.length > 0 ? staff_member.email[0].value : '');

        $details.find('.json-function').text(staff_member.function.length > 0 ? staff_member.function[0].value : '');
        $details.find('.json-employee-type').text(staff_member.employee_scope.length > 0 ? staff_member.employee_scope[0].value : '');

        const reporting = staff_member.reporting.length > 0 ? staff_member.reporting[0].value : '';
        $details.find('.json-reporting').html(reporting);

        const team = staff_member.team.length > 0 ? staff_member.team.map((idx) => idx.value).join(', ') : '';
        $details.find('.json-team').html(team);

        $details.addClass('active');
      }

      // Salviamo la posizione originale del box
      const $detailsBox = $('.men-layout__staff-directory .member-details-content');
      const $filtersBox = $('.men-layout__staff-directory .region--complementary--facets');
      const dbW = $('aside.member-details').width();
      const fbW = $('aside.facets').width();
      const originalPosition = $detailsBox.css('position');
      const originalTop = $detailsBox.css('top');
      
      $(window).scroll(function() {
        console.log(dbW);
        // Controlliamo la posizione attuale dello scroll
        var scrollPosition = $(window).scrollTop();
        
        // Se lo scroll è maggiore di 340px, imposta position: fixed
        if (scrollPosition > 340) {
          $detailsBox.css({
            'position': 'fixed',
            'top': '120px',
            'width': dbW
          });
          $filtersBox.css({
            'position': 'fixed',
            'top': '120px',
            'width': fbW
          });
        } else {
          // Altrimenti, ripristina la posizione originale
          $detailsBox.css({
            'position': originalPosition,
            'top': originalTop
          });
          $filtersBox.css({
            'position': originalPosition,
            'top': originalTop
          });
        }
      });
    }
  }
})(jQuery, Drupal, window.Cookies);