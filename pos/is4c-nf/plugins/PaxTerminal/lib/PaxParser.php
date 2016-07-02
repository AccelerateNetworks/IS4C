<?php
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

class PaxParser extends Parser {

  public $accepted_types = array('CC', 'DC', 'EC', 'EF');
  public $other_cmds = array('VOID');

  public function check($str) {
    return (is_numeric(substr($str, 0, -2)) || strlen($str) == 2) && in_array(strtoupper(substr($str, -2)), $this->accepted_types) || in_array(strtoupper($str), $this->other_cmds);
  }

  public function parse($str) {
    $ret = $this->default_json();
    $info = new PaxTerminal();
    if(in_array($str, $this->other_cmds)) {
      switch(strtoupper($str)) {
        case "VOID":
          $ret['main_frame'] = $info->pluginUrl() . '/gui/VoidPage.php';
        break;
      }
    } else {
      $transaction_type = substr($str, -2);
      $amount = CoreLocal::get('amtdue');
      if($transaction_type == "EF") {
        $amount = CoreLocal::get('fsEligible');
      }
      if(is_numeric(substr($str, 0, -2))) {
        $amount = floatval(substr($str, 0, -2))/100;
      }
      $ret['main_frame'] = $info->pluginUrl() . '/gui/paxcommand.php?amount='.$amount.'&type='.$transaction_type;
    }
    return $ret;
  }

  public function doc(){
          return "<table cellspacing=0 cellpadding=3 border=1>
              <tr>
                  <th>Input</th><th>Result</th>
              </tr>
              <tr>
                  <td>PAX</td>
                  <td>Send the current total to the PAX unit for charging</td>
              </tr>
              </table>";
      }
}
