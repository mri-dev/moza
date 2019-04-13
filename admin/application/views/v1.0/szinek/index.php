<div class="row">
  <div class="col-md-12">
    <h1>Színek</h1>
    <? if($this->err): ?>
    	<?=$this->bmsg?>
    <? endif; ?>
  </div>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="card <?=( $this->color_d ) ? 'border-danger':( ($this->color)?'border-success':'' )?>">
      <div class="card-header">
        <?php if( $this->color_d ): ?>
          Szín törlése
        <?php else: ?>
          <?=($this->color ? 'Szín szerkesztése':'Új szín rögzítése')?>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <? if( $this->color_d ): ?>
        Biztos benne, hogy törli a(z) <strong><u><?=$this->color_d->getName()?></u></strong> elnevezésű színt? A művelet nem visszavonható!
  			<div class="row np">
  				<div class="col-md-12 right">
  					<form action="" method="post">
  						<a href="/szinek/" class="btn btn-danger"><i class="fa fa-times"></i> Mégse</a>
  						<button name="delColor" value="1" class="btn btn-success">Igen, véglegesen törlöm <i class="fa fa-check"></i></button>
  					</form>
  				</div>
  			</div>
        <?php else: ?>
          <form action="" method="post" class="p-0">
					<div class="row" style="margin: 0 -15px;">
            <div class="col-md-9">
							<label for="kod">Azonosító*</label>
							<input type="text" id="kod" name="kod" value="<?= ( $this->err ? $_POST['kod'] : ($this->color ? $this->color->getAzonosito():'') ) ?>" class="form-control">
						</div>
						<div class="col-md-3">
							<label for="sortnum">Sorrend</label>
							<input type="number" id="sortnumber" name="sortnumber" value="<?=($this->err ? $_POST['sortnumber']:($this->color ? $this->color->getSortNumber() : '0'))?>" class="form-control">
						</div>
					</div>
          <br>
          <div class="row" style="margin: 0 -15px;">
						<div class="col-md-12">
							<label for="name">Elnevezés (HU)*</label>
							<input type="text" id="name" name="name" value="<?= ( $this->err ? $_POST['name'] : ($this->color ? $this->color->getName():'') ) ?>" class="form-control">
						</div>
					</div>
          <br>
          <div class="row" style="margin: 0 -15px;">
						<div class="col-md-12">
							<label for="name_en">Elnevezés (EN)</label>
							<input type="text" id="name_en" name="name_en" value="<?= ( $this->err ? $_POST['name_en'] : ($this->color ? $this->color->getName('en'):'') ) ?>" class="form-control">
						</div>
					</div>
					<? if( true ): ?>
					<br>
					<div class="row" style="margin: 0 -15px;">
            <div class="col-md-7">
							<label for="szin_rgb">Szín (RGB)*</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">#</span>
                </div>
                <input type="text" id="szin_rgb" onkeyup="$('#rgb_preview').css({backgroundColor:'#'+$(this).val()})" name="szin_rgb" value="<?= ( $this->err ? $_POST['szin_rgb'] : ($this->color ? $this->color->getRGB():'') ) ?>" class="form-control">
                <div class="input-group-append">
                  <span class="input-group-text" id="rgb_preview" <? if($this->color): ?>style="background-color:#<?=$this->color->getRGB()?>;"<? endif; ?>>&nbsp;</span>
                </div>
              </div>
						</div>
            <div class="col-md-5">
							<label for="szin_ncs">Színkód (NCS)</label>
							<input type="text" id="szin_ncs" name="szin_ncs" value="<?= ( $this->err ? $_POST['szin_ncs'] : ($this->color ? $this->color->getNCS():'') ) ?>" class="form-control">
						</div>
					</div>
					<? endif; ?>

					<br>
					<div class="row np">
						<div class="col-md-12 right">
							<? if($this->color): ?>
							<a href="/szinek/" class="btn btn-danger"><i class="fa fa-times"></i> mégse</a>
							<? endif; ?>
							<button name="<?=($this->color ? 'saveColor':'addColor')?>" value="1" class="btn btn-<?=($this->color ? 'success':'primary')?>"><?=($this->color ? 'Változások mentése <i class="fa fa-save">':'Rögzítés <i class="fa fa-plus">')?></i></button>
						</div>
					</div>
				</form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        Rögzített színek
      </div>
      <div class="card-body">
        <?
  				if( false ):
  				while( $this->colors->walk() ):
  				$cat = $this->colors->the_cat();
  			?>
        <?
  				if($cat['deep'] == 1) {
  					echo '&mdash;';
  				} else if($cat['deep'] == 2) {
  					echo '&mdash;&mdash;';
  				}	 else if($cat['deep'] == 3) {
  					echo '&mdash;&mdash;&mdash;';
  				}

  			?>
  			<STRONG style="color:#2c3e50;"><?=$cat['neve']?></STRONG> &nbsp;&mdash;&nbsp; <SPAN STYLE="COLOR:#43a0de;"><?=$cat[hashkey]?></SPAN><BR>
  			<? endwhile; endif;  ?>
        <div class="row np row-head">
  				<div class="col-md-2"><em>Azonosító</em></div>
  				<div class="col-md-3"><em>Elnevezés</em></div>
  				<div class="col-md-2 center"><em>Szín (RGB)</em></div>
  				<div class="col-md-2 center"><em>Színkód (NCS)</em></div>
  				<div class="col-md-2 center"><em>Sorrend</em></div>
  				<div class="col-md-1"></div>
  			</div>
  			<div class="categories">
  				<?
  					while( $this->colors->walk() ):
  					$cat = $this->colors->the_cat();
  				?>
  				<div style="line-height:32px;" class="row np deep<?=$cat['deep']?> <?=($this->color && $this->color->getId() == $cat['ID'] ? 'on-edit' : ( $this->color_d && $this->color_d->getId() == $cat['ID'] ? 'on-del':'') )?>">
            <div class="col-md-2">
              <div class="float-left" style="width:32px; height: 32px; margin-right: 5px; background:#<?=$cat['szin_rgb']?>; border: 1px solid #f1f1f1;">&nbsp;</div>
              <div class="">
                <?php echo $cat['kod']; ?>
              </div>
            </div>
            <div class="col-md-3">
  						<a href="/szinek/szerkeszt/<?=$cat['ID']?>" title="Szerkesztés"><strong><?=$cat['neve']?></strong><?php if ($cat['neve_en'] != ''): ?> / <?=$cat['neve_en']?><?php endif; ?></a>
  					</div>
            <div class="col-md-2 center">
              #<?=$cat['szin_rgb']?>
            </div>
            <div class="col-md-2 center">
              <?=$cat['szin_ncs']?>
            </div>
  					<div class="col-md-2 center">
  						<?=$cat['sorrend']?>
  					</div>
                      <div class="col-md-1 actions" align="right">
                      	<a href="/szinek/torles/<?=$cat['ID']?>" title="Törlés"><i class="fa fa-times"></i></a>
                      </div>
  				</div>
  				<? endwhile; ?>
			   </div>
      </div>
    </div>
  </div>
</div>
