<?php
$ads = (isset($ads) ? $ads : array());
$ads_unique = (isset($ads['unique']) ? $ads['unique'] : array());
?>

<table class="pad-td-5">
<thead>
	<tr>
		<th>Partner</th>
		<th>From</th>
		<th>To</th>
		<th>Ad</th>
		<th>Loc</th>
		<th>Slot</th>
		<th>Views</th>
		<th>Clicks</th>
	</tr>
</thead>
<tbody>
<?php  foreach ($ads_unique as $ad_id => $ad): ?>
		<?php
			$ad_partner_id = ($corp_id > 0 ? $corp_id : $corps[$ad_id]);
		?>
		<?php foreach ($ad as $ad_loc => $slots): ?>
			<?php foreach ($slots as $ad_slot => $actions): ?>
				<tr>
				<td><?php echo $ad_partner_id; ?></td>
				<td><?php echo $start_date; ?></td>
				<td><?php echo $end_date; ?></td>
				<td><?php echo $ad_id; ?></td>
				<td><?php echo $ad_loc;?></td>
				<td><?php echo $ad_slot;?></td>
				<td><?php echo (isset($actions['view']) ? $actions['view'] : 0);?></td>
				<td><?php echo (isset($actions['click']) ? $actions['click'] : 0);?></td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
<?php endforeach; ?>
</tbody>
</table>
