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

class PaxTerminal extends Plugin {
  public $plugin_description = "Plugin for using Pax's card terminals.";
  public $plugin_settings = array(
    'PaxHost' => array(
      'label' => 'Pax terminal hostname or IP',
      'description' => 'The hostname or IP address of the Pax terminal for this lane',
      'default' => '127.0.0.1',
    ),
    'PaxPort' => array(
      'label' => 'Pax terminal port',
      'description' => 'The port to communicate with the pax terminal on',
      'default' => '10009'
    ),
    'PaxSigLimit' => array(
      'label' => 'Signature Threshhold',
      'description' => 'Transactions below this amount will not be asked for a signature. Set to -1 to never ask for a signature',
      'default' => '-1'
    )
  );
}
