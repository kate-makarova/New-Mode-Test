(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.representative_match_behavior = {
    attach(context, settings) {

      /**
       * Renders the representative contacts table.
       *
       * @param data
       */
      $.fn.formTable = function (data) {
        var content = $('<ul />')
        content.addClass('set-list')
        $.each(data, function (setIndex, set) {
          var setItem = $('<li />')
          setItem.addClass('set-item')
          setItem.append('<label>' + setIndex + '</label>')
          var representativeList = $('<ul />')
          representativeList.addClass('contact-list')
          $.each(set, function (rIndex, representative) {
            var rItem = $('<li />')
            rItem.addClass('contact-item')
            var rItemMain = $('<div />')
            rItemMain.addClass('contact-item-main')
            rItemMain.append('<span class="name">'+representative.name+'</span>')
            rItemMain.append('<span class="office">'+representative.office+'</span>')
            rItemMain.append('<span class="email">'+representative.email+'</span>')
            rItem.append(rItemMain)

            var offices = $('<div />')
            offices.addClass('contact-item-offices')
            $.each(representative.offices, function(index, office) {
              var officeItem = $('<ul />')
              officeItem.addClass('office')
              $.each(office, function(prop, val) {
                officeItem.append('<li><label class="office-item">'+prop+'</label>'+val+'</li>')
              })
              offices.append(officeItem)
            })
            rItem.append(offices)

            representativeList.append(rItem)
          })
          setItem.append(representativeList)
          content.append(setItem)
        })
        $(this).html(content)
      }

      $.fn.showErrors = function (data) {
        console.log(data);
      }

    }
  }
})(jQuery, Drupal, drupalSettings)
