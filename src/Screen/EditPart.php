<?php
/**
 * Edit Part screen provider.
 *
 * @since   0.9.0
 * @license GPL-2.0-or-later
 * @package Pressody
 */

/*
 * This file is part of a Pressody module.
 *
 * This Pressody module is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * This Pressody module is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this Pressody module.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (c) 2021, 2022 Vlad Olaru (vlad@thinkwritecode.com)
 */

declare ( strict_types=1 );

namespace Pressody\Records\Screen;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Pressody\Records\PackageManager;

/**
 * Edit Part screen provider class.
 *
 * @since 0.9.0
 */
class EditPart extends EditPackage {

	const SLUG_PREFIX = 'part_';

	/**
	 * Register hooks.
	 *
	 * @since 0.9.0
	 */
	public function register_hooks() {
		parent::register_hooks();

		$this->add_action( 'save_post_' . $this->package_manager::PACKAGE_POST_TYPE, 'enforce_part_prefix_to_post_name' );
	}

	protected function change_title_placeholder( string $placeholder, \WP_Post $post ): string {
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) ) {
			return $placeholder;
		}

		return esc_html__( 'Add part title', 'pressody_records' );
	}

	protected function add_post_slug_description( string $post_name, $post ): string {
		// We want this only on the edit post screen.
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_current_screen()->id ) {
			return $post_name;
		}

		// Only on our post type.
		if ( $this->package_manager::PACKAGE_POST_TYPE !== get_post_type( $post ) ) {
			return $post_name;
		}
		// Just output it since there is no way to add it other way. ?>
		<p class="description">
			<?php _e( '<strong>The post slug is, at the same time, the Composer PROJECT NAME.</strong><br>
Of equal importance is the fact that Composer will use the slug(name) as <strong>the directory for the part\'s plugin files</strong> (i.e. <code>wp-content/plugins/slug</code>), regardless of the directory name in the .zip file!<br>
In the end this will be joined with the vendor name (like so: <code>vendor/slug</code>) to form the package name to be used in composer.json.<br>
<strong>The slug/name must be</strong> lowercased and consist of words separated by <code>-</code> or <code>_</code>. It also must respect <a href="https://regexr.com/5sr9h" target="_blank">this regex</a>.<br>
<em>Note: Add the <code>' . static::SLUG_PREFIX . '</code> prefix or <strong>it will be added automatically</strong> on update! This intends to separate part names from packages names.</em>', 'pressody_records' ); ?>
		</p>
		<style>
			input#post_name {
				width: 20%;
			}
		</style>
		<?php

		// We must return the post slug.
		return $post_name;
	}

	/**
	 * Adds a part prefix to the post name/slug if it is not already present.
	 *
	 * @since 0.9.0
	 *
	 * @param int $post_id The ID of the post that's being saved.
	 */
	protected function enforce_part_prefix_to_post_name( int $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		// If the suffix is already present, don't do anything.
		if ( static::SLUG_PREFIX === substr( $post->post_name, 0, strlen( static::SLUG_PREFIX ) ) ) {
			return;
		}

		// Deduct the suffix length from the 200 characters limit imposed by WordPress for post names/slugs.
		$post_name = static::SLUG_PREFIX . _truncate_post_slug( $post->post_name, 200 - strlen( static::SLUG_PREFIX ) );
		$wpdb->update( $wpdb->posts, array( 'post_name' => $post_name ), array( 'ID' => $post->ID ) );
		clean_post_cache( $post->ID );
	}

	protected function attach_post_meta_fields() {
		// Register the metabox for managing the source details of the part.
		Container::make( 'post_meta', 'carbon_fields_container_source_configuration_' . $this->package_manager::PACKAGE_POST_TYPE, esc_html__( 'Part\'s plugin source configuration', 'pressody_records' ) )
		         ->where( 'post_type', '=', $this->package_manager::PACKAGE_POST_TYPE )
		         ->set_context( 'normal' )
		         ->set_priority( 'core' )
		         ->add_fields( [
				         Field::make( 'html', 'source_configuration_html', __( 'Section Description', 'pressody_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'First, configure details about <strong>where from should we get releases</strong> for this part\'s plugin.<br>This should be a WP plugin that handles the integration for this part once deployed on a site.', 'pressody_records' ) ) ),

				         Field::make( 'select', 'package_source_type', __( 'Set the part\'s plugin source type', 'pressody_records' ) )
				              ->set_help_text( __( 'Composer works with packages and repositories to find the core to use for the defined dependencies. We will strive to keep as close to that in terms of concepts. Learn more about it <a href="https://getcomposer.org/doc/05-repositories.md#repository" target="_blank">here</a>.', 'pressody_records' ) )
				              ->set_options( [
						              null            => esc_html__( 'Pick your source, carefully..', 'pressody_records' ),
						              'packagist.org' => esc_html__( 'A Packagist.org public repo', 'pressody_records' ),
						              'vcs'           => esc_html__( 'A VCS repo (git, SVN, fossil or hg)', 'pressody_records' ),
						              'local.manual'  => esc_html__( 'A local repo: part releases/versions are managed here, manually', 'pressody_records' ),
				              ] )
				              ->set_default_value( null )
				              ->set_required( true )
				              ->set_width( 50 ),

				         Field::make( 'text', 'package_source_name', __( 'Package Source Name', 'pressody_records' ) )
				              ->set_help_text( __( 'Composer identifies a certain package (the package name) by its project name and vendor, resulting in a <code>vendor/projectname</code> package name. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#name" target="_blank">here</a>. Most often you will find the correct project name in the project\'s <code>composer.json</code> file, under the <code>"name"</code> JSON key.<br>The vendor and project name must be lowercased and consist of words separated by <code>-</code>, <code>.</code> or <code>_</code>.<br><strong>Provide the whole package name (e.g. <code>wp-media/wp-rocket</code>)!</strong>', 'pressody_records' ) )
				              ->set_width( 50 )
				              ->set_conditional_logic( [
						              'relation' => 'AND',
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'packagist.org', 'vcs', ],
								              'compare' => 'IN',
						              ],
				              ] ),

				         Field::make( 'text', 'package_source_version_range', __( 'Package Source Version Range', 'pressody_records' ) )
				              ->set_help_text( __( 'A certain source can contain tens or even hundreds of historical versions/releases. <strong>It is wasteful to pull all those in</strong> (and cache them) if we are only interested in the latest major version, for example.<br>
 Specify a version range to <strong>limit the available versions/releases for this package.</strong> Most likely you will only lower-bound your range (e.g. <code>>2.0</code>), but that is up to you.<br>
 Learn more about Composer <a href="https://getcomposer.org/doc/articles/versions.md#writing-version-constraints" target="_blank">versions</a> or <a href="https://semver.mwl.be/?package=madewithlove%2Fhtaccess-cli&constraint=%3C1.2%20%7C%7C%20%3E1.6&stability=stable" target="_blank">play around</a> with version ranges.', 'pressody_records' ) )
				              ->set_width( 75 )
				              ->set_conditional_logic( [
						              'relation' => 'AND',
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'packagist.org', 'vcs' ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'select', 'package_source_stability', __( 'Package Source Stability', 'pressody_records' ) )
				              ->set_help_text( __( 'Limit the minimum stability required for versions. <code>Stable</code> is the most restrictive one, while <code>dev</code> the most all encompassing.<br><code>Stable</code> is the recommended (and default) one.', 'pressody_records' ) )
				              ->set_width( 25 )
				              ->set_options( [
						              'stable' => esc_html__( 'Stable', 'pressody_records' ),
					              /** The uppercase 'RC' key is important. @see BasePackage::$stabilities */
						              'RC'     => esc_html__( 'RC', 'pressody_records' ),
						              'beta'   => esc_html__( 'Beta', 'pressody_records' ),
						              'alpha'  => esc_html__( 'Alpha', 'pressody_records' ),
						              'dev'    => esc_html__( 'Dev', 'pressody_records' ),
				              ] )
				              ->set_default_value( 'stable' )
				              ->set_conditional_logic( [
						              'relation' => 'AND',
						              [
								              'field'   => 'package_source_type',
								              'value'   => [ 'packagist.org', 'vcs' ],
								              'compare' => 'IN',
						              ],
				              ] ),

				         Field::make( 'text', 'package_vcs_url', __( 'Package VCS URL', 'pressody_records' ) )
				              ->set_help_text( __( 'Just provide the full URL to your VCS repo (e.g. a GitHub repo URL like <code>https://github.com/pressody/satispress</code>). Learn more about it <a href="https://getcomposer.org/doc/05-repositories.md#vcs" target="_blank">here</a>.', 'pressody_records' ) )
				              ->set_conditional_logic( [
						              'relation' => 'AND',
						              [
								              'field'   => 'package_source_type',
								              'value'   => 'vcs',
								              'compare' => '=',
						              ],
				              ] ),

				         Field::make( 'complex', 'package_manual_releases', __( 'Part\'s Plugin Releases', 'pressody_records' ) )
				              ->set_help_text( __( 'The manually uploaded part\'s plugin releases (zips).<br>
<strong>These zip files will be cached</strong> just like external or installed sources. If you remove a certain release and update the post, the cache will keep up and auto-clean itself.<br>
<strong>If you upload a different zip to a previously published release, the cache will not auto-update itself</strong> (for performance reasons). In this case, first delete the release, hit "Update" for the post and them add a new release.<br>
Also, bear in mind that <strong>we do not clean the Media Gallery of unused zip files.</strong> That is up to you, if you can\'t stand some mess.<br><br>
<em>TIP: If you <strong>switch the package type to manual entries,</strong> hit "Update" and all existing, stored releases will be migrated for you to manually manage.</em>', 'pressody_records' ) )
				              ->set_classes( 'package-manual-releases' )
				              ->set_collapsed( true )
				              ->add_fields( [
						              Field::make( 'text', 'version', __( 'Version', 'pressody_records' ) )
						                   ->set_help_text( __( 'Semver-formatted version string. Bear in mind that we currently don\'t do any check regarding the version. It is up to you to <strong>make sure that the zip file contents match the version specified.</strong>', 'pressody_records' ) )
						                   ->set_required( true )
						                   ->set_width( 25 ),
						              Field::make( 'file', 'file', __( 'Zip File', 'pressody_records' ) )
						                   ->set_type( 'zip' ) // The allowed mime-types (see wp_get_mime_types())
						                   ->set_value_type( 'id' ) // Change to 'url' to store the file/attachment URL instead of the attachment ID.
						                   ->set_required( true )
						                   ->set_width( 50 ),
				              ] )
				              ->set_header_template( '
								    <% if (version) { %>
								        Version: <%- version %>
								    <% } %>
								    <% if (file) { %>
								        (file ID or URL: <%- file %>)
								    <% } %>
								' )
				              ->set_conditional_logic( [
						              'relation' => 'AND',
						              [
								              'field'   => 'package_source_type',
								              'value'   => 'local.manual',
								              'compare' => '=',
						              ],
				              ] ),


				         Field::make( 'separator', 'package_details_separator', '' )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'vcs',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'html', 'package_details_html', __( 'Section Description', 'pressody_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'Configure details about <strong>the part itself,</strong> as it will be exposed for consumption.<br>
<strong>Leave empty</strong> and we will try to figure them out on save; after that you can modify them however you like.<br>
<em>Note: These details only refer to the part\'s plugin code, not its dependencies.</em>', 'pressody_records' ) ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'wpackagist.org',
										              'vcs',
										              'local.plugin',
										              'local.theme',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'textarea', 'package_details_description', __( 'Part Description', 'pressody_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'vcs',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'text', 'package_details_homepage', __( 'Part Homepage URL', 'pressody_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'vcs',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'text', 'package_details_license', __( 'Part License', 'pressody_records' ) )
				              ->set_help_text( __( 'The part license in a standard format (e.g. <code>GPL-3.0-or-later</code>). If there are multiple licenses, comma separate them. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#license" target="_blank">here</a>.', 'pressody_records' ) )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'vcs',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
				         Field::make( 'complex', 'package_details_authors', __( 'Package Authors', 'pressody_records' ) )
				              ->set_help_text( __( 'The part authors details. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#authors" target="_blank">here</a>.', 'pressody_records' ) )
				              ->add_fields( [
						              Field::make( 'text', 'name', __( 'Author Name', 'pressody_records' ) )->set_required( true )->set_width( 50 ),
						              Field::make( 'text', 'email', __( 'Author Email', 'pressody_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'homepage', __( 'Author Homepage', 'pressody_records' ) )->set_width( 50 ),
						              Field::make( 'text', 'role', __( 'Author Role', 'pressody_records' ) )->set_width( 50 ),
				              ] )
				              ->set_header_template( '
							    <% if (name) { %>
							        <%= name %>
							    <% } %>
							  ' )
				              ->set_conditional_logic( [
						              [
								              'field'   => 'package_source_type',
								              'value'   => [
										              'packagist.org',
										              'vcs',
										              'local.manual',
								              ],
								              'compare' => 'IN',
						              ],
				              ] ),
		         ] );

		// Register the metabox for managing the managed packages the part depends on (dependencies that will translate in composer `require`s).
		Container::make( 'post_meta', 'carbon_fields_container_dependencies_configuration_' . $this->package_manager::PACKAGE_POST_TYPE, esc_html__( 'Included Packages Configuration', 'pressody_records' ) )
		         ->where( 'post_type', '=', $this->package_manager::PACKAGE_POST_TYPE )
		         ->set_context( 'normal' )
		         ->set_priority( 'core' )
		         ->add_fields( [
				         Field::make( 'html', 'dependencies_configuration_html', __( 'Dependencies Description', 'pressody_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'Here you edit and configure <strong>the list of managed packages</strong> this part provides/includes (required packages that translate into entries in Composer\'s <code>require</code> entries).<br>
For each required package you can <strong>specify a version range</strong> to better control the package releases/versions required. Set to <code>*</code> to <strong>use the latest available required-package release that matches all constraints</strong> (other packages in a module might impose stricter limits).<br>
Learn more about Composer <a href="https://getcomposer.org/doc/articles/versions.md#writing-version-constraints" target="_blank">versions</a> or <a href="https://semver.mwl.be/?package=madewithlove%2Fhtaccess-cli&constraint=%3C1.2%20%7C%7C%20%3E1.6&stability=stable" target="_blank">play around</a> with version ranges.', 'pressody_records' ) ) ),

				         Field::make( 'complex', 'package_required_packages', __( 'Included Packages', 'pressody_records' ) )
				              ->set_help_text( __( 'The order is not important, from a logic standpoint. Also, if you add <strong>the same package multiple times</strong> only the last one will take effect since it will overwrite the previous ones.<br>
<strong>FYI:</strong> Each required package label is comprised of the standardized <code>source_name</code> and the <code>#post_id</code>.', 'pressody_records' ) )
				              ->set_classes( 'package-required-packages' )
				              ->set_collapsed( true )
				              ->add_fields( [
						              Field::make( 'select', 'pseudo_id', __( 'Choose one of the managed packages', 'pressody_records' ) )
						                   ->set_options( [ $this, 'get_available_required_packages_options' ] )
						                   ->set_default_value( null )
						                   ->set_required( true )
						                   ->set_width( 50 ),
						              Field::make( 'text', 'version_range', __( 'Version Range', 'pressody_records' ) )
						                   ->set_default_value( '*' )
						                   ->set_required( true )
						                   ->set_width( 25 ),
						              Field::make( 'select', 'stability', __( 'Stability', 'pressody_records' ) )
						                   ->set_options( [
								                   'stable' => esc_html__( 'Stable', 'pressody_records' ),
								                   'rc'     => esc_html__( 'RC', 'pressody_records' ),
								                   'beta'   => esc_html__( 'Beta', 'pressody_records' ),
								                   'alpha'  => esc_html__( 'Alpha', 'pressody_records' ),
								                   'dev'    => esc_html__( 'Dev', 'pressody_records' ),
						                   ] )
						                   ->set_required( true )
						                   ->set_default_value( 'stable' )
						                   ->set_width( 25 ),
				              ] )
				              ->set_header_template( '
								    <% if (pseudo_id) { %>
								        <%- pseudo_id %> (version range: <%= version_range %><% if ("stable" !== stability) { %>@<%= stability %><% } %>)
								    <% } %>
								' ),
		         ] );

		// Register the metabox for managing the other parts the current part depends on (dependencies that will translate in composer `require`s).
		Container::make( 'post_meta', 'carbon_fields_container_part_dependencies_configuration', esc_html__( 'Required Parts Configuration', 'pressody_records' ) )
		         ->where( 'post_type', '=', $this->package_manager::PACKAGE_POST_TYPE )
		         ->set_context( 'normal' )
		         ->set_priority( 'core' )
		         ->add_fields( [
				         Field::make( 'html', 'dependencies_configuration_html', __( 'Required Parts Description', 'pressody_records' ) )
				              ->set_html( sprintf( '<p class="description">%s</p>', __( 'Here you edit and configure <strong>the list of other parts</strong> this part depends on.<br>
For each required part you can <strong>specify a version range</strong> to better control the part releases/versions required. Set to <code>*</code> to <strong>use the latest available required-part release that matches all constraints</strong> (other parts present on a site might impose stricter limits).<br>
Learn more about Composer <a href="https://getcomposer.org/doc/articles/versions.md#writing-version-constraints" target="_blank">versions</a> or <a href="https://semver.mwl.be/?package=madewithlove%2Fhtaccess-cli&constraint=%3C1.2%20%7C%7C%20%3E1.6&stability=stable" target="_blank">play around</a> with version ranges.', 'pressody_records' ) ) ),

				         Field::make( 'complex', 'package_required_parts', __( 'Required Parts', 'pressody_records' ) )
				              ->set_help_text( __( 'The order is not important, from a logic standpoint. Also, if you add <strong>the same part multiple times</strong> only the last one will take effect since it will overwrite the previous ones.<br>
<strong>FYI:</strong> Each required part label is comprised of the standardized <code>source_name</code> and the <code>#post_id</code>.', 'pressody_records' ) )
				              ->set_classes( 'package-required-packages package-required-parts' )
				              ->set_collapsed( true )
				              ->add_fields( [
						              Field::make( 'select', 'pseudo_id', __( 'Choose one of the parts', 'pressody_records' ) )
						                   ->set_options( [ $this, 'get_available_required_parts_options' ] )
						                   ->set_default_value( null )
						                   ->set_required( true )
						                   ->set_width( 50 ),
						              Field::make( 'text', 'version_range', __( 'Version Range', 'pressody_records' ) )
						                   ->set_default_value( '*' )
						                   ->set_required( true )
						                   ->set_width( 25 ),
						              Field::make( 'select', 'stability', __( 'Stability', 'pressody_records' ) )
						                   ->set_options( [
								                   'stable' => esc_html__( 'Stable', 'pressody_records' ),
								                   'rc'     => esc_html__( 'RC', 'pressody_records' ),
								                   'beta'   => esc_html__( 'Beta', 'pressody_records' ),
								                   'alpha'  => esc_html__( 'Alpha', 'pressody_records' ),
								                   'dev'    => esc_html__( 'Dev', 'pressody_records' ),
						                   ] )
						                   ->set_required( true )
						                   ->set_default_value( 'stable' )
						                   ->set_width( 25 ),
				              ] )
				              ->set_header_template( '
								    <% if (pseudo_id) { %>
								        <%- pseudo_id %> (version range: <%= version_range %><% if ("stable" !== stability) { %>@<%= stability %><% } %>)
								    <% } %>
								' ),
				         Field::make( 'complex', 'package_replaced_parts', __( 'Replaced Parts', 'pressody_records' ) )
				              ->set_help_text( __( 'These are parts that are <strong>automatically ignored from a site\'s composition</strong> when the current part is included. The order is not important, from a logic standpoint.<br>
These apply the Composer <code>replace</code> logic, meaning that the current part already includes the replaced parts. Learn more about it <a href="https://getcomposer.org/doc/04-schema.md#replace" target="_blank">here</a>.<br>
<strong>FYI:</strong> Each replaced part label is comprised of the part <code>source_name</code> and the <code>#post_id</code>.', 'pressody_records' ) )
				              ->set_classes( 'package-required-packages package-required-parts' )
				              ->set_collapsed( true )
				              ->add_fields( [
						              Field::make( 'select', 'pseudo_id', __( 'Choose one of the parts', 'pressody_records' ) )
						                   ->set_options( [ $this, 'get_available_required_parts_options' ] )
						                   ->set_default_value( null )
						                   ->set_required( true )
						                   ->set_width( 50 ),
						              Field::make( 'text', 'version_range', __( 'Version Range', 'pressody_records' ) )
						                   ->set_default_value( '*' )
						                   ->set_required( true )
						                   ->set_width( 25 ),
						              Field::make( 'select', 'stability', __( 'Stability', 'pressody_records' ) )
						                   ->set_options( [
								                   'stable' => esc_html__( 'Stable', 'pressody_records' ),
								                   'rc'     => esc_html__( 'RC', 'pressody_records' ),
								                   'beta'   => esc_html__( 'Beta', 'pressody_records' ),
								                   'alpha'  => esc_html__( 'Alpha', 'pressody_records' ),
								                   'dev'    => esc_html__( 'Dev', 'pressody_records' ),
						                   ] )
						                   ->set_required( true )
						                   ->set_default_value( 'stable' )
						                   ->set_width( 25 ),
				              ] )
				              ->set_header_template( '
								    <% if (pseudo_id) { %>
								        <%- pseudo_id %> (version range: <%= version_range %><% if ("stable" !== stability) { %>@<%= stability %><% } %>)
								    <% } %>
								' ),
		         ] );
	}

	/**
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public function get_available_required_packages_options(): array {
		$options = [];

		$exclude_post_ids = [];
		// We can't exclude the currently required packages because if we use carbon_get_post_meta()
		// to fetch the current complex field value, we enter an infinite loop since that requires the field options.
		// And to replicate the Carbon Fields logic to parse complex fields datastore is not fun.
		$package_ids = $this->package_manager->get_package_ids_by( [
			// target the PackageManager post type specifically.
			'post_type'        => PackageManager::PACKAGE_POST_TYPE,
			'exclude_post_ids' => $exclude_post_ids,
		] );

		foreach ( $package_ids as $post_id ) {
			$package_pseudo_id = $this->package_manager->get_post_package_source_name( $post_id ) . $this->package_manager::PSEUDO_ID_DELIMITER . $post_id;

			$options[ $package_pseudo_id ] = sprintf( __( '%s - %s', 'pressody_records' ), $this->package_manager->get_post_package_name( $post_id ), $package_pseudo_id );
		}

		ksort( $options );

		// Prepend an empty option.
		$options = [ null => esc_html__( 'Pick your included package, carefully..', 'pressody_records' ) ] + $options;

		return $options;
	}

	/**
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public function get_available_required_parts_options(): array {
		$options = [];

		// We exclude the current part post ID, of course.
		$exclude_post_ids = [ get_the_ID(), ];
		// We can't exclude the currently required parts because if we use carbon_get_post_meta()
		// to fetch the current complex field value, we enter an infinite loop since that requires the field options.
		// And to replicate the Carbon Fields logic to parse complex fields datastore is not fun.
		$package_ids = $this->package_manager->get_package_ids_by( [ 'exclude_post_ids' => $exclude_post_ids, ] );

		foreach ( $package_ids as $post_id ) {
			$package_pseudo_id = $this->package_manager->get_post_package_source_name( $post_id ) . $this->package_manager::PSEUDO_ID_DELIMITER . $post_id;

			$options[ $package_pseudo_id ] = sprintf( __( '%s - %s', 'pressody_records' ), $this->package_manager->get_post_package_name( $post_id ), $package_pseudo_id );
		}

		ksort( $options );

		// Prepend an empty option.
		$options = [ null => esc_html__( 'Pick your required part, carefully..', 'pressody_records' ) ] + $options;

		return $options;
	}
}
