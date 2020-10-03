(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.representative_match_behavior = {
    attach(context, settings) {
      $.fn.formTable = function (data) {
        var content = $('<ul />');
        $.each(data, function (setIndex, set) {
          var setItem = $('<li />');
          setItem.append('<lable>' + setIndex + '</lable>')
          var representativeList = $('<ul />');
          $.each(set, function (rIndex, representative) {
            representativeList.append('<li>' + representative + '</li>')
          });
          setItem.append(representativeList);
          content.append(setItem);
        });
        $(this).html(content);
      };
    }
  };
})(jQuery, Drupal, drupalSettings);
