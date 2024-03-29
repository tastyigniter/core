<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Asset;

/**
 * An asset collection.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface AssetCollectionInterface extends \Traversable, AssetInterface
{
    /**
     * Returns all child assets.
     *
     * @return array An array of AssetInterface objects
     */
    public function all(): array;

    /**
     * Adds an asset to the current collection.
     *
     * @param AssetInterface $asset An asset
     */
    public function add(AssetInterface $asset);

    /**
     * Removes a leaf.
     *
     * @param AssetInterface $leaf The leaf to remove
     * @param bool $graceful Whether the failure should return false or throw an exception
     *
     * @return bool Whether the asset has been found
     *
     * @throws \InvalidArgumentException If the asset cannot be found
     */
    public function removeLeaf(AssetInterface $leaf, bool $graceful = false): bool;

    /**
     * Replaces an existing leaf with a new one.
     *
     * @param AssetInterface $needle The current asset to replace
     * @param AssetInterface $replacement The new asset
     * @param bool $graceful Whether the failure should return false or throw an exception
     *
     * @return bool Whether the asset has been found
     *
     * @throws \InvalidArgumentException If the asset cannot be found
     */
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, bool $graceful = false): bool;
}
