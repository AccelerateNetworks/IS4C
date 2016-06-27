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

  public function check($str) {
    return (is_numeric(substr($str, 0, -3)) || strlen($str) == 3) && substr($str, -3) == "PAX";
  }

  public function parse($str) {
    $amount = CoreLocal::get('amtdue');
    if(is_numeric(substr($str, 0, -3))) {
      $amount = floatval(substr($str, 0, -3))/100;
    }
    $info = new PaxTerminal();
    $ret = $this->default_json();
    $ret['main_frame'] = $info->pluginUrl() . '/gui/paxcommand.php?amount='.$amount;
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
