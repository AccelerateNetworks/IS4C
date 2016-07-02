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

class PaxVoidPage extends BasicCorePage {

  const PAGESIZE = 10;

  private function showNull($input) {
    if(strlen($input) > 0) {
      return $input;
    } else {
      return "NULL";
    }
  }


  function preprocess() {
    if (strtoupper(FormLib::get('reginput')) == 'CL') {
        // cancel
        $this->change_page($this->page_url."gui-modules/pos2.php");
        return false;
    } else {
      return true;
    }
  }

  function head_content() {
    echo "<link rel=\"stylesheet\" href=\"../css/pax.css\" />";
    echo "<script src=\"../js/scrollable.js\"></script>";
    echo "<script src=\"../js/ui.js\"></script>";
    echo "<script src=\"../js/void.js\"></script>";
  }

  function body_content() {
    $this->input_header("action=\"".$_SERVER['PHP_SELF']."\"");
    $page = 0;
    if(isset($_GET['page'])) {
      $page = intval($_GET['page']);
    }
    $offset = $page*self::PAGESIZE;
    ?>
    <div class="baseHeight">
    <?php
    $dbTrans = PaycardLib::paycard_db();
    $query = "SELECT responseDatetime, registerNo, empNo, transNo, refNum, cardType, amount, name,
      xTransactionID FROM PaycardTransactions WHERE transType = '01' ORDER BY paycardTransactionID DESC
      LIMIT ? OFFSET ?";
    $statement = $dbTrans->prepare($query);
    $dbResponse = $dbTrans->execute($statement, array(self::PAGESIZE, $offset));
    while($row = $dbTrans->fetchArray($dbResponse)) {
      $class = "coloredText";
      if($row['xTransactionID'] != "") {
        $class = "errorColoredText";
      } elseif($row['registerNo'] != CoreLocal::get("laneno")) {
        $class = "lightColorText";
      }
      ?>
      <div class="item" data-transaction='<?php echo json_encode($row); ?>'>
        <div class="<?php echo $class; ?>" style="width: 40%"><?php echo self::showNull($row['name']); ?></div>
        <div class="<?php echo $class; ?>" style="width: 30%"><?php echo self::showNull($row['responseDatetime']); ?></div>
        <div class="<?php echo $class; ?>" style="width: 14%"><?php echo self::showNull($row['amount']); ?></div>
        <div class="<?php echo $class; ?>" style="width: 4%"><?php echo self::showNull($row['transNo']); ?></div>
      </div>
      <?php } ?>
    </div>
    <script type="text/javascript">
    doPagination(<?php echo $page.", ".$dbResponse->_numOfRows; ?>);
    laneno = <?php echo CoreLocal::get("laneno"); ?>;
    </script>
    <?php
  }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaxVoidPage();
