(function ($, Drupal) {

  Drupal.behaviors.xtc_masonry = {
    attach: function (context, settings) {
      var $grid = $('.gallery-wrapper').masonry({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        horizontalOrder: true,
        percentPosition: true,
        transitionDuration: 500,
      });
      setTimeout(function () {
        $grid.masonry('reloadItems');
        $grid.masonry('layout');
      }, 251);
    }
  }
})(jQuery, Drupal);
