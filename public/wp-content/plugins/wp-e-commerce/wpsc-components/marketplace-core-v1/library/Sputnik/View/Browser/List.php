<?php

class Sputnik_View_Browser_List extends Sputnik_View_Browser_Grid {
	public $id = 'list';

	protected function display_grid() {
		WP_List_Table::display();
	}
	protected function display_row($plugin, $style) {
		$name = strip_tags( $plugin->name . ' ' . $plugin->version );
		$action_links = array();
		$action_links[] = '<a href="' . Sputnik_Admin::build_url(array('info' => $plugin->slug, 'TB_iframe' => true))
							. '" class="button thickbox info" title="' .
							esc_attr( sprintf( __( 'More information about %s', 'wp-e-commerce' ), $name ) ) . '">' . __( 'Details', 'wp-e-commerce' ) . '</a>';

		$purchase_link = $plugin->price;

		if ($plugin->slug === '__add_new') {
			$status = 'addown';
			$style['name'] .= ' class="addown"';
			$action_links = array();
			$action_links[] = '<a href="http://developer.renku.me/" class="thickbox button info">' . __( 'Documentation', 'wp-e-commerce' ) . '</a>';
			$purchase_link = '<a class="button-primary addown" href="' . Sputnik::SITE_BASE . '/plugins/add/" title="Add New Plugin">' . esc_html__('Add Now', 'wp-e-commerce') . '</a>';
		}
		elseif ( current_user_can( 'install_plugins' ) || current_user_can( 'update_plugins' ) ) {
			$status = Sputnik_Admin::install_status( $plugin );

			switch ( $status['status'] ) {
				case 'purchase':
					if ( $status['url'] ) {
						$purchase_link = '<a id="' . $plugin->slug . '" class="button-primary buy" href="' . esc_url( $status['url'] ) . '" title="'
							. esc_attr(sprintf(__( 'Buy %s', 'wp-e-commerce'), $name)) . '">' . sprintf(__('<span>%s</span> Buy Now</a>', 'wp-e-commerce'), $plugin->price);
					}
					break;
				case 'install':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 800, 'height' => 600 ), $status['url']);
						$purchase_link = '<a class="button install" href="' . esc_url( $status['url'] ) . '" title="'
							. esc_attr(sprintf(__( 'Install %s', 'wp-e-commerce'), $name)) . '">' . __('Install', 'wp-e-commerce') . '</a>';
					}
					else {
						$purchase_link = '<span title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wp-e-commerce') . '">'
							. __('Install', 'wp-e-commerce') . '</span>';
					}
					break;
				case 'update_available':
					if ( $status['url'] ) {
						$status['url'] = add_query_arg(array('TB_iframe' => true, 'width' => 800, 'height' => 600 ), $status['url']);
						$purchase_link = '<a class="button install" href="' . esc_url( $status['url'] ) . '" title="'
							. esc_attr(sprintf(__( 'Update to version %s', 'wp-e-commerce'), $status['version'])) . '">' . __('Update', 'wp-e-commerce') . '</a>';
					}
					else {
						$purchase_link = '<span title="' . esc_attr__('Cannot auto-install, report this as a bug', 'wp-e-commerce') . '">'
							. __('Update', 'wp-e-commerce') . '</span>';
					}
					break;
				case 'latest_installed':
				case 'newer_installed':
					$purchase_link = '<span title="' . esc_attr__('This plugin is already installed and is up to date', 'wp-e-commerce') . ' ">'
						. __('Installed', 'wp-e-commerce') . '</span>';
					break;
			}
		}

		$action_links = apply_filters( 'sputnik_install_row_action_links', $action_links, $plugin );
?>
		<tr>
			<td class="name column-name"<?php echo $style['name']; ?>><strong><?php echo $plugin->name; ?></strong>
				<div class="action-links"><?php if ( !empty( $action_links ) ) echo implode( ' ', $action_links ); ?></div>
			</td>
			<td class="vers column-version"<?php echo $style['version']; ?>><?php echo $plugin->version; ?></td>
			<td class="vers column-price"<?php echo $style['price']; ?>><?php echo $purchase_link; ?></td>
			<td class="vers column-rating"<?php echo $style['rating']; ?>>
				<div class="star-holder" title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin->rating->count, 'wp-e-commerce' ), number_format_i18n( $plugin->rating->count ) ) ?>">
					<div class="star star-rating" style="width: <?php echo (int) (20 * $plugin->rating->average) ?>px"></div>
				</div>
			</td>
			<td class="desc column-description"<?php echo $style['description']; ?>><?php echo $plugin->description, $plugin->author; ?></td>
		</tr>
<?php
	}
}