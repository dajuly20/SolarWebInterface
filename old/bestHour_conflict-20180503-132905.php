<?php

require "sqlConn.php";

$id = 100;
$sql = "SELECT id, curtime as mynow,
       DATE_FORMAT(curtime, '%H%:%i') as date,
       
       round(min(current)/10,2) as 'min-cur', 
	   round(avg(current)/10,2) as 'avg-cur',
       round(max(current)/10,2) as 'max-cur', 
       
       round(min(power)/1000,2) as 'min-pow',
       round(avg(power)/1000,2) as 'avg-pow', 
       round(max(power)/1000,2) as 'max-pow',
       
       round(min(charge)/1000,2) as 'charge',
	   round(max(charge)/1000,2) as 'chargem',
	   
       round((min(charge) - max(charge))/1000,2) as chargedThisHour
       
      FROM $_sqlTableNameFull WHERE DAY(curtime) = day(now()) GROUP BY HOUR(curtime) ORDER BY mynow desc
";


// new statement
$sql = "
		SELECT id as _cid, curtime as mynow,
	
       DATE_FORMAT(curtime, '%d%.%m.%Y %H:00') as date,
       ROUND((select charge from MeasurementData.SolarPower WHERE max(_cid) =  id)/1000,2) as charge,
       round((((select charge from MeasurementData.SolarPower WHERE max(_cid) =  id) - charge)/1000),2) as chgDiff,
      
       
	   round(min(current)/10,2) as 'min-cur', 
	   round(avg(current)/10,2) as 'avg-cur',
       round(max(current)/10,2) as 'max-cur', 
       
	   round(min(power)/1000,2) as 'min-pow',
       round(avg(power)/1000,2) as 'avg-pow', 
       round(max(power)/1000,2) as 'max-pow'
       
	   
      FROM MeasurementData.SolarPower 
	  #WHERE DAY(curtime) = day(now()) 
	  GROUP BY date ORDER BY chgDiff asc
	  LIMIT 0,10;
	  ";

$statement = $mysqli->prepare($sql);

if ( false===$statement ) {
  // and since all the following operations need a valid/ready statement object
  // it doesn't make sense to go on
  // you might want to use a more sophisticated mechanism than die()
  // but's it's only an example
  die('prepare() failed: ' . htmlspecialchars($mysqli->error));
}



//$rc = $statement->bind_param('i', $id);
// bind_param() can fail because the number of parameter doesn't match the placeholders in the statement
// or there's a type conflict(?), or ....
// if ( false===$rc ) {
  // // again execute() is useless if you can't bind the parameters. Bail out somehow.
  // die('bind_param() failed: ' . htmlspecialchars($stmt->error));
// }

$rc = $statement->execute();
//$statement->store_result();
 
 
if($rc){
$result = $statement->get_result();
$numRows =  $result->num_rows;
?>


<table class="rwd-table">
  <tr>
    <th>Stunde</th>
    <th>Strom (Min; Ø; Max)</th>
    <th>Leistung (Min; Ø; Max)</th>
    <th>Gesamt-Ladung</th>
	<th>Ladung in dieser Stunde</th>
  </tr>

  <?php
  $chgLast = 0;
 
  

  if($numRows == 0) $numRows = 1;
  $colorFull = 255; $colorStep = $colorFull / $numRows; $colorI = $colorFull;
  
  echo "Anzahl Zeilen: $numRows Colorstep: $colorStep";
  while($row = $result->fetch_assoc()) { 
  
  ?>
  <tr>
	<td data-th="Stunde">					<?php echo $row["date"];  	?></td>
    <td data-th="Strom (Min; Ø; Max)">		<?php echo $row["min-cur"]; ?>A <?php echo $row["avg-cur"]; ?>A <?php echo $row["max-cur"]; ?>A</td>		</td>
    <td data-th="Leistung (Min; Ø; Max)">	<?php echo $row["min-pow"]; ?>W <?php echo $row["avg-pow"]; ?>W <?php echo $row["max-pow"]; ?>W		</td>
    <td data-th="Gesamt-Ladung">			<?php echo $row["charge"];  	?>A/h</b>	</td>
	
	<td data-th="Ladung in dieser Stunde"><div><p style="color:rgb(<?php echo $colorI;?>,0,0); font-weight: bold;"><?php /*  if($chgLast != 0){ echo round($chgLast-$row["charge"],2); }else{ echo " --- ";} */ echo $row["chgDiff"];     ?></p>A/h</div>	</td>
  </tr>
  <?php 
  $chgLast = $row["charge"];
$colorI -= $colorStep;
  } ?>
<tr> 
<th colspan="6"><small>Stand: <?php echo date("H:i:s");?></small></th>
</tr>
</table>
<?php

}
else{
	
	die('execute() failed: ' . htmlspecialchars($stmt->error))."<br>";
	echo $mysqli->error;
}
?>

