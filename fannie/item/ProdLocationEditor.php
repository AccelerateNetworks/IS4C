<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProdLocationEditor extends FannieRESTfulPage
{
    protected $header = 'Product Location Update';
    protected $title = 'Product Location Update';

    public $description = '[Product Location Update] find and update products missing 
        floor section locations.';
    public $has_unit_tests = true;

    private $date_restrict = 1;
    private $data = array();

    function preprocess()
    {
        
        $this->__routes[] = 'get<start>';
        $this->__routes[] = 'get<batch>';
        $this->__routes[] = 'post<batch><save>';
        $this->__routes[] = 'post<upc><save>';
        $this->__routes[] = 'get<start>';
        $this->__routes[] = 'get<searchupc>';
        return parent::preprocess();
    }
    
    function post_upc_save_view()
    {
        echo '<h4>post_upc_save_view</h4>';
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get('upc');
        //$section = FormLib::get('section');
        //$model = new FloorSectionProductMapModel($dbc);
        $model = new FloorSectionProductMapModel($dbc);
        $count = FormLib::get('numolocations');
        $newSection = array();
        $oldSection = array();
        for ($i = 1; $i <= $count; $i++) {
            $curName = 'newSection' . $i;
            $oldName = 'oldLocation' . $i;
            $newSection[] = FormLib::get($curName);
            $oldSection[] = FormLib::get($oldName);
        }
        
        foreach ($newSection as $key => $floorsection) {
            $args = array($floorsection, $upc, $oldSection[$key]);
            $prep = $dbc->prepare('
                UPDATE FloorSectionProductMap
                SET floorSectionID = ?
                WHERE upc = ? 
                    AND floorSectionID = ?;
            ');
            $dbc->execute($prep, $args);
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }
            echo $floorsection . '<br>';
        }
        
        
        
        
        /*
        $error = array('error'=>0);        
        $model->upc($upc);
        $model->floorSectionID($section);
        //$model->store_id($store_id);
        $result = $model->save();
        */
        
        /*
        $args = array($upc, $section);
        $prep = $dbc->prepare('
            INSERT INTO FloorSectionProductMap (upc, floorSectionID) 
            VALUES (?, ?)
        ');
        $dbc->execute($prep, $args);
        */
        $ret = '';
        if ($dbc->error()) {
            //$error['error'] = 1;
            $ret .= '<div class="alert alert-danger">Save Failed</div>';
            $ret .= '<div class="alert alert-warning">Error: ' . $dbc->error() . '</div>';
        } else {
            $ret .= '<div class="alert alert-success">Product Location Saved</div>';
        }
        $ret .= '<a class="btn btn-default" href="http://localhost/IS4C/fannie/item/ProdLocationEditor.php">Return</a><br><br>';
        
        return $ret;
    }
    
    function post_batch_save_view()
    {
        echo 'post_batch_save_view<br>';
        
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $store_id = $_POST['store_id'];
        $start = $_GET['start'];
        $end = $_GET['end'];
        $ret = '';
        $item = array();
        foreach ($_POST as $upc => $section) {
            if (strlen($upc) == 13) $item[$upc] = $section;
        }
            
        /*
        $model = new FloorSectionProductMapModel($dbc);
        foreach ($item as $upc => $section) {
            echo $section . '<br>';
            $model->upc($upc);
            //$model->store_id($store_id); //deprecated
            $model->floorSectionID($section);
            $model->save();
        }
        */
        foreach ($item as $upc => $section) {
            $args = array($upc, $section );
            $prep = $dbc->prepare('
                INSERT INTO FloorSectionProductMap (upc, floorSectionID) values (?, ?);
            ');
            $dbc->execute($prep, $args);    
        }
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        } else {
            $ret .= '<div class="alert alert-success">Update Successful</div>';
        }        
        
        $ret .= '<br><br><a class="btn btn-default" href="javascript:history.back()">Back</a><br><br>';
        $ret .= '<a class="btn btn-default" href="http://localhost/IS4C/fannie/item/ProdLocationEditor.php">Return</a><br><br>';
        
        return $ret;
    }

    function get_start_view()
    {
        echo '<h4>get_start_view</h4>';
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $start = FormLib::get('start');
        $end = FormLib::get('end');
        $store_id = FormLib::get('store_id');
        $args = array($start, $end, $store_id);
            
            $query = $dbc->prepare('
                select 
                    p.upc, 
                    p.description as pdesc, 
                    p.department,
                    pu.description as pudesc,
                    p.brand,
                    d.dept_name
                from products as p 
                    left join FloorSectionProductMap as pp on pp.upc=p.upc 
                    left join batchList as bl on bl.upc=p.upc 
                    left join batches as b on b.batchID=bl.batchID 
                    left join productUser as pu on pu.upc=p.upc
                    left join departments as d on d.dept_no=p.department
                where b.batchID >= ?
                    and b.batchID <= ?
                    and p.store_id= ?
                    and (pp.floorSectionID is NULL OR pp.floorSectionID=0)
                    AND department NOT BETWEEN 508 AND 998
                    AND department NOT BETWEEN 250 AND 259
                    AND department NOT BETWEEN 225 AND 234
                    AND department NOT BETWEEN 1 AND 25
                    AND department NOT BETWEEN 61 AND 78
                    AND department != 46
                    AND department != 150
                    AND department != 208
                    AND department != 235
                    AND department != 240
                    AND department != 500
                order by p.department;
            ');
            $result = $dbc->execute($query, $args);
            $item = array();
            while($row = $dbc->fetch_row($result)) {
                $item[$row['upc']]['upc'] = $row['upc'];
                $item[$row['upc']]['dept'] = $row['department'];
                $item[$row['upc']]['desc'] = $row['pdesc'];
                $item[$row['upc']]['brand'] = $row['brand'];
                $item[$row['upc']]['dept_name'] = $row['dept_name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            } 
            
            //  Find suggestions for each item's location based on department.
            foreach ($item as $key => $row) {
                $item[$key]['sugDept'] = self::getLocation($item[$key]['dept']);
            }

            $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections
                ORDER BY name;');
            $result = $dbc->execute($query);
            $floor_section = array();
            while($row = $dbc->fetch_row($result)) {
                $floor_section[$row['floorSectionID']] = $row['name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }    
            
            $ret = "";
            $ret .= '<table class="table">
                <thead>
                    <th>UPC</th>
                    <td>Brand</th>
                    <td>Description</th>
                    <td>Dept. No.</th>
                    <td>Department</th>
                    <td>Location</th>
                </thead>
                <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="batch" value="1">
                    <input type="hidden" name="start" value="' . $start . '">
                    <input type="hidden" name="end" value="' . $end . '">
                    <input type="hidden" name="store_id" value="' . $store_id . '">
                ';
            foreach ($item as $key => $row) {
                $ret .= '
                    <tr><td><a href="ItemEditorPage.php?searchupc=' . $key . '" target="">' . $key . '</a></td>
                    <td>' . $row['brand'] . '</td>
                    <td>' . $row['desc'] . '</td>
                    <td>' . $row['dept'] . '</td>
                    <td>' . $row['dept_name'] . '</td>
                    <td><Span class="collapse"> </span>
                        <select class="form-control input-sm" name="' . $key . '" value="" />
                            <option value="NULL">* no location selected *</option>';
                    
                    foreach ($floor_section as $fs_key => $fs_value) {
                        if ($fs_key == $item[$key]['sugDept']) {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
                        } else {
                            $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                        }
                    }
                            
                    $ret .= '</tr>';
            }
            
        $ret .= '<tr><td><input type="submit" class="btn btn-default" value="Update Locations"></td>
            <td><a class="btn btn-default" href="http://localhost/IS4C/fannie/item/ProdLocationEditor.php">Back</a><br><br></td></table>
            </form>';   
            
                
        return $ret;
    }
    
    function get_batch_view()
    {
        echo '<h4>get_batch_view</h4>';
        $ret = "";
        $ret .= '
            <form method="get"class="form-inline">
            
            <div class="input-group" style="width:200px;">
                <span class="input-group-addon">Batch#</span>
                <input type="text" class="form-control inline" name="start" autofocus required>
            </div>
            <div class="input-group" style="width:200px;">
                <span class="input-group-addon">to Batch#</span>
                <input type="text" class="form-control inline" name="end" required><br>                
            </div><br><br>
            
                <input type="hidden" name="store_id" value="1" required>
                
                <input type="submit" class="btn btn-default" value="Find item locations">
            </form><br>
            <a class="btn btn-default" href="http://localhost/IS4C/fannie/item/ProdLocationEditor.php">Back</a><br><br>
        ';
        
        return $ret;
    }
    
    function get_searchupc_view()
    {
        echo '<h4>get_searchupc_view</h4>';
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $query = $dbc->prepare('SELECT
                    floorSectionID,
                    name
                FROM FloorSections
                ORDER BY name;');
            $result = $dbc->execute($query);
            $floor_section = array();
            while($row = $dbc->fetch_row($result)) {
                $floor_section[$row['floorSectionID']] = $row['name'];
            }
            if (mysql_errno() > 0) {
                echo mysql_errno() . ": " . mysql_error(). "<br>";
            }
            $floor_section['none'] = 'none';
            
        $ret = '';
        $ret .= '<div class="container"><div class="row"><div class="col-md-5">';
        $ret .= '
            
            <form class="form-inline" method="get">
                <input type="hidden" name="store_id" class="form-control" style="width: 315px;">
                <br><br>
                <div class="input-group">
                    <span class="input-group-addon">UPC</span>
                    <input type="text" class="form-control" id="upc" style="width: 200px" name="upc" autofocus required>&nbsp;&nbsp;
                    <input type="hidden" class="btn btn-default" style="width: 300px" name="searchupc" value="Update Locations by UPC">
                    <input type="submit" class="btn btn-default" value="Go">
                </div>
            </form><br>
        ';
        
        //  Delete me please!
        echo '
            <div class="input-group">
                <span class="input-group-addon">test</span>
                <select class="form-control" style="width: 200px"></select>
            </div>
        ';
        
        if ($upc = FormLib::get('upc')) {
            $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
            $store_id = FormLib::get('store_id');
            $args = array($upc, $store_id);
            $prep = $dbc->prepare('
                SELECT 
                    p.upc,
                    p.description,
                    f.floorSectionID,
                    p.department,
                    d.dept_name,
                    p.brand
                FROM products AS p
                    left join FloorSectionProductMap as f on f.upc=p.upc 
                    left join departments as d on d.dept_no=p.department
                WHERE p.upc = ?
                    AND p.store_id = ?
                GROUP BY f.floorSectionID
            ');
            $result = $dbc->execute($prep, $args);
            $curLocation = array();
            while ($row = $dbc->fetch_row($result)) {
                $floorID = $row['floorSectionID'];
                $brand = $row['brand'];
                $department = $row['department'];
                $dept_name = $row['dept_name'];
                $description = $row['description'];
                $curLocation[] = $row['floorSectionID'];
                $sugLocation = self::getLocation($row['department']);
                //$floor_section[$row['floorSectionID']] == the name of the floor section.
            }
            
            $ret .= '<div class="panel panel-default" style="width: 400px; border: none;">';
                $ret .= '<table class="table table-striped">';
                $ret .= '<tr><td><b>UPC: </b></td><td>' . $upc . '</td></tr>';
                $ret .= '<tr><td><b>Brand / Description: </b></td><td>' . $brand . ' - ' . $description . '</td></tr>';
                $ret .= '<tr><td><b>Department: </b></td><td>' . $department . ' - ' . $dept_name . '</td></tr>';
                $ret .= '<tr><td><b>Suggested Location: </b></td><td>' . $floor_section[$sugLocation] . '</td></tr>';
                $ret .= '</table></div>';
                $ret .= '<div class="well">spot-holder for adding a<br>new location for this item.<br></div>';
                
                $ret .= '<a class="btn btn-default" href="http://localhost/IS4C/fannie/item/ProdLocationEditor.php">Back</a><br><br>';                
                $ret .= '</div><div class="col-md-5">'; //end of column A
                
                $ret .= '
                    <form method="post">
                    <input type="hidden" name="save" value="1">
                    <input type="hidden" name="upc" value="' . $upc . '">
                ';
                $count = 0;
                foreach ($curLocation as $value) {
                    $count++;
                    $name = 'section' . $count; 
                    $oldName = 'oldLocation' . $count;
                    $ret .= '<input type="hidden" name="' . $oldName . '" value="' . $value . '">';
                    $ret .= '
                        <div class="panel panel-default" style="width: 300px;">
                        <div class="panel-heading"><b>Location #' . $count . '</b></div>
                            <select name="' . $name . '" class="form-control" style="width: 100%;">
                    ';
                    foreach ($floor_section as $fs_key => $fs_value) {
                        $ret .= '<option value="' . $fs_key . '" name="' . $fs_key . '"';
                        if ($value == $fs_key) {
                            $ret .= 'selected';
                        }    
                        $ret .= '>' . $fs_value . '</option>';
                    }
                    $ret .= '</select></div>';
                }
                
            
            /*$ret .= '
                <select name="section" class="form-control" style="width: 100%;">
                    <option value="0">No Location Designated</option>';
            foreach ($floor_section as $fs_key => $fs_value) {
                $ret .= '<option value="' . $fs_key . '" name="' . $fs_key . '"';
                if ($floorID) {
                    $ret .= 'selected';
                }    
                $ret .= '>' . $fs_value . '</option>';
            }
            
            $ret .= '</select>';*/
            //$ret .= '</div>';
            $ret .= '
                <input type="submit" class="btn btn-default" style="width: 250px;" value="Update Locations"><br><br>
                <input type="hidden" name="numolocations" value="' . $count . '">
                </form>
            ';
            
            
            /*
            foreach ($floor_section as $fs_key => $fs_value) {
                if ($fs_key == $item[$key]['sugDept']) {
                    $ret .= '<option value="' . $fs_key . '" name="' . $key . '" selected>' . $fs_value . '</option>';
                } else {
                    $ret .= '<option value="' . $fs_key . '" name="' . $key . '">' . $fs_value . '</option>';
                }
            }*/
            
        }   
        
        $ret .= '</div></div></div>'; //<column B><row><container>
        
        return $ret;
    }
    
    function get_view()
    {
        return '
            <h4>get_view</h4>
            <div class="container pull-left">
            <form class="form-inline" method="get">
                <input type="submit" class="btn btn-default" style="width: 300px" name="searchupc" value="Update Locations by UPC"><br><br>
            </form>
            <form class="form-inline" method="get">
                <input type="submit" class="btn btn-default" style="width: 300px" name="batch" value="Update Locations by BATCH"><br><br>
            </form>
            </div>
        ';
    }

    public function helpContent()
    {
        return '<p>
            Edit the physical sales-floor location
            of products found in batches that fall within a 
            specified range of batch IDs.
            <lu>
                <li><b>Update Locations by UPC</b> Update or add locations for an individual item.</li>
                <li><b>Update Locations by BATCH</b> Generate a list of items included in a desired range of 
                    batch IDs to update multiple locations at once.</li>
            </lu>
            </p>
            ';
    }
    
    public function getLocation($dept)
    {
        //  Suggest physical location of product based on department.
        if ($dept>=1 && $dept<=17) {
            //return 
            return 16;
        } elseif ($dept==245) {
            //bulk 1
            return 16;
        } elseif ($dept==39 || $dept==40 || $dept==44 || $dept==45 ||
        $dept==260 || $dept==261) {
            // Cool 1
            return 11;
        } elseif ($dept==38 || $dept==41 || $dept==42){
            // Cool 2
            return 13;
        } elseif ($dept==32 || $dept==35 || $dept==37 ) {
            // Cool 3
            return 14;
        } elseif ( ($dept>=26 && $dept<=31)
            || ($dept==34 ) ) {
            // Cool 4
            return 15;
        } elseif( ($dept>=170 && $dept<=173)
            || ($dept==160 ) || ($dept==169) ) {
            // Grocery 1
            return 1;
        } elseif( ($dept==156) || ($dept==161) || ($dept==163)
            || ($dept==166) || ($dept==172) || ($dept==174)
            || ($dept==175) || ($dept==177) ) {
            // Grocery 2
            return 2;
        } elseif( ($dept==153) || ($dept==157)
            || ($dept==164) || ($dept==167) || ($dept==168)
            || ($dept==176) ) {
            // Grocery 3
            return 3;
        } elseif( ($dept==151) || ($dept==152) || $dept==182) {
            // Grocery 4
            return 4;
        } elseif( ($dept==159) || ($dept==155) ) {
            // Grocery 5
            return 5;
        } elseif($dept==158) {
            // Grocery 6
            return 6;
        } elseif($dept==165) {
            // Grocery 7
            return 7;
        } elseif($dept==159 || $dept==162 || $dept==179 
            || $dept==154 || $dept==242) {
            // Grocery 8
            return 8;
        } elseif($dept==88 || $dept==90 || $dept==95 ||
            $dept==96 || $dept==98 || $dept==99 ||
            $dept==100) {
            // Wellness 1
            return 9;
        } elseif($dept==86 ||$dept==87 || $dept==89 ||
            $dept==90 || $dept==90 || $dept==91 ||
            $dept==94 || $dept==97 || $dept==102 ||
            $dept==93 || $dept==104) {
            // Wellness 2
            return 10;
        } elseif($dept==101 || ($dept>=105 && $dept<=109) ) {
            // Wellness 3
            return 12;
        } else {
            return 'none';
        }
        
    }
    
}

FannieDispatch::conditionalExec();

