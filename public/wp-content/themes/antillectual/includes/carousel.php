<?php

/**
 * [createCarousel creates a Bootstrap carousel]
 * @param  [String] $id      [carousel id, used in DOM]
 * @param  [Array] $content [items in carousel]
 * @param  [boolean] $paused  [is the carousel paused?]
 * @param  [int] $interval  [interval in milliseconds]
 */
function createCarousel ($id, $content, $paused = false, $interval = 5000) {
  $CAROUSEL_CONTENT = $content;
  $CAROUSEL_PAUSE = $paused;
  $CAROUSEL_ID = $id;
  $CAROUSEL_INTERVAL = $interval;
  include (locate_template ('partials/carousel.php'));
}
