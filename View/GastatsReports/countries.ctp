<?php
$countries = (isset($countries) ? $countries : array());
?>

<table class="pad-td-5">
<thead>
<tr>
	<th>From</th>
	<th>To</th>
	<th>Country</th>
	<th>Visits</th>
</tr>
</thead>
<tbody>
	<?php foreach ($countries as $country => $visits):?>
		<tr>
		<td><?php echo $start_date; ?></td>
		<td><?php echo $end_date; ?></td>
		<td><?php echo $country; ?></td>
		<td><?php echo $visits;?></td>
		</tr>
	<?php endforeach; ?>
</tbody>
</table>

