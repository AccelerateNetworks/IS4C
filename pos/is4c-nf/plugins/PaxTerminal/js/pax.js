/*******************************************************************************

    Copyright 2016 Accelerate Networks

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

function parseWrapper(str) {

}

function fail(result) {
  console.log("Post failed", result);
  $("#localmsg").text("Failed to complete transaction. Try again with [RETRY]");
}

function creditdone(result) {
  console.log("Post succeeded", result);
  if(result.code === 0) {
    switch(result.action) {
      case "signature":
        signature();
      break;
      case "redirect":
        window.location.href = result.redirect;
      break;
    }
  } else {
    switch(result.code) {
      case 100001:
        $("#localmsg").text("Took too long to swipe card. Hit [RETRY] to try again.");
      break;
      default:
        $("#localmsg").text("Failed to complete transaction: " + result.message + " (" + result.code + ")");
      break;
    }
  }
}

function signaturedone(result) {
  if(result.code === 0) {
    $("#localmsg").html($("<img>").attr('src', '../signatures/' + result.signature));
  }
}

function signature() {
  window.terminalrequest = $.post("../ajax/pax.php", {action: "signature"}).done(signaturedone).fail(fail);
}


function pax_transaction(transaction) {
  console.log(transaction);
  $("#localmsg").text("Use payment terminal to complete transaction.");
  window.terminalrequest = $.post("../ajax/pax.php", {action: "credit", amount: transaction.amtdue}).done(creditdone).fail(fail);
}
