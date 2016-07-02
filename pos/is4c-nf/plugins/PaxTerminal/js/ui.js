window.ui = {};
ui.alert = function(title, body) {
  var msg = $("<div>").addClass('coloredArea').addClass('alertBox');
  var titleElement = $("<span>").addClass("larger").text(title);
  var bodyElement = $("<span>").text(body);
  if(ui._currentAlert !== undefined) {
    $(ui._currentAlert).remove();
  }
  $(".baseHeight").prepend(msg);
  msg.append(titleElement);
  msg.append("<br />");
  msg.append($("<p>").append(bodyElement));
  ui._currentAlert = msg;
};

ui.clearAlert = function () {
  if(ui._currentAlert !== undefined) {
    $(ui._currentAlert).remove();
  }
  ui._currentAlert = undefined;
};
