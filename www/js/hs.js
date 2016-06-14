 var HS = {
  isTop: true,
  init: function () {
    $(document).scroll(HS.preventFocus);
    HS.focusForm();
    $('.criteria').click(HS.criteriaClick);
    $('.numeric').bind('input', HS.numericCriteriaChange).keydown(HS.submitOnEnter);
    $('.operator').change(HS.numericCriteriaChange);
    $('.textual').bind('input', HS.textualCriteriaChange).keydown(HS.submitOnEnter);
  },
  preventFocus: function () {
    HS.isTop = false;
  },
  focusForm: function () {
    if (HS.isTop && document.f) {
      document.f.q.select();
    }
  },
  criteriaClick: function () {
    HS.updateQuery($(this), $(this).data('key'), ':', $(this).data('value'));
  },
  numericCriteriaChange: function () {
    var operator, value,
      key = $(this).data('key');
    if ($(this).hasClass('numeric')) {
      operator = $(this).siblings('.operator').val();
      value = $(this).val();
    } else {
      operator = $(this).val();
      value = $(this).siblings('.numeric').val();
    }
    HS.updateQuery($(this), key, operator, value);
  },
  textualCriteriaChange: function () {
    HS.updateQuery($(this), $(this).data('key'), ':', $(this).val(), /* removeOnlyOnEmpty = */ true);
  },
  submitOnEnter: function (e) {
    if (e.keyCode === 13) {
      document.f.submit();
    }
  },
  updateQuery: function ($element, key, operator, value) {
    var i, explode,
      operators = /:|>=|<=|<|>/,
      k = key.trim().replace(' ', '_').toLowerCase(),
      v = value.trim().replace(' ', '_'),
      newPart = k + operator + v,
      newQ = [],
      q = document.f.q.value,
      parts = q.split(' '),
      found = false,
      removeOnlyOnEmpty = $element.hasClass('textual');
    for (i = 0; i < parts.length; i++) {
      if (!parts[i].startsWith(k)) {
        newQ.push(parts[i]);
      } else if (removeOnlyOnEmpty && v) {
        found = true;
        newQ.push(newPart);
      } else {
        found = true;
        explode = parts[i].split(operators);
        if ((explode.length >= 2 && explode[1] === v)) {
          // We don't add it to newQ - we are removing it.
          // Undepress the button, if it is a button. Bit of a hack.
          if ($element.hasClass('btn')) {
            setTimeout(function () { $element.removeClass('active'); }, 1);
          }
        } else if (v) {
          newQ.push(newPart);
        }
      }
    }
    if (!found && v) {
      newQ.push(newPart);
    }
    document.f.q.value = newQ.join(' ').trim();
  }
};

$(document).ready(HS.init);
