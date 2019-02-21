<div class="wp mh">
	<div style="padding:25px;">
		<h1 style="font-size:3em;">A keresett oldal nem található!</h1>
        <br />
        	<div style="color:#ec5c5c;" align="center">
            	<strong>Hibás oldal:</strong> <br />
				<?=DOMAIN.substr($_SERVER['REQUEST_URI'],1)?>
            </div>
		<br />
        <br />
        	<table width="500">
        		<tr>
        			<td colspan="2" align="left">
                    	<strong>A következő okok egyike miatt:</strong>
                        <div class="light">
                        	<ul>
                        		<li>A keresett oldal nem létezik.</li>
                                <li>A keresett oldal elérhetősége hibás.</li>
                                <li>A keresett oldal korábban létezett, de azóta el lett távolítva.</li>
                        	</ul>
                        </div>
                    </td>
        		</tr>
                <tr>
                	<td align="left"><a href="<?=$_SERVER['HTTP_REFERER']?>">&larr; vissza az előző oldalra</a></td>
                	<td align="right"><a href="/">Főoldal</a> &nbsp;|&nbsp; <a href="/kapcsolat">Kapcsolat &rarr;</a></td>
                </tr>
        	</table>
        <br />
		<img src="<?=IMG?>404_hal.png" alt="" />
    </div>
</div>