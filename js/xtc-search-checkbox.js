(function ($, Drupal) {
  var checked, parent, child;

  $('input:checkbox').click(function () {
    checked = $(this).is(':checked');
    parent = $(this).data('parent');
    child = $(this).data('child');
    checkOrUncheckInput(checked, parent, child);
  });

  function checkOrUncheckInput(checked, parent, child) {
    //cas du cochage du checkbox
    if (checked) {
      //Lorsqu'on a coché un checkox parent , on cose tous ses fils
      checkInput(parent, child);
    }
    else { //cas du décochage du checkbox
      //Lorsqu'on a décoché un checkox parent , on décoche tous ses fils
      uncheckedInput(parent, child);
    }
  }

  function checkInput(parent, child) {
    //Lorsqu'on a coché un checkox parent , on cose tous ses fils
    if (typeof parent !== typeof undefined && parent !== false) {
      var dataChild = "[data-child=" + parent + "]";
      var dataParent = "[data-parent=" + parent + "]";
      $(dataParent).prop("checked", true);
      $(dataChild).prop("checked", true);
      if ($(dataChild).length > 0) {
        $(dataParent).attr('type', 'hidden');
      }
    }
    else {
      if (typeof child !== typeof undefined && child !== false) {
        var dataParent = "[data-parent=" + child + "]";
        var dataChild = "[data-child=" + child + "]";
        $(dataParent).prop("checked", false);
        if ($(dataChild).length == $(dataChild + ':checked').length) {
          $(dataParent).prop("checked", true);
          $(dataParent).attr('type', 'hidden');
        }

      }
    }
    if ($(dataChild).length > 0) {
      $(dataParent).attr("disabled", "disabled");
    }
  }

  function uncheckedInput(parent, child) {
    if (typeof parent !== typeof undefined && parent !== false) {
      var dataChild = "[data-child=" + parent + "]";
      var dataParent = "[data-parent=" + parent + "]";
      $(dataParent).prop("checked", false);
      $(dataChild).prop("checked", false);
    }
    else {
      if (typeof child !== typeof undefined && child !== false) {
        var dataParent = "[data-parent=" + child + "]";
        $(dataParent).prop("checked", false);
        $(dataParent).removeAttr("disabled");
        $(dataParent).attr('type', 'checkbox');
      }
    }
  }

  $("[data-parent]").each(function (i, o) {
    var parent = $(this).attr('data-parent');
    var child = false;
    var checked = $(this).is(':checked');
    if (checked) {
      //Lorsqu'on a coché un checkbox parent , on cose tous ses fils
      checkInput(parent, child);
    }
  });

  $("[data-child]").each(function (i, o) {
    var child = $(this).attr('data-child');
    var parent = false;
    var checked = $(this).is(':checked');
    if (checked) {
      //Lorsqu'on a coché un checkbox enfant , on cose son parent
      checkInput(parent, child);
    }
  });
})(jQuery, Drupal);
