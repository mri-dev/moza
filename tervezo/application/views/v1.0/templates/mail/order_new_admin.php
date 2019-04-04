<? require "head.php"; ?>
<h2>Új megrendelés igény érkezett!</h2>
<div><h3>Megrendelő adatok</h3></div>
<table class="if" width="100%" border="1" style="border-collapse:collapse;" cellpadding="10" cellspacing="0">
	<tbody style="color:#888;">
		<tr>
			<th>Név:</th>
			<td><?=$order_name?></td>
		</tr>
		<tr>
			<th>E-mail:</th>
			<td><?=$order_email?></td>
		</tr>
		<tr>
			<th>Telefon:</th>
			<td><?=$order_phone?></td>
		</tr>
	</tbody>
</table>

<div><h3>Összesítés</h3></div>
<table class="if smaller-tbl" width="100%" border="1" style="border-collapse:collapse;" cellpadding="10" cellspacing="0">
	<tbody style="color:#888;">
		<?php
		$previews = array();
		foreach ((array)$motifs as $m):
			$me_db = (int)$qtyconfig[$m['hashid']]['db'];
			$me_nm = (int)$qtyconfig[$m['hashid']]['nm'];

			if (!array_key_exists($m['hashid'],$previews)) {
				$previews[$m['hashid']] = array(
					'img' => $m['imageurl'],
					'minta' => $m['minta']
				);
			}
		?>
		<tr>
			<td width="80">
				<img src="<?=$m['imageurl']?>" width="80" height="80" alt="Minta: <?=$m['minta']?>">
			</td>
			<td>
				Minta: <strong><?=$m['minta']?></strong><br>
				<strong>Színkonfiguráció:</strong><br>
				<?php foreach ((array)$m['coloring'] as $c): ?>
					<div class="">
						<span class="color-preview" style="display: block; float: left; width: 20px; height: 20px; background:<?=$c['rgb']?>;">&nbsp;</span>&nbsp;
						<strong><?=$c['obj']['kod']?></strong> - <?=$c['obj']['neve']?> &bull; <?=$c['obj']['szin_ncs']?>
					</div>
					<div class="clr"></div>
				<?php endforeach; ?>
			</td>
			<td>
				<div class="me">Darab: <strong><?=$me_db?></strong></div>
				<div class="me">Négyzetméter: <strong><?=$me_nm?></strong></div>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div><h3>Előnézet</h3></div>
<table class="preview" width="100%" border="1" style="border-collapse:collapse;" cellpadding="10" cellspacing="0">
	<tbody style="color:#888;">
		<?php for($x = 0; $x < (int)$gridsizes['x']; $x++){ ?>
		<tr>
			<?php for($y = 0; $y < (int)$gridsizes['y']; $y++){
					$key = $gridconfig[$x.'x'.$y]['hashid'];
			?>
			<td>
				<img src="<?=$previews[$key]['img']?>" alt="Minta: <?=$previews[$key]['minta']?>">
			</td>
			<? } ?>
		</tr>
		<? } ?>
	</tbody>
</table>

<br>
<strong>A megrendelés adatlapja megtekinthető a következő linken:</strong><br />
<a href="<?=$settings['domain']?>/order/<?=$hashkey?>"><?=$settings['domain']?>/order/<?=$hashkey?></a>
</div>
<? require "footer.php"; ?>
