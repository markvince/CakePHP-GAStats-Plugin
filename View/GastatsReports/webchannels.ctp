<?php
$webchannels = (isset($webchannels) ? $webchannels : array());
?>

<?php foreach ($webchannels as $corp_id => $webchannel): ?>
	<table class="pad-td-5">
	<thead>
	<tr>
		<th width="170">Channel</th>
		<th>From</th>
		<th>To</th>
		<th>Metric</th>
		<th>Value</th>
	</tr>
</thead>
<tbody>
	<?php foreach ($webchannel['metrics'] as $metric => $value):?>
		<tr>
		<td><?php echo $webchannel['channel']; ?></td>
		<td><?php echo $start_date; ?></td>
		<td><?php echo $end_date; ?></td>
		<td><?php echo $metric; ?></td>
		<td><?php echo $value;?></td>
		</tr>
	<?php endforeach; ?>
</tbody>
</table>
<?php endforeach;?>

