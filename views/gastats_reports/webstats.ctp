<?php
$stats = (isset($stats) ? $stats : array());
?>

<table class="pad-td-5">
<thead>
<tr>
	<th>From</th>
	<th>To</th>
	<th>Metric</th>
	<th>Value</th>
</tr>
</thead>
<tbody>
	<?php foreach ($stats as $metric => $value):?>
		<tr>
		<td><?php echo $start_date; ?></td>
		<td><?php echo $end_date; ?></td>
		<td><?php echo $metric; ?></td>
		<td><?php echo $value;?></td>
		</tr>
	<?php endforeach; ?>
</tbody>
</table>

