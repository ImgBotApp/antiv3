<?php
/**
 * A Bootstrap carousel / slider
 *
 * expects:
 * $CAROUSEL_ID: the name of the carousel, used to create a unique carousel ID
 * $CAROUSEL_CONTENT: array of items to be shown (wrap in <div class="item '> to display properly)
 * $CAROUSEL_PAUSE: should the carousel pause by default? (optional)
 * $CAROUSEL_INTERVAL
 */
?>
<div id="<?php echo $CAROUSEL_ID; ?>" class="carousel slide" data-interval="<?php echo $CAROUSEL_INTERVAL ?>">
  <!-- Wrapper for slides -->
  <div class="carousel-inner" role="listbox">
  <?php
  foreach ($CAROUSEL_CONTENT as $content) {
    echo $content;
    $ids[$i] = $i;
    $i++;
    }
  ?>
  <!-- Indicators -->
  <ol class="carousel-indicators">
    <?php
    foreach ($ids as $o) {
    ?>
    <li data-target="#<?php echo $CAROUSEL_ID; ?>" data-slide-to="<?php echo $o ?>" class="<?php echo ($o == 0) ? 'active' : ''; ?>"></li>
    <?php
    }
    ?>
  </ol>
</div><!-- .carousel-inner -->
  <!-- Controls -->
  <a class="left carousel-control" href="#<?php echo $CAROUSEL_ID; ?>" role="button" data-slide="prev">
    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>
  <a class="right carousel-control" href="#<?php echo $CAROUSEL_ID; ?>" role="button" data-slide="next">
    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
</div><!-- .carousel -->
<?php $paused = ($CAROUSEL_PAUSE == true) ? '\'pause\'' : '' ?>
<script type="text/javascript">
  $('#<?php echo $CAROUSEL_ID; ?>').carousel(<?php echo $paused;?>);
</script>
