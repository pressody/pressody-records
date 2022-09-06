<?php
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

declare ( strict_types = 1 );

namespace Pressody\Records\Tests\Integration\Repository;

use Pressody\Records\PackageType\LocalPlugin;
use Pressody\Records\Tests\Integration\TestCase;

use function Pressody\Records\plugin;

class InstalledPluginsTest extends TestCase {

	public function test_get_plugin_from_source() {
		$repository = plugin()->get_container()['repository.local.plugins'];

		$package    = $repository->first_where( [ 'slug' => 'basic/basic.php', 'source_type' => 'local.plugin' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'unmanaged/unmanaged.php' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'basename' => 'unmanaged/unmanaged.php' ] );
		$this->assertInstanceOf( LocalPlugin::class, $package );

		$package    = $repository->first_where( [ 'slug' => 'unmanaged/unmanaged.php', 'source_type' => 'local.theme' ] );
		$this->assertNull( $package );
	}
}
