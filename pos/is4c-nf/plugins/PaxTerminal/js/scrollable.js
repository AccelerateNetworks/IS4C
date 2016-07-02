var scrollable = {};

scrollable.highlight = function(i) {
  $(".coloredArea").addClass("coloredText").removeClass("coloredArea");
  $(".lightColorArea").addClass("lightColorText").removeClass("lightColorArea");
  $(".errorColoredArea").addClass("errorColoredText").removeClass("errorColoredArea");

  $(".item:eq(" + i + ") .coloredText").removeClass("coloredText").addClass("coloredArea");
  $(".item:eq(" + i + ") .lightColorText").removeClass("lightColorText").addClass("lightColorArea");
  $(".item:eq(" + i + ") .errorColoredText").removeClass("errorColoredText").addClass("errorColoredArea");

  scrollable.selected = i;
  return true;
};

scrollable.up = function() {
  if(scrollable.selected > 0) {
    return scrollable.highlight(scrollable.selected-1);
  } else {
    if(scrollable.pageUp !== undefined) {
      return scrollable.pageUp;
    } else {
      return false;
    }
    return false;
  }
};

scrollable.down = function() {
  if(scrollable.selected >= scrollable.totalElements) {
    if(scrollable.pageDown !== undefined) {
      return scrollable.pageDown;
    } else {
      return false;
    }
  } else {
    return scrollable.highlight(scrollable.selected+1);
  }
};

scrollable.getSelected = function() {
  if(scrollable.selected !== undefined) {
    var row = $(".item:eq(" + scrollable.selected + ")");
    if(row.data('transaction') === undefined) {
      return row;
    } else {
      try {
        return JSON.parse(row.data('transaction'));
      } catch(e) {
        return row.data('transaction');
      }
    }
  }
};

$(document).ready(function() {
  scrollable.totalElements = $(".item").length;
  scrollable.highlight(0);
});
