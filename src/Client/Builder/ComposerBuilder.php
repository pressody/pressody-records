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

declare(strict_types=1);

/*
 * This file is part of composer/satis.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Pressody\Records\Client\Builder;

use Composer\IO\IOInterface;

abstract class ComposerBuilder implements ComposerBuilderInterface
{
    /** @var IOInterface The output Interface. */
    protected $output;
    /** @var string The directory where to build. */
    protected $outputDir;
    /** @var array The parameters from ./satis.json. */
    protected $config;
    /** @var bool Skips Exceptions if true. */
    protected $skipErrors;

    public function __construct(IOInterface $output, string $outputDir, array $config, bool $skipErrors)
    {
        $this->output = $output;
        $this->outputDir = $outputDir;
        $this->config = $config;
        $this->skipErrors = $skipErrors;
    }
}
