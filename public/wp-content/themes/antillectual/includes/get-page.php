<?php

/**
 * [loadPageBySlug display a pages content]
 * @param  string $slug [the page slug]
 */
function loadPageBySlug ($slug = '') {
  $PAGE_ID = $slug;
  include (locate_template ('partials/page-id.php'));
}
