(function ($, Drupal) {
  var checked, parent, child;

  Drupal.behaviors.xtc_checkbox = {
    attach: function (context, settings) {
      $("[data-parent]").each(function (i, o) {
        var parent = $(this).attr('data-parent');
        var child = false;
        var checked = $(this).is(':checked');
        if (checked) {
          //Lorsqu'on a coché un checkbox parent , on coche tous ses fils
          checkInput(parent, child);
        }
      });

      $("[data-child]").each(function (i, o) {
        var child = $(this).attr('data-child');
        var parent = false;
        var checked = $(this).is(':checked');
        if (checked) {
          //Lorsqu'on a coché un checkbox enfant , on coche son parent
          checkInput(parent, child);
        }
      });

      $('input:checkbox').click(function () {
        checked = $(this).is(':checked');
        parent = $(this).data('parent');
        child = $(this).data('child');
        checkOrUncheckInput(checked, parent, child);
      });

      function checkOrUncheckInput(checked, parent, child) {
        //cas du cochage du checkbox
        if (checked) {
          //Lorsqu'on a coché un checkox parent , on coche tous ses fils
          checkInput(parent, child);
        }
        else { //cas du décochage du checkbox
          //Lorsqu'on a décoché un checkox parent , on décoche tous ses fils
          uncheckedInput(parent, child);
        }
      }

      function checkInput(parent, child) {
        //Lorsqu'on a coché un checkox parent , on coche tous ses fils
        if (typeof parent !== typeof undefined && parent !== false) {
          // updateParent(parent);
          checkAllChildren(parent);
        }
        if (typeof child !== typeof undefined && child !== false) {
          updateParent(child);
        }
      }

      function uncheckedInput(parent, child) {
        var dataParent = "[data-parent=" + parent + "]";
        if (typeof parent !== typeof undefined && parent !== false) {
          // updateParent(parent);
          $(dataParent).removeClass('partial');
          uncheckAllChildren(parent);
        }
        if (typeof child !== typeof undefined && child !== false) {
          updateParent(child);
        }
      }

      function updateParent(id) {
        var dataChild = "[data-child=" + id + "]";
        var dataParent = "[data-parent=" + id + "]";
        if (0 == $(dataChild + ':checked').length) {
          $(dataParent).prop("checked", false);
          $(dataParent).removeClass('partial');
        }
        else{
          if ($(dataChild).length == $(dataChild + ':checked').length) {
            $(dataParent).prop("checked", true);
            $(dataParent).removeClass('partial');
          }
          if ($(dataChild).length > $(dataChild + ':checked').length) {
            $(dataParent).prop("checked", false);
            $(dataParent).addClass('partial');
          }
        }
      }

      function checkAllChildren(id) {
        var dataChild = "[data-child=" + id + "]";
        $(dataChild).prop("checked", true);
      }

      function uncheckAllChildren(id) {
        var dataChild = "[data-child=" + id + "]";
        $(dataChild).prop("checked", false);
      }
    }
  }

  Drupal.behaviors.xtc_checkbox.attach();

})(jQuery, Drupal);
