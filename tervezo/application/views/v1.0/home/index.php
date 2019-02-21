<div class="home inside-content">
  <div class="wrapper">
    <div class="side-left">
      <div class="bubble-block block-szinek">
        <div class="header">
          <div class="title">
            <?php echo __('Színek'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          ...
        </div>
      </div>
      <div class="bubble-block block-motivum">
        <div class="header">
          <div class="title">
            <?php echo __('Minta'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          <?php
            $js = "ctx.beginPath();
              ctx.moveTo(0.5, 0.5);
              ctx.lineTo(200.5, 0.5);
              ctx.lineTo(200.5, 200.5);
              ctx.lineTo(0.5, 200.5);
              ctx.lineTo(0.5, 0.5);
              ctx.fillStyle = colors[0];
              ctx.fill();
              if (c) {
              ctx.stroke();
              }
              ctx.beginPath();
              ctx.moveTo(100.5, 200.5);
              ctx.lineTo(100.5, 0.5);
              ctx.lineTo(0.5, 0.5);
              ctx.lineTo(0.5, 100.5);
              ctx.lineTo(200.5, 100.5);
              ctx.lineTo(200.5, 200.5);
              ctx.lineTo(100.5, 200.5);
              ctx.fillStyle = colors[1];
              ctx.fill();
              if (c) {
              ctx.stroke();
              }";
            echo "<script>
              $(function(){
                var c = false;
                var colors = [];
                colors[0] = '#000000';
                colors[1] = '#ffffff';
                var motivum = document.getElementById('motivum'),
                  eleft = motivum.offsetLeft,
                  etop = motivum.offsetTop;
                var ctx = motivum.getContext('2d');
                ctx.scale(1,1);
                ".$js."
                motivum.addEventListener('click', function(event) {
                  var x = event.pageX - eleft,
                      y = event.pageY - etop;
                  console.log(event);
                }, false);

              });
            </script>";
          ?>
          <br>
          <canvas id="motivum" width="200" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="side-right">
      <div class="bubble-block block-tervezo">
        <div class="header">
          <div class="title">
            <?php echo __('Tervező'); ?>
          </div>
          <div class="act">
            <i class="fa fa-angle-down"></i>
          </div>
        </div>
        <div class="cwrapper">
          <br><br><br><br><br><br><br><br><br>
        </div>
      </div>
    </div>
  </div>
</div>
