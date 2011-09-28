<?php
$contents = (isset($contents) ? $contents : array());
?>

<table class="pad-td-5">
<thead>
<tr>
	<th>From</th>
	<th>To</th>
	<th>Path</th>
	<th>Views</th>
</tr>
</thead>
<tbody>
	<?php foreach ($contents as $contents => $views):?>
		<tr>
		<td><?php echo $start_date; ?></td>
		<td><?php echo $end_date; ?></td>
		<td><?php echo $contents; ?></td>
		<td><?php echo $views;?></td>
		</tr>
	<?php endforeach; ?>
</tbody>
</table>
