<?php
/**
 * Part (Package) factory.
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

namespace Pressody\Records;

use Pressody\Records\PackageType\BasePackage;
use Pressody\Records\PackageType\Builder\ExternalPartBuilder;
use Pressody\Records\PackageType\Builder\ManualPartBuilder;
use Pressody\Records\PackageType\ExternalBasePackage;
use Pressody\Records\PackageType\Builder\BasePackageBuilder;
use Pressody\Records\PackageType\PackageTypes;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating part (package) builders.
 *
 * @since 0.9.0
 */
final class PartFactory {

	/**
	 * Part (Package) manager.
	 *
	 * @var PartManager
	 */
	private PartManager $part_manager;

	/**
	 * Release manager.
	 *
	 * @var ReleaseManager
	 */
	private ReleaseManager $release_manager;

	/**
	 * Archiver.
	 *
	 * @var Archiver
	 */
	protected Archiver $archiver;

	/**
	 * Logger.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Constructor.
	 *
	 * @since 0.9.0
	 *
	 * @param PartManager  $package_manager Part (Packages) manager.
	 * @param ReleaseManager  $release_manager Release manager.
	 * @param Archiver        $archiver        Archiver.
	 * @param LoggerInterface $logger          Logger.
	 */
	public function __construct(
		PartManager $package_manager,
		ReleaseManager $release_manager,
		Archiver $archiver,
		LoggerInterface $logger
	) {
		$this->part_manager    = $package_manager;
		$this->release_manager = $release_manager;
		$this->archiver        = $archiver;
		$this->logger          = $logger;
	}

	/**
	 * Create a part (package) builder.
	 *
	 * @since 0.9.0
	 *
	 * @param string $package_type Package type.
	 * @param string $source_type  The managed package source type, if that is the case.
	 *
	 * @return ExternalPartBuilder|ManualPartBuilder|BasePackageBuilder Package builder instance.
	 */
	public function create( string $package_type, string $source_type = '' ): BasePackageBuilder {
		// Parts package code must be some type of plugin.
		if ( in_array( $package_type, [ PackageTypes::PLUGIN, PackageTypes::MUPLUGIN, PackageTypes::DROPINPLUGIN ] ) ) {

			if ( 'local.manual' === $source_type ) {
				return new ManualPartBuilder( new BasePackage(), $this->part_manager, $this->release_manager, $this->archiver, $this->logger );
			}

			if ( in_array( $source_type, [ 'packagist.org', 'vcs', ] ) ) {
				return new ExternalPartBuilder( new ExternalBasePackage(), $this->part_manager, $this->release_manager, $this->archiver, $this->logger );
			}
		}

		return new BasePackageBuilder( new BasePackage(), $this->part_manager, $this->release_manager, $this->archiver, $this->logger );
	}
}
