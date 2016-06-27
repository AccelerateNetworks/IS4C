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

use \COREPOS\pos\lib\FormLib;
include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaxCommandPage extends BasicCorePage {
  function preprocess() {
    if (strtoupper(FormLib::get('reginput') == 'CL')) {
        // cancel
        $this->change_page($this->page_url."gui-modules/pos2.php");
        return false;
    } else {
      return true;
    }
  }

  function head_content() {
    echo "<script src=\"../js/pax.js\"></script>";
  }

  function body_content() {
    $amount = CoreLocal::get('amtdue');
    if(isset($_GET['amount'])) {
      $amount = $_GET['amount'];
    }
    $this->input_header("action=\"".$_SERVER['PHP_SELF']."\"");
    $info = _("Pay $".$amount." via card");
    $js_array = array('amtdue' => $amount);
    ?>
    <div class="baseHeight">
    <div class="coloredArea centeredDisplay">
    <span class="larger">
    <?php echo $info ?>
    </span><br />
    <p>
    <span id="localmsg"><i>Initializing payment terminal.</i></span>
    </p>
    </div>
    </div>
    <script type="text/javascript">pax_transaction(<?php echo json_encode($js_array); ?>);</script>
    <?php
  }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaxCommandPage();
